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
            $tempModulesEnabled = Core::$systemDB->selectMultiple("course_module", ["course" => $course["id"], "isEnabled" => 1], "moduleId");
            $modulesArr = array();
            foreach ($tempModulesEnabled as $mod) {
                if($mod["moduleId"] == "badges"){
                    $badgesArr = array();
                    
                    $badgesVarDB_ = Core::$systemDB->selectMultiple("badges_config", ["course"=>$course["id"]], "*");
                    if($badgesVarDB_){
                        $badgesArray = array();
                        foreach ($badgesVarDB_ as $badgesVarDB) {
                            array_push($badgesArray, array(
                                "maxBonusReward" => $badgesVarDB["maxBonusReward"]
                            ));
                        }
                        $badgesArr["badges_config"] = $badgesArray;
                        
                        if (array_key_exists("badges",$modulesArr)){
                            array_push($modulesArr["badges"], $badgesArr);
                        } else {
                            $modulesArr["badges"] = $badgesArr;
                        }
                    }

                }else if($mod["moduleId"] == "plugin"){
                    $pluginArr = array();

                    $moodleVarsDB_ = Core::$systemDB->selectMultiple("config_moodle", ["course" => $course["id"]], "*");
                    if($moodleVarsDB_){
                        $moodleArray = array();
                        foreach ($moodleVarsDB_ as $moodleVarsDB) {
                            array_push($moodleArray, array(
                                "dbserver" => $moodleVarsDB["dbServer"],
                                "dbuser" => $moodleVarsDB["dbUser"],
                                "dbpass" => $moodleVarsDB["dbPass"],
                                "db" => $moodleVarsDB["dbName"],
                                "dbport" => $moodleVarsDB["dbPort"],
                                "prefix" => $moodleVarsDB["tablesPrefix"],
                                "time" => $moodleVarsDB["moodleTime"],
                                "course" => $moodleVarsDB["moodleCourse"],
                                "user" => $moodleVarsDB["moodleUser"]
                            ));
                        }
                          $pluginArr["config_moodle"] = $moodleArray;
                    }

                    $classCheckDB_ = Core::$systemDB->selectMultiple("config_class_check", ["course" => $course["id"]], "*");
                    if ($classCheckDB_) {
                        $ccArray = array();
                        foreach ($classCheckDB_ as $classCheckDB) {
                            array_push($ccArray, array("tsvCode" => $classCheckDB["tsvCode"]));
                        }
                        $pluginArr["config_class_check"] = $ccArray;
                    }

                    $googleSheetsDB_ = Core::$systemDB->selectMultiple("config_google_sheets", ["course" => $course["id"]], "*");
                    if ($googleSheetsDB_) {
                        $gcArray = array();
                        foreach ($googleSheetsDB_ as $googleSheetsDB) {

                        array_push($gcArray, array(
                            "authCode" => $googleSheetsDB["authCode"],
                            "key_" => $googleSheetsDB["key_"],
                            "clientId" => $googleSheetsDB["clientId"],
                            "projectId" => $googleSheetsDB["projectId"],
                            "authUri" => $googleSheetsDB["authUri"],
                            "tokenUri" => $googleSheetsDB["tokenUri"],
                            "authProvider" => $googleSheetsDB["authProvider"],
                            "clientSecret" => $googleSheetsDB["clientSecret"],
                            "redirectUris" => $googleSheetsDB["redirectUris"],
                            "authUrl" => $googleSheetsDB["authUrl"],
                            "accessToken" => $googleSheetsDB["accessToken"],
                            "expiresIn" => $googleSheetsDB["expiresIn"],
                            "scope" => $googleSheetsDB["scope"],
                            "tokenType" => $googleSheetsDB["tokenType"],
                            "created" => $googleSheetsDB["created"],
                            "refreshToken" => $googleSheetsDB["refreshToken"],
                            "authCode" => $googleSheetsDB["authCode"],
                            "spreadsheetId" => $googleSheetsDB["spreadsheetId"],
                            "sheetName" => $googleSheetsDB["sheetName"]
                        ));
                        }
                        $pluginArr["config_google_sheets"] = $gcArray;

                    }

                    if($moodleVarsDB_ || $classCheckDB_ || $googleSheetsDB_){
                        if (array_key_exists("plugin", $modulesArr)) {
                            array_push($modulesArr["plugin"] ,$pluginArr);
                        } else {
                            $modulesArr["plugin"] = $pluginArr;
                        }
                    }

                }else if($mod["moduleId"] == "skills"){
                    $skillsArr = array();

                    $skillTreeVarDB_ = Core::$systemDB->selectMultiple("skill_tree", ["course" => $course["id"]], "*");
                    if ($skillTreeVarDB_) {
                        $skillTreeArray = array();
                        $skillTierArray = array();
                        $skillArray = array();
                        $dependencyArray = array();
                        $skillDependencyArray = array();
                        foreach ($skillTreeVarDB_ as $skillTreeVarDB) {
                            
                            array_push($skillTreeArray, array(
                                "maxReward" => $skillTreeVarDB["maxReward"]
                            ));
                            
                            $skillTierVarDB_ = Core::$systemDB->selectMultiple("skill_tier", ["treeId" => $skillTreeVarDB["id"]], "*");
                            if ($skillTierVarDB_) {
                                foreach ($skillTierVarDB_ as $skillTierVarDB) {
                                    array_push($skillTierArray, array(
                                        "tier" => $skillTierVarDB["tier"],
                                        "reward" => $skillTierVarDB["reward"],
                                        "treeId" => $skillTierVarDB["treeId"]
                                    ));

                                    $skillVarDB_ = Core::$systemDB->selectMultiple("skill", ["treeId" => $skillTreeVarDB["id"], "tier" =>  $skillTierVarDB["tier"]], "*");
                                    if($skillVarDB_){
                                        foreach ($skillVarDB_ as $skillVarDB) {
                                            array_push($skillArray, array(
                                                "name" => $skillVarDB["name"],
                                                "color" => $skillVarDB["color"],
                                                "page" => $skillVarDB["page"],
                                                "tier" => $skillVarDB["tier"],
                                                "treeId" => $skillVarDB["treeId"]
                                            ));

                                            $dependencyDB_ = Core::$systemDB->selectMultiple("dependency", ["superSkillId" => $skillVarDB["id"]], "*");
                                            if ($dependencyDB_) {
                                                foreach ($dependencyDB_ as $dependencyDB) {
                                                    array_push($dependencyArray, array(
                                                        "superSkillId" => $dependencyDB["superSkillId"]
                                                    ));
                                                    $skillDependencyDB_ = Core::$systemDB->selectMultiple("skill_dependency", ["dependencyId" => $dependencyDB["id"], "normalSkillId" => $skillVarDB["id"]], "*");
                                                    if ($skillDependencyDB_) {
                                                        foreach ($skillDependencyDB_ as $skillDependencyDB) {
                                                            array_push($skillDependencyArray, array(
                                                                "dependencyId" => $skillDependencyDB["dependencyId"],
                                                                "normalSkillId" => $skillDependencyDB["normalSkillId"]
                                                            ));

                                                        }
                                                    }

                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $skillsArr["skill_tree"] = $skillTreeArray;
                        if($skillTierArray){
                            $skillsArr["skill_tier"] = $skillTierArray;
                        }
                        if ($skillArray) {
                            $skillsArr["skill"] = $skillArray;
                        }
                        if ($dependencyArray) {
                            $skillsArr["dependency"] = $skillArray;
                        }
                        if ($skillDependencyArray) {
                            $skillsArr["skill_dependency"] = $skillDependencyArray;
                        }
                        
                        
                        if (array_key_exists("skills", $modulesArr)) {
                            array_push($modulesArr["skills"], $skillsArr);
                        } else {
                            $modulesArr["skills"] = $skillsArr;
                        }
                    }

                }
                else if($mod["moduleId"] == "xp"){
                    $xpArr = array();

                    $xpVarDB = Core::$systemDB->select("level", ["course" => $course["id"]], "*");
                    if($xpVarDB){

                        $xpArr["level"] = array(
                            "number" => $xpVarDB["number"],
                            "goal" => $xpVarDB["goal"],
                            "description" => $xpVarDB["description"]
                        );

                        if (array_key_exists("xp", $modulesArr)) {
                            array_push($modulesArr["xp"], $xpArr);
                        } else {
                            $modulesArr["xp"] = $xpArr;
                        }
                    }
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
    //verificar o isActive e isVisible tb? se sim, tem não se pode dar enable a esses botoes caso haja um curso com esse nome ativo
    public static function importCourses($fileData, $replace=false)
    {
        $fileData = json_decode($fileData);

        foreach ($fileData as $course) {
            if(!Core::$systemDB->select("course", ["name" => $course->name, "year" => $course->year])){
                $courseObj = Course::newCourse($course->name, $course->short,$course->year, $course->color, $course->isVisible, $course->isActive);
                
                $viewModule = ModuleLoader::getModule("views");
                $handler = $viewModule["factory"]();
                $viewHandler = new ViewHandler($handler);

                //modules


                Core::$systemDB->update("course_module", ["isEnabled" => 1], ["course" => $courseObj->cid, "moduleId" => "views"]);
                ModuleLoader::initModules($courseObj->cid);
                
                // return static::$courses[$cid];
                // foreach ($course->modulesEnabled as $module) {

                // }

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
                        //print_r($aspect);
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect, false, true);
                    }
                    Core::$systemDB->insert("page", ["course" => $courseObj->cid, "name" => $page->name, "roleType" => $page->roleType, "viewId"=>$aspects[0]["id"]]);
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
                    Core::$systemDB->insert("template", ["course" => $courseObj->cid, "name" => $template->name, "roleType" => $template->roleType]);
                    $templateId = Core::$systemDB->getLastId();
                    Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["id"], "templateId" => $templateId]);
                }

            }else{
                if ($replace){
                    
                }
            }
        }
        // $newCoursesNr = 0;
        // $lines = explode("\n", $fileData);
        //  foreach ($lines as $line) {
        //     $course = explode(",", $line);
        //     if (!$course[3]) {
        //         if (!Core::$systemDB->select("course", ["name" => $course[1]])) {
        //             $newCoursesNr++;
        //             Core::$systemDB->insert(
        //                 "course",
        //                 [
        //                     "color" => $course[0],
        //                     "name" => $course[1],
        //                     "short" => $course[2],
        //                     "year" => $course[3],
        //                     "isActive" => $course[4],
        //                     "isVisible" => $course[5]
        //                 ]
        //             );
        //         }
        //     } else {
        //         if (!Core::$systemDB->select("course", ["name" => $course[1], "year" => $course[3]])) {
        //             $newCoursesNr++;
        //             Core::$systemDB->insert(
        //                 "course",
        //                 [
        //                     "color" => $course[0],
        //                     "name" => $course[1],
        //                     "short" => $course[2],
        //                     "year" => $course[3],
        //                     "isActive" => $course[4],
        //                     "isVisible" => $course[5]
        //                 ]
        //             );
        //         }
        //     }
        // }
        // return $newCoursesNr;
    }
    // public function getDictionary()
    // {
    //     return Core::$systemDB->select("dictionary", ["course" => $this->cid]);
    // }

    // public function getLibraries($moduleId)
    // {
    //     return Core::$systemDB->selectMultiple("dictionary", ["moduleId" => $moduleId, "course" => $this->cid], "library");
    // }

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
