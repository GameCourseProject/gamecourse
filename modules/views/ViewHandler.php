<?php
namespace Modules\Views;

use Modules\Views\Expression\EvaluateVisitor;
use Modules\Views\Expression\ExpressionEvaluatorBase;

use Modules\Views\Expression\ValueNode;
use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\API;
use SmartBoards\DataSchema;
use SmartBoards\ModuleLoader;

class ViewHandler {
    const VT_SINGLE = 1;
    const VT_ROLE_SINGLE = 2;
    const VT_ROLE_INTERACTION = 3;
    private $viewsModule;
    private $registeredPages = array();
    
    private $registeredFunctions = array();
    private $registeredPartTypes = array();
    
    public function parse($exp) {
        static $parser;
        if ($parser == null)
            $parser = new ExpressionEvaluatorBase();
        if (trim($exp) == '')
            return new ValueNode('');
        return $parser->parse($exp);
    }

    public function parseSelf(&$exp) {
        $exp = $this->parse($exp);
    }
    
    public function __construct($viewsModule) {
        $this->viewsModule = $viewsModule;
    }

    public function getRegisteredPages() {
        return $this->registeredPages;
    }
    
    public function addViewParameter($type,$value,$viewId){   
        
        $parmOfView = Core::$systemDB->select("parameter join view_parameter on id=parameterId",
                    "id,value",["type"=>$type, "viewId"=>$viewId]);
        if (!empty($parmOfView)){
            if ($parmOfView["value"]!=$value)
                Core::$systemDB->delete("view_parameter",["viewId"=>$viewId,"parameterId"=>$parmOfView["id"]]);
            else //the view is already associated with this parameter
                return;
        }
        
        $parameter = Core::$systemDB->select("parameter","id",["type"=>$type,"value"=>$value]);
        
        if (empty($parameter)){
            Core::$systemDB->insert("parameter",["type"=>$type,"value"=>$value]);
            $parameter=Core::$systemDB->getLastId();
        }            
        if (empty(Core::$systemDB->select("view_parameter","*",["viewId"=>$viewId,"parameterId"=>$parameter])))
            Core::$systemDB->insert("view_parameter",["viewId"=>$viewId,"parameterId"=>$parameter]);
    }
    
