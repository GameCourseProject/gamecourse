<?php
namespace Modules\Views;

use Modules\Views\Expression\ValueNode;
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\DataRetrieverContinuation;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;
use SmartBoards\Settings;

class Views extends Module {
    private $viewHandler;
    
    public function getCourseId(){
        return $this->getParent()->getId();
    }
    
    public function setupResources() {
        parent::addResources('js/views.js');
        parent::addResources('js/views.service.js');
        parent::addResources('js/views.part.value.js');
        parent::addResources('Expression/SmartboardsExpression.js');
        parent::addResources('js/');
        parent::addResources('css/views.css');
    }

    public function initSettingsTabs() {
        $childTabs = array();
        $views = $this->viewHandler->getRegisteredViews();
        foreach($views as $viewId => $view)
            $childTabs[] = Settings::buildTabItem($view['name'], 'course.settings.views.view({view:\'' . $viewId . '\'})', true);
        Settings::addTab(Settings::buildTabItem('Views', 'course.settings.views', true, $childTabs));
    }

    private function breakTableRows(&$rows, &$savePart) {
        ViewEditHandler::breakRepeat($rows, $savePart, function(&$row) use(&$savePart) {
            foreach($row['values'] as &$cell) {
                ViewEditHandler::breakPart($cell['value'], $savePart);
            }
        });
    }

    function putTogetherRows(&$rows, &$getPart) {
        ViewEditHandler::putTogetherRepeat($rows, $getPart, function(&$row) use(&$getPart) {
            foreach($row['values'] as &$cell) {
                ViewEditHandler::putTogetherPart($cell['value'], $getPart);
            }
        });
    }

    private function parseTableRows(&$rows) {
        for($i = 0; $i < count($rows); ++$i) {
            $row = &$rows[$i];
            if (array_key_exists('style', $row))
                $this->viewHandler->parseSelf($row['style']);
            if (array_key_exists('class', $row))
                $this->viewHandler->parseSelf($row['class']);

            $this->viewHandler->parseData($row);

            foreach($row['values'] as &$cell)
                $this->viewHandler->parsePart($cell['value']);

            $this->viewHandler->parseRepeat($row);
            $this->viewHandler->parseIf($row);
        }
    }

    private function processTableRows(&$rows, $viewParams, $visitor) {
        $this->viewHandler->processRepeat($rows, $viewParams, $visitor, function(&$row, $viewParams, $visitor) {
            $this->viewHandler->processData($row, $viewParams, $visitor, function($viewParams, $visitor) use(&$row) {
                if (array_key_exists('style', $row))
                    $row['style'] = $row['style']->accept($visitor)->getValue();
                if (array_key_exists('class', $row))
                    $row['class'] = $row['class']->accept($visitor)->getValue();

                foreach($row['values'] as &$cell) {
                    $this->viewHandler->processPart($cell['value'], $viewParams, $visitor);
                }
            });
        });
    }

