<?php

namespace GameCourse;
use Modules\Views\ViewHandler;
class Course
{
    private $loadedModules = array();
    private static $courses = array();
    private $cid;

    public function __construct($cid, $create = false)
    {
        $this->cid = $cid;
    }

    public function getId()
    {
        return $this->cid;
    }

    public function getData($field = '*')
    {
        return Core::$systemDB->select("course", ["id" => $this->cid], $field);
    }
    public function getName()
    {
        return $this->getData("name");
    }
    //public function getNumBadges(){
    //    return $this->getData("numBadges");
    //}
    public function getActive()
    {
        return $this->getData("isActive");
    }
    public function getVisible()
    {
        return $this->getData("isVisible");
    }
    public function getLandingPage()
    {
        return $this->getData("defaultLandingPage");
    }

    public function setData($field, $value)
    {
        Core::$systemDB->update("course", [$field => $value], ["id" => $this->cid]);
    }
    public function setActiveState($active)
    {
        $this->setData("isActive", $active);
    }
    public function setVisibleState($active)
    {
        $this->setData("isVisible", $active);
    }
    public function setLandingPage($page)
    {
        $this->setData("defaultLandingPage", $page);
    }

    public function getUsers()
    {
        return Core::$systemDB->selectMultiple(
            "course_user natural join game_course_user",
            ["course" => $this->cid]
        );
    }

    //receives name of role and gets all the course_users w that role
    public function getUsersWithRole($role)
    {
        return Core::$systemDB->selectMultiple(
            "game_course_user u natural join course_user cu natural join user_role ur join role r on r.id=ur.role",
            ["r.course" => $this->cid, "r.name" => $role],
            "u.*,cu.*,r.name as role"
        );
    }
    //receives id of role and gets all the course_users w that role
    public function getUsersWithRoleId($role)
    {
        return Core::$systemDB->selectMultiple(
            "game_course_user natural join course_user natural join user_role",
            ["course" => $this->cid, "role" => $role]
        );
    }

    public function getUsersIds()
    {
        return array_column(Core::$systemDB->selectMultiple("course_user", ["course" => $this->cid], 'id'), 'id');
    }

    public function getUser($istid)
    {
        if (!empty(Core::$systemDB->select("course_user", ["course" => $this->cid, "id" => $istid], 'id')))
            return new CourseUser($istid, $this);
        else
            return new NullCourseUser($istid, $this);
    }

    //public function getUserData($istid) {
    //    return $this->db->getWrapped('users')->getWrapped($istid)->getWrapped('data');
    //}

    public function getLoggedUser()
    {
        $user = Core::getLoggedUser();
        if ($user == null)
            return new NullCourseUser(-1, $this);

        return self::getUser($user->getId());
    }

    public function setRoleData($name, $field, $value)
    {
        return Core::$systemDB->update("role", [$field => $value], ["course" => $this->cid, "name" => $name]);
    }
    public function setRoleDataById($id, $field, $value)
    {
        return Core::$systemDB->update("role", [$field => $value], ["id" => $id]);
    }
    public function getRoleById($id, $field = '*')
    {
        return Core::$systemDB->select("role", ["course" => $this->cid, "id" => $id], $field);
    }
    public function getRoleByName($name, $field = '*')
    {
        return Core::$systemDB->select("role", ["course" => $this->cid, "name" => $name], $field);
    }
    public function getRolesData($field = '*')
    {
        return Core::$systemDB->selectMultiple("role", ["course" => $this->cid], $field);
    }
    //return an array with role names
    public function getRoles($field = '*')
    {
        return $this->getRolesData($field);
    }
    public static function getRoleId($role, $courseId)
    {
        return Core::$systemDB->select("role", ["course" => $courseId, "name" => $role], "id");
    }
    //receives array of roles to replace in the DB,
    public function setRoles($newroles)
    {
        //ToDo:If this is suposed to work with repeated roles, then there should be hiearchy info in role table in DB
        $oldRoles=$this->getRoles();
        // $newroles-> array of obj with: name, id, landingPage
        // $oldRoles-> array of obj with: name, id, landingPage, course
        foreach ($newroles as $role){
            //updates existing role
            if( $role["id"] != null){
                Core::$systemDB->update("role",["landingPage"=>$role["landingPage"]],["id"=>$role["id"]]);
            }
            //creates new role
            else{
                Core::$systemDB->insert("role",["name"=>$role["name"],"landingPage"=>$role["landingPage"],"course"=>$this->cid]);
            }
        }

        foreach($oldRoles as $oldRole){
            $isPresent = false;
            foreach($newroles as $newRole) {
                if ($oldRole["id"] == $newRole["id"] ) {
                    $isPresent = true;
                    break;
                }
            }
            if (!$isPresent){
                Core::$systemDB->delete("role",["id"=>$oldRole["id"] ]);
            }
        }
    }