    public function arrumarACasa($viewPart, $aspectClass=null, &$partsInDB=null){
    //insert data into DB, should check previous data and update/delete stuff   
        if ($viewPart["partType"]!="aspect"){
            //get aspect class
            
            //insert/update views
            if (array_key_exists("id", $viewPart)){
                //already in DB, may need update
                //index is probably the only thing that can be updated
                //ToDo: only update if there were changes
                Core::$systemDB->update("view",["viewIndex"=>$viewPart["viewIndex"],
                        "partType"=>$viewPart["partType"],"parent"=>$viewPart["parent"]],
                        ["id"=>$viewPart["id"]]);   
                unset($partsInDB[$viewPart["id"]]);
            }else{
                //not in DB, insert it
                Core::$systemDB->insert("view",["aspectClass"=>$aspectClass,
                "parent"=>$viewPart["parent"],"role"=>$viewPart["role"],
                "partType"=>$viewPart["partType"], "viewIndex"=>$viewPart["viewIndex"]]);
                $viewPart["id"]=Core::$systemDB->getLastId();
            }         
           
            if (array_key_exists("parameters", $viewPart)){
                foreach ($viewPart["parameters"] as $type => $param){
                    $this->addViewParameter($type,$param,$viewPart["id"]);
                }
            }       
            if (array_key_exists("variables", $viewPart)){
                $value = json_encode($viewPart["variables"]);
                $this->addViewParameter("variables",$value,$viewPart["id"]);
            }  
        
            //ToDo:: delete views
            
            $viewPart["aspectClass"]=$aspectClass;
        }//else print_r($viewPart);
        if (array_key_exists("children", $viewPart)){
            
            $children = Core::$systemDB->selectMultiple("view","*",["parent"=>$viewPart["id"]]);
            $children = array_combine(array_column($children,"id"),$children);
            foreach ($viewPart["children"] as $key => $child){
                if ($child["partType"]=="header"){
                    unset($children[$child["id"]]);
                    continue;
                }
                $child["parent"]=$viewPart["id"];
                $child["role"]=$viewPart["role"];
                $child["viewIndex"]=$key;
                $this->arrumarACasa($child, $viewPart["aspectClass"],$children);
            }
            foreach ($children as $deleted){
                Core::$systemDB->delete("view",["id"=>$deleted["id"]]);
            }
        }
        //deal with header of block
        if ($viewPart["partType"]=="block") {
            $header = Core::$systemDB->select("view","id",["parent"=>$viewPart["id"], "partType"=>"header"]);
            if (array_key_exists("header", $viewPart)){
                if(empty($header)){ //insert
                    Core::$systemDB->insert("view",["parent"=>$viewPart["id"], 
                        "partType"=>"header","aspectClass"=>$aspectClass,"role"=>$viewPart["role"]]);
                    $headerId = Core::$systemDB->getLastId();
                    Core::$systemDB->insert("view",["parent"=>$headerId, 
                        "partType"=>"image","aspectClass"=>$aspectClass,"role"=>$viewPart["role"],"viewIndex"=>0]);
                    $imageId= Core::$systemDB->getLastId();
                    $this->addViewParameter("value",$viewPart["header"]["image"]["parameters"]["value"],$imageId);
                    Core::$systemDB->insert("view",["parent"=>$headerId, 
                        "partType"=>"text","aspectClass"=>$aspectClass,"role"=>$viewPart["role"],"viewIndex"=>1]);
                    $titleId= Core::$systemDB->getLastId();
                    $this->addViewParameter("value",$viewPart["header"]["title"]["parameters"]["value"],$titleId);
                }else{//update
                    //probably no reason to update the image and title views, just updating parameters
                    $headerParts = Core::$systemDB->selectMultiple("view","*",["parent"=>$header]);
                    foreach($headerParts as $part){
                        if ($part["partType"]=="text")
                            $type="title";
                        else $type = "image";
                        $this->addViewParameter("value",$viewPart["header"][$type]["info"],$part["id"]);
                    }
                }
            }
            else if (!empty($header)){
                Core::$systemDB->delete("view",["parent"=>$viewPart["id"], "partType"=>"header"]);
            }
        }
    }
    //receives array with view info and organizes info to put into DB
    public function organizeViewData($data){
        $partList=$data['partlist'];
        unset($data['partlist']);
        if (array_key_exists('replacements', $data)){
            $data['replacements']=json_encode($data['replacements']);
        }
        
        $returnArray=['view_role'=>$data,'view_part'=>[]];
        foreach ($partList as $part){
            $view_part=[];
            $view_part['pid']=$part['pid'];
            $view_part['partType']=$part['partType'];
            unset($part['pid']);
            unset($part['partType']); 
            $view_part['partContents']=json_encode($part);
            $returnArray['view_part'][]=$view_part;
        }
        
        return $returnArray;
    }
    
    //gets a parameter with all params of each view of the given condition
    public function getViewParameters($where){
        //ToDo: check if this query could be done in a more eficient way
        $db_params = Core::$systemDB->selectMultiple(
                "view v left join view_parameter on viewId=v.id right join parameter p on p.id=parameterId",
            "viewId,p.id,type,value",$where);
        $view_params=[];
        foreach($db_params as $p){
            if ($p["viewId"]==null)
                continue;
            $view_params[$p["viewId"]][]=$p;
        }
        //print_r($view_params)
        return $view_params;
    }
    
