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
    private $courseId;
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
        $this->courseId=API::getValue('course');
    }

    public function getRegisteredViews() {
        return $this->registeredViews;
    }
    
    public function getViewWithParts($viewId,$role){//return everything organized like the previous db sistem
        $viewRole=$this->getViewRoles($viewId,$role);
        $viewParts=Core::$sistemDB->selectMultiple("view_part", '*', 
                ['viewId' => $viewId, 'course' => $this->courseId, 'role'=>$role]);
       
        $viewRole['replacements']=json_decode($viewRole['replacements']);
        
        $viewRole['partlist']=[];
        foreach ($viewParts as $part){
            $part= array_merge($part,json_decode($part['partContents'],true));
            unset($part['partContents']);
            $viewRole['partlist'][$part['pid']]=$part;
        }  
        return $viewRole;
    }
    
    public function getViewRoles($viewId,$role=null){
        if ($role == null) {
            return Core::$sistemDB->selectMultiple("view_role", '*', ['viewId' => $viewId, 'course' => $this->courseId]);
        } else {
            return Core::$sistemDB->select("view_role", '*', 
                    ['viewId' => $viewId, 'course' => $this->courseId,'role'=>$role]);
        }
    }
    
    public function getViews($viewId = null) {//supposed to return multiple views if there are specializations
        if ($viewId==null)
            return Core::$sistemDB->selectMultiple("view",'*',['course'=>$this->courseId]);
        else
            return Core::$sistemDB->select("view",'*',['viewId'=>$viewId,'course'=>$this->courseId])[0];
  
        /*
        $views = $this->viewsModule->getData()->getWrapped('views');
        if ($viewId != null)
            return $views->getWrapped($viewId)->getWrapped('view');
        else
            return $views;
         */
    }

    public function registerView($module, $viewId, $viewName, $viewSettings = array()) {
        if (array_key_exists($viewId, $this->registeredViews))
            new \Exception('View' . $viewId . ' was previously defined by ' . $this->registeredViews[$viewId]['module']);

        $viewSettings = array_replace_recursive(static::$defaultViewSettings, $viewSettings);//type
        $viewSettings['module'] = $module->getId();
        $viewSettings['name'] = $viewName;
        $this->registeredViews[$viewId] = $viewSettings;
        //$views = $this->getViews();
        //$view = $views->get($viewId);
        $view = $this->getViews($viewId);
        if ($view === null) {//TODO talvez verizicar se e' empty em vez de null
            $viewpid = ViewEditHandler::getRandomPid();
            $newView=array_merge($viewSettings,['course'=>$this->courseId,'viewId'=>$viewId]);
            $role="";
            if ($viewSettings['type'] == self::VT_ROLE_SINGLE)
                $role='role.Default';
            else if ($viewSettings['type'] == self::VT_ROLE_INTERACTION)
                $role='role.Default>role.Default';
            
            $viewRole=['course'=>$this->courseId,'viewId'=>$viewId,
                'part'=>$viewpid,
                'role'=>$role,
                'replacements'=>json_encode([]) ];
            
            $part = ['viewId' => $viewId,'course'=>$this->courseId,'role' =>$role,
                    'type' => 'view',
                    'partContents' => json_encode(['content'=>array()]),
                    'pid' => $viewpid
            ];
            Core::$sistemDB->insert('view',$newView);
            Core::$sistemDB->insert('view_role',$viewRole);
            Core::$sistemDB->insert('view_part',$part);
            
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

    //ToDo
    public function processData(&$part, $viewParams, $visitor, $func = null) {
        $actualVisitor = $visitor;
        $params = $viewParams;
        if (array_key_exists('data', $part)) {
            foreach ($part['data'] as $k => &$v) {
                if ($v['context'] == 'js')
                    $v['value'] = $v['value']->accept($visitor)->getValue();
                else if ($v['context'] == 'variable') {
                    $this->getContinuationOrValue($v['value'], $visitor, function($continuation) use ($k, &$params) {
                        $params[$k] = $continuation;
                    }, function($value) use ($k, &$params) {
                        $params[$k] = $value;
                    });
                } else {
                    throw new \RuntimeException('Unknown data context: ' . $v['context']);
                }
            }

            if ($params != $viewParams)
                $actualVisitor = new EvaluateVisitor($params, $this);
        }

        if ($func != null && is_callable($func))
            $func($params, $actualVisitor);
    }

    private function getContinuationOrValue(&$node, &$visitor, $cont, $val) {
        if (is_a($node, 'Modules\Views\Expression\DatabasePath'))
            $cont($node->accept($visitor, null, true));
        else if (is_a($node, 'Modules\Views\Expression\DatabasePathFromParameter'))
            $cont($node->accept($visitor, true));
        else
            $val($node->accept($visitor)->getValue());
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
                $this->getContinuationOrValue($child['repeat']['for'], $visitor, function($continuation) use(&$repeatParams, &$keys, $repeatKey) {
                    $keys = $continuation->getKeys();//array_keys($continuation->getValue());
                    foreach ($keys as $k)
                        $repeatParams[$k] = array($repeatKey => $continuation->followKey($k), $repeatKey . 'key' => $k);
                }, function($value) use(&$repeatParams, &$keys, $repeatKey) {
                    if (is_null($value) || !is_array($value))
                        throw new \Exception('Repeat must be an Array or a Continuation.');
                    $keys = array_keys($value);
                    foreach ($keys as $k)
                        $repeatParams[$k] = array($repeatKey => $value[$k], $repeatKey . 'key' => $k);
                });

                if (array_key_exists('filter', $child['repeat'])) {
                    $filter = $child['repeat']['filter'];
                    $keys = array_filter($keys, function($arrKey) use($filter, $viewParams, $repeatParams) {
                        $params = array_merge($viewParams, $repeatParams[$arrKey]);
                        return $filter->accept(new EvaluateVisitor($params, $this))->getValue();
                    });
                }

                if (array_key_exists('sort', $child['repeat'])) {
                    $sort = $child['repeat']['sort'];
                    $valueExp = $sort['value'];
                    $values = array();
                    foreach ($keys as $key) {
                        $values[$key] = $valueExp->accept(new EvaluateVisitor(array_merge($viewParams, $repeatParams[$key]), $this))->getValue();
                    }

                    if ($sort['order'] == 'ASC')
                        usort($keys, function($a, $b) use($values) {
                            return $values[$a] - $values[$b];
                        });
                    else
                        usort($keys, function($a, $b) use($values) {
                            return $values[$b] - $values[$a];
                        });
                }

                unset($child['repeat']);
                $p = 0;
                foreach ($keys as $k => $arrKey) {
                    $dupChild = $child;
                    $params = array_merge($viewParams, $repeatParams[$arrKey], array($repeatKey . 'pos' => $p));
                    $visitor = new EvaluateVisitor($params, $this);

                    if ($this->processIf($dupChild, $visitor)) {
                        $func($dupChild, $params, $visitor);
                        $containerArr[] = $dupChild;
                    }

                    ++$p;
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
        foreach ($content as &$part) {
            $this->parsePart($part);
        }
    }
    public function hasRole($view,$role){
        
    }
    public function handle($viewId) {
        if (!array_key_exists($viewId, $this->registeredViews)) {
            API::error('Unknown view: ' . $view, 404);
        }

        $viewParams = array();
        if (API::hasKey('course') && (is_int(API::getValue('course')) || ctype_digit(API::getValue('course')))) {
            $course = Course::getCourse((string)API::getValue('course'));
            //$views = $this->getViews()->getWrapped($viewId)->get('view');
            $view = $this->getViews($viewId);
            $viewType = $this->registeredViews[$viewId]['type'];
//todo
            echo " Handle stop point ";
            if ($viewType == ViewHandler::VT_ROLE_SINGLE || $viewType == ViewHandler::VT_ROLE_INTERACTION) {
                if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                    API::requireValues('user');

                    $roleOne = null;
                    $userSpecificView = 'user.' . (string)API::getValue('user');
                    if (array_key_exists($userSpecificView, $views)) {
                        $views = $views[$userSpecificView];
                        $roleOne = $userSpecificView;
                    } else {
                        $userView = null;
                        if (array_key_exists('role.Default', $views))
                            $userView = $views['role.Default'];

                        $userRoles = $course->getUser((string)API::getValue('user'))->getRoles();
                        $course->goThroughRoles(function ($roleName, $hasChildren, $continue) use ($userRoles, $views, &$userView, &$roleOne) {
                            if (array_key_exists('role.' . $roleName, $views) && in_array($roleName, $userRoles)) {
                                $userView = $views['role.' . $roleName];
                                $roleOne = 'role.' . $roleName;
                            }

                            if ($hasChildren)
                                $continue();
                        });
                        $views = $userView;
                    }
                }

                $roleFound = null;
                if ($viewType == ViewHandler::VT_ROLE_INTERACTION && array_key_exists('special.Own', $views) && (string)API::getValue('user') == (string)Core::getLoggedUser()->getId()) {
                    $userView = $views['special.Own'];
                    $roleFound = 'special.Own';
                } else {
                    $userSpecificView = 'user.' . (string)Core::getLoggedUser()->getId();
                    if (array_key_exists($userSpecificView, $views)) {
                        $userView = $views[$userSpecificView];
                        $roleFound = $userSpecificView;
                    } else {
                        $userView = null;
                        if (array_key_exists('role.Default', $views))
                            $userView = $views['role.Default'];

                        $userRoles = $course->getLoggedUser()->getRoles();
                        $course->goThroughRoles(function ($roleName, $hasChildren, $continue) use ($userRoles, $views, &$userView, &$roleFound) {
                            if (array_key_exists('role.' . $roleName, $views) && in_array($roleName, $userRoles)) {
                                $userView = $views['role.' . $roleName];
                                $roleFound = 'role.' . $roleName;
                            }

                            if ($hasChildren)
                                $continue();
                        });
                    }
                }

                if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                    $roleTwo = $roleFound;
                } else {
                    $roleOne = $roleFound;
                    $roleTwo = null;
                }

                $parentParts = $this->viewsModule->findParentParts($course, $viewId, $viewType, $roleOne, $roleTwo);

                //echo '<pre>', print_r(round((microtime(true) - $t) * 1000, 1), true), 'ms</pre>';
                //$t = microtime(true);
                $userView = ViewEditHandler::putTogetherView($userView, $parentParts);
            } else {
                $userView = ViewEditHandler::putTogetherView($views, array());
            }

            $viewParams = array(
                'course' => (string)API::getValue('course'),
                'viewer' => (string)Core::getLoggedUser()->getId()
            );

            if (API::hasKey('user'))
                $viewParams['user'] = (string)API::getValue('user');
            
            $userView['content'] = json_decode($userView['content'],true);
            $this->parseView($userView);
            $this->processView($userView, $viewParams);
            $userView['content'] = json_encode($userView['content']);

            $viewData = $userView;
        } else {
            // general views (plugins not bound to courses)
            API::error('Unsupported!');
            //$viewData = Core::getViews()->get($view)['view'];
        }
        API::response(array(
            'fields' => DataSchema::getFields($viewParams),
            'view' => $viewData
        ));  
    }
}