    public function setHierarchyId(&$role, $rolesByName)
    {
        $role["id"] = $rolesByName[$role["name"]]["id"];
        if (array_key_exists("children", $role)) {
            foreach ($role["children"] as &$child) {
                $this->setHierarchyId($child, $rolesByName);
            }
        }
    }
    //returns array with all the roles ordered by hierarchy
    public function getRolesHierarchy()
    {
        //ToDO, if we want to allow repeated role names,the hierarchy will work diferently
        $roles = Core::$systemDB->selectMultiple("role", ["course" => $this->cid]);
        $rolesByName = array_combine(array_column($roles, "name"), $roles);

        $hierarchy = json_decode(Core::$systemDB->select("course", ["id" => $this->cid], "roleHierarchy"), true);
        foreach ($hierarchy as &$role) {
            $this->setHierarchyId($role, $rolesByName);
        }
        return $hierarchy;
    }
    public function setRolesHierarchy($rolesHierarchy)
    {
        Core::$systemDB->update("course", ["roleHierarchy" => json_encode($rolesHierarchy)], ["id" => $this->cid]);
    }

    //returns array w module names
    public function getEnabledModules()
    {
        return array_column(Core::$systemDB->selectMultiple("course_module", ["course" => $this->cid, "isEnabled" => true], "moduleId", "moduleId"), 'moduleId');
    }

    public function addModule($module)
    {
        return $this->loadedModules[$module->getId()] = $module;
    }

    public function getModules()
    {
        return $this->loadedModules;
    }

    public function getModule($module)
    {
        if (array_key_exists($module, $this->loadedModules))
            return $this->loadedModules[$module];
        return null;
    }

    public function getModulesResources()
    {
        $modules = $this->getModules();
        $resources = array();
        foreach ($modules as $id => $module) {
            $moduleResources = $module->getResources();
            $resources[] = array(
                'name' => 'module.' . $id,
                'files' => $moduleResources
            );
        }
        return $resources;
    }

    public function getModuleData($module)
    {
        if ($module == null)
            return null;
        else if (is_string($module))
            $moduleId = $module;
        else if (is_object($module))
            $moduleId = $module->getId();
        else
            return null;
        return Core::$systemDB->select("module", ["moduleId" => $moduleId]);
    }

    public function setModuleEnabled($moduleId, $enabled)
    {
        Core::$systemDB->update("course_module", ["isEnabled" => $enabled], ["course" => $this->cid, "moduleId" => $moduleId]);
        if (!$enabled) {
            //ToDo:do something about views that use this module?
            //  Core::$systemDB->delete("page",["module"=>$moduleId, "course"=>$this->cid]);
        }
    }

    //goes from higher in the hierarchy to lower (eg: Teacher > Student), maybe shoud add option to use reverse order
    public function goThroughRoles($func, &...$data)
    {
        \Utils::goThroughRoles($this->getRolesHierarchy(), $func, ...$data);
    }

    public static function getCourse($cid, $initModules = true)
    {
        if (!array_key_exists($cid, static::$courses)) {
            static::$courses[$cid] = new Course($cid);
            if ($initModules)
                ModuleLoader::initModules(static::$courses[$cid]);
        }
        return static::$courses[$cid];
    }

    public static function deleteCourse($courseId)
    {
        unset(static::$courses[$courseId]);
        Core::$systemDB->delete("course", ["id" => $courseId]);
    }

