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
    private $registeredViews = array();
    private static $defaultViewSettings = ['type' => self::VT_ROLE_SINGLE];
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

    public function getRegisteredViews() {
        return $this->registeredViews;
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
            $view_part['type']=$part['type'];
            unset($part['pid']);
            unset($part['type']); 
            $view_part['partContents']=json_encode($part);
            $returnArray['view_part'][]=$view_part;
        }
        
        return $returnArray;
    }
    
    //gets info from view_role and view_part tables, contructs an array of the view (like in the old system)
    public function getViewWithParts($viewId,$role){//return everything organized like the previous db system
        $viewRole=$this->getViewRoles($viewId,$role);
        
        if ($viewRole['replacements']!==null)
            $viewRole['replacements']=json_decode($viewRole['replacements'],true);
        else $viewRole['replacements']=[];
        
        $viewParts=Core::$systemDB->selectMultiple("view_part", '*', 
                ['viewId' => $viewId, 'course' => $this->getCourseId(), 'role'=>$role]);
        
        $viewRole['partlist']=[];
        foreach ($viewParts as $part){
            $part= array_merge($part,json_decode($part['partContents'],true));
            unset($part['partContents']);
            $viewRole['partlist'][$part['pid']]=$part;
        }  
        return $viewRole;
    }
    //returns all the view_roles for a given view or for the view_role of the given role
    public function getViewRoles($viewId,$role=null){
        if ($role === null) {
            return Core::$systemDB->selectMultiple("view_role", '*', ['viewId' => $viewId, 'course' => $this->getCourseId()]);
        } else {
            return Core::$systemDB->select("view_role", '*', 
                    ['viewId' => $viewId, 'course' => $this->getCourseId(),'role'=>$role]);
        }
    }
    //returns all the views or the view of the id given , (old version did the same as getViewRoles
    public function getViews($viewId = null) {
        if ($viewId==null)
            return Core::$systemDB->selectMultiple("view",'*',['course'=>$this->getCourseId()]);
        else
            return Core::$systemDB->select("view",'*',['viewId'=>$viewId,'course'=>$this->getCourseId()]);
    }
    public function getCourseId(){
        return $this->viewsModule->getParent()->getId();
    }

    public function registerView($module, $viewId, $viewName, $viewSettings = array()) {
        if (array_key_exists($viewId, $this->registeredViews))
            new \Exception('View' . $viewId . ' was previously defined by ' . $this->registeredViews[$viewId]['module']);

        $viewSettings = array_replace_recursive(static::$defaultViewSettings, $viewSettings);//type
        $viewSettings['module'] = $module->getId();
        $viewSettings['name'] = $viewName;
        $this->registeredViews[$viewId] = $viewSettings;
        $view = $this->getViews($viewId);
        if (empty($view)) {
            $viewpid = ViewEditHandler::getRandomPid();
            $courseId=$this->getCourseId();
            $newView=array_merge($viewSettings,['course'=>$courseId,'viewId'=>$viewId]);
            $role="";
            if ($viewSettings['type'] == self::VT_ROLE_SINGLE)
                $role='role.Default';
            else if ($viewSettings['type'] == self::VT_ROLE_INTERACTION)
                $role='role.Default>role.Default';
            
            $viewRole=['course'=>$courseId,'viewId'=>$viewId,
                'part'=>$viewpid,
                'role'=>$role,
                'replacements'=>json_encode([]) ];
            
            $part = ['viewId' => $viewId,'course'=>$courseId,'role' =>$role,
                    'type' => 'view',
                    'partContents' => json_encode(['content'=>array()]),
                    'pid' => $viewpid
            ];
            Core::$systemDB->insert('view',$newView);
            Core::$systemDB->insert('view_role',$viewRole);
            Core::$systemDB->insert('view_part',$part);
            
               /*
            $defaultView = $newView;
            if ($viewSettings['type'] == self::VT_ROLE_SINGLE)
                $defaultView = array('role.Default' => $newView);
            else if ($viewSettings['type'] == self::VT_ROLE_INTERACTION)
                $defaultView = array('role.Default' => array('role.Default' => $newView));
            $views->set($viewId, array('settings' => $viewSettings, 'view' => $defaultView));
             * 
             */
        }
    }

    public function registerFunction($funcName, $processFunc) {
        if (array_key_exists($funcName, $this->registeredFunctions))
            new \Exception('Function ' . $funcName . ' already exists');

        $this->registeredFunctions[$funcName] = $processFunc;
    }

    public function callFunction($funcName, $args) {
        if (!array_key_exists($funcName, $this->registeredFunctions))
            throw new \Exception('Function ' . $funcName . ' is not defined');
        return $this->registeredFunctions[$funcName](...$args);
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

    
    public function processData(&$part, $viewParams, $visitor, $func = null) {
        $actualVisitor = $visitor;
        $params = $viewParams;
        if (array_key_exists('data', $part)) {
            foreach ($part['data'] as $k => &$v) {
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
                $part['data'][$k]["value"]=$val;
            }
        }
        

        if ($func != null && is_callable($func))
            $func($params, $actualVisitor);
    }

    private function getContinuationOrValue(&$node, &$visitor, $cont, $val) {
        if (is_a($node, 'Modules\Views\Expression\DatabasePath'))
            $cont($node->accept($visitor, null, true));
        else if (is_a($node, 'Modules\Views\Expression\DatabasePathFromParameter'))
            $cont($node->accept($visitor, true));
        else {
            $val($node->accept($visitor)->getValue());
        }
    }

    public function processRepeat(&$container, $viewParams, $visitor, $func) {
        $containerArr = array();
        foreach($container as &$child) {
            if (!array_key_exists('repeat', $child)) {
                if ($this->processIf($child, $visitor)) {
                    $func($child, $viewParams, $visitor);
                    $containerArr[] = $child;
                }
            } else {
                $repeatKey = $child['repeat']['key'];
                $repeatParams = array();
                $keys = null;
                $this->getContinuationOrValue($child['repeat']['for'], $visitor, 
                   function($continuation) use(&$repeatParams, &$keys, $repeatKey) {
                    $repeatParams= $continuation;
                }, function($value) use(&$repeatParams, &$keys, $repeatKey) {
                    if (is_null($value))
                        $value = [];
                    if (!is_array($value)) {
                         print_r($value);
                        throw new \Exception('Repeat must be an Array or a Continuation.');
                    }

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
                
                foreach ($repeatParams as &$params){
                    $params = [$repeatKey => $params];
                }
                
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
                
                unset($child['repeat']);
                $repeatParams= array_values($repeatParams);
                for($p=0;$p < sizeof($repeatParams);$p++){
                    
                    if (array_key_exists('index', $repeatParams[$p])) {
                        unset($repeatParams[$p]['index']);
                    }
                    
                    $dupChild = $child;
                    $paramsforEvaluator = array_merge($viewParams, $repeatParams[$p], array($repeatKey . 'pos' => $p));
     
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
        $this->processData($part, $viewParams, $visitor, function($viewParams, $visitor) use(&$part) {
            if (array_key_exists('style', $part))
                $part['style'] = $part['style']->accept($visitor)->getValue();
            if (array_key_exists('class', $part))
                $part['class'] = $part['class']->accept($visitor)->getValue();

            $this->callPartProcess($part['type'], $part, $viewParams, $visitor);
        });
    }

    public function processView(&$view, $viewParams) {
        $visitor = new EvaluateVisitor($viewParams, $this);
        $this->processRepeat($view['content'], $viewParams, $visitor, function(&$part, $viewParams, $visitor) {
            $this->processPart($part, $viewParams, $visitor);

        });
        /*foreach ($view['content'] as &$part) {
            $this->processPart($part, $viewParams, $visitor);
        }*/
    }

    public function parseData(&$part) {
        if (array_key_exists('data', $part)) {
            foreach ($part['data'] as $k => &$v)
                $this->parseSelf($v['value']);
        }
    }

    public function parseRepeat(&$part) {
        if (array_key_exists('repeat', $part)) {
            $this->parseSelf($part['repeat']['for']);

            if (array_key_exists('filter', $part['repeat']))
                $this->parseSelf($part['repeat']['filter']);

            if (array_key_exists('sort', $part['repeat']))
                $this->parseSelf($part['repeat']['sort']['value']);
        }
    }

    public function parseIf(&$part) {
        if (array_key_exists('if', $part))
            $this->parseSelf($part['if']);
    }

    public function parsePart(&$part) {
        $this->parseData($part);
        if (array_key_exists('style', $part))
            $this->parseSelf($part['style']);
        if (array_key_exists('class', $part))
            $this->parseSelf($part['class']);

        $this->parseRepeat($part);
        $this->parseIf($part);

        $this->callPartParse($part['type'], $part);
    }

    public function parseView(&$view) {
        foreach ($view['content'] as &$part) {
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
    
    //handles requests to show a view, chooses 
    public function handle($viewId) {
        if (!array_key_exists($viewId, $this->registeredViews)) {
            API::error('Unknown view: ' . $viewId, 404);
        }

        $viewParams = array();
        if (API::hasKey('course') && (is_int(API::getValue('course')) || ctype_digit(API::getValue('course')))) {
            $course = Course::getCourse((string)API::getValue('course'));
            $viewRoles = array_column($this->getViewRoles($viewId),'role');
            $viewType = $this->registeredViews[$viewId]['type'];
            
            $roleOne=$roleTwo=null;
            if ($viewType == ViewHandler::VT_SINGLE){
                $view=$this->getViewWithParts($viewId, "");
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
                    
                    $userView=$this->getViewWithParts($viewId, $roleOne.'>'.$roleTwo);
                }else if ($viewType == ViewHandler::VT_ROLE_SINGLE){
                    $userRoles = $course->getLoggedUser()->getRoles();
                    $roleOne=$this->handleHelper($viewRoles, $course,$userRoles); 
                    $userView=$this->getViewWithParts($viewId, $roleOne);
                }  
                $parentParts = $this->viewsModule->findParentParts($course, $viewId, $viewType, $roleOne, $roleTwo);  
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