    public function init() {
        $this->viewHandler = new ViewHandler($this);

        $this->viewHandler->registerFunction('value', function($val) {
            return new ValueNode($val->getValue());
        });

        $this->viewHandler->registerFunction('urlify', function($val) {
            return new ValueNode(str_replace(' ', '', $val));
        });

        $this->viewHandler->registerFunction('time', function() {
            return new ValueNode(time());
        });

        $this->viewHandler->registerFunction('formatDate', function($val) {
            return new ValueNode(date('d-M-Y', strtotime($val)));
        });
        $this->viewHandler->registerFunction('timestamp', function($val) {
            return new ValueNode( strtotime($val) );
        });
        $this->viewHandler->registerFunction('if', function($cond, $val1, $val2) {
            return new ValueNode($cond ? $val1 :  $val2);
        });

        $this->viewHandler->registerFunction('size', function($val) {
            if (is_null($val))
                return new ValueNode(0);
            if (is_array($val))
                return new ValueNode(count($val));
            else
                return new ValueNode(strlen($val));
        });

        $this->viewHandler->registerFunction('abs', function($val) { return new ValueNode(abs($val)); });
        $this->viewHandler->registerFunction('min', function($val1, $val2) { return new ValueNode(min($val1, $val2)); });
        $this->viewHandler->registerFunction('max', function($val1, $val2) { return new ValueNode(max($val1, $val2)); });
        $this->viewHandler->registerFunction('int', function($val1) { return new ValueNode(intval($val1)); });

        $course = $this->getParent();
        $this->viewHandler->registerFunction('isModuleEnabled', function($module) use ($course) {
            return new ValueNode($course->getModule($module) != null);
        });

        $this->viewHandler->registerFunction('getModules', function() use ($course) {
            return DataRetrieverContinuation::buildForArray($course->getEnabledModules());
        });

        $this->viewHandler->registerPartType('value', null, null,
            function(&$value) {
                if (array_key_exists('link', $value))
                    $this->viewHandler->parseSelf($value['link']);

                if ($value['valueType'] == 'expression')
                    $this->viewHandler->parseSelf($value['info']);
            },
            function(&$value, $viewParams, $visitor) {
                if (array_key_exists('link', $value))
                    $value['link'] = $value['link']->accept($visitor)->getValue();

                if ($value['valueType'] == 'field') {
                    $context = array_key_exists('context', $value['info']) ? $value['info']['context'] : array();
                    $fieldValue = \SmartBoards\DataSchema::getValue($value['info']['field'], $context, $viewParams, false);
                    if (array_key_exists('format', $value['info']))
                        $fieldValue = str_replace('%v', $fieldValue, $value['info']['format']);
                    $value['valueType'] = 'text';
                    $value['info'] = $fieldValue;
                } else if ($value['valueType'] == 'expression') {
                    $value['valueType'] = 'text';
                    $value['info'] = $value['info']->accept($visitor)->getValue();
                }
            }
        );

        $this->viewHandler->registerPartType('image', null, null,
            function(&$image) {
                if (array_key_exists('link', $image))
                    $this->viewHandler->parseSelf($image['link']);

                if ($image['valueType'] == 'expression')
                    $this->viewHandler->parseSelf($image['info']);
            },
            function(&$image, $viewParams, $visitor) {
                if (array_key_exists('link', $image))
                    $image['link'] = $image['link']->accept($visitor)->getValue();

                if ($image['valueType'] == 'field') {
                    $context = array_key_exists('context', $image['info']) ? $image['info']['context'] : array();
                    $fieldValue = \SmartBoards\DataSchema::getValue($image['info']['field'], $context, $viewParams, false);
                    if (array_key_exists('format', $image['info']))
                        $fieldValue = str_replace('%v', $fieldValue, $image['info']['format']);
                    $image['valueType'] = 'text';
                    $value['info'] = $fieldValue;
                } else if ($image['valueType'] == 'expression') {
                    $image['valueType'] = 'text';
                    $image['info'] = $image['info']->accept($visitor)->getValue();
                }
            }
        );

        $this->viewHandler->registerPartType('table',
            function(&$table, &$savePart) {
                $this->breakTableRows($table['headerRows'], $savePart);
                $this->breakTableRows($table['rows'], $savePart);
            },
            function(&$table, &$getPart) {
                $this->putTogetherTableRows($table['headerRows'], $getPart);
                $this->putTogetherTableRows($table['rows'], $getPart);
            },
            function(&$table) {
                for($i = 0; $i < count($table['columns']); ++$i) {
                    $column = &$table['columns'][$i];
                    if (array_key_exists('style', $column))
                        $this->viewHandler->parseSelf($column['style']);
                    if (array_key_exists('class', $column))
                        $this->viewHandler->parseSelf($column['class']);
                }

                $this->parseTableRows($table['headerRows']);
                $this->parseTableRows($table['rows']);
            },
            function(&$table, $viewParams, $visitor) {
                for($i = 0; $i < count($table['columns']); ++$i) {
                    $column = &$table['columns'][$i];
                    if (array_key_exists('style', $column))
                        $column['style'] = $column['style']->accept($visitor)->getValue();
                    if (array_key_exists('class', $column))
                        $column['class'] = $column['class']->accept($visitor)->getValue();
                }
                $this->processTableRows($table['headerRows'], $viewParams, $visitor);
                $this->processTableRows($table['rows'], $viewParams, $visitor);
            }
        );

        $this->viewHandler->registerPartType('block', null, null,
            function(&$block) {
                if (array_key_exists('header', $block)) {
                    $block['header']['title']['type'] = 'value';
                    $block['header']['image']['type'] = 'image';
                    $this->viewHandler->parsePart($block['header']['title']);
                    $this->viewHandler->parsePart($block['header']['image']);
                }

                if (array_key_exists('children', $block)) {
                    foreach ($block['children'] as &$child)
                        $this->viewHandler->parsePart($child);
                }
            },
            function(&$block, $viewParams, $visitor) {
                if (array_key_exists('header', $block)) {
                    $this->viewHandler->processPart($block['header']['title'], $viewParams, $visitor);
                    $this->viewHandler->processPart($block['header']['image'], $viewParams, $visitor);
                }

                if (array_key_exists('children', $block)) {
                    $this->viewHandler->processRepeat($block['children'], $viewParams, $visitor, function(&$child, $viewParams, $visitor) {
                        $this->viewHandler->processPart($child, $viewParams, $visitor);
                    });
                }
            }
        );


        API::registerFunction('views', 'view', function() {
            API::requireValues('view');

            if (API::hasKey('course'))
                Course::getCourse((string)API::getValue('course'))->getLoggedUser()->refreshActivity();
            
            $this->viewHandler->handle(API::getValue('view'));
        });

        API::registerFunction('views', 'listViews', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course');

            API::response(array('views' => $this->viewHandler->getRegisteredViews(), 'templates' => array_column($this->getTemplates(),'id')));
        });