    //insert data to tiers and roles tables 
    //FixMe, this has hard coded info
    public static function insertBasicCourseData($db, $courseId)
    {
        $db->insert("role", ["name" => "Teacher", "course" => $courseId]);
        $teacherId = $db->getLastId();
        $db->insert("role", ["name" => "Student", "course" => $courseId]);
        $db->insert("role", ["name" => "Watcher", "course" => $courseId]);

        $roles = [["name" => "Teacher"], ["name" => "Student"], ["name" => "Watcher"]];
        $db->update("course", ["roleHierarchy" => json_encode($roles)], ["id" => $courseId]);

        return $teacherId;
    }

    //copies content of a specified table in DB to new rows for the new course
    private static function copyCourseContent($content, $fromCourseId, $newCourseId, $ignoreID = false)
    {
        $fromData = Core::$systemDB->selectMultiple($content, ["course" => $fromCourseId]);
        foreach ($fromData as $data) {
            $data['course'] = $newCourseId;
            if ($ignoreID)
                unset($data['id']);
            Core::$systemDB->insert($content, $data);
        }
        return $fromData;
    }
    public static function getCourseLegacyFolder($courseId, $courseName = null)
    {
        if ($courseName === null) {
            $courseName = Course::getCourse($courseId)->getName();
        }
        $courseName = preg_replace("/[^a-zA-Z0-9_ ]/", "", $courseName);
        $folder = LEGACY_DATA_FOLDER . '/' . $courseId . '-' . $courseName;
        return $folder;
    }

    public static function createCourseLegacyFolder($courseId, $courseName)
    {
        $folder = Course::getCourseLegacyFolder($courseId, $courseName);
        if (!file_exists($folder))
            mkdir($folder);
        if (!file_exists($folder . "/tree"))
            mkdir($folder . "/tree");
        return $folder;
    }

    public function editCourse($courseName, $courseShort, $courseYear, $courseColor, $courseIsVisible, $courseIsActive)
    {
        $this->setData("name", $courseName);
        $this->setData("short", $courseShort);
        $this->setData("year", $courseYear);
        $this->setData("color", $courseColor);
        $this->setActiveState($courseIsActive);
        $this->setVisibleState($courseIsVisible);
    }

