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
            return new ValueNode(date('d-M-Y', $val));
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

            API::response(array('views' => $this->viewHandler->getRegisteredViews(), 'templates' => array_keys($this->getData()->get('templates'))));
        });

        API::registerFunction('views', 'createView', function() {
            API::requireCourseAdminPermission();
            API::requireValues('view', 'course');

            $views = $this->viewHandler->getRegisteredViews();
            $view = API::getValue('view');
            if (!array_key_exists($view, $views))
                API::error('Unknown view ' . $view);

            $course = Course::getCourse(API::getValue('course'));
            $viewSettings = $views[$view];

            $type = $viewSettings['type'];

            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                $viewSpecializations = $this->viewHandler->getViews()->getWrapped($view)->getWrapped('view');

                $roleToFind = $info['roleOne'];
                $finalParents = $this->findParents($course, $roleToFind);
                $parentViews = $this->findViews($view, array_merge($finalParents, array($roleToFind)));

                if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    $parentsTwo = array_merge($this->findParents($course, $info['roleTwo']), array($info['roleTwo']));
                    $finalViews = array();
                    foreach ($parentViews as $viewsRoleOne) {
                        foreach ($parentsTwo as $role) {
                            if (array_key_exists($role, $viewsRoleOne)) {
                                $finalViews[] = $viewsRoleOne[$role];
                            }
                        }
                    }
                    $parentViews = $finalViews;
                }

                $sizeParents = count($parentViews);
                if ($sizeParents > 0) {
                    $newView = array(
                        'part' => $parentViews[$sizeParents - 1]['part'],
                        'partlist' => array(
                        )
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
                    $viewSpecializations->set($info['roleOne'], $newView);
                else if ($type == ViewHandler::VT_ROLE_INTERACTION)
                    $viewSpecializations->getWrapped($info['roleOne'])->set($info['roleTwo'], $newView);


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

            $type = $viewSettings['type'];
            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                $viewSpecializations = $this->viewHandler->getViews()->getWrapped($view)->getWrapped('view');

                API::requireValues('info');
                $info = API::getValue('info');

                if (!array_key_exists('roleOne', $info))
                    API::error('Missing roleOne in info');

                if ($type == ViewHandler::VT_ROLE_SINGLE || ($type == ViewHandler::VT_ROLE_INTERACTION && !array_key_exists('roleTwo', $info))) {
                    $views = $viewSpecializations->getValue();
                    unset($views[$info['roleOne']]);
                    $viewSpecializations->setValue($views);
                } else if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    $viewSpecializations = $viewSpecializations->getWrapped($info['roleOne']);
                    $views = $viewSpecializations->getValue();
                    unset($views[$info['roleTwo']]);
                    $viewSpecializations->setValue($views);
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
                //$viewSpecializations = $this->viewHandler->getViews($viewId);
                $result = array();
        
                $doubleRoles=[];//for views w role interaction
                //foreach (array_keys($viewSpecializations) as $id)
                foreach ($viewSpecializations as $role){
                    $id=$role['role'];
                    if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                        $roleTwo= substr($id, strpos($id, '>'), strlen($id));
                        $roleOne= substr($id, 0, strpos($id, '>'));
                        $doubleRoles['$roleOne'][]=$roleTwo;
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
                    /*foreach($result as &$spec) {
                        $secondKeys = array_keys($viewSpecializations[$spec['id']]);
                        $viewedBy = array();
                        foreach ($secondKeys as $id)
                            $viewedBy[] = array('id' => $id, 'name' => substr($id, strpos($id, '.') + 1));
                        $spec['viewedBy'] = $viewedBy;
                    }*/
                }

                $response['viewSpecializations'] = $result;
                $response['allIds'] = array();
                $roles = array_merge(array('Default'), array_column($course->getRoles(),'name'));
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
            API::requireValues('course', 'name', 'part');
            $this->getData()->getWrapped('templates')->set(API::getValue('name'), API::getValue('part'));
        });

        API::registerFunction('views', 'deleteTemplate', function() {
            API::requireCourseAdminPermission();
            API::requireValues('name');
            $this->getData()->getWrapped('templates')->delete(API::getValue('name'));
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
            $view = $this->viewHandler->getViews()->getWrapped($viewId)->getWrapped('view');

            $viewSettings = $views[$viewId];
            $viewType = $viewSettings['type'];

            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('role', $info))
                    API::error('Missing role');

                $view = $view->get($info['role']);
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info))
                    API::error('Missing roleOne and/or roleTwo in info');

                $view = $view->getWrapped($info['roleOne'])->get($info['roleTwo']);
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['roleOne'], $info['roleTwo']);
            } else {
                $parentParts = array();
                $view = $view->getValue();
            }

            //print_r($view);
            $view = ViewEditHandler::putTogetherView($view, $parentParts);//print_r($view);
            $fields = \SmartBoards\DataSchema::getFields(array('course' => $courseId));
            API::response(array('view' => $view, 'fields' => $fields, 'templates' => $this->getData()->get('templates', array())));
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
            $view = $this->viewHandler->getViews()->getWrapped($viewId)->getWrapped('view');

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
                API::error('Error saving view: ' . $e->getMessage());
            }

            $parentParts = array();
            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                $parentParts = $this->findParentParts($course, $viewId, $viewType, $info['roleOne'], $info['roleTwo']);
            }

            $viewContent = ViewEditHandler::breakView($viewContent, $parentParts);

            $viewSettings = $views[$viewId];
            if ($viewSettings['type'] == ViewHandler::VT_ROLE_SINGLE) {
                $view->set($info['role'], $viewContent);
            } else if ($viewSettings['type'] == ViewHandler::VT_ROLE_INTERACTION) {
                $view->getWrapped($info['roleOne'])->set($info['roleTwo'], $viewContent);
            } else {
                $view->setValue($viewContent);
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
            $view = $this->viewHandler->getViews()->getWrapped($viewId)->getWrapped('view');

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
            $users = array_keys($course->getUsersWithRole($role)->getValue());
            if (count($users) != 0)
                $uid = $users[0];
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
                $parentViews = $this->findViews($viewId, $finalParents);
            else
                $parentViews = $this->findViews($viewId, array_merge($finalParents, array($roleOne)));

            if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                $parentsTwo = $this->findParents($course, $roleTwo);
                $finalViews = array();
                foreach ($parentViews as $viewsRoleOne) {
                    foreach ($parentsTwo as $role) {
                        if (array_key_exists($role, $viewsRoleOne)) {
                            $finalViews[] = $viewsRoleOne[$role];
                        }
                    }
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
        $course->goThroughRoles(function($role, $hasChildren, $cont, &$parents) use ($roleToFind, &$finalParents) {
            if ('role.' . $role == $roleToFind) {
                $finalParents = $parents;
                return;
            }

            $parentCopy = $parents;
            $parentCopy[] = 'role.' . $role;
            $cont($parentCopy);
        }, $parents);
        return array_merge(array('role.Default'), $finalParents);
    }

    private function findViews($view, $viewsToFind, $roleOne = null) {
        $views = $this->getViewHandler()->getViews($view);
        if ($roleOne != null)
            $views = $views->getWrapped($roleOne);

        $views = $views->getValue();

        $viewsFound = array();
        foreach ($viewsToFind as $viewToFind) {
            if (array_key_exists($viewToFind, $views))
                $viewsFound[] = $views[$viewToFind];
        }
        return $viewsFound;
    }

    public function &getViewHandler() {
        return $this->viewHandler;
    }

    public function getTemplate($id) {
         return Core::$sistemDB->select('view_template','*',['id'=>$id])[0];
   //     return $this->getData()->getWrapped('templates')->get($id);
    }

    public function setTemplate($id, $template) {
        //todo decide between unserialize and json decode for when using this data.
        // remove the unserialie from the callers (simple send the file contents)
        Core::$sistemDB->insert('view_template',['id'=>$id,'content'=>json_encode($template)]);
   //     return $this->getData()->getWrapped('templates')->set($id, $template);
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
