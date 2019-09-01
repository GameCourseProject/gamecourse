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
        parent::addResources('js/views.part.text.js');
        parent::addResources('js/views.part.value.js');
        parent::addResources('Expression/SmartboardsExpression.js');
        parent::addResources('js/');
        parent::addResources('css/views.css');
    }

    public function initSettingsTabs() {
        $childTabs = array();
        $views = $this->viewHandler->getRegisteredPages();
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

            $this->viewHandler->parseVariables($row);

            foreach($row['values'] as &$cell)
                $this->viewHandler->parsePart($cell['value']);

            $this->viewHandler->parseLoop($row);
            $this->viewHandler->parseIf($row);
        }
    }

    private function processTableRows(&$rows, $viewParams, $visitor) {
        $this->viewHandler->processLoop($rows, $viewParams, $visitor, function(&$row, $viewParams, $visitor) {
            $this->viewHandler->processVariables($row, $viewParams, $visitor, function($viewParams, $visitor) use(&$row) {
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
    //auxiliar functions for the expression language functions
    public function getModuleNameOfAwardOrParticipation($object,$award=true){
        if (array_key_exists("name",$object["value"]))
            return $object["value"]["name"];
        $type=$object["value"]["type"];
        if ($type=="badge"){
            return Core::$systemDB->select($type,["id"=>$object["value"]["moduleInstance"]],"name");
        }
        if  ($type=="skill"){
            if ($award)
                return $object["value"]["description"];
            else
                return Core::$systemDB->select($type,["id"=>$object["value"]["moduleInstance"]],"name");
        }            
        return null;
    }
    //gets timestamps and converts it to DD/MM/YYYY
    public function getDate($object){
        $date = implode("/",array_reverse(explode("-",explode(" ",$object["value"]["date"])[0])));
        return new ValueNode($date);
    }
    //get award or participations from DB
    public function getAwardOrParticipationAux($courseId,$user,$type,$moduleInstance,$initialDate,$finalDate,$where=[],$object="award"){
        
        $awardParticipation = $this->getAwardOrParticipation($courseId,$user,$type,$moduleInstance,$initialDate,$finalDate,$where=[],$object);
        return $this->createNode($awardParticipation,"collection");
    }
    public function init() {
        $this->viewHandler = new ViewHandler($this);

        //functions of views' expression language
        $this->viewHandler->registerFunction('system','value', function($val) {
            return new ValueNode($val->getValue());
        });

        $this->viewHandler->registerFunction('system','urlify', function($val) {
            return new ValueNode(str_replace(' ', '', $val));
        });

        $this->viewHandler->registerFunction('system','time', function() {
            return new ValueNode(time());
        });

        $this->viewHandler->registerFunction('system','formatDate', function($val) {
            return new ValueNode(date('d-M-Y', strtotime($val)));
        });
        $this->viewHandler->registerFunction('system','timestamp', function($val) {
            return new ValueNode( strtotime($val) );
        });
        $this->viewHandler->registerFunction('system','if', function($cond, $val1, $val2) {
            return new ValueNode($cond ? $val1 :  $val2);
        });

        $this->viewHandler->registerFunction('system','size', function($val) {
            if (is_null($val))
                return new ValueNode(0);
            if (is_array($val))
                return new ValueNode(count($val));
            else
                return new ValueNode(strlen($val));
        });

        $this->viewHandler->registerFunction('system','abs', function($val) { return new ValueNode(abs($val)); });
        $this->viewHandler->registerFunction('system','min', function($val1, $val2) { return new ValueNode(min($val1, $val2)); });
        $this->viewHandler->registerFunction('system','max', function($val1, $val2) { return new ValueNode(max($val1, $val2)); });
        $this->viewHandler->registerFunction('system','int', function($val1) { return new ValueNode(intval($val1)); });

        $course = $this->getParent();
        $courseId = $course->getId();
        $this->viewHandler->registerFunction('system','isModuleEnabled', function($module) use ($course) {
            return new ValueNode($course->getModule($module) != null);
        });
        $this->viewHandler->registerFunction('system','getModules', function() use ($course) {
            return DataRetrieverContinuation::buildForArray($course->getEnabledModules());
        });
        //functions of users library
        $this->viewHandler->registerFunction('users','getAllUsers',function($role=null,$courseId=null) use ($course){
            //the courseId argument won't be used (for now) since we already know the course of the view
            if ($courseId!==null){
                $course = new Course($courseId);
            }
            if ($role==null)
                return $this->createNode($course->getUsers(),"collection");
            else
                return $this->createNode($course->getUsersWithRole($role),"collection");
        });
        $this->viewHandler->registerFunction('users','getUser',function($id) use ($course){
            return $this->createNode($course->getUser($id)->getAllData());
        });
        $this->viewHandler->registerFunction('users','campus',function($user){
            return new ValueNode($user["value"]["campus"]);
        });
        $this->viewHandler->registerFunction('users','email',function($user){
            return new ValueNode($user["value"]["email"]);
        });
        $this->viewHandler->registerFunction('users','id',function($user){
            return new ValueNode($user["value"]["id"]);
        });
        $this->viewHandler->registerFunction('users','isAdmin',function($user){
            return new ValueNode($user["value"]["isAdmin"]);
        });
        $this->viewHandler->registerFunction('users','lastActivity',function($user){
            return new ValueNode($user["value"]["lastActivity"]);
        });
        $this->viewHandler->registerFunction('users','name',function($user){
            return new ValueNode($user["value"]["name"]);
        });
        $this->viewHandler->registerFunction('users','roles',function($user)use ($course){
            return $this->createNode((new \SmartBoards\CourseUser($user["value"]["id"], $course))->getRoles(),
                                    "collection");
        });
        $this->viewHandler->registerFunction('users','username',function($user){
            return new ValueNode($user["value"]["username"]);
        });
        $this->viewHandler->registerFunction('users','picture',function($user){
            return new ValueNode("photos/".$user["value"]["username"].".png");
        });
        
        //functions of awards library
        $this->viewHandler->registerFunction('awards','getAllAwards', 
        function($user=null,$type=null,$moduleInstance=null,$initialDate=null,$finalDate=null) use ($courseId){
            return $this->getAwardOrParticipationAux($courseId,$user,$type,$moduleInstance,$initialDate,$finalDate);
        });
        
        $this->viewHandler->registerFunction('awards','renderPicture',function($award,$item){
            if ($item=="user"){
                $username = Core::$systemDB->select("game_course_user",["id"=>$award["value"]["user"]],"username");
                return new ValueNode("photos/".$username.".png");
            }
            else{//$item=="type
                switch ($award["value"]['type']) {
                    case 'grade':
                        return new ValueNode('<img src="images/quiz.svg">');
                    case 'badge':
                        $name = $this->getModuleNameOfAwardOrParticipation($award);
                        $level = substr($award["value"]["description"],-2,1);//assuming that level are always single digit
                        $imgName = str_replace(' ', '', $name . '-' . $level);
                        return new ValueNode('<img src="badges/' . $imgName . '.png">');
                        break;
                    case 'skill':
                        $name = $this->getModuleNameOfAwardOrParticipation($award);
                        $color = '#fff';
                        $skillColor = Core::$systemDB->select("skill",["id"=>$award['value']["moduleInstance"]],"color");
                        if($skillColor)
                            $color=$skillColor;
                        //needs width and height , should have them if it has latest-awards class in a profile
                        return new ValueNode('<div class="skill" style="background-color: ' . $color . '">');
                    case 'bonus':
                        return new ValueNode('<img src="images/awards.svg">');
                    default:
                        return new ValueNode('<img src="images/quiz.svg">');
                }
            }
            return new ValueNode($award["value"]["description"]);
        });
        $this->viewHandler->registerFunction('awards','description',function($award){
            return new ValueNode($award["value"]["description"]);
        });
        $this->viewHandler->registerFunction('awards','moduleInstance',function($award){
            return new ValueNode($this->getModuleNameOfAwardOrParticipation($award));
        });
        $this->viewHandler->registerFunction('awards','reward',function($award){
            return new ValueNode($award["value"]["reward"]);
        });
        $this->viewHandler->registerFunction('awards','type',function($award){
            return new ValueNode($award["value"]["type"]);
        });
        $this->viewHandler->registerFunction('awards','date',function($award){
            return $this->getDate($award);
        });
        $this->viewHandler->registerFunction('awards','user',function($award){
            return new ValueNode($award["value"]["user"]);
        });
        
        //functions of the participation library
        $this->viewHandler->registerFunction('participations','getAllParticipations', 
        function($user=null,$type=null,$moduleInstance=null,$rating=null,$evaluator=null,$initialDate=null,$finalDate=null) use ($courseId){
            $where=[];
            if ($rating !== null) {
                $where["rating"]=$rating;
            }
            if ($evaluator !== null) {
                $where["evaluator"]=$evaluator;
            }
            return $this->getAwardOrParticipationAux($courseId,$user,$type,$moduleInstance,$initialDate,$finalDate,$where,"participation");
        });
        
        $this->viewHandler->registerFunction('participations','date',function($participation){
            return $this->getDate($participation);
        });
        $this->viewHandler->registerFunction('participations','description',function($participation){
            return new ValueNode($participation["value"]["description"]);
        });
        $this->viewHandler->registerFunction('participations','evaluator',function($participation){
            return new ValueNode($participation["value"]["evaluator"]);
        });
        $this->viewHandler->registerFunction('participations','moduleInstance',function($participation){
            return new ValueNode($this->getModuleNameOfAwardOrParticipation($participation,false));
        });
        $this->viewHandler->registerFunction('participations','post',function($participation){
            return new ValueNode($participation["value"]["post"]);
        });
        $this->viewHandler->registerFunction('participations','rating',function($participation){
            return new ValueNode($participation["value"]["post"]);
        });
        $this->viewHandler->registerFunction('participations','type',function($participation){
            return new ValueNode($participation["value"]["type"]);
        });
        $this->viewHandler->registerFunction('participations','user',function($participation){
            return new ValueNode($participation["value"]["user"]);
        });
        
        //parts
        $this->viewHandler->registerPartType('text', null, null,
            function(&$value) {
                if (array_key_exists('link', $value))
                    $this->viewHandler->parseSelf($value['link']);

                $this->viewHandler->parseSelf($value['parameters']["value"]);
            },
            function(&$value, $viewParams, $visitor) {
                if (array_key_exists('link', $value))
                    $value['link'] = $value['link']->accept($visitor)->getValue();
                
                $value['valueType'] = 'text';
                $value['parameters']["value"] = $value['parameters']["value"]->accept($visitor)->getValue();
            }
        );
        
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

                $this->viewHandler->parseSelf($image['parameters']["value"]);
            },
            function(&$image, $viewParams, $visitor) {
                if (array_key_exists('link', $image))
                    $image['link'] = $image['link']->accept($visitor)->getValue();

                $image['edit'] = false;
                $image['parameters']["value"] = $image['parameters']["value"]->accept($visitor)->getValue();
                    // $image['info'] = $image['parameters']["value"]->accept($visitor)->getValue();
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
                //print_r($table);
                $this->parseTableRows($table['headerRows']);
                $this->parseTableRows($table['rows']);
            },
            function(&$table, $viewParams, $visitor) {
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
                    $this->viewHandler->processLoop($block['children'], $viewParams, $visitor, function(&$child, $viewParams, $visitor) {
                        $this->viewHandler->processPart($child, $viewParams, $visitor);
                    });
                }
            }
        );


        API::registerFunction('views', 'view', function() {
            API::requireValues('view');

            if (API::hasKey('course'))
                Course::getCourse((string)API::getValue('course'))->getLoggedUser()->refreshActivity();
            
            $viewId = API::getValue('view');
            //If this request can come from  js file that only knows the name of the view
            if (!is_int($viewId)){
                foreach ($this->viewHandler->getRegisteredPages() as $id => $viewData){
                    if ($viewData["name"]==$viewId){
                        $viewId=$id;
                        break;
                    }
                }
            }
            $this->viewHandler->handle($viewId);
        });

        API::registerFunction('views', 'listViews', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course');
            $templates=$this->getTemplates(true);
            API::response(array('views' => $this->viewHandler->getRegisteredPages(), 
                'templates' => $templates[0], "globals"=>$templates[1]));
        });
        //ToDO change the name to createAspect maybe (check when it is used)
        API::registerFunction('views', 'createView', function() {
            API::requireCourseAdminPermission();
            API::requireValues('view', 'course');

            $pages = $this->viewHandler->getRegisteredPages();
            $pageId = API::getValue('view');
            if (!array_key_exists($pageId, $pages))
                API::error('Unknown view ' . $pageId);
            
            $courseId=API::getValue('course');
            $course = Course::getCourse($courseId);
            $pageSettings = $pages[$pageId];
            
            $type = $pageSettings['roleType'];

            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                
                $finalParents = $this->findParents($course, $info['roleOne']);
                $parentViews = $this->findViews($pageId,$pageSettings,$type, array_merge($finalParents, array($info['roleOne'])));
                
                if ($type == ViewHandler::VT_ROLE_SINGLE)
                    $role=$info['roleOne'];
                else if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                    $role=$info['roleOne'].'>'.$info['roleTwo'];
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
                    $parent = $parentViews[$sizeParents - 1];
                    
                    if ($parent["aspectClass"]==null){
                        $aspectClass = Core::$systemDB->select("aspect_class",[],"max(aspectClass)")+1;
                        $parent["aspectClass"]=$aspectClass;
                        //update aspectClass of parent view in DB
                        Core::$systemDB->insert("aspect_class",["aspectClass"=>$aspectClass, "viewId"=>$parent["id"]]);
                        $this->viewHandler->updateViewAndChildren($parent, true);
                    }
                    $newView = ["role"=>$role, "partType"=>"aspect"];
                    Core::$systemDB->insert("view",$newView);
                    $newView["id"]=Core::$systemDB->getLastId();
                    Core::$systemDB->insert("aspect_class",["aspectClass"=>$parent["aspectClass"], "viewId"=>$newView["id"]]);
                    $newView = array_merge($parent,$newView);
                
                    $this->viewHandler->updateViewAndChildren($newView, false, true);
                    
                } else {
                    $newView = ["role"=>$role, "partType"=>"aspect", 
                            "aspectClass"=>null,"parent"=>null,"viewIndex"=>null];
                    $this->viewHandler->updateViewAndChildren($newView);
                }
                http_response_code(201);
                return;
            }
            API::error('Unexpected...');
        });
        //ToDo change to deleteAspect
        API::registerFunction('views', 'deleteView', function() {
            API::requireCourseAdminPermission();
            API::requireValues('view', 'course');

            $pages = $this->viewHandler->getRegisteredPages();
            $page = API::getValue('view');
            if (!array_key_exists($page, $pages))
                API::error('Unknown view ' . $page);

            $pageSettings = $pages[$page];
            $courseId = API::getValue('course');

            $type = $pageSettings['roleType'];
            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                
                API::requireValues('info');
                $info = API::getValue('info');

                if (!array_key_exists('roleOne', $info))
                    API::error('Missing roleOne in info');
                
                $aspects = $this->viewHandler->getAspects($pageSettings["viewId"]);
                
                if ($type == ViewHandler::VT_ROLE_INTERACTION && !array_key_exists('roleTwo', $info)) {
                    $id= Core::$systemDB->select("aspect_class left join view",
                            ["role"=>$info['roleOne'].'>%', "aspectClass"=>$aspects[0]["aspectClass"]],"id");
                    Core::$systemDB->deletee("view",["id"=>$id]);
                } 
                else{
                    $aspectsByRole = array_combine(array_column($aspects,"role"),$aspects);
                    if ($type == ViewHandler::VT_ROLE_SINGLE ) {
                        $aspect = $aspectsByRole[$info["roleOne"]];
                    }else if ($type == ViewHandler::VT_ROLE_INTERACTION) {
                        $aspect = $aspectsByRole[$info['roleOne'].'>'.$info['roleTwo']];
                    }
                    Core::$systemDB->delete("view",["id"=>$aspect["id"]]);
                }
                if(sizeof($aspects)==2){
                    //only 1 aspect after deletion, aspectClass becomes null
                    Core::$systemDB->delete("aspect_class",["aspectClass"=>$aspects[0]["aspectClass"]]);
                }
                http_response_code(200);
                return;
            }
            API::error('Unexpected...');
        });

        API::registerFunction('views', 'getInfo', function() {
            API::requireValues('view', 'course');
            
            $pages = $this->viewHandler->getRegisteredPages();
            $pageId = API::getValue('view');
            if (!array_key_exists($pageId, $pages))
                API::error('Unknown page ' . $pageId);

            $pageSettings = $pages[$pageId];

            $course = Course::getCourse(API::getValue('course'));
            $response = array(
                'viewSettings' => $pageSettings,
            );

            $response['types'] = array(
                array('id'=> 1, 'name' => 'Single'),
                array('id'=> 2, 'name' => 'Role - Single'),
                array('id'=> 3, 'name' => 'Role - Interaction')
            );

            $type = $pageSettings['roleType'];
            if ($type == ViewHandler::VT_ROLE_SINGLE || $type == ViewHandler::VT_ROLE_INTERACTION) {
                
                $viewSpecializations = $this->viewHandler->getAspects($pageSettings["viewId"]);
                $result = [];
                
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
        
        API::registerFunction('views', 'exportTemplate', function() {
            API::requireCourseAdminPermission();
            API::requireValues('name','course');
            $name = API::getValue('name');
            $filename = "Template-".preg_replace("/[^a-zA-Z0-9-]/", "", $name) . ".txt";
            file_put_contents($filename,Core::$systemDB->select("view_template",["course"=>API::getValue('course'),"id"=>$name],"content")); 
            API::response(array('filename' => $filename ));
            
        });

        API::registerFunction('views', 'getEdit', function() {
            
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');

            $courseId = API::getValue('course');
            $pageId = API::getValue('view');
         
            $pages = $this->viewHandler->getRegisteredPages();
            if (!array_key_exists($pageId, $pages))
                API::error('Unknown view ' . $pageId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);
            
            $pageSettings = $pages[$pageId];//set id
            $viewType = $pageSettings['roleType'];

            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                API::requireValues('info');
                $info = API::getValue('info');

                if (!array_key_exists('role', $info))
                    API::error('Missing role');

                $view = $this->viewHandler->getViewWithParts($pageSettings["viewId"], $info['role']);
                //print_r($view);
                //$parentParts = $this->findParentParts($course, $pageId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                API::requireValues('info');
                $info = API::getValue('info');
                if (!array_key_exists('roleOne', $info) || !array_key_exists('roleTwo', $info))
                    API::error('Missing roleOne and/or roleTwo in info');

                $view = $this->viewHandler->getViewWithParts($pageId, $info['roleOne'].'>'.$info['roleTwo']);
                $parentParts = $this->findParentParts($course, $pageId, $viewType, $info['roleOne'], $info['roleTwo']);
            } else {
                $parentParts = array();
                $view = $this->viewHandler->getViewWithParts($pageId, "");  
            }
            
            //$view = ViewEditHandler::putTogetherView($view, $parentParts);
            $fields = \SmartBoards\DataSchema::getFields(array('course' => $courseId));

            $templates= $this->getTemplates();
            API::response(array('view' => $view, 'fields' => $fields, 'templates' =>$templates ));
        });

        API::registerFunction('views', 'saveEdit', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');
            
            $courseId = API::getValue('course');
            $pageId = API::getValue('view');
            $viewContent = API::getValue('content');
            
            $pages = $this->viewHandler->getRegisteredPages();
            if (!array_key_exists($pageId, $pages))
                API::error('Unknown view ' . $pageId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);

            $pageSettings = $pages[$pageId];
            $viewType = $pageSettings['roleType'];

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
                //print_r("here");
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
            
            $this->viewHandler->updateViewAndChildren($viewContent);
            //print_r($viewContent);
            if (!$testDone)
                API::response('Saved, but skipping test (no users in role to test or special role)');

            return;
            $parentParts = array();
            if ($viewType == ViewHandler::VT_ROLE_SINGLE) {
                $parentParts = $this->findParentParts($course, $pageId, $viewType, $info['role']);
            } else if ($viewType == ViewHandler::VT_ROLE_INTERACTION) {
                $parentParts = $this->findParentParts($course, $pageId, $viewType, $info['roleOne'], $info['roleTwo']);
            }
            
            //$viewContent = ViewEditHandler::breakView($viewContent, $parentParts);
           
            //print_r($viewContent);
            //$viewContent = $this->viewHandler->organizeViewData($viewContent);
            
            //print_r($viewContent);//array ( part=>,partList=>)
            if ($pageSettings['roleType'] == ViewHandler::VT_ROLE_SINGLE) {
                $role=$info['role'];
            } else if ($pageSettings['roleType'] == ViewHandler::VT_ROLE_INTERACTION) {
                $role=$info['roleOne'].'>'.$info['roleTwo'];
            } else {
                $role="";
            }
            $viewRoleInfo=['course'=>$courseId,'viewId'=>$pageId,'role'=>$role];
            
            //this may be unecessary, unless part or replacements changed
            //Core::$systemDB->update("view_role",
             //           $viewContent['view_role'],
              //          $viewRoleInfo);
            //$currParts = array_column(Core::$systemDB->selectMultiple("view_part",$viewRoleInfo,'pid'),'pid');
            
            foreach($viewContent['view_part'] as $part){
                if (empty(Core::$systemDB->select("view_part",['pid'=>$part['pid'],'course'=>$courseId]))){
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
                     
            
        });

        API::registerFunction('views', 'previewEdit', function() {
            API::requireCourseAdminPermission();
            API::requireValues('course', 'view');

            $courseId = API::getValue('course');
            $viewId = API::getValue('view');
            $viewContent = API::getValue('content');

            $views = $this->viewHandler->getRegisteredPages();
            if (!array_key_exists($viewId, $views))
                API::error('Unknown view ' . $viewId, 404);

            $course = \SmartBoards\Course::getCourse($courseId);

            $viewSettings = $views[$viewId];
            $viewType = $viewSettings['roleType'];

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
    
    //ToDo decide if this function is actualy useful, maybe delete
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

    private function findViews($pageId,$pageSettings,$type, $viewsToFind, $roleOne = null) {
        //$views = $this->getViewHandler()->getViews($viewId);
        //if ($roleOne != null) {//this argument always null
        //$views = $this->getViewHandler()->getViewsRoles($view,$roleOne);
           // $views = $views->getWrapped($roleOne);
        //}
        //$views = $views->getValue();
        $aspects = $this->getViewHandler()->getAspects($pageSettings["viewId"]); 
        //$views = $this->getViewHandler()->getViewRoles($viewId);
        $viewRoles = array_column($aspects,'role');
        $viewsFound = array();
        if ($type== ViewHandler::VT_ROLE_INTERACTION){
            $rolesFound=[];
            foreach ($viewRoles as $dualRole) {
                $role = substr($dualRole,0, strpos($dualRole, '>'));
                if (in_array($role, $viewsToFind) && !in_array($role, $rolesFound)){
                    $viewsFound[]=$this->getViewHandler()->getViewWithParts($pageId, $dualRole);
                    $rolesFound[]=$dualRole;
                }
            }
        }
        else{
            foreach ($viewsToFind as $viewToFind) {
                if (in_array($viewToFind, $viewRoles))
                    $viewsFound[]=$this->getViewHandler()->getViewWithParts($pageId, $viewToFind);
                //if (array_key_exists($viewToFind, $views))
                    //$viewsFound[] = $views[$viewToFind];
            }
        }
        return $viewsFound;
    }

    public function &getViewHandler() {
        return $this->viewHandler;
    }
    
    public function getTemplates($includeGlobals=false){
        //gets normal templates of this course
        //ToDO: get global templates
        $temps = Core::$systemDB->selectMultiple('template',
                ['course'=>$this->getCourseId()]);
        if ($includeGlobals) {
            $globalTemp = Core::$systemDB->selectMultiple("template",["isGlobal" => true]);
            return [$temps, $globalTemp];
        }
        //foreach ($temps as &$temp){
          //  $temp['content'] = unserialize($temp['content']);
        //}
        return $temps;
    }
    public function getTemplate($id) {
        $temp = Core::$systemDB->select('view_template',['id'=>$id,'course'=>$this->getCourseId()]);
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