    public static function newCourse($courseName, $courseShort, $courseYear, $courseColor, $courseIsVisible, $courseIsActive, $copyFrom = null)
    {
        //if (static::$coursesDb->get($newCourse) !== null) // Its in the Course graveyard
        //    static::$coursesDb->delete($newCourse);

        Core::$systemDB->insert("course", ["name" => $courseName, "short" => $courseShort, "year" => $courseYear, "color" => $courseColor, "isActive" => $courseIsActive, "isVisible" => $courseIsVisible]); //adicionar campos extra aqui
        $courseId = Core::$systemDB->getLastId();
        $course = new Course($courseId);
        static::$courses[$courseId] = $course;
        $legacyFolder = Course::createCourseLegacyFolder($courseId, $courseName);

        //course_user table (add current user)
        $currentUserId = Core::getLoggedUser()->getId();
        Core::$systemDB->insert("course_user", ["id" => $currentUserId, "course" => $courseId]);

        if ($copyFrom !== null) { //&& $courseExists) {
            $copyFromCourse = Course::getCourse($copyFrom);

            //course table
            $keys = ['defaultLandingPage', "roleHierarchy", "theme"];
            $fromCourseData = $copyFromCourse->getData(); //Core::$systemDB->select("course",["id"=>$fromId]);
            $newData = [];
            foreach ($keys as $key)
                $newData[$key] = $fromCourseData[$key];
            Core::$systemDB->update("course", $newData, ["id" => $courseId]);

            //copy content of tables to new course
            Course::copyCourseContent("course_module", $copyFrom, $courseId);
            // Course::copyCourseContent("dictionary", $copyFrom, $courseId);
            //copy roles, create mapping from old roles to new
            $oldRoles = Course::copyCourseContent("role", $copyFrom, $courseId, true);
            $oldRolesById = array_combine(array_column($oldRoles, "id"), $oldRoles);
            $newRoles = Core::$systemDB->selectMultiple("role", ["course" => $courseId]);
            $newRolesByName = array_combine(array_column($newRoles, "name"), $newRoles);
            $newRoleOfOldId = function ($id)  use ($newRolesByName, $oldRolesById) {
                return $newRolesByName[$oldRolesById[$id]["name"]]["id"];
            };

            //pages and views data
            $pages = Core::$systemDB->selectMultiple("page", ["course" => $copyFrom]);
            $handler = $copyFromCourse->getModule("views")->getViewHandler();
            foreach ($pages as $p) {
                $p['course'] = $courseId;
                unset($p['id']);
                $view = Core::$systemDB->select("view", ["id" => $p["viewId"]]);
                $views = $handler->getViewWithParts($view["id"]);

                $defaultAspectId = null;
                if (sizeof($views) > 1) {
                    Core::$systemDB->insert("aspect_class");
                    $aspectClass = Core::$systemDB->getLastId();
                } else $aspectClass = null;
                //set view
                foreach ($views as $v) {
                    unset($v["id"]);
                    $v["aspectClass"] = $aspectClass;
                    //need to convert the roles of the aspects to the new roles                 
                    if ($p["roleType"] == "ROLE_INTERACTION") {
                        $roles = explode(">", $v["role"]);
                    } else {
                        $roles = [$v["role"]];
                    }
                    foreach ($roles as &$role) {
                        $specificationName = explode(".", $role);
                        if ($specificationName[0] == "role" && $specificationName[1] != "Default") {
                            $role = "role." . $newRoleOfOldId($specificationName[1]);
                        }
                    }
                    $v["role"] = implode(">", $roles);

                    $copy = $v;
                    unset($copy["children"]);
                    Core::$systemDB->insert("view", $copy);
                    $aspectId = Core::$systemDB->getLastId();
                    if ($defaultAspectId == null) {
                        $defaultAspectId = $aspectId;
                    }

                    $v["id"] = $aspectId;
                    $handler->updateViewAndChildren($v, null, true);
                }
                $p["viewId"] = $defaultAspectId;

                Core::$systemDB->insert("page", $p);
            }
            //copy templates
            //$templates = Core::$systemDB->selectMultiple("template",["course"=>$copyFrom]);
            /*
            $aspect=Core::$systemDB->select("view_template join view on viewId=id",
                    ["partType"=>"block","parent"=>null,"templateId"=>$template["id"]]);
            $aspect["aspectClass"]=null;
            $views = $this->viewHandler->getViewWithParts($aspect["id"]);

            //$aspectClass
            $this->setTemplateHelper($views,$aspectClass,API::getValue("course"),$template["name"],$template["roleType"]);
            
                 *                  */

            //copy skill tree and tiers, for the rest we'll just use the config file
            $oldTree = Course::copyCourseContent("skill_tree", $copyFrom, $courseId, true);
            $oldTreeId = $oldTree[0]["id"];
            $tree = Core::$systemDB->getLastId();
            $tiers = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $oldTreeId]);
            foreach ($tiers as $tier) {
                $tier["treeId"] = $tree;
                Core::$systemDB->insert("skill_tier", $tier);
            }
            //Course::copyCourseContent("skill_tier",$copyFrom,$courseId);
            //Course::copyCourseContent("skill",$copyFrom,$courseId);
            //Course::copyCourseContent("skill_dependency",$copyFrom,$courseId);

            Course::copyCourseContent("badges_config", $copyFrom, $courseId, true);
            //Course::copyCourseContent("badge",$copyFrom,$courseId,true);
            //Course::copyCourseContent("badge_has_level",$copyFrom,$courseId);
            //Course::copyCourseContent("level",$copyFrom,$courseId,true);

            //copy some contents of the legacy folder
            $fromFolder = Course::getCourseLegacyFolder($copyFrom);
            $fromTree = file_get_contents($fromFolder . "/tree.txt");
            file_put_contents($legacyFolder . "/tree.txt", $fromTree);
            $fromBagdes = file_get_contents($fromFolder . "/achievements.txt");
            file_put_contents($legacyFolder . "/achievements.txt", $fromBagdes);
            $fromLevels = file_get_contents($fromFolder . "/levels.txt");
            file_put_contents($legacyFolder . "/levels.txt", $fromLevels);

