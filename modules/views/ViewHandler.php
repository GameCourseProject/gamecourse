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
    //private $registeredPages = array(); //this was used when all the pages where created by templates
                            //it could still be usefull if we wanted to know what pages where create by templates
    
    private $registeredFunctions = array();
    private $registeredLibFunctions = array();
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

    //public function getRegisteredPages() {
    //    return $this->registeredPages;
    //}
    
    //receives parameter and viewid, and adds what is necessary to DB
    public function addViewParameter($type,$value,$viewId,$parmOfView=null){   
        if ($parmOfView===null){
            $parmOfView = Core::$systemDB->select("parameter join view_parameter on id=parameterId",
                    ["type"=>$type, "viewId"=>$viewId],"id,value");
        }
        
        if (!empty($parmOfView)){
            if ($parmOfView["value"]!=$value)
                //the view_param has a parm w dif val, we delete the view_param, 
                // if the param isnt used by any others,a trigger will delete it
                Core::$systemDB->delete("view_parameter",["viewId"=>$viewId,"parameterId"=>$parmOfView["id"]]);
            else //the view is already associated with this parameter
                return;
        }
        $parameter = Core::$systemDB->select("parameter",["type"=>$type,"value"=>$value],"id");
        //if param doesnt exist, insert it
        if (empty($parameter)){
            Core::$systemDB->insert("parameter",["type"=>$type,"value"=>$value]);
            $parameter=Core::$systemDB->getLastId();
        }            
        //param is not yet associatd with this view
        if (empty(Core::$systemDB->select("view_parameter",["viewId"=>$viewId,"parameterId"=>$parameter])))
            Core::$systemDB->insert("view_parameter",["viewId"=>$viewId,"parameterId"=>$parameter]);
    }
    public function updateParameters(&$viewPart){
        $oldParameters = Core::$systemDB->selectMultiple("parameter join view_parameter on parameterId=id",
            ["viewId"=>$viewPart["id"]],"type,value,id");
        $paramesToDelete= array_combine(array_column($oldParameters, "type"),$oldParameters);
        if (array_key_exists("parameters", $viewPart)){
            foreach ($viewPart["parameters"] as $type => $param){
                $viewParam=null;
                if (array_key_exists($type, $paramesToDelete)) {
                    $viewParam=$paramesToDelete[$type];
                    unset($paramesToDelete[$type]);
                }
                $this->addViewParameter($type,$param,$viewPart["id"],$viewParam);
            }
        }       
        if (array_key_exists("variables", $viewPart)){
            $value = json_encode($viewPart["variables"]);
            $viewParam=null;
            if (array_key_exists("variables", $paramesToDelete)) {
                $viewParam=$paramesToDelete["variables"];
                unset($paramesToDelete["variables"]);
            }
            $this->addViewParameter("variables",$value,$viewPart["id"],$viewParam);
        }  
        foreach($paramesToDelete as $type => $param){
            Core::$systemDB->delete("view_parameter",["viewId"=>$viewPart["id"],"parameterId"=>$param["id"]]);
        }
    }
    //receives view and updates the DB with its info
    //propagates changes in the main view to all its children