    function lookAtParameter($child,$view_params,&$organizedView){
        $child["parameters"]=[];
        if (array_key_exists($child['id'], $view_params)){
            $params = $view_params[$child['id']];
            foreach($params as $param){
                if ($param["type"]=="variables"){
                    $child["variables"]= json_decode($param["value"],true);
                }
                else
                    $child["parameters"][$param["type"]]=$param["value"];
            }
        }
        $organizedView["children"][] = array_merge($child,["children"=>[]]);
    }
    //Go through views and update array with parameters info (it receives arrays with all the data, doesnt do more queries)
    public function lookAtChildren($parent,$children, $view_params, &$organizedView){
        if (!array_key_exists($parent, $children))
            return;
        
        for($i=0;$i<count($children[$parent]);$i++){
        //foreach($children[$parent] as $child){
            $child=$children[$parent][$i];
            $this->lookAtParameter($child,$view_params,$organizedView);
            //ToDo: instances
            $this->lookAtChildren($child['id'],$children, $view_params, $organizedView["children"][$i]);    
        }
    }
    
    //Go through views and update array with parameters info (this does all the necessary queries)
    public function lookAtChildrenWQueries($parent,&$organizedView){
        $children=Core::$systemDB->selectMultiple("view","*",["parent"=>$parent],"viewIndex");  
        $view_params=$this->getViewParameters(["parent"=>$parent]);
        
        for($i=0;$i<count($children);$i++){
            $child=$children[$i];
            $this->lookAtParameter($child,$view_params,$organizedView);
            $this->lookAtChildrenWQueries($child["id"],$organizedView["children"][$i]);        
        }    
        if ($organizedView["partType"]=="block" && sizeof($organizedView["children"])>0){
            if ($organizedView["children"][0]["partType"]=="header"){
                $organizedView["header"]=[];
                $organizedView["header"]["image"]=$organizedView["children"][0]["children"][0];
                $organizedView["header"]["title"]=$organizedView["children"][0]["children"][1];
                unset($organizedView["children"][0]);
                $organizedView["children"]= array_values($organizedView["children"]);
            }
        } 
    }
    //contructs an array of the view with all it's children
    public function getViewWithParts($anAspectId,$role){//return everything organized like the previous db system
        //ToDo: template references
        $anAspect = Core::$systemDB->select("view","*",["id"=>$anAspectId]);
        
        if ($anAspect["aspectClass"]==null){//only 1 aspect exists
            //this has a lot of queries (select all children, each of their params and children)
            //this happens because null aspectClass when there's only one aspect
            $organizedView=$anAspect;
            $organizedView["children"]=[];
            $this->lookAtChildrenWQueries($anAspectId,$organizedView);          
        }
        else{//multiple aspects exist
            $viewsOfAspect= Core::$systemDB->selectMultiple("aspect_class left join view on id=viewId",
                    "*",["aspect"=>$anAspect["aspectClass"],"role"=>$role],"parent,viewIndex");
                
            $view_params=$this->getViewParameters(["aspectClass"=>$anAspect["aspectClass"],"role"=>$role]);
            
            $viewsByParent=[];
            foreach ($viewsOfAspect as $v){
                if ($v['partType']=="aspect")
                    $viewsByParent = $v;
                else
                    $viewsByParent['parts'][$v['parent']][]= $v;
            }
            
            $organizedView=$viewsByParent;
            unset($organizedView["parts"]);
            $organizedView["children"]=[];
            if (array_key_exists("parts", $viewsByParent))
                $this->lookAtChildren($viewsByParent['id'],$viewsByParent['parts'], $view_params, $organizedView);  
        }
        return $organizedView;   
    }
    
    public function getAspects($anAspeptId){
        
        $asp=Core::$systemDB->select("view","*",["id"=>$anAspeptId]);
        if ($asp["aspectClass"]!=null){
            //there are other aspects
            $aspects= Core::$systemDB->selectMultiple("aspect_class left join view on viewId=id","*",
                    ["aspect"=>$asp["aspectClass"]]);
            return $aspects;
        }
        return [$asp];
    }
    //returns all the aspects for a given view or for the given role
    public function getViewRoles($viewId,$role=null){
        if ($role === null) {
            return Core::$systemDB->selectMultiple("view","*",["pageId"=>$viewId, "partType"=>"aspect"]);
            //return Core::$systemDB->selectMultiple("view_role", '*', ['viewId' => $viewId, 'course' => $this->getCourseId()]);
        } else {
            return Core::$systemDB->select("view","*",["pageId"=>$viewId, "role"=>$role, "partType"=>"aspect"]);
            //return Core::$systemDB->select("view_role", '*', 
            //        ['viewId' => $viewId, 'course' => $this->getCourseId(),'role'=>$role]);
        }
    }
    //returns all pages or page of the name given
    public function getPages($pageName = null) {
        if ($pageName == null) {
            //return Core::$systemDB->selectMultiple("view", '*', ['course' => $this->getCourseId()]);
            return Core::$systemDB->selectMultiple("page","*",['course' => $this->getCourseId()]);
        } else {
            //return Core::$systemDB->select("view", '*', ['viewId' => $viewId, 'course' => $this->getCourseId()]);
            return Core::$systemDB->select("page","*",["name"=>$pageName,'course' => $this->getCourseId()]);
        }
    }
    public function getCourseId(){
        return $this->viewsModule->getParent()->getId();
    }
    
