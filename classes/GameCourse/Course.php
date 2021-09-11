<?php

namespace GameCourse;

include 'GameRules.php';

use GameRules;
use Modules\Views\ViewHandler;
use GameCourse\CronJob;

class Course
{
    private $loadedModules = array();
    private static $courses = array();
    private $cid;

    public function __construct($cid)
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
        $module = ModuleLoader::getModule("plugin");
        $handler = $module["factory"]();
        $handler->setCourseCronJobs($this->cid, $active);
    }
    public function setVisibleState($active)
    {
        $this->setData("isVisible", $active);
    }
    public function setLandingPage($page)
    {
        $this->setData("defaultLandingPage", $page);
    }

    public function getUsers($active = true)
    {
        if (!$active) {
            $where = ["course" => $this->cid];
        } else {
            $where = ["course" => $this->cid, "c.isActive" => true];
        }
        return Core::$systemDB->selectMultiple(
            "course_user c left join game_course_user g on c.id=g.id",
            $where
        );
    }

    //receives name of role and gets all the course_users w that role
    public function getUsersWithRole($role, $active = true)
    {
        if (!$active) {
            $where = ["r.course" => $this->cid, "r.name" => $role];
        } else {
            $where = ["r.course" => $this->cid, "r.name" => $role, "cu.isActive" => true];
        }
        $result = Core::$systemDB->selectMultiple(
            "course_user cu left join game_course_user u on cu.id=u.id join user_role ur on ur.id=u.id join role r on r.id=ur.role join auth a on u.id=a.game_course_user_id",
            $where,
            "u.*,cu.lastActivity, cu.previousActivity,a.username,r.name as role"
        );
        return $result;
    }

    //receives id of role and gets all the course_users w that role
    public function getUsersWithRoleId($role, $active = true)
    {
        if (!$active) {
            $where = ["r.course" => $this->cid, "role" => $role];
        } else {
            $where = ["r.course" => $this->cid, "role" => $role, "c.isActive" => true];
        }
        return Core::$systemDB->selectMultiple(
            "game_course_user g  join course_user c on c.id=g.id join user_role r on c.id=r.id",
            $where,
            "g.*, c.lastActivity, c.previousActivity, role"
        );
    }

    public function getUsersIds()
    {
        return array_column(Core::$systemDB->selectMultiple("course_user", ["course" => $this->cid], 'id'), 'id');
    }

    public function getUsersNames($active = false)
    {
        if (!$active) {
            $where = ["course" => $this->cid];
        } else {
            $where = ["course" => $this->cid, "c.isActive" => true];
        }
        return array_column(Core::$systemDB->selectMultiple("course_user c left join game_course_user g on c.id=g.id", $where, 'name'), 'name');
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
        $oldRoles = $this->getRoles();
        // $newroles-> array of obj with: name, id, landingPage
        // $oldRoles-> array of obj with: name, id, landingPage, course
        foreach ($newroles as $role) {
            //updates existing role
            if ($role["id"] != null) {
                Core::$systemDB->update("role", ["landingPage" => $role["landingPage"]], ["id" => $role["id"]]);
            }
            //creates new role
            else {
                Core::$systemDB->insert("role", ["name" => $role["name"], "landingPage" => $role["landingPage"], "course" => $this->cid]);
            }
        }

        foreach ($oldRoles as $oldRole) {
            $isPresent = false;
            foreach ($newroles as $newRole) {
                if ($oldRole["id"] == $newRole["id"]) {
                    $isPresent = true;
                    break;
                }
            }
            if (!$isPresent) {
                Core::$systemDB->delete("role", ["id" => $oldRole["id"]]);
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
        if (!empty($hierarchy)) {
            foreach ($hierarchy as &$role) {
                $this->setHierarchyId($role, $rolesByName);
            }
        }
        return $hierarchy;
    }
    public function setRolesHierarchy($rolesHierarchy)
    {
        Core::$systemDB->update("course", ["roleHierarchy" => json_encode($rolesHierarchy)], ["id" => $this->cid]);
    }

    //returns number of awards
    public function getNumAwards()
    {
        return Core::$systemDB->select("award", ["course" => $this->cid], "count(*)");
    }

    //returns number of awards
    public function getNumParticipations()
    {
        return Core::$systemDB->select("participation", ["course" => $this->cid], "count(*)");
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
        if ($enabled) {
            $enabled = 1;
        } else {
            $enabled = 0;
        }
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
        Course::removeCourseDataFolder(Course::getCourseDataFolder($courseId));
        unset(static::$courses[$courseId]);
        new CronJob("Moodle", API::getValue('course'), null, null, true);
        new CronJob("ClassCheck", API::getValue('course'), null, null, true);
        new CronJob("GoogleSheets", API::getValue('course'), null, null, true);
        Core::$systemDB->delete("course", ["id" => $courseId]);
        Course::removeCourseDataFolder("autogame/imported-functions/" . strval($courseId));
        if (file_exists("autogame/config/config_" . strval($courseId) . ".txt")) {
            unlink("autogame/config/config_" . strval($courseId) . ".txt");
        }
    }

    //insert data to tiers and roles tables 
    //FixMe, this has hard coded info
    public static function insertBasicCourseData($db, $courseId)
    {
        $teacherId = $db->insert("role", ["name" => "Teacher", "course" => $courseId]);
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
    public static function getCourseDataFolder($courseId, $courseName = null)
    {
        if ($courseName === null) {
            $courseName = Course::getCourse($courseId, false)->getName();
        }
        $courseName = preg_replace("/[^a-zA-Z0-9_ ]/", "", $courseName);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . $courseName;
        return $folder;
    }

    public static function createCourseDataFolder($courseId, $courseName)
    {
        $folder = Course::getCourseDataFolder($courseId, $courseName);
        if (!file_exists($folder))
            mkdir($folder);
        /*if (!file_exists($folder . "/tree"))
            mkdir($folder . "/tree");*/
        return $folder;
    }

    public static function copyCourseDataFolder($source, $destination)
    {
        $dir = opendir($source);
        if (!file_exists($destination))
            mkdir($destination);

        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($source . '/' . $file)) {
                    // Recursively calling custom copy function for sub directory  

                    Course::copyCourseDataFolder($source . '/' . $file, $destination . '/' . $file);
                } else {
                    copy($source . '/' . $file, $destination . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    public static function removeCourseDataFolder($target)
    {
        $directory = new \RecursiveDirectoryIterator($target,  \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (is_dir($file)) {
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($target);
    }

    public function editCourse($courseName, $courseShort, $courseYear, $courseColor, $courseIsVisible, $courseIsActive)
    {
        $oldName = $this->getData("name");
        if (strcmp($oldName, $courseName) !== 0) {
            $id = $this->getData("id");
            rename(Course::getCourseDataFolder($id, $oldName), Course::getCourseDataFolder($id, $courseName));
            $this->setData("name", $courseName);
        }
        $this->setData("short", $courseShort);
        $this->setData("year", $courseYear);
        $this->setData("color", $courseColor);
        $this->setActiveState($courseIsActive);
        $this->setVisibleState($courseIsVisible);
    }

    public static function newCourse($courseName, $courseShort, $courseYear, $courseColor, $courseIsVisible, $courseIsActive, $copyFrom = null)
    {

        Core::$systemDB->insert("course", ["name" => $courseName, "short" => $courseShort, "year" => $courseYear, "color" => $courseColor, "isActive" => $courseIsActive, "isVisible" => $courseIsVisible]); //adicionar campos extra aqui
        $courseId = Core::$systemDB->getLastId();
        $course = new Course($courseId);
        static::$courses[$courseId] = $course;
        $dataFolder = Course::createCourseDataFolder($courseId, $courseName);


        //course_user table (add current user)
        $currentUserId = null;
        if (Core::getLoggedUser()) {
            $currentUserId = Core::getLoggedUser()->getId();
            Core::$systemDB->insert("course_user", ["id" => $currentUserId, "course" => $courseId]);
        }

        if ($copyFrom !== null) {
            $copyFromCourse = Course::getCourse($copyFrom);
            $copyDataFolder = Course::getCourseDataFolder($copyFrom);
            Course::copyCourseDataFolder($copyDataFolder, $dataFolder);

            //course table
            $keys = ['defaultLandingPage', "roleHierarchy", "theme"];
            $fromCourseData = $copyFromCourse->getData(); //Core::$systemDB->select("course",["id"=>$fromId]);
            $newData = [];
            foreach ($keys as $key)
                $newData[$key] = $fromCourseData[$key];
            Core::$systemDB->update("course", $newData, ["id" => $courseId]);

            //copy content of tables to new course
            $oldRoles = Course::copyCourseContent("role", $copyFrom, $courseId, true);
            $oldRolesById = array_combine(array_column($oldRoles, "id"), $oldRoles);
            $oldRolesByName = array_combine(array_column($oldRoles, "name"), $oldRoles);
            $newRoles = Core::$systemDB->selectMultiple("role", ["course" => $courseId]);
            $newRolesByName = array_combine(array_column($newRoles, "name"), $newRoles);
            $newRoleOfOldId = function ($id) use ($newRolesByName, $oldRolesByName) {
                //return $newRolesByName[$oldRolesById[$id]["name"]]["id"];
                return $newRolesByName[$oldRolesByName[$id]["name"]]["id"];
            };
            Core::$systemDB->insert("user_role", ["id" => $currentUserId, "course" => $courseId, "role" => $newRolesByName["Teacher"]["id"]]);

            //modules
            Course::copyCourseContent("course_module", $copyFrom, $courseId);
            $enabledModules = $copyFromCourse->getEnabledModules();
            foreach ($enabledModules as $moduleName) {
                $module = ModuleLoader::getModule($moduleName);
                $handler = $module["factory"]();
                if ($handler->is_configurable() && $moduleName != "awardList") {
                    $moduleArray = $handler->moduleConfigJson($copyFrom);
                    $result = $handler->readConfigJson($courseId, $moduleArray, false);
                }
            }

            //pages and views data
            if (in_array("views", $enabledModules)) {
                $viewModule = ModuleLoader::getModule("views");
                $handler = $viewModule["factory"]();
                $viewHandler = new ViewHandler($handler);

                $pages = Core::$systemDB->selectMultiple("page", ["course" => $copyFrom]);
                $handler = $copyFromCourse->getModule("views")->getViewHandler();
                foreach ($pages as $p) {
                    $p['course'] = $courseId;
                    unset($p['id']);
                    $view = Core::$systemDB->select("view", ["id" => $p["viewId"]]);
                    $views = $handler->getViewWithParts($view["viewId"]);

                    $defaultAspectId = null;
                    // if (sizeof($views) > 1) {
                    //     Core::$systemDB->insert("aspect_class");
                    //     $aspectClass = Core::$systemDB->getLastId();
                    // } else $aspectClass = null;
                    //set view
                    foreach ($views as $v) {
                        unset($v["id"]);
                        //$v["aspectClass"] = $aspectClass;
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

                //templates
                $templates = Core::$systemDB->selectMultiple("template", ["course" => $copyFrom, "isGlobal" => 0]);
                $tempTemplates = array();
                foreach ($templates as $t) {
                    $t['course'] = $course;
                    $aspect = Core::$systemDB->select(
                        "view_template vt join view v on vt.viewId=v.viewId",
                        ["templateId" => $t["id"]]
                    );
                    $views = $viewHandler->getViewWithParts($aspect["viewId"]);

                    $arrTemplate = array("roleType" => $t["roleType"], "name" => $t["name"], "views" => $views);
                    array_push($tempTemplates, $arrTemplate);
                }

                //import
                foreach ($tempTemplates as $template) {
                    $aspects = $template["views"];
                    //$aspectClass = null;
                    // if (sizeof($aspects) > 1) {
                    //     Core::$systemDB->insert("aspect_class");
                    //     $aspectClass = Core::$systemDB->getLastId();
                    // }
                    $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                    $content = null;

                    foreach ($aspects as &$aspect) {
                        //$aspect["aspectClass"] = $aspectClass;
                        Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"]]);
                        $aspect["id"] = Core::$systemDB->getLastId();
                        //print_r($aspect);
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect, false, true);
                    }
                    // $existingTemplate = Core::$systemDB->select("page", ["course" => $courseId, "name" => $template["name"], "roleType" => $template["roleType"]]);
                    // if ($existingTemplate) {
                    //     $id = $existingTemplate["id"];
                    //     Core::$systemDB->delete("template", ["id" => $id]);
                    // }
                    Core::$systemDB->insert("template", ["course" => $courseId, "name" => $template["name"], "roleType" => $template["roleType"]]);
                    $templateId = Core::$systemDB->getLastId();
                    Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["viewId"], "templateId" => $templateId]);
                }
            }
        } else {
            $teacherRoleId = Course::insertBasicCourseData(Core::$systemDB, $courseId);
            if ($currentUserId) {
                Core::$systemDB->insert("user_role", ["id" => $currentUserId, "course" => $courseId, "role" => $teacherRoleId]);
            }
            $modules = Core::$systemDB->selectMultiple("module");
            foreach ($modules as $mod) {
                Core::$systemDB->insert("course_module", ["course" => $courseId, "moduleId" => $mod["moduleId"]]);
            }
        }

        // insert line in AutoGame table
        Core::$systemDB->insert("autogame", ["course" => $courseId]);
        $rulesfolder = join("/", array($dataFolder, "rules"));
        $functionsFolder = "autogame/imported-functions/" . $courseId;
        $functionsFileDefault = "autogame/imported-functions/defaults.py";
        $defaultFunctionsFile = "/defaults.py";
        $metadataFile = "autogame/config/config_" . $courseId . ".txt";
        mkdir($rulesfolder);
        mkdir($functionsFolder);
        $defaults = file_get_contents($functionsFileDefault);
        file_put_contents($functionsFolder . $defaultFunctionsFile, $defaults);
        file_put_contents($metadataFile, "");

        return $course;
    }

    public static function exportCourses($id = null, $options = null)
    {
        if (!is_null($id)) {
            $allCourses = Core::$systemDB->selectMultiple("course", ["id" => $id]);
        } else {
            $allCourses = Core::$systemDB->selectMultiple("course");
        }
        $zip = new \ZipArchive();
        $zipName = "courses.zip";
        $jsonArr = array();

        if ($zip->open($zipName, (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)) !== true)
            die("Failed to create archive\n");

        foreach ($allCourses as $course) {
            $tempArr = array("courseId" => $course["id"], "color" => $course["color"], "name" => $course["name"], "short" =>  $course["short"], "year" => $course["year"], "isActive" => $course["isActive"], "isVisible" => $course["isVisible"]);

            $tempArr["page"] = [];
            $tempArr["template"] = [];
            $tempArr["modulesEnabled"] = [];
            $tempArr["users"] = [];
            $tempArr["awards"] = [];
            $tempArr["participations"] = [];

            if (is_null($options) or $options["modules"]) {
                $viewModule = ModuleLoader::getModule("views");
                $handler = $viewModule["factory"]();
                $viewHandler = new ViewHandler($handler);

                //Course data folder
                $dataFolder = Course::getCourseDataFolder($course["id"], $course["name"]);
                $courseIdName = explode("/", $dataFolder)[1] . "/"; // Ex: 1-PCM/

                $zip->addEmptyDir($courseIdName);
                $rootPath = realpath($dataFolder);

                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($rootPath)
                );

                foreach ($files as $name => $file) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);

                    if (!$file->isDir()) {
                        // Add current file to archive
                        $zip->addFile($filePath, $courseIdName . $relativePath);
                    } else {
                        if ($relativePath !== false) {
                            $zip->addEmptyDir($courseIdName . $relativePath);
                        }
                    }
                }

                //modules
                $tempModulesEnabled = Core::$systemDB->selectMultiple("course_module", ["course" => $course["id"], "isEnabled" => 1], "moduleId", "moduleId desc");
                $modulesArr = array();
                foreach ($tempModulesEnabled as $mod) {
                    $module = ModuleLoader::getModule($mod["moduleId"]);
                    $handler = $module["factory"]();
                    if ($handler->is_configurable() && $mod["moduleId"] != "awardlist") {
                        $moduleArray = $handler->moduleConfigJson($course["id"]);
                        if ($moduleArray) {
                            if (array_key_exists($mod["moduleId"], $modulesArr)) {
                                array_push($modulesArr[$mod["moduleId"]], $moduleArray);
                            } else {
                                $modulesArr[$mod["moduleId"]] = $moduleArray;
                            }
                        }
                    } else {
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
                    //$views = $viewHandler->getViewWithParts($view["id"]);

                    $arrPage = array("roleType" => $p["roleType"], "name" => $p["name"], "theme" => $p["theme"], "viewId" => $view["viewId"]);
                    array_push($tempPages, $arrPage);
                }

                //templates
                $templates = Core::$systemDB->selectMultiple("template", ["course" => $course["id"], "isGlobal" => 0]);
                $tempTemplates = array();
                foreach ($templates as $t) {
                    $t['course'] = $course;
                    //will get all the aspects (and contents) of the template
                    // $view = Core::$systemDB->select("view", ["id" => $p["viewId"]]);
                    // var_dump($view["id"]);
                    $aspect = Core::$systemDB->select(
                        "view_template vt join view v on vt.viewId=v.viewId",
                        ["templateId" => $t["id"]]
                    );
                    $views = $viewHandler->getViewWithParts($aspect["viewId"], null, true);

                    $arrTemplate = array("roleType" => $t["roleType"], "name" => $t["name"], "views" => $views);
                    array_push($tempTemplates, $arrTemplate);
                }

                $tempArr["page"] = $tempPages;
                $tempArr["template"] = $tempTemplates;
                $tempArr["modulesEnabled"] = $modulesArr;
            }
            if (!is_null($options) and $options["users"]) {
                $users = Core::$systemDB->selectMultiple("game_course_user g join course_user u on g.id = u.id join auth a on a.game_course_user_id = u.id", ["course" => $course["id"]], "g.*, a.username, a.authentication_service");
                foreach ($users as &$user) {
                    $roles = $roles = Core::$systemDB->selectMultiple("user_role ur join role r on ur.role = r.id", ["ur.id" => $user["id"], "ur.course" => $course["id"]]);
                    $rolesArr = [];
                    foreach ($roles as $role) {
                        array_push($rolesArr, $role["name"]);
                    }
                    $user["roles"] = $rolesArr;
                }
                $tempArr["users"] = $users;
            }
            if (!is_null($options) and $options["awards"]) {
                $participations = Core::$systemDB->selectMultiple("participation", ["course" => $course["id"]]);
                $tempArr["participations"] = $participations;

                $awards = Core::$systemDB->selectMultiple("award", ["course" => $course["id"]]);
                $tempArr["awards"] = $awards;
            }
            array_push($jsonArr, $tempArr);
        }
        $json = json_encode($jsonArr);
        $zip->addFromString('courses.json', $json);
        $zip->close();

        return $zipName;
    }

    //nao importa curso com o mesmo nome no mesmo ano
    public static function importCourses($zipContents, $replace = false)
    {
        $newCourse = 0;
        $path = time() . ".zip";
        file_put_contents($path, $zipContents);

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true)
            die("Failed to create archive\n");

        $fileData = json_decode($zip->getFromName("courses.json"));

        foreach ($fileData as $course) {
            if (!Core::$systemDB->select("course", ["name" => $course->name, "year" => $course->year])) {
                $courseObj = Course::newCourse($course->name, $course->short, $course->year, $course->color, $course->isVisible, $course->isActive);
                $newCourse++;

                //data folder
                $toFolder = Course::getCourseDataFolder($courseObj->cid, $course->name);
                $fromFolder = $course->courseId . "-" . $course->name;

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    // Skip files not in $fromFolder
                    if (strpos($name, "{$fromFolder}/") !== 0) continue;
                    // Read from Zip and write to disk
                    $zip->extractTo($toFolder, array($name));
                }
                Course::copyCourseDataFolder($toFolder . "/" . $fromFolder, $toFolder);
                Course::removeCourseDataFolder($toFolder . "/" . $fromFolder);

                //users
                $newIds = [];
                $users = json_decode(json_encode($course->users), true);

                foreach ($users as $user) {
                    $gcUser =  Core::$systemDB->select("game_course_user", ["studentNumber" => $user["studentNumber"]]);
                    if ($gcUser === false) {
                        $id = User::addUserToDB($user["name"], $user["username"], $user["authentication_service"], $user["email"], $user["studentNumber"], $user["nickname"], $user["major"], $user["isAdmin"], $user["isActive"]);
                        $newCourseUser = true;
                    } else {
                        $id = $gcUser["id"];
                        $newCourseUser = !Core::$systemDB->select("course_user", ["id" => $id, "course" => $courseObj->cid]);
                    }

                    $newIds[$user["id"]] = $id;
                    if ($newCourseUser)
                        CourseUser::addCourseUser($courseObj->cid, $id, null);

                    $courseUser = new CourseUser($id, Course::getCourse($courseObj->cid, false));
                    $courseUser->setRoles($user["roles"]);
                }

                //participations
                $participations = json_decode(json_encode($course->participations), true);
                foreach ($participations as &$participation) {
                    $participation["user"] = $newIds[$participation["user"]];
                    if (!is_null($participation["evaluator"])) {
                        $participation["evaluator"] = $newIds[$participation["evaluator"]];
                    }
                    $participation["course"] = $courseObj->cid;
                    Core::$systemDB->insert("participation", $participation);
                }

                //modules
                $modulesArray = json_decode(json_encode($course->modulesEnabled), true);
                $moduleNames = array_keys($modulesArray);
                $newModuleInstances = array();

                foreach ($moduleNames as $moduleName) {
                    Core::$systemDB->update("course_module", ["isEnabled" => 1], ["course" => $courseObj->cid, "moduleId" => $moduleName]);
                    $module = ModuleLoader::getModule($moduleName);
                    if ($modulesArray[$moduleName]) {
                        $handler = $module["factory"]();
                        $newModuleInstances[$moduleName] = $handler->readConfigJson($courseObj->cid, $modulesArray[$moduleName], false);
                    }
                }

                if (!$courseObj->getModule("views")) {
                    ModuleLoader::initModules($courseObj);
                }
                $viewModule = ModuleLoader::getModule("views");
                $handler = $viewModule["factory"]();
                $viewHandler = new ViewHandler($handler);

                //pages
                foreach ($course->page as $page) {
                    $aspects = json_decode(json_encode($page->views), true);
                    // $aspectClass = null;
                    // if (sizeof($aspects) > 1) {
                    //     Core::$systemDB->insert("aspect_class");
                    //     $aspectClass = Core::$systemDB->getLastId();
                    // }
                    $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                    $content = null;

                    foreach ($aspects as $key => &$aspect) {
                        // $aspect["aspectClass"] = $aspectClass;
                        Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"]]);
                        $aspect["id"] = Core::$systemDB->getLastId();
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect);
                        $existingPage = Core::$systemDB->select("page", ["course" => $courseObj->cid, "name" => $page->name, "roleType" => $page->roleType]);
                        if ($existingPage) {
                            Core::$systemDB->delete("page", ["id" => $existingPage["id"]]);
                        }
                        Core::$systemDB->insert("page", ["course" => $courseObj->cid, "name" => $page->name, "roleType" => $page->roleType, "viewId" => $aspect["id"], "seqId" => $key + 1]);
                    }
                }

                //templates
                foreach ($course->template as $template) {
                    $aspects = json_decode(json_encode($template->views), true);
                    // $aspectClass = null;
                    // if (sizeof($aspects) > 1) {
                    //     Core::$systemDB->insert("aspect_class");
                    //     $aspectClass = Core::$systemDB->getLastId();
                    // }
                    $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                    $content = null;

                    foreach ($aspects as &$aspect) {
                        // $aspect["aspectClass"] = $aspectClass;
                        Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"]]);
                        $aspect["id"] = Core::$systemDB->getLastId();
                        if ($content) {
                            $aspect["children"][] = $content;
                        }
                        $viewHandler->updateViewAndChildren($aspect, false, true);
                    }
                    // $existingTemplate = Core::$systemDB->select("page", ["course" => $courseObj->cid, "name" => $template->name, "roleType" => $template->roleType]);
                    // if ($existingTemplate) {
                    //     $id = $existingTemplate["id"];
                    //     Core::$systemDB->delete("template", ["id" => $id]);
                    // }
                    Core::$systemDB->insert("template", ["course" => $courseObj->cid, "name" => $template->name, "roleType" => $template->roleType]);
                    $templateId = Core::$systemDB->getLastId();
                    Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["viewId"], "templateId" => $templateId]);
                }

                //awards
                $awards = json_decode(json_encode($course->awards), true);
                foreach ($awards as &$award) {
                    $award["user"] = $newIds[$award["user"]];
                    $award["course"] = $courseObj->cid;
                    if ($award["type"] == "badge")
                        $award["moduleInstance"] = $newModuleInstances["badges"][$award["moduleInstance"]];
                    else if ($award["type"] == "skill")
                        $award["moduleInstance"] = $newModuleInstances["skills"][$award["moduleInstance"]];
                    Core::$systemDB->insert("award", $award);
                }
            } else {
                if ($replace) {
                    $id = Core::$systemDB->select("course", ["name" => $course->name, "year" => $course->year], "id");
                    $courseEdit = new Course($id);
                    $courseEdit->editCourse($course->name, $course->short, $course->year, $course->color, $course->isVisible, $course->isActive);

                    //modules
                    $modulesArray = json_decode(json_encode($course->modulesEnabled), true);
                    $moduleNames = array_keys($modulesArray);

                    foreach ($moduleNames as $module) {
                        Core::$systemDB->update("course_module", ["isEnabled" => 1], ["course" => $courseEdit->cid, "moduleId" => $module]);
                    }

                    for ($i = 0; $i < count($modulesArray); $i++) {
                        $moduleName = array_keys($modulesArray)[$i];
                        $module = ModuleLoader::getModule($moduleName);
                        if ($modulesArray[$moduleName]) {
                            $handler = $module["factory"]();
                            $result = $handler->readConfigJson($courseEdit->cid, $modulesArray[$moduleName], true);
                        }
                    }
                    $courseObjEdit = Course::getCourse($courseEdit->cid, false);
                    // if (!$courseObjEdit->getModule("views")) {
                    //     ModuleLoader::initModules($courseEdit);
                    // }

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

                        foreach ($aspects as $key => &$aspect) {
                            $aspect["aspectClass"] = $aspectClass;
                            Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"], "aspectClass" => $aspectClass]);
                            $aspect["id"] = Core::$systemDB->getLastId();
                            if ($content) {
                                $aspect["children"][] = $content;
                            }
                            $viewHandler->updateViewAndChildren($aspect);
                            $existingPage = Core::$systemDB->select("page", ["course" => $courseEdit->cid, "name" => $page->name, "roleType" => $page->roleType]);
                            if ($existingPage) {
                                Core::$systemDB->delete("page", ["id" => $existingPage["id"]]);
                            }
                            Core::$systemDB->insert("page", ["course" => $courseEdit->cid, "name" => $page->name, "roleType" => $page->roleType, "viewId" => $aspect["id"], "seqId" => $key + 1]);
                        }
                    }

                    //templates
                    foreach ($course->template as $template) {
                        $aspects = json_decode(json_encode($template->views), true);
                        // $aspectClass = null;
                        // if (sizeof($aspects) > 1) {
                        //     Core::$systemDB->insert("aspect_class");
                        //     $aspectClass = Core::$systemDB->getLastId();
                        // }
                        $roleType = $viewHandler->getRoleType($aspects[0]["role"]);
                        $content = null;

                        foreach ($aspects as &$aspect) {
                            // $aspect["aspectClass"] = $aspectClass;
                            Core::$systemDB->insert("view", ["role" => $aspect["role"], "partType" => $aspect["partType"]]);
                            $aspect["id"] = Core::$systemDB->getLastId();
                            //print_r($aspect);
                            if ($content) {
                                $aspect["children"][] = $content;
                            }
                            $viewHandler->updateViewAndChildren($aspect, false, true);
                        }
                        $existingTemplate = Core::$systemDB->select("template", ["course" => $courseEdit->cid, "name" => $template->name, "roleType" => $template->roleType]);
                        if ($existingTemplate) {
                            $id = $existingTemplate["id"];
                            Core::$systemDB->delete("template", ["id" => $id]);
                        }
                        Core::$systemDB->insert("template", ["course" => $courseEdit->cid, "name" => $template->name, "roleType" => $template->roleType]);
                        $templateId = Core::$systemDB->getLastId();
                        Core::$systemDB->insert("view_template", ["viewId" => $aspects[0]["viewId"], "templateId" => $templateId]);
                    }
                }
            }
        }
        $zip->close();
        unlink($path);
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
            "id, name, description, moduleId",
            "name"
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

    public function getFunctions()
    {
        $res = Core::$systemDB->selectMultipleSegmented(
            "dictionary_library right join dictionary_function on libraryId = dictionary_library.id",
            "refersToType='library' and returnType != 'null'",
            "moduleId, name, keyword, libraryId, refersToType, refersToName, returnType, dictionary_function.description as description, args",
            "keyword"
        );

        foreach ($res as $index => $row) {
            $res[$index]["args"] = json_decode($res[$index]["args"]);
        }

        return $res;
    }

    public function getAllFunctionsForEditor()
    {
        $res = Core::$systemDB->selectMultipleSegmented(
            "dictionary_library right join dictionary_function on libraryId = dictionary_library.id",
            null,
            "moduleId, name, keyword, libraryId, refersToType, returnName, refersToName, returnType, dictionary_function.description as description, args",
            "keyword"
        );

        foreach ($res as $index => $row) {
            $res[$index]["args"] = json_decode($res[$index]["args"]);
        }

        return $res;
    }

    public function checkFunctionReturnLoop($functions)
    {
        $f = $functions;
        for ($i = 0; $i < count($functions); $i++) {
            if ($functions[$i]["returnType"] == "collection") {
                $f[$i]["returnsLoop"] =  true;
            } else {
                $returnLoopResult = $this->returnLoop($functions[$i]["returnName"], $functions[$i]["returnType"], $functions);
                $f[$i]["returnsLoop"] = $returnLoopResult;
            }
        }
        return $f;
    }

    public function returnLoop($returnName, $returnType, $functions)
    {
        $hasLoop = false;
        foreach ($functions as $func) {
            if ($func["refersToType"] == $returnType && $func["refersToName"] == $returnName && $func["returnType"] == "collection") {
                $hasLoop = true;
            }
        }
        return $hasLoop;
    }
    public function getAvailablePages()
    {
        return Core::$systemDB->selectMultiple("page", ["course" => $this->cid, 'isEnabled' => 1], 'name');
    }

    public static function newExternalData($courseId, $all = False, $targets = null, $test = False)
    {
        if ($test) { // Test exec
            if ($all) {
                // run for all targets
                $gr = new GameRules($courseId, True, null, True);
                $res = $gr->run();
            } else {
                if ($targets) {
                    // run for selected targets
                    $gr = new GameRules($courseId, False, $targets, True);
                    $res = $gr->run();
                }
            }
            return $res;
        } else { // Normal GameRules exec
            if ($all) {
                // run for all targets
                $gr = new GameRules($courseId, True, null);
                $gr->run();
            } else {
                if ($targets) {
                    // run for selected targets
                    $gr = new GameRules($courseId, False, $targets);
                    $gr->run();
                } else {
                    // run normally
                    $gr = new GameRules($courseId, False, null);
                    $gr->run();
                }
            }
        }
    }

    public function upload($file, $filename, $module = null, $subfolder = null)
    {
        $location = Course::getCourseDataFolder($this->getId());

        if ($module) {
            $location .=  "/" . strtolower($module);
        }
        if ($subfolder) {
            $location .=  "/" . str_replace(' ', '', $subfolder);
        }
        $locationFile = "/" . $filename;
        $response = 0;

        // Upload file
        $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));
        if (!file_exists($location))
            mkdir($location);

        $result = file_put_contents($location . $locationFile, $decoded);
        if ($result) {
            $response = $location . $locationFile;
        }
        return $response;
    }

    public static function getDataFolders($dir)
    {
        $results = [];
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $temp = explode(".", $value);

                $extension = "." . end($temp);
                $file = array('name' => $value, 'filetype' => 'file', 'extension' => $extension);
                array_push($results, $file);
            } else if ($value != "." && $value != "..") {
                $folder = array('name' => $value, 'filetype' => 'folder', 'files' => Course::getDataFolders($path));
                $results[$value] = $folder;
            }
        }
        return $results;
    }

    public function deleteFile($path)
    {
        $locationFile = Course::getCourseDataFolder($this->getId()) . $path;
        unlink($locationFile);
    }

    public function createStyleFile()
    {
        $location = Course::getCourseDataFolder($this->getId()) . '/css';
        if (!file_exists($location))
            mkdir($location);

        $locationFile = $location . '/' . str_replace(' ', '', $this->getName()) . '.css';
        $response = 0;

        $result = file_put_contents($locationFile, '');
        if ($result !== false) {
            $response = $locationFile;
        }
        return $response;
    }

    public function getStyleFile()
    {
        $location = Course::getCourseDataFolder($this->getId()) . '/css';
        if (file_exists($location)) {
            $locationFile = $location . '/' . str_replace(' ', '', $this->getName()) . '.css';
            if (file_exists($locationFile))
                return [file_get_contents($locationFile), $locationFile];
            else
                return false;
        }

        return false;
    }

    public function updateStyleFile($content)
    {
        $locationFile = Course::getCourseDataFolder($this->getId()) . '/css' . '/' . str_replace(' ', '', $this->getName()) . '.css';
        $response = 0;

        $result = file_put_contents($locationFile, $content);
        if ($result !== false) {
            $response = $locationFile;
        }

        return $response;
    }
}