//$basicUpdate -> u only update basic view atributes(ignores view parameters and deletion of viewparts), change in aspectclass
    public function updateViewAndChildren($viewPart, $basicUpdate=false, $ignoreIds=false,&$partsInDB=null){
    //insert data into DB, should check previous data and update/delete stuff   
        if ($viewPart["partType"]!="aspect" ){            
            //insert/update views
            if (array_key_exists("id", $viewPart) && !$ignoreIds){//already in DB, may need update
                Core::$systemDB->update("view",["viewIndex"=>$viewPart["viewIndex"],
                    "partType"=>$viewPart["partType"],"parent"=>$viewPart["parent"],"aspectClass"=>$viewPart["aspectClass"]],
                        ["id"=>$viewPart["id"]]);  
                
                if (!$basicUpdate)
                    unset($partsInDB[$viewPart["id"]]);
            }else{//not in DB, insert it
                Core::$systemDB->insert("view",
                    ["parent"=>$viewPart["parent"],"role"=>$viewPart["role"],"aspectClass"=>$viewPart["aspectClass"],
                    "partType"=>$viewPart["partType"], "viewIndex"=>$viewPart["viewIndex"]]);
                $viewPart["id"]=Core::$systemDB->getLastId();
            }     
            if (!$basicUpdate){//update parameters
                $this->updateParameters($viewPart);
            }
        }
        if ($viewPart["partType"]=="table"){
            foreach($viewPart["headerRows"] as $headRow){
                $rowPart=$headRow;
                $rowPart["partType"]="headerRow";
                foreach($headRow["values"] as $rowElement){
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $viewPart["children"][]=$rowPart;
            }
            foreach($viewPart["rows"] as $row){
                $rowPart=$row;
                $rowPart["partType"]="row";
                foreach($row["values"] as $rowElement){
                    $rowPart["children"][] = $rowElement["value"];
                }
                unset($rowPart["values"]);
                $viewPart["children"][]=$rowPart;
            }
            unset($viewPart["rows"]);
            unset($viewPart["headerRows"]);
        }
        if (array_key_exists("children", $viewPart)){
            $children = null;
            if (!$basicUpdate){
                $children = Core::$systemDB->selectMultiple("view",["parent"=>$viewPart["id"]]);
                $children = array_combine(array_column($children,"id"),$children);
            }
            
            foreach ($viewPart["children"] as $key => &$child){
                $child["role"]=$viewPart["role"];
                $child["aspectClass"]=$viewPart["aspectClass"];
                $child["parent"]=$viewPart["id"];
                $child["viewIndex"]=$key;
                $this->updateViewAndChildren($child,$basicUpdate,$ignoreIds, $children);
            }
            if (!$basicUpdate){
                foreach ($children as $deleted){
                    Core::$systemDB->delete("view",["id"=>$deleted["id"]]);
                }
            }
        }
        if ($viewPart["partType"]=="block") {//deal with header of block
            $header = Core::$systemDB->select("view",["parent"=>$viewPart["id"], "partType"=>"header"],"id");
            if (array_key_exists("header", $viewPart)){//if there is a header
                if(!$basicUpdate && empty($header)){ //insert (header is not in DB)
                    Core::$systemDB->insert("view",["parent"=>$viewPart["id"], 
                        "partType"=>"header","role"=>$viewPart["role"],"aspectClass"=>$viewPart["aspectClass"]]);
                    $headerId = Core::$systemDB->getLastId();
                       
                    $image = ["role"=>$viewPart["role"],"parent"=>$headerId,
                        "partType"=>"image","aspectClass"=>$viewPart["aspectClass"],
                        "viewIndex"=>0,"parameters"=>$viewPart["header"]["image"]["parameters"]];
                    $this->updateViewAndChildren($image,$basicUpdate,$ignoreIds);
                    
                    $text = ["role"=>$viewPart["role"],"parent"=>$headerId,
                        "partType"=>"text","aspectClass"=>$viewPart["aspectClass"],
                        "viewIndex"=>1,"parameters"=>$viewPart["header"]["title"]["parameters"]];
                    $this->updateViewAndChildren($text,$basicUpdate,$ignoreIds);
                    
                }else if (!empty($header)){//update (header is in DB)
                    //in most cases just updating parameters
                    $headerParts = Core::$systemDB->selectMultiple("view",["parent"=>$header]);
                    foreach($headerParts as $part){
                        if ($basicUpdate) {
                            Core::$systemDB->update("view",["role" => $viewPart["role"],"aspectClass"=>$viewPart["aspectClass"]], 
                                    ["id" => $part["id"]]);
                        } else {
                            if ($part["partType"] == "text")
                                $type = "title";
                            else
                                $type = "image";
                            $part["parameters"]=$viewPart["header"][$type]["info"];
                            $this->updateParameters($part);
                        }
                    }
                }
            }
            else if (!empty($header) && !$basicUpdate){//delete header in db
                Core::$systemDB->delete("view",["parent"=>$viewPart["id"], "partType"=>"header"]);
            }
            if ($basicUpdate && !empty($header)) {//ToDo
                Core::$systemDB->update("view",["role" => $viewPart["role"],"aspectClass"=>$viewPart["aspectClass"]],
                        ["id" => $header]);
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
    
    //gets a array with all params of each view of the given condition
    public function getViewParameters($where){
        //ToDo: check if this query could be done in a more eficient way
        if (array_key_exists("aspectClass", $where)){
            $db_params = Core::$systemDB->selectMultiple(
                "aspect_class a natural join view v left join view_parameter vp".
                " on vp.viewId=v.id right join parameter p on p.id=parameterId",
            $where,"vp.viewId,p.id,type,value");
        }else{
            $db_params = Core::$systemDB->selectMultiple(
                "view v left join view_parameter vp on vp.viewId=v.id "
                    . "right join parameter p on p.id=parameterId",
            $where,"vp.viewId,p.id,type,value");
        }
        
        $view_params=[];
        foreach($db_params as $p){
            if ($p["viewId"]==null)
                continue;
            $view_params[$p["viewId"]][]=$p;
        }
        return $view_params;
    }
    function lookAtTable(&$organizedView){
        if ($organizedView["partType"]=="table" && sizeof($organizedView["children"])>0){
            $organizedView["rows"]=[];
            $organizedView["headerRows"]=[];
            foreach($organizedView["children"] as $row){
                $rowType = $row["partType"]."s";
                $values=[];
                foreach($row["children"] as $cell){
                    $values[]=["value"=>$cell];
                }
                unset($row["children"]);
               
                $organizedView[$rowType][]= array_merge($row,["values"=>$values]);
            }
            for ($i=0;$i<(sizeof($organizedView["rows"])+sizeof($organizedView["headerRows"]));$i++){
                unset($organizedView["children"][$i]);
            }
            $organizedView["columns"]=sizeof($organizedView["rows"][0]["values"]);
        } 
    }
    function lookAtHeader(&$organizedView){
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
        $this->lookAtHeader($organizedView);
        $this->lookAtTable($organizedView);
    }
    
    //Go through views and update array with parameters info (receives parents and uses queries to get the rest)
    public function lookAtChildrenWQueries($parentId,&$organizedView){
        $children=Core::$systemDB->selectMultiple("view",["parent"=>$parentId],"*","viewIndex");  
        $view_params=$this->getViewParameters(["parent"=>$parentId]);
        
        for($i=0;$i<count($children);$i++){
            $child=$children[$i];
            $this->lookAtParameter($child,$view_params,$organizedView);
            $organizedView["children"][$i]["aspectClass"]=$organizedView["aspectClass"];
            $this->lookAtChildrenWQueries($child["id"],$organizedView["children"][$i]);        
        }    
        $this->lookAtHeader($organizedView);
        $this->lookAtTable($organizedView);
    }
    //gets aspect view 
    public function getAspect($aspectId){
        $asp=Core::$systemDB->select("aspect_class natural join view",["id"=>$aspectId]);
        if (empty($asp)){
            //aspect class hasnt' been assigned because it has only 1 aspect
            $asp=Core::$systemDB->select("view",["id"=>$aspectId]);
        }
        return $asp;
    }
    //receives an aspect id and returns all aspects of that aspectClass
    public function getAspects($anAspeptId){
        $asp = $this->getAspect($anAspeptId);
        if ($asp["aspectClass"]!=null){
            //there are other aspects
            $aspects= Core::$systemDB->selectMultiple("aspect_class natural join view",
                    ["aspectClass"=>$asp["aspectClass"],"partType"=>"aspect"]);
            return $aspects;
        }
        return [$asp];
    }
    //contructs an array of the view with all it's children, if there isn't a role returns array of view arrays
    //ToDO template references
    public function getViewWithParts($anAspectId,$role=null){
        $anAspect = $this->getAspect($anAspectId);
        if ($anAspect["aspectClass"]===null){//only 1 aspect exists
            //this has a lot of queries (select all children, each of their params and children)
            //this happens because null aspectClass when there's only one aspect
            $organizedView=$anAspect;
            $organizedView["children"]=[];
            $this->lookAtChildrenWQueries($anAspect["id"],$organizedView);     
            $aspectsViews = [$organizedView];
        }
        else{//multiple aspects exist
            $where = ["aspectClass"=>$anAspect["aspectClass"]];
            if ($role)
                $where["role"]=$role;
            //gets all the views of the aspect (using aspectclass and role)
            $viewsOfAspect= Core::$systemDB->selectMultiple("aspect_class natural join view",
                    $where,
                    "id,role,partType,parent,viewIndex,aspectClass","parent,viewIndex");
            
            $view_params=$this->getViewParameters($where);
            $aspectsViews=[];
            $parts=[];
            foreach ($viewsOfAspect as $v){
                if ($v['partType']=="aspect")
                    $aspectsViews[] = $v;
                else
                    $parts[$v['parent']][]= $v;
            }
            foreach($aspectsViews as &$organizedView){
                $organizedView["children"]=[];
                if (sizeof($parts)>0)
                    $this->lookAtChildren($organizedView['id'],$parts, $view_params, $organizedView); 
            }
        }
        if ($role)
           return $organizedView;
        else
           return $aspectsViews; 
    }
    
    //returns all the aspects for a given view or for the given role
    public function getViewRoles($viewId,$role=null){
        if ($role === null) {
            return Core::$systemDB->selectMultiple("view",["pageId"=>$viewId, "partType"=>"aspect"]);
        } else {
            return Core::$systemDB->select("view",["pageId"=>$viewId, "role"=>$role, "partType"=>"aspect"]);
        }
    }
    //returns all pages or page of the name or id given
    public function getPages($id=null,$pageName = null) {
        return $this->getPagesOfCourse($this->getCourseId(), $id, $pageName);
    }
    public static function getPagesOfCourse($courseId,$id=null,$pageName = null) {
        $fileds="course,id,name,theme,viewId,roleType+0 as roleType";
        if ($pageName == null && $id==null) {
            $pages=Core::$systemDB->selectMultiple("page",['course' => $courseId],$fileds);
            return array_combine(array_column($pages, "id"),$pages);
        } else if ($id !== null) {
            return Core::$systemDB->select("page",["id"=>$id,'course' => $courseId],$fileds);
        } else {
            return Core::$systemDB->select("page",["name"=>$pageName,'course' => $courseId],$fileds);
        }
    }
    public function getCourseId(){
        return $this->viewsModule->getParent()->getId();
    }
    public function createPageOrTemplateIfNew($name,$pageOrTemp,$roleType=self::VT_ROLE_SINGLE){
        if(empty($this->getPages(null,$name))){
            $this->createPageOrTemplate($name,$pageOrTemp,$roleType);
        }
    }
    public function createPageOrTemplate($name,$pageOrTemp,$roleType=self::VT_ROLE_SINGLE){
        $courseId=$this->getCourseId();
        
        if ($roleType == self::VT_ROLE_SINGLE)
            $role='role.Default';
        else if ($roleType == self::VT_ROLE_INTERACTION)
            $role='role.Default>role.Default';
        
        Core::$systemDB->insert("view",["partType"=>"aspect","role"=>$role]);
        $viewId=Core::$systemDB->getLastId();
        
        //page or template to insert in db
        $newView=["name"=>$name,"course"=>$courseId,"roleType"=>$roleType];
        if ($pageOrTemp=="page"){
            $newView["viewId"]=$viewId;
            Core::$systemDB->insert("page",$newView);
        }else{
            Core::$systemDB->insert("template",$newView);
            $templateId=Core::$systemDB->getLastId();
            Core::$systemDB->insert("view_template",["viewId"=>$viewId,"templateId"=>$templateId]);
        }
        //return $id;
    }
            
    
    /*public function registerPage($module, $pageName, $roleType=self::VT_ROLE_SINGLE) {
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
    }*/

    public function registerFunction($funcLib,$funcName, $processFunc) {
        //ToDO: save on dictionary table ?
        if ($funcLib==null ){            
            if (array_key_exists($funcName, $this->registeredFunctions))
                new \Exception('Function ' . $funcName . ' already exists.');
            $this->registeredFunctions[$funcName] = $processFunc;
        }else{
            if (!array_key_exists($funcLib, $this->registeredLibFunctions))
                $this->registeredLibFunctions[$funcLib]=[];
            if (array_key_exists($funcLib, $this->registeredLibFunctions[$funcLib]))
                new \Exception('Function ' . $funcName . ' already exists in library '. $funcLib);

            $this->registeredLibFunctions[$funcLib][$funcName] = $processFunc;
        }
        
    }

    public function callFunction($funcLib, $funcName, $args, $context=null) {
        //check if it's a function not associated with a library
        if (array_key_exists($funcName, $this->registeredFunctions)){
            $fun = $this->registeredFunctions[$funcName];            
        }
        else if ($funcLib!=null){
            if (!array_key_exists($funcLib, $this->registeredLibFunctions))
                throw new \Exception('Called function ' . $funcName . ' on an unexistent library '. $funcLib);
            if (!array_key_exists($funcName, $this->registeredLibFunctions[$funcLib]))
                throw new \Exception('Function ' . $funcName . ' is not defined in library '. $funcLib);
            $fun = $this->registeredLibFunctions[$funcLib][$funcName];
        }else
            throw new \Exception("Function " . $funcName . " doesn't exists.");
        if ($context!==null)
            array_unshift($args,$context);
        return $fun(...$args);
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
                $params[$k] = $v['value']->accept($actualVisitor)->getValue();
                if ($params != $viewParams)
                    $actualVisitor = new EvaluateVisitor($params, $this);
            }
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

    public function processLoop(&$container, $viewParams, $visitor, $func) {
        $containerArr = array();
        foreach($container as &$child) {
            if (!array_key_exists('loopData', $child["parameters"])) {
                if ($this->processVisibilityCondition($child, $visitor)) {
                    $func($child, $viewParams, $visitor);
                    $containerArr[] = $child;
                }
            } else {
                $repeatKey = "item";
                $repeatParams = array();
                $keys = null;
                $value = $child['parameters']['loopData']->accept($visitor)->getValue();
                
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
                
                $i=0;
                foreach ($repeatParams as &$params){
                    $params = [$repeatKey => $params];
                    $i++;
                }
                
                //unset($child['repeat']);
                $repeatParams= array_values($repeatParams);
                for($p=0;$p < sizeof($repeatParams);$p++){
                    $value=$repeatParams[$p][$repeatKey];
                    if (!is_array($value))
                        $loopParam = [$repeatKey => $value];
                    else
                        $loopParam = [$repeatKey => ["type"=>"object", "value"=>$value]];
                    
                    $dupChild = $child;
                    $paramsforEvaluator = array_merge($viewParams, $loopParam, array("index" => $p));
                    $newvisitor = new EvaluateVisitor($paramsforEvaluator, $this);
                    
                    if ($this->processVisibilityCondition($dupChild, $newvisitor)) {
                        $func($dupChild, $paramsforEvaluator, $newvisitor);
                        $containerArr[] = $dupChild;
                    }
                }
            }
        }
        $container = $containerArr;
    }

    public function processVisibilityCondition(&$child, $visitor) {
        if (!array_key_exists('visibilityCondition', $child['parameters']))
            return true;
        else {
            $ret = false;
            if ($child['parameters']['visibilityCondition']->accept($visitor)->getValue() == true)
                $ret = true;
            unset($child['parameters']['visibilityCondition']);
            return $ret;
        }
    }

    public function processPart(&$part, $viewParams, $visitor) {
        $this->processVariables($part, $viewParams, $visitor, function($viewParams, $visitor) use(&$part) {
            $part["style"]="";
                      
            if (array_key_exists("visibilityType", $part["parameters"]) && $part["parameters"]["visibilityType"]=="invisible")
                $part['style'] .= " display: none; ";
            if (array_key_exists('style', $part["parameters"])) {
                $part['style'] .= $part["parameters"]['style']->accept($visitor)->getValue();
                
            }
            if (array_key_exists('class', $part["parameters"]))
                $part['class'] = $part["parameters"]['class']->accept($visitor)->getValue();

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
        if (array_key_exists("loopData", $part['parameters'])){
            if ($part['parameters']['loopData']=="{}" || $part['parameters']['loopData']=="")
                unset($part['parameters']['loopData']);
            else{        
                $this->parseSelf($part['parameters']['loopData']);           
            }
        }
    }

    public function parseVisibilityCondition(&$part) {
        //print_r($part);
        if (($part["parameters"]["visibilityType"] == "visible" && array_key_exists("loopData", $part["parameters"]))
                    || $part['parameters']['visibilityCondition']=="{}" || $part['parameters']['visibilityCondition']==""){        
            unset($part["parameters"]["visibilityCondition"]);          
        }
        else{        
            $this->parseSelf($part['parameters']['visibilityCondition']);           
        }
    }

    public function parsePart(&$part) {
        $this->parseVariables($part);
        if (array_key_exists('style', $part["parameters"]))
            $this->parseSelf($part["parameters"]['style']);
        if (array_key_exists('class', $part["parameters"]))
            $this->parseSelf($part["parameters"]['class']);

        $this->parseLoop($part);
        $this->parseVisibilityCondition($part);
        
        $this->callPartParse($part['partType'], $part);
    }

    public function parseView(&$view) {
        foreach ($view['children'] as &$part) {
            $this->parsePart($part);
        }
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
        if (empty($this->getPages($pageId))) {
            API::error('Unknown view: ' . $pageId, 404);
        }

        $viewParams = array();
        if (API::hasKey('course') && (is_int(API::getValue('course')) || ctype_digit(API::getValue('course')))) {
            $course = Course::getCourse((string)API::getValue('course'));
            $page = Core::$systemDB->select("page",["id"=>$pageId]);
            $viewRoles = array_column($this->getAspects($page["viewId"]),'role');
            $viewType = $this->getPages($pageId)['roleType'];
            
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
                    
                    $userView=$this->getViewWithParts($page["viewId"], $roleOne.'>'.$roleTwo);
                }
                else if ($viewType == ViewHandler::VT_ROLE_SINGLE){
                    $userRoles = $course->getLoggedUser()->getRoles();
                    $roleOne=$this->handleHelper($viewRoles, $course,$userRoles); 
                    $userView=$this->getViewWithParts($page["viewId"], $roleOne);
                }  
                //$parentParts = $this->viewsModule->findParentParts($course, $pageId, $viewType, $roleOne, $roleTwo);  
                // ToDo check if  parentparts is working for role interaction
                //$userView = ViewEditHandler::putTogetherView($userView, $parentParts);
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