            \Utils::copyFolder($fromFolder . "/tree", $legacyFolder . "/tree");
        } else {
            $teacherRoleId = Course::insertBasicCourseData(Core::$systemDB, $courseId);
            Core::$systemDB->insert("user_role", ["id" => $currentUserId, "course" => $courseId, "role" => $teacherRoleId]);
            $modules = Core::$systemDB->selectMultiple("module");
            foreach ($modules as $mod) {
                Core::$systemDB->insert("course_module", ["course" => $courseId, "moduleId" => $mod["moduleId"]]);
            }
        }
        return $course;
    }

    public static function exportCourses()
    {
        $allCourses = Core::$systemDB->selectMultiple("course");
        $jsonArr = array();

        foreach ($allCourses as $course) {
            $tempArr = array("color"=> $course["color"], "name"=> $course["name"], "short" =>  $course["short"], "year" => $course["year"], "isActive" => $course["isActive"], "isVisible"=> $course["isVisible"]);
            
            $viewModule = ModuleLoader::getModule("views");
            $handler = $viewModule["factory"]();
            $viewHandler = new ViewHandler($handler);

            //modules
            $tempModulesEnabled = Core::$systemDB->selectMultiple("course_module", ["course" => $course["id"], "isEnabled" => 1], "moduleId", "moduleId desc");
            $modulesArr = array();
            foreach ($tempModulesEnabled as $mod) {
                $module = ModuleLoader::getModule($mod["moduleId"]);
                $handler = $module["factory"]();
                if($handler->is_configurable() && $mod["moduleId"] != "awards"){
                    $moduleArray = $handler->moduleConfigJson($course["id"]);
                    if($moduleArray){
                        if (array_key_exists($mod["moduleId"], $modulesArr)) {
                            array_push($modulesArr[$mod["moduleId"]], $moduleArray);
                        } else {
                            $modulesArr[$mod["moduleId"]] = $moduleArray;
                        }
                    }
                }else{
                    $modulesArr[$mod["moduleId"]] = false;
                }
            }    
            
            //pages
            $pages = Core::$systemDB->selectMultiple("page", ["course" => $course["id"]]);
            $tempPages = array();
            foreach ($pages as $p) {
                $p['course'] = $course;
                unset($p['id']);
                $view = Core::$systemDB->select("view", ["id" => $p["viewId"]]);
                $views = $viewHandler->getViewWithParts($view["id"]);
               
                $arrPage = array("roleType"=> $p["roleType"], "name" => $p["name"], "theme" => $p["theme"], "views" => $views);
                array_push($tempPages, $arrPage);
            }

            //templates
            $templates = Core::$systemDB->selectMultiple("template",["course"=>$course["id"], "isGlobal"=>0]);
            $tempTemplates = array();
            foreach ($templates as $t) {
                $t['course'] = $course;
                //will get all the aspects (and contents) of the template
                // $view = Core::$systemDB->select("view", ["id" => $p["viewId"]]);
                // var_dump($view["id"]);
                $aspect = Core::$systemDB->select(
                    "view_template join view on viewId=id",
                    ["partType" => "block", "parent" => null, "templateId" => $t["id"]]
                );
                $views = $viewHandler->getViewWithParts($aspect["id"]);

                $arrTemplate = array("roleType" => $t["roleType"], "name" => $t["name"], "views" => $views);
                array_push($tempTemplates, $arrTemplate);
            }
            
            $tempArr["page"] = $tempPages;
            $tempArr["template"] = $tempTemplates;
            $tempArr["modulesEnabled"] = $modulesArr;
            array_push($jsonArr, $tempArr);
        }
        return json_encode($jsonArr);
    }

    //nao importa curso com o mesmo nome no mesmo ano
    public static function importCourses($fileData, $replace = true)
    {
        $newCourse = 0;
        $fileData = json_decode($fileData);
        foreach ($fileData as $course) {
            if(!Core::$systemDB->select("course", ["name" => $course->name, "year" => $course->year])){
                $courseObj = Course::newCourse($course->name, $course->short,$course->year, $course->color, $course->isVisible, $course->isActive);
                $newCourse++;

                //modules
                $modulesArray = json_decode(json_encode($course->modulesEnabled), true);
                $moduleNames = array_keys($modulesArray);
                
                foreach ($moduleNames as $module) {
                    Core::$systemDB->update("course_module", ["isEnabled" => 1], ["course" => $courseObj->cid, "moduleId" => $module]);
                }

                $levelIds = array();
                for ($i = 0; $i < count($modulesArray); $i++) {
                    $moduleName = array_keys($modulesArray)[$i];
                    $module = ModuleLoader::getModule($moduleName);
                    if($modulesArray[$moduleName]){
                        $handler = $module["factory"]();
                        if($moduleName == "badges"){
                            $result = $handler->readConfigJson($courseObj->cid, $modulesArray[$moduleName], $levelIds);
                        }else{
                            $result = $handler->readConfigJson($courseObj->cid, $modulesArray[$moduleName]);
                        }
                        if($result){
                            $levelIds = $result;
                        }
                    }
                }

                ModuleLoader::initModules($courseObj);
                $viewModule = ModuleLoader::getModule("views");
                $handler = $viewModule["factory"]();
                $viewHandler = new ViewHandler($handler);
                //pages
                foreach ($course->page as $page) {
                    $aspects = json_decode(json_encode($page->views), true);
                    $aspectClass = null;
                    if (sizeof($aspects) > 1) {
                        Core::$systemDB->insert("aspect_class");
                        $aspectClass = Core::$systemDB->getLastId();
                    }
                    $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                    $content = null;

                    foreach ($aspects as &$aspect) {
                        $aspect["aspectClass"] = $aspectClass;
                        Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"], "aspectClass" => $aspectClass]);
                        $aspect["id"] = Core::$systemDB->getLastId();
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect);
                        $existingPage = Core::$systemDB->select("page", ["course" => $courseObj->cid, "name" => $page->name, "roleType" => $page->roleType]);
                        if($existingPage){
                            Core::$systemDB->delete("page", ["id" => $existingPage["id"]]);
                        }
                        Core::$systemDB->insert("page", ["course" => $courseObj->cid, "name" => $page->name, "roleType" => $page->roleType, "viewId"=>$aspect["id"]]);
                    }
                }

                //templates
                foreach ($course->template as $template) {
                    $aspects = json_decode(json_encode($template->views), true);
                    $aspectClass = null;
                    if (sizeof($aspects) > 1) {
                        Core::$systemDB->insert("aspect_class");
                        $aspectClass = Core::$systemDB->getLastId();
                    }
                    $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                    $content = null;

                    foreach ($aspects as &$aspect) {
                        $aspect["aspectClass"] = $aspectClass;
                        Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"], "aspectClass" => $aspectClass]);
                        $aspect["id"] = Core::$systemDB->getLastId();
                        //print_r($aspect);
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect, false, true);
                    }
                    $existingTemplate = Core::$systemDB->select("page", ["course" => $courseObj->cid, "name" => $template->name, "roleType" => $template->roleType]);
                    if ($existingTemplate) {
                        $id = $existingTemplate["id"];
                        Core::$systemDB->delete("template", ["id" => $id]);
                    }
                    Core::$systemDB->insert("template", ["course" => $courseObj->cid, "name" => $template->name, "roleType" => $template->roleType]);
                    $templateId = Core::$systemDB->getLastId();
                    Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["id"], "templateId" => $templateId]);
                }

            }else{
                if ($replace){
                    $id = Core::$systemDB->select("course", ["name" => $course->name, "year" => $course->year], "id");
                    $courseEdit = new Course($id);
                    $courseEdit->editCourse($course->name, $course->short, $course->year, $course->color, $course->isVisible, $course->isActive);

                    //modules
                    $modulesArray = json_decode(json_encode($course->modulesEnabled), true);
                    $moduleNames = array_keys($modulesArray);

                    foreach ($moduleNames as $module) {
                        Core::$systemDB->update("course_module", ["isEnabled" => 1], ["course" => $courseEdit->cid, "moduleId" => $module]);
                    }

                    $levelIds = array();
                    for ($i = 0; $i < count($modulesArray); $i++) {
                        $moduleName = array_keys($modulesArray)[$i];
                        $module = ModuleLoader::getModule($moduleName);
                        if ($modulesArray[$moduleName]) {
                            $handler = $module["factory"]();
                            if ($moduleName == "badges") {
                                $result = $handler->readConfigJson($courseEdit->cid, $modulesArray[$moduleName], $levelIds, true);
                            } else {
                                $result = $handler->readConfigJson($courseEdit->cid, $modulesArray[$moduleName], true);
                            }
                            if ($result) {
                                $levelIds = $result;
                            }
                        }
                    }
                }
            }
        }
        return $newCourse;
    }
  

    public function getEnabledLibraries()
    {
        $modulesEnabled = $this->getEnabledModules();
        $whereCondition = "libraryId is null or ";

        $i = 0;
        foreach ($modulesEnabled as $module) {
            $whereCondition .=  "moduleId=\"" . $module . "\"";

            if ($i != count($modulesEnabled) - 1) {
                $whereCondition .= " or ";
            }
            $i++;
        }

        $functions =  Core::$systemDB->selectMultipleSegmented(
            "dictionary_library right join dictionary_function on libraryId = dictionary_library.id",
            $whereCondition,
            "name, keyword, refersToType, refersToName, returnType, returnName, args"
        );
        $arrMerged = $this->checkFunctionReturnLoop($functions);
        return $arrMerged;
    }

    //enabled porque no futuro podem haver variáveis dependentes de modules
    public function getEnabledVariables()
    {
        $modulesEnabled = $this->getEnabledModules();
        $whereCondition = "libraryId is null or ";

        $i = 0;
        foreach ($modulesEnabled as $module) {
            $whereCondition .=  "moduleId=\"" . $module . "\"";

            if ($i != count($modulesEnabled) - 1) {
                $whereCondition .= " or ";
            }
            $i++;
        }

        return Core::$systemDB->selectMultipleSegmented(
            "dictionary_library right join dictionary_variable on libraryId = dictionary_library.id",
            $whereCondition,
            "dictionary_library.name as library, dictionary_variable.name as name, returnType, returnName"
        );
    }

    public function getEnabledLibrariesInfo()
    {
        $modulesEnabled = $this->getEnabledModules();
        $whereCondition = "";

        foreach ($modulesEnabled as $module) {
            $whereCondition .=  "moduleId=\"" . $module . "\" or ";
        }
        $whereCondition .= "moduleId is null";
        return Core::$systemDB->selectMultipleSegmented(
            "dictionary_library",
            $whereCondition,
            "id, name, description, moduleId"
        );
    }

    public function getLibraryFunctions($library)
    {
        if ($library) {
            $whereCondition =  "libraryId=\"" . $library . "\"";
        } else {
            $whereCondition = "libraryId is null";
        }
        return Core::$systemDB->selectMultipleSegmented(
            "dictionary_library right join dictionary_function on libraryId = dictionary_library.id",
            $whereCondition,
            "name, keyword, refersToType, refersToName, returnType, dictionary_function.description as description, args",
            "refersToType"
        );
    }
    public function checkFunctionReturnLoop($functions){
        $f = $functions;
        for ($i = 0; $i < count($functions); $i++) { 
            if($functions[$i]["returnType"] == "collection"){
                $f[$i]["returnsLoop"] =  true;
            }else{
                $returnLoopResult = $this->returnLoop($functions[$i]["returnName"], $functions[$i]["returnType"], $functions);
                $f[$i]["returnsLoop"] = $returnLoopResult;
            }
        }
        return $f;
    }

    public function returnLoop($returnName, $returnType, $functions){
        $hasLoop = false;
        foreach ($functions as $func) {
            if($func["refersToType"] == $returnType && $func["refersToName"] == $returnName && $func["returnType"] == "collection"){
                $hasLoop=true;
            }
        }
        return $hasLoop;
    }
    public function getAvailablePages(){
        return Core::$systemDB->selectMultiple("page",["course"=>$this->cid], 'name');
    }
}