    //ToDo::change name to registerPage, and registerdViews to registered pages
    public function registerPage($module, $pageName, $roleType=self::VT_ROLE_SINGLE) {
        $pageSettings = ["name"=> $pageName, "roleType"=>$roleType];              
        $page = $this->getPages($pageName);
        
        if (empty($page)) {
            $courseId=$this->getCourseId();
            $role="";
            if ($roleType == self::VT_ROLE_SINGLE)
                $role='role.Default';
            else if ($roleType == self::VT_ROLE_INTERACTION)
                $role='role.Default>role.Default';

            Core::$systemDB->insert('view',[ 
                    "role"=>$role, "partType"=>"aspect"]);
            $page["viewId"]=Core::$systemDB->getLastId();
            
            Core::$systemDB->insert('page',array_merge($pageSettings,['course'=>$courseId,"viewId"=>$page["viewId"]]));
            $page["id"]=Core::$systemDB->getLastId();  
        }
        
        if (array_key_exists($page['id'], $this->registeredPages))
            new \Exception('Page' . $pageName . ' (id='.$page['id'].' is aready defined.');
        $pageSettings["viewId"]=$page["viewId"];
        $this->registeredPages[$page['id']] = $pageSettings;
    }

    public function registerFunction($funcLib,$funcName, $processFunc) {
        //ToDO: save on dictionary table ?
        if (!array_key_exists($funcLib, $this->registeredFunctions))
            $this->registeredFunctions[$funcLib]=[];
        if (array_key_exists($funcLib, $this->registeredFunctions[$funcLib]))
            new \Exception('Function ' . $funcName . ' already exists in library '. $funcLib);

        $this->registeredFunctions[$funcLib][$funcName] = $processFunc;
    }

    public function callFunction($funcLib, $funcName, $args, $context=null) {
        if (!array_key_exists($funcLib, $this->registeredFunctions))
            throw new \Exception('Called function ' . $funcName . ' on an unexistent library '. $funcLib);
        if (!array_key_exists($funcName, $this->registeredFunctions[$funcLib]))
            throw new \Exception('Function ' . $funcName . ' is not defined in library '. $funcLib);
        
        if ($context!==null)
            array_unshift($args,$context);
        return $this->registeredFunctions[$funcLib][$funcName](...$args);
    }

    
    public function registerPartType($partType, $breakFunc, $putTogetherFunc, $parseFunc, $processFunc) {
        if (array_key_exists($partType, $this->registeredPartTypes))
            new \Exception('Part ' . $partType . ' is already exists');

        $this->registeredPartTypes[$partType] = array($breakFunc, $putTogetherFunc, $parseFunc, $processFunc);
    }