        API::registerFunction('views', 'createView', function() {
            API::requireCourseAdminPermission();
            API::requireValues('view', 'course');

            $views = $this->viewHandler->getRegisteredViews();
            $viewId = API::getValue('view');
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId);
            
            $courseId=API::getValue('course');
            $course = Course::getCourse($courseId);
            $viewSettings = $views[$viewId];

            $type = $viewSettings['type'];

            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');

                $finalParents = $this->findParents($course, $info['roleOne']);
                $parentViews = $this->findViews($viewId,$type, array_merge($finalParents, array($info['roleOne'])));
                
                if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    //$parentsTwo = array_merge($this->findParents($course, $info['roleTwo']), array($info['roleTwo']));
                    $finalViews = array();
                    foreach ($parentViews as $viewsRoleOne) {
                        $separatorPos = strpos( $viewsRoleOne['role'], '>');
                        $roleOne = substr( $viewsRoleOne['role'], 0, $separatorPos);
                        $roleTwo = substr( $viewsRoleOne['role'], $separatorPos+1, strlen($viewsRoleOne['role']));
                        if (($roleTwo == "role.Default") && ($roleOne==$roleTwo || $roleOne==$info["roleOne"])){
                            $finalViews[]=$viewsRoleOne;
                        }
                        /*foreach ($parentsTwo as $role) {
                            if($role== substr( $viewsRoleOne['role'], 0, strpos( $viewsRoleOne['role'], '>'))){
                                $finalViews[]=$viewsRoleOne;
                            }
                        }*/
                    }
                    $parentViews = $finalViews;
                }
                $sizeParents = count($parentViews);
                if ($sizeParents > 0) {
                    $newView = array(
                        'part' => $parentViews[$sizeParents - 1]['part'],
                        'partlist' => []
                    );
                } else {
                    $viewpid = ViewEditHandler::getRandomPid();
                    $newView = array(
                        'part' => $viewpid,
                        'partlist' => array(
                            $viewpid => array(
                                'type' => 'view',
                                'content' => array(),
                                'pid' => $viewpid
                            )
                        )
                    );
                }

                if ($type == ViewHandler::VT_ROLE_SINGLE)
                    Core::$systemDB->insert("view_role",
                            ["viewId"=>$viewId,"course"=>$courseId,"part"=>$newView['part'],"role"=>$info['roleOne']]);
                

                else if ($type == ViewHandler::VT_ROLE_INTERACTION)
                    Core::$systemDB->insert("view_role",
                            ["viewId"=>$viewId,"course"=>$courseId,"part"=>$newView['part'],"role"=>$info['roleOne'].'>'.$info['roleTwo']]);