    public function callPartBreak($partType, &...$args) {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][0];
        if ($func != null)
            $func(...$args);
    }

    public function callPartPutTogether($partType, &...$args) {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][1];
        if ($func != null)
            $func(...$args);
    }

    public function callPartParse($partType, &...$args) {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][2];
        if ($func != null)
            $func(...$args);
    }

    public function callPartProcess($partType, &...$args) {
        if (!array_key_exists($partType, $this->registeredPartTypes))
            throw new \Exception('Part ' . $partType . ' is not defined');
        $func = $this->registeredPartTypes[$partType][3];
        if ($func != null)
            $func(...$args);
    }

    
    public function processVariables(&$part, $viewParams, $visitor, $func = null) {
        $actualVisitor = $visitor;
        $params = $viewParams;
        if (array_key_exists('variables', $part)) {
            foreach ($part['variables'] as $k => &$v) {
                $this->getContinuationOrValue($v['value'], $visitor, function($continuation) use ($k, &$params, &$v) {
                    if (is_array($continuation) && sizeof($continuation) == 1 && array_key_exists(0, $continuation))
                        $continuation = $continuation[0];

                    if ($continuation instanceof ValueNode)
                        $continuation = $continuation->getValue();

                    $params[$k] = $continuation;
                }, function($value) use ($k, &$params, &$v) {
                    $params[$k] = $value;
                });
            }
            if ($params != $viewParams)
                $actualVisitor = new EvaluateVisitor($params, $this);
        }
        //adding all parameters to $part (so they can be used in js)
        if (array_key_exists("events", $part) || array_key_exists("directive", $part)){
            foreach ($params as $k => $val){
                $part['variables'][$k]["value"]=$val;
            }
        }
        

        if ($func != null && is_callable($func))
            $func($params, $actualVisitor);
    }
//fixme
    private function getContinuationOrValue(&$node, &$visitor, $cont, $val) {
        /*if (is_a($node, 'Modules\Views\Expression\DatabasePath'))
            $cont($node->accept($visitor, null, true));
        else if (is_a($node, 'Modules\Views\Expression\DatabasePathFromParameter'))
            $cont($node->accept($visitor, true));*/
        //print_R($node);
        $lib=null;
        $visitedNode = $node->accept($visitor)->getValue();
        if (is_a($node,'Modules\Views\Expression\FunctionOp')){
            $lib=$node->getLib();
        }
        
        if (is_array($visitedNode) && $lib!==null){
            if ($visitedNode["type"]=="object")
                $visitedNode["value"]["libraryOfVariable"]=$lib;
            else {//type == collection
                foreach ($visitedNode["value"] as &$element){
                    $element["libraryOfVariable"]=$lib;
                }
            }
        }
        //print_R($visitedNode);
        $val($visitedNode);
    }

    public function processLoop(&$container, $viewParams, $visitor, $func) {
        $containerArr = array();
        foreach($container as &$child) {
            if (!array_key_exists('loopData', $child["parameters"])) {
                if ($this->processIf($child, $visitor)) {
                    $func($child, $viewParams, $visitor);
                    $containerArr[] = $child;
                }
            } else {
                $repeatKey = "item";
                $repeatParams = array();
                $keys = null;
                $this->getContinuationOrValue($child['parameters']['loopData'], $visitor, 
                   function($continuation) use(&$repeatParams, &$keys, $repeatKey) {
                    $repeatParams= $continuation;
                }, function($value) use(&$repeatParams, &$keys, $repeatKey) {
                    if (is_null($value))
                        $value = [];
                    if (!is_array($value)) {
                         print_r($value);
                        throw new \Exception('Repeat must be an Array or a Continuation.');
                    }
                    $value=$value["value"];
                    //if the $value array is associative it will be put in a sequential array
                    $isNumericArray=true;
                    foreach(array_keys($value) as $key){
                        if (!is_int($key)){
                            $isNumericArray=false;
                            break;
                        }
                    }
                    if (!$isNumericArray)
                        $value= [$value];
                     
                    $repeatParams = $value;
                });
                $i=0;
                foreach ($repeatParams as &$params){
                    $params = [$repeatKey => $params];
                    $i++;
                }
                /*
                if (array_key_exists('filter', $child['repeat'])) {
                    $filter = $child['repeat']['filter'];
                    $repeatParams = array_filter($repeatParams, function($repeatParams) use($filter, $viewParams) {
                        $params = array_merge($viewParams, $repeatParams);
                        return $filter->accept(new EvaluateVisitor($params, $this))->getValue();
                    });
                }
                
                if (array_key_exists('sort', $child['repeat'])) {
                    $sort = $child['repeat']['sort'];
                    $valueExp = $sort['value'];
                    $values = array();
                    $i=0;
                    foreach ($repeatParams as &$params){
                        $values[$i] = $valueExp->accept(new EvaluateVisitor(array_merge($viewParams, $params), $this))->getValue();
                        $params['index']=$i;
                        $i++;  
                    }
                    
                    if ($sort['order'] == 'ASC')
                        usort($repeatParams, function($a, $b) use($values) {
                            return $values[$a['index']] > $values[$b['index']] ? 1 : -1;
                        });
                    else
                        usort($repeatParams, function($a, $b) use($values) {
                            return $values[$a['index']] < $values[$b['index']] ? 1 : -1;
                        });
                }
                */
                //unset($child['repeat']);
                $repeatParams= array_values($repeatParams);
                for($p=0;$p < sizeof($repeatParams);$p++){
                    
                    $loopParam = [$repeatKey => ["type"=>"object", "value"=>$repeatParams[$p][$repeatKey]]];
                    
                    $dupChild = $child;
                    $paramsforEvaluator = array_merge($viewParams, $loopParam, array("index" => $p));
                    $visitor = new EvaluateVisitor($paramsforEvaluator, $this);

                    if ($this->processIf($dupChild, $visitor)) {
                        $func($dupChild, $paramsforEvaluator, $visitor);
                        $containerArr[] = $dupChild;
                    }
                }
            }
        }
        $container = $containerArr;
    }

    public function processIf(&$child, $visitor) {
        if (!array_key_exists('if', $child))
            return true;
        else {
            $ret = false;
            if ($child['if']->accept($visitor)->getValue() == true)
                $ret = true;
            unset($child['if']);
            return $ret;
        }
    }

    public function processPart(&$part, $viewParams, $visitor) {
        $this->processVariables($part, $viewParams, $visitor, function($viewParams, $visitor) use(&$part) {
            if (array_key_exists('style', $part))
                $part['style'] = $part['style']->accept($visitor)->getValue();
            if (array_key_exists('class', $part))
                $part['class'] = $part['class']->accept($visitor)->getValue();

            $this->callPartProcess($part['partType'], $part, $viewParams, $visitor);
        });
    }

    public function processView(&$view, $viewParams) {
        $visitor = new EvaluateVisitor($viewParams, $this);
        $this->processLoop($view['children'], $viewParams, $visitor, function(&$part, $viewParams, $visitor) {
            $this->processPart($part, $viewParams, $visitor);

        });
        /*foreach ($view['content'] as &$part) {
            $this->processPart($part, $viewParams, $visitor);
        }*/
    }

    public function parseVariables(&$part) {
        if (array_key_exists('variables', $part)) {
            foreach ($part['variables'] as $k => &$v){
                
                $this->parseSelf($v['value']);
  
            }  
        }
    }

    public function parseLoop(&$part) {
        if (array_key_exists('loopData', $part["parameters"])) {
            $this->parseSelf($part['parameters']['loopData']);

            //ToDo
            /*if (array_key_exists('filter', $part['repeat']))
                $this->parseSelf($part['repeat']['filter']);

            if (array_key_exists('sort', $part['repeat']))
                $this->parseSelf($part['repeat']['sort']['value']);*/
            
        }
    }

    public function parseIf(&$part) {
        if (array_key_exists('if', $part))
            $this->parseSelf($part['if']);
    }

    public function parsePart(&$part) {
        //parse ["data"] or ["variables"]
        $this->parseVariables($part);
        if (array_key_exists('style', $part))
            $this->parseSelf($part['style']);
        if (array_key_exists('class', $part))
            $this->parseSelf($part['class']);

        $this->parseLoop($part);
        $this->parseIf($part);
        
        $this->callPartParse($part['partType'], $part);
    }

    public function parseView(&$view) {
        //print_r($view);
        foreach ($view['children'] as &$part) {
            $this->parsePart($part);
        }
        //print_r($view);
    }
    
    //go throgh roles of a view to find the role of the user
    public function handleHelper($roleArray, $course,$userRoles){
        $roleFound = null;
        $userSpecificView = 'user.' . (string)API::getValue('user');
        if (in_array($userSpecificView, $roleArray)) {
            $roleFound = $userSpecificView;                 
        }else {
            if (in_array('role.Default', $roleArray)) {
               $roleFound = 'role.Default';
            }

            //this is choosing a role with low hirearchy (maybe change)
            $course->goThroughRoles(function ($roleName, $hasChildren, $continue) use ($userRoles, $roleArray, &$roleFound) {
                if (in_array('role.' . $roleName, $roleArray) && in_array($roleName, $userRoles)) {
                    
                    $roleFound = 'role.' . $roleName;
                }
                if ($hasChildren)
                    $continue();
            });
        }
        return $roleFound;
    }
    
    //handles requests to show a page
    public function handle($pageId) {
        if (!array_key_exists($pageId, $this->registeredPages)) {
            API::error('Unknown view: ' . $pageId, 404);
        }

        $viewParams = array();
        if (API::hasKey('course') && (is_int(API::getValue('course')) || ctype_digit(API::getValue('course')))) {
            $course = Course::getCourse((string)API::getValue('course'));
            $page = Core::$systemDB->select("page","*",["id"=>$pageId]);
            $viewRoles = array_column($this->getAspects($page["viewId"]),'role');
            $viewType = $this->registeredPages[$pageId]['roleType'];
            
            $roleOne=$roleTwo=null;
            if ($viewType == ViewHandler::VT_SINGLE){
                $view=$this->getViewWithParts($pageId, "");
                $userView = ViewEditHandler::putTogetherView($view, array());
            }else{
                //TODO check if everything works with the roles in the handle helper (test w user w multiple roles, and child roles)
                if ($viewType == ViewHandler::VT_ROLE_INTERACTION){
                    API::requireValues('user');
                    $roleArray=[];//role1=>[roleA,roleB],role2=>[roleA],...
                    foreach ($viewRoles as $roleInteraction){
                        $roles= explode('>',$roleInteraction);
                        $roleArray[$roles[0]][]=$roles[1];
                    }
                    $userRoles=$course->getUser((string)API::getValue('user'))->getRoles();
                    $roleOne=$this->handleHelper(array_keys($roleArray), $course,$userRoles); 
                    $roleArray = $roleArray[$roleOne];
                    
                    if (in_array('special.Own', $roleArray) && (string)API::getValue('user') == (string)Core::getLoggedUser()->getId()) {
                        $roleTwo = 'special.Own';
                    }
                    else {
                        $loggedUserRoles = $course->getLoggedUser()->getRoles();
                        $roleTwo=$this->handleHelper($roleArray, $course,$loggedUserRoles);     
                    }
                    
                    $userView=$this->getViewWithParts($pageId, $roleOne.'>'.$roleTwo);
                }
                else if ($viewType == ViewHandler::VT_ROLE_SINGLE){
                    $userRoles = $course->getLoggedUser()->getRoles();
                    $roleOne=$this->handleHelper($viewRoles, $course,$userRoles); 
                    $userView=$this->getViewWithParts($page["viewId"], $roleOne);
                }  
                $parentParts = $this->viewsModule->findParentParts($course, $pageId, $viewType, $roleOne, $roleTwo);  
                // ToDo check if  parentparts is working for role interaction
                $userView = ViewEditHandler::putTogetherView($userView, $parentParts);
            }
                     
            $viewParams = array(
                'course' => (string)API::getValue('course'),
                'viewer' => (string)Core::getLoggedUser()->getId()
            );

            if (API::hasKey('user'))
                $viewParams['user'] = (string)API::getValue('user');
            
            $this->parseView($userView);
            $this->processView($userView, $viewParams);

            $viewData = $userView;
        } else {
            // general views (plugins not bound to courses)
            API::error('Unsupported!');
            //$viewData = Core::getViews()->get($view)['view'];
        }
        API::response(array(
            //'fields' => DataSchema::getFields($viewParams),//not beeing user currently
            'view' => $viewData
        ));  
    }
}