                http_response_code(201);
                return;
            }
            API::error('Unexpected...');
        });

        API::registerFunction('views', 'deleteView', function() {
            API::requireCourseAdminPermission();
            API::requireValues('view', 'course');

            $views = $this->viewHandler->getRegisteredViews();
            $view = API::getValue('view');
            if (!array_key_exists($view, $views))
                API::error('Unknown view ' . $view);

            $viewSettings = $views[$view];
            $courseId = API::getValue('course');

            $type = $viewSettings['type'];
            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                
                API::requireValues('info');
                $info = API::getValue('info');

                if (!array_key_exists('roleOne', $info))
                    API::error('Missing roleOne in info');
                
                if ($type == ViewHandler::VT_ROLE_SINGLE ) {
                    Core::$systemDB->delete("view_role",["viewId"=>$view,"course"=>$courseId,"role"=>$info['roleOne']]);
                }else if ($type == ViewHandler::VT_ROLE_INTERACTION && !array_key_exists('roleTwo', $info)) {
                    Core::$systemDB->delete("view_role",["viewId"=>$view,"course"=>$courseId],["role"=>$info['roleOne'].'>%']);
                } else if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    Core::$systemDB->delete("view_role",["viewId"=>$view,"course"=>$courseId,"role"=>$info['roleOne'].'>'.$info['roleTwo']]);
                }

                http_response_code(200);
                return;
            }
            API::error('Unexpected...');
        });

        API::registerFunction('views', 'getInfo', function() {
            API::requireValues('view', 'course');

            $views = $this->viewHandler->getRegisteredViews();
            $viewId = API::getValue('view');
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId);

            $viewSettings = $views[$viewId];

            $course = Course::getCourse(API::getValue('course'));
            $response = array(
                'viewSettings' => $viewSettings,
            );

            $response['types'] = array(
                array('id'=> 1, 'name' => 'Single'),
                array('id'=> 2, 'name' => 'Role - Single'),
                array('id'=> 3, 'name' => 'Role - Interaction')
            );

            $type = $viewSettings['type'];
            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                $viewSpecializations = $this->viewHandler->getViewRoles($viewId);
                $result = array();
        
                $doubleRoles=[];//for views w role interaction
                foreach ($viewSpecializations as $role){
                    $id=$role['role'];
                    if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                        $roleTwo= substr($id, strpos($id, '>')+1, strlen($id));
                        $roleOne= substr($id, 0, strpos($id, '>'));
                        $doubleRoles[$roleOne][]=$roleTwo;
                    }
                    else
                        $result[] = array('id' => $id, 'name' => substr($id, strpos($id, '.') + 1));
                }
                
                if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    foreach($doubleRoles as $roleOne => $rolesTwo){
                        $viewedBy = [];
                        foreach($rolesTwo as $roleTwo ){
                            $viewedBy[] = array('id' => $roleTwo, 'name' => substr($roleTwo, strpos($roleTwo, '.') + 1));
                        }
                        $result[] = array('id' => $roleOne, 'name' => substr($roleOne, strpos($roleOne, '.') + 1),
                            'viewedBy'=>$viewedBy);
                        
                    }
                }

                $response['viewSpecializations'] = $result;
                $response['allIds'] = array();
                $roles = array_merge(array('Default'), $course->getRoles());
                $users = $course->getUsersIds();
                $response['allIds'][] = array('id' => 'special.Own', 'name' => 'Own (special)');
                foreach ($roles as $role)
                    $response['allIds'][] = array('id' => 'role.' . $role, 'name' => $role);
                foreach ($users as $user)
                    $response['allIds'][] = array('id' => 'user.' . $user, 'name' => $user);
            }
            API::response($response);
        });

        API::registerFunction('views', 'changeType', function() {
            API::requireCourseAdminPermission();
            // TODO: implement change.. for pages that can change type, currently, none
        });

        API::registerFunction('views', 'saveTemplate', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'name', 'part');//json_encode?
            $this->setTemplate(API::getValue('name'), serialize(API::getValue('part')), "views");//use this course?
        });

        API::registerFunction('views', 'deleteTemplate', function() {
            API::requireCourseAdminPermission();
            API::requireValues('name','course');
            Core::$systemDB->delete("view_template",["course"=>API::getValue('course'),"id"=>API::getValue('name')]);
        });

        API::registerFunction('views', 'getEdit', function() {
            
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');

            $courseId = API::getValue('course');
            $viewId = API::getValue('view');


            $views = $this->viewHandler->getRegisteredViews();
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);
            
            $viewSettings = $views[$viewId];
            $viewType = $viewSettings['type'];

            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('role', $info))
                    API::error('Missing role');

                $view = $this->viewHandler->getViewWithParts($viewId, $info['role']);
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info))
                    API::error('Missing roleOne and/or roleTwo in info');

                $view = $this->viewHandler->getViewWithParts($viewId, $info['roleOne'].'>'.$info['roleTwo']);
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['roleOne'], $info['roleTwo']);
  
            } else {
                $parentParts = array();
                $view = $this->viewHandler->getViewWithParts($viewId, "");           
            }
            
            $view = ViewEditHandler::putTogetherView($view, $parentParts);
            $fields = \SmartBoards\DataSchema::getFields(array('course' => $courseId));

            $templates= $this->getTemplates();
            API::response(array('view' => $view, 'fields' => $fields, 'templates' =>$templates ));
        });

        API::registerFunction('views', 'saveEdit', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');
            
            $courseId = API::getValue('course');
            $viewId = API::getValue('view');
            $viewContent = API::getValue('content');

            $views = $this->viewHandler->getRegisteredViews();
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);

            $viewSettings = $views[$viewId];
            $viewType = $viewSettings['type'];

            $info = array();
            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('role', $info))
                    API::error('Missing role');
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info))
                    API::error('Missing roleOne and/or roleTwo in info');
            }

            $testDone = false;
            $viewCopy = $viewContent;
            try {

                //replaces expressions with objects of Expression language
                $this->viewHandler->parseView($viewCopy);
                if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                    $viewerId = $this->getUserIdWithRole($course, $info['role']);

                    if ($viewerId != -1) {
                        $this->viewHandler->processView($viewCopy, array(
                            'course' => (string)$courseId,
                            'viewer' => (string)$viewerId,
                        ));
                        $testDone = true;
                    }
                } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                    $userId = $this->getUserIdWithRole($course, $info['roleOne']);
                    $viewerId = $this->getUserIdWithRole($course, $info['roleTwo']);

                    if ($viewerId != -1 && $userId != -1) {
                        $this->viewHandler->processView($viewCopy, array(
                            'course' => (string)$courseId,
                            'viewer' => (string)$viewerId,
                            'user' => (string)$userId
                        ));
                        $testDone = true;
                    }
                } else {
                    $this->viewHandler->processView($viewCopy, array(
                        'course' => $courseId,
                        'viewer' => (string)Core::getLoggedUser()->getId(),
                    ));
                    $testDone = true;
                }
            } catch (\Exception $e) {
                API::error('Error saving view: ' . $e->getMessage());
            }

            $parentParts = array();
            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['roleOne'], $info['roleTwo']);
            }

            $viewContent = ViewEditHandler::breakView($viewContent, $parentParts);
            $viewContent = $this->viewHandler->organizeViewData($viewContent);
            
            $viewSettings = $views[$viewId];
            //print_r($viewContent);//array ( part=>,partList=>)
            if ($viewSettings['type'] == ViewHandler::VT_ROLE_SINGLE) {
                $role=$info['role'];
            } else if ($viewSettings['type'] == ViewHandler::VT_ROLE_INTERACTION) {
                $role=$info['roleOne'].'>'.$info['roleTwo'];
            } else {
                $role="";
            }
            $viewRoleInfo=['course'=>$courseId,'viewId'=>$viewId,'role'=>$role];
            
            //this may be unecessary, unless part or replacements changed
            Core::$systemDB->update("view_role",
                        $viewContent['view_role'],
                        $viewRoleInfo);
            $currParts = array_column(Core::$systemDB->selectMultiple("view_part",'pid',$viewRoleInfo),'pid');
            
            foreach($viewContent['view_part'] as $part){
                if (empty(Core::$systemDB->select("view_part",'*',['pid'=>$part['pid'],'course'=>$courseId]))){
                    Core::$systemDB->insert('view_part',array_merge($part,$viewRoleInfo));
                }else{
                    Core::$systemDB->update('view_part',array_merge($part,$viewRoleInfo),['pid'=>$part['pid'],'course'=>$courseId]);
                }
                $key=array_search($part['pid'], $currParts);
                if ($key!==false){
                   unset($currParts[$key]);
                }
            }
            //delete remaining parts on db
            foreach($currParts as $part){
                Core::$systemDB->delete("view_part",['pid'=>$part,'course'=>$courseId]);
            }
                     
            if (!$testDone)
                API::response('Saved, but skipping test (no users in role to test or special role)');
        });

        API::registerFunction('views', 'previewEdit', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');

            $courseId = API::getValue('course');
            $viewId = API::getValue('view');
            $viewContent = API::getValue('content');

            $views = $this->viewHandler->getRegisteredViews();
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);

            $viewSettings = $views[$viewId];
            $viewType = $viewSettings['type'];

            $info = array();
            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('role', $info))
                    API::error('Missing role');
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info))
                    API::error('Missing roleOne and/or roleTwo in info');
            }

            $testDone = false;
            $viewCopy = $viewContent;
            try {
                $this->viewHandler->parseView($viewCopy);
                if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                    $viewerId = $this->getUserIdWithRole($course, $info['role']);

                    if ($viewerId != -1) {
                        $this->viewHandler->processView($viewCopy, array(
                            'course' => (string)$courseId,
                            'viewer' => (string)$viewerId,
                        ));
                        $testDone = true;
                    }
                } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                    $userId = $this->getUserIdWithRole($course, $info['roleOne']);
                    $viewerId = $this->getUserIdWithRole($course, $info['roleTwo']);

                    if ($viewerId != -1 && $userId != -1) {
                        $this->viewHandler->processView($viewCopy, array(
                            'course' => (string)$courseId,
                            'viewer' => (string)$viewerId,
                            'user' => (string)$userId
                        ));
                        $testDone = true;
                    }
                } else {
                    $this->viewHandler->processView($viewCopy, array(
                        'course' => $courseId,
                        'viewer' => (string)Core::getLoggedUser()->getId(),
                    ));
                    $testDone = true;
                }
            } catch (\Exception $e) {
                API::error('Error in preview: ' . $e->getMessage());
            }
            if (!$testDone)
                API::error('Previewing of Views for Roles with no users or Special Roles is not implemented.');

            API::response(array('view' => $viewCopy));
        });
    }

    function getUserIdWithRole($course, $role) {
        $uid = -1;
        if (strpos($role, 'role.') === 0) {
            $role = substr($role, 5);
            if ($role == 'Default')
                return $course->getUsersIds()[0];
            $users = $course->getUsersWithRole($role);
            if (count($users) != 0)
                $uid = $users[0]['id'];
        } else if (strpos($role, 'user.') === 0) {
            $uid = substr($role, 5);
        }
        return $uid;
    }

    function findParentParts($course, $viewId, $viewType, $roleOne, $roleTwo = null) {
        if ($roleOne == 'role.Default' && ($roleTwo == null || $roleTwo == 'role.Default'))
            return array();
        $parentParts = array();
        if ($viewType == ViewHandler::VT_ROLE_SINGLE || $viewType == ViewHandler::VT_ROLE_INTERACTION) {
            $finalParents = $this->findParents($course, $roleOne);
            if ($viewType == ViewHandler::VT_ROLE_SINGLE || $roleTwo == 'role.Default')
                $parentViews = $this->findViews($viewId,$viewType, $finalParents);
            else
                $parentViews = $this->findViews($viewId,$viewType, array_merge($finalParents, array($roleOne)));

            if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {   
                //$parentsTwo = $this->findParents($course, $roleTwo);
                $finalViews = [];
                foreach ($parentViews as $viewsRoleOne) {
                        $separatorPos = strpos( $viewsRoleOne['role'], '>');
                        $viewRoleOne = substr( $viewsRoleOne['role'], 0, $separatorPos);
                        $viewRoleTwo = substr( $viewsRoleOne['role'], $separatorPos+1, strlen($viewsRoleOne['role']));
                        if (($viewRoleTwo == "role.Default") && ($viewRoleOne==$viewRoleTwo || $viewRoleOne==$roleOne)){
                            $finalViews[]=$viewsRoleOne;
                    }
                    /*
                    foreach ($parentsTwo as $role) {
                        if($role== substr( $viewsRoleOne['role'], 0, strpos( $viewsRoleOne['role'], '>'))){
                                $finalViews[]=$viewsRoleOne;
                        }
                    }*/
                }
                $parentViews = $finalViews;
            }

            $parentParts = array();
            foreach ($parentViews as $viewDef) {
                $parentParts = array_merge($parentParts, $viewDef['partlist']);
                if (array_key_exists('replacements', $viewDef)) {
                    $replacements = $viewDef['replacements'];
                    foreach ($replacements as $part => $replacement) {
                        $parentParts[$part] = array('pid-point' => $replacement);
                    }
                }
            }
            return $parentParts;
        }
        return $parentParts;
    }

    private function findParents($course, $roleToFind) {
        $finalParents = array();
        $parents = array();
        $course->goThroughRoles(function($roleName, $hasChildren, $cont, &$parents) use ($roleToFind, &$finalParents) {
            if ('role.' . $roleName == $roleToFind) {
                $finalParents = $parents;
                return;
            }

            $parentCopy = $parents;
            $parentCopy[] = 'role.' . $roleName;
            $cont($parentCopy);
        }, $parents);
        return array_merge(array('role.Default'), $finalParents);
    }

    private function findViews($viewId,$type, $viewsToFind, $roleOne = null) {
        //$views = $this->getViewHandler()->getViews($viewId);
        //if ($roleOne != null) {//this argument always null
        //$views = $this->getViewHandler()->getViewsRoles($view,$roleOne);
           // $views = $views->getWrapped($roleOne);
        //}
        //$views = $views->getValue();
        $views = $this->getViewHandler()->getViewRoles($viewId);
        $viewRoles = array_column($views,'role');
        $viewsFound = array();
        if ($type== ViewHandler::VT_ROLE_INTERACTION){
            $rolesFound=[];
            foreach ($viewRoles as $dualRole) {
                $role = substr($dualRole,0, strpos($dualRole, '>'));
                if (in_array($role, $viewsToFind) && !in_array($role, $rolesFound)){
                    $viewsFound[]=$this->getViewHandler()->getViewWithParts($viewId, $dualRole);
                    $rolesFound[]=$dualRole;
                }
            }
        }
        else{
            foreach ($viewsToFind as $viewToFind) {
                if (in_array($viewToFind, $viewRoles))
                    $viewsFound[]=$this->getViewHandler()->getViewWithParts($viewId, $viewToFind);
                //if (array_key_exists($viewToFind, $views))
                    //$viewsFound[] = $views[$viewToFind];
            }
        }
        return $viewsFound;
    }

    public function &getViewHandler() {
        return $this->viewHandler;
    }
    
    public function getTemplates(){
        $temps = Core::$systemDB->selectMultiple('view_template','*',['course'=>$this->getCourseId()]);
        foreach ($temps as &$temp){
            $temp['content'] = unserialize($temp['content']);
        }
        return $temps;
    }
    public function getTemplate($id) {
        $temp = Core::$systemDB->select('view_template','*',['id'=>$id,'course'=>$this->getCourseId()]);
        if (!empty($temp)) {
            $temp['content'] = unserialize($temp['content']);
        }
        return $temp;
    }
    
    //receives the template id, its serialized contents and module id, and puts it in the database
    public function setTemplate($id, $template, $moduleId) {
        Core::$systemDB->insert('view_template',['id'=>$id,'content'=>$template,
                                'course'=>$this->getCourseId(),'module'=>$moduleId]);
    }
}

ModuleLoader::registerModule(array(
    'id' => 'views',
    'name' => 'Views',
    'version' => '0.1',
    'factory' => function() {
        return new Views();
    }
));
