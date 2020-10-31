<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include('classes/ClassLoader.class.php');
include('api_functions/courses_list.php');
include('api_functions/users_list.php');
include('api_functions/course_users_list.php');
include('api_functions/course_settings_pages.php');
include('api_functions/course_related.php');
include('api_functions/system_related.php');

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;
use GameCourse\Module;
use GameCourse\Settings;
use GameCourse\User;
use GameCourse\CourseUser;

Core::denyCLI();
if (!Core::requireLogin(false)) {
    API::error("Not logged in!", 400);
}
if (!Core::requireSetup(false))
    API::error("GameCourse is not yet setup.", 400);
Core::init();
if (!Core::checkAccess(false))
    API::error("Access denied.", 400);

ModuleLoader::scanModules();
API::gatherRequestInfo();

//------------------- self page

//get logged user informaition on both system and course
API::registerFunction('core', 'getUserInfo', function() {
    $user = Core::getLoggedUser();
    $userInfo = $user->getData();
    $userInfo['username'] = $user->getUsername();
    $userInfo['authenticationService'] = User::getUserAuthenticationService($userInfo['username']);
    API::response(array('userInfo' => $userInfo));
});

//------------------- main page

//get list of active courses of the logged user
API::registerFunction('core', 'getUserActiveCourses', function() {
    $user = Core::getLoggedUser();

    $coursesId = $user->getCourses();
    $courses=[];
    foreach($coursesId as $cid){
        $course = Core::getCourse($cid);
        if ($course["isActive"]){
            $courses[]=$course;
        }
    }
    array_combine(array_column($courses,'id'), $courses);

    API::response(array('userActiveCourses' => $courses));
});

//-------------------Course List related


API::registerFunction('core', 'importCourses', function(){
    API::requireAdminPermission();
    API::requireValues('file');
    $file = explode(",", API::getValue('file'));
    $fileType = explode(";", $file[0]);
    $fileContents = base64_decode($file[1]);
    $nCourses = Course::importCourses($fileContents);
    API::response(array('nCourses' => $nCourses));
});

API::registerFunction('core', 'exportCourses', function(){
    API::requireAdminPermission();
    $courses = Course::exportCourses();
    API::response(array('courses' => $courses));
});
//-------------------


API::registerFunction('core', 'getCourseInfo', function() {
    API::requireCoursePermission();
    API::requireValues('course');
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    //adding other pages to navigation
    $pages = \Modules\Views\ViewHandler::getPagesOfCourse($courseId);
    $OldNavPages = Core::getNavigation();
    $navNames= array_column($OldNavPages,"text");
    foreach ($pages as $pageId => $page){
        
        if ($page["roleType"]=="ROLE_INTERACTION")//not adding pages like profile to the nav bar
            continue;
        //pages added by modules already have navigation, the otheres need to be added
        if(!in_array($page["name"], $navNames)){
            $simpleName=str_replace(' ', '', $page["name"]);
            Core::addNavigation( $page["name"], 'course.customPage({name: \''.$simpleName.'\',id:\''.$pageId.'\'})', true); 
        }
    }
   
    $user = Core::getLoggedUser();
    $courseUser = $course->getLoggedUser();
    $isAdmin =(($user != null && $user->isAdmin()) || $courseUser->isTeacher());
    
    if ($isAdmin)
        Core::addNavigation( "Users", 'course.users', true); 
        Core::addNavigation('Course Settings', 'course.settings', true, 'dropdown', true);
        Core::addSettings('This Course', 'course.settings.global', true);
        Core::addSettings('Roles', 'course.settings.roles', true);
        Core::addSettings('Modules', 'course.settings.modules', true);
        Core::addSettings('Views', 'course.settings.views', true);

    $navPages = Core::getNavigation();
    $navSettings = Core::getSettings();
    //print_r($navPages);
    foreach ($navPages as $nav){
        if ($nav["restrictAcess"]===true && !$isAdmin){
            unset($navPages[array_search($nav, $navPages)]);
        }
    }
    API::response(array(
        'navigation' => $navPages,
        'settings' => $navSettings,
        'landingPage' => $courseUser->getLandingPage(),
        'courseName' => $course->getName(),
        'resources' => $course->getModulesResources(),
        'user' => $user
    ));
});

//set active/inactive state
API::registerFunction('settings', 'setCourseState', function() {
    API::requireCourseAdminPermission();
    API::requireValues('course', 'state');

    $courseId = API::getValue('course');
    $state = API::getValue('state');
    
    $course = Course::getCourse($courseId);
    $course->setActiveState($state);
});

//see and/or set landing page for a role
API::registerFunction('settings', 'roleInfo', function() {
    API::requireCourseAdminPermission();
    API::requireValues('id');
    $course = Course::getCourse(API::getValue('course'));

    $roleId = API::getValue('id');
    if (API::hasKey('landingPage')) {
        if ($roleId != 0) {//id 0 is default role
            $course->setRoleDataById($roleId,"landingPage",API::getValue('landingPage'));
        } else {
            $course->setLandingPage(API::getValue('landingPage'));
        }
    } else {
        if ($roleId != 0) {
            API::response(['landingPage'=>$course->getRoleById($roleId, "landingPage")]);
        } else {
            API::response(['landingPage'=>$course->getLandingPage()]);
        }
    }
});

API::registerFunction('settings', 'landingPages', function() {
    API::requireCourseAdminPermission();

    $course = Course::getCourse(API::getValue('course'));

    if (API::hasKey('landingPage')) {
        $roleId = API::getValue('id');
        if ($roleId != 0) {//id 0 is default role
            $course->setRoleDataById($roleId,"landingPage",API::getValue('landingPage'));
        } else {
            $course->setLandingPage(API::getValue('landingPage'));
        }
    } else {
        $roles = $course->getRoles();
        //add default
        foreach ($roles as $role){
            $roleId = $role["id"];
            if ($roleId != "0") {
                $role["landingPage"] = $course->getRoleById($roleId, "landingPage");
            } else {
                $role["landingPage"] = $course->getLandingPage();
            }
        }
        $default = [ "id" => "0", "name" => "Default", "course" => $course->getId(), "landingPage" => $course->getLandingPage()];
        array_unshift($roles, $default);

        $globalInfo = array('roles' => $roles);
        API::response($globalInfo);
    }

    
});

//change user roles or role hierarchy
API::registerFunction('settings', 'roles', function() {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));

    if (API::hasKey('updateRoleHierarchy')) {
        
        API::requireValues('hierarchy');
        API::requireValues('roles');
        
        $hierarchy = API::getValue('hierarchy');
        $newRoles = API::getValue('roles');
        
        $course->setRoles($newRoles);
        $course->setRolesHierarchy($hierarchy);
        http_response_code(201);

    } else {
        $globalInfo = array(
            'pages' => $course->getAvailablePages(),
            'roles' => array_column($course->getRoles("name"),"name"),
            'roles_obj' => $course->getRoles('id, name, landingPage'), //
            'rolesHierarchy' => $course->getRolesHierarchy(),
        );
        API::response($globalInfo);
    }
});

//main course settings page
API::registerFunction('settings', 'courseGlobal', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    
    $globalInfo = array(
        'name' => $course->getName(),
        'theme' => $GLOBALS['theme'],
    );
    API::response($globalInfo);
});

API::registerFunction('settings', 'courseModules', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if (API::hasKey('module') && API::hasKey('enabled')) {
        $moduleId = API::getValue('module');
        $modules = ModuleLoader::getModules();
        $module = ModuleLoader::getModule($moduleId);
        if ($module == null) {
            API::error('Unknown module!', 400);
            http_response_code(400);
        } else {
            $moduleObject = $module['factory']();
            $moduleEnabled = (in_array($module["id"], $course->getEnabledModules()));
            if ($moduleEnabled && !API::getValue('enabled')) {//disabling module
                $modules = $course->getModules();
                foreach ($modules as $mod) {
                    $dependencies = $mod->getDependencies();
                    foreach ($dependencies as $dependency) {
                        if ($dependency['id'] == $moduleId && $dependency['mode'] != 'optional')
                            API::error('Must disable all modules that depend on this one first.');
                    }
                }
                //ToDo: check if is working correctly with multiple courses
                // if (Core::$systemDB->select("course_module",["moduleId"=>$moduleId, "isEnabled"=>1],"count(*)")==1){
                    //only drop the tables of the module data if this is the last course where it is enabled
                    // $moduleObject->dropTables($moduleId);//deletes tables associated with the module
                // }else{
                    $moduleObject->deleteDataRows($course->getId());
                // }
            } else if(!$moduleEnabled && API::getValue('enabled')) {//enabling module
                foreach ($module['dependencies'] as $dependency) {
                    if ($dependency['mode'] != 'optional' && ModuleLoader::getModules($dependency['id']) == null)
                        API::error('Must enable all dependencies first.');
                }
            }
            if ($moduleEnabled != API::getValue('enabled')) {
                $course->setModuleEnabled($moduleId, !$moduleEnabled);
            }
            http_response_code(201);
        }
    } else {
        $allModules = ModuleLoader::getModules();
        $enabledModules = $course->getEnabledModules();
       
        $modulesArr = [];
        foreach ($allModules as $module) {            
            
            if (in_array($module['id'], $enabledModules)){
                $moduleInfo = ModuleLoader::getModule($module['id']);
                $moduleObj = $moduleInfo['factory']();
                $module['hasConfiguration'] = $moduleObj->is_configurable();
                $module['enabled'] = true;
            }
            else{
                $module['hasConfiguration'] = false;
                $module['enabled'] = false;
            }

            $dependencies = [];
            $canBeEnabled = true;
            foreach($module['dependencies'] as $dependency){
                if ($dependency['mode'] != 'optional'){
                    if(in_array($dependency['id'], $enabledModules)){
                        $dependencies[] = array('id' => $dependency['id'], 'enabled' => true);
                    }
                    else{
                        $dependencies[] = array('id' => $dependency['id'], 'enabled' => false);
                        $canBeEnabled = false;
                    } 
                }
            }

            $mod = array(
                'id' => $module['id'],
                'name' => $module['name'],
                'dir' => $module['dir'],
                'version' => $module['version'],
                'compatibleVersions' => json_encode($module['compatibleVersions']),
                'enabled' => $module['enabled'],
                'canBeEnabled' => $canBeEnabled,
                'dependencies' => $dependencies,
                'description' => $module['description'],
                'hasConfiguration' => $module['hasConfiguration']
            );
            $modulesArr[] = $mod;
        }
        API::response($modulesArr);
    }
});


API::registerFunction('settings', 'getModuleConfigInfo', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    $module = $course->getModule(API::getValue('module'));

    $moduleInfo = array(
        'id' => $module->getId(),
        'name' => $module->getName(),
        'description' => $module->getDescription()
    );

    $generalInputs=[];
    if($module->has_general_inputs()){
        $generalInputs = $module->get_general_inputs($course->getId());
    }

    $personalizedConfig=[];
    if($module->has_personalized_config()){
        $personalizedConfig = $module->get_personalized_function();
    }

    $listingItems=[];
    if($module->has_listing_items()){
        $listingItems = $module->get_listing_items($course->getId());
    }

    $info = array(
        'generalInputs' => $generalInputs,
        'listingItems' => $listingItems,
        'personalizedConfig' => $personalizedConfig,
        'module' => $moduleInfo
    );

    API::response($info);

});

API::registerFunction('settings', 'saveModuleConfigInfo', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    $module = $course->getModule(API::getValue('module'));

    if(API::hasKey('generalInputs')){
        $generalInputs = API::getValue('generalInputs');
        $module->save_general_inputs($generalInputs, $course->getId());
    }
    
    //personalized configuration should create its own API request
    //inside the currespondent module 

    if(API::hasKey('listingItems')){
        $listingItems = API::getValue('listingItems');
        $action_type = API::getValue('action_type'); //new, edit, delete
        $module->save_listing_item($action_type, $listingItems, $course->getId());
    }

});



//get tabs for course settings
API::registerFunction('settings', 'courseTabs', function() {
    API::requireCourseAdminPermission();
    API::response(Settings::getTabs());
});

//system settings (theme settings)
API::registerFunction('settings', 'global', function() {
    API::requireAdminPermission();

    if (API::hasKey('setTheme')) {
        if (file_exists('themes/' . API::getValue('setTheme')))
            Core::setTheme(API::getValue('setTheme'));
    } else {
        $themes = array();

        $themesDir = dir('themes/');
        while (($themeDirName = $themesDir->read()) !== false) {
            $themeDir = 'themes/' . $themeDirName;
            if ($themeDirName == '.' || $themeDirName == '..' || filetype($themeDir) != 'dir')
                continue;
            $themes[] = array('name' => $themeDirName, 'preview' => file_exists($themeDir . '/preview.png'));
        }
        $themesDir->close();
        
        API::response(array('theme' => $GLOBALS['theme'], 'themes' => $themes));
    }
});

//system settingd (courses installed)
API::registerFunction('settings', 'modules', function() {
    API::requireAdminPermission();
    // $course = Course::getCourse(API::getValue('course'));
    
    $allModules = ModuleLoader::getModules();
    //$enabledModules = $course->getModules();
    
    $modulesArr = [];
    foreach ($allModules as $module) {
        $mod = array(
            'id' => $module['id'],
            'name' => $module['name'],
            'dir' => $module['dir'],
            'version' => $module['version'],
            'compatibleVersions' => json_encode($module['compatibleVersions']),
            'dependencies' => $module['dependencies'],
            'description' => $module['description']
        );
        $modulesArr[] = $mod; 
    }
    
    API::response($modulesArr);

});

//get tabs for system settings
//deixa de ser utilizado, remover depois
API::registerFunction('settings', 'tabs', function() {
    API::requireAdminPermission();
    $courses = Core::getCourses();
    $coursesTabs = array();
    foreach ($courses as $course) {
        $coursesTabs[] = Settings::buildTabItem($course['name'] . ($course['isActive'] ? '' : ' - Inactive'), 'settings.courses.course({course:\'' . $course['id'] . '\'})', true);
    }
    $tabs = array(
        Settings::buildTabItem('Courses', 'settings.courses', true, $coursesTabs)
    );
    
    API::response($tabs);
});

//system users (manage admins)
API::registerFunction('core', 'users', function() {
    API::requireAdminPermission();


// ------------------- Users List

API::registerFunction('core', 'importUser', function(){
    API::requireAdminPermission();
    API::requireValues('file');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $nUsers = User::importUsers($fileContents);
    API::response(array('nUsers' => $nUsers));
});
API::registerFunction('core', 'exportUsers', function(){
    API::requireAdminPermission();
    $users = User::exportUsers();
    API::response(array('users' => $users));
});

API::registerFunction('core', 'importModule', function () {
    API::requireAdminPermission();
    API::requireValues('file');
    API::requireValues('fileName');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    Module::importModules($fileContents, API::getValue("fileName"));
    API::response(array());
});

API::registerFunction('core', 'exportModule', function () {
    API::requireAdminPermission();
    $zipFile = Module::exportModules();
    API::response(array("file"=> $zipFile));
});


//------------------Users inside the course

//used on the notCourseUsers API function, do not remove
function udiffCompare($a, $b){
    return $a['id'] - $b['id'];
}

API::registerFunction('course', 'importUser', function(){
    API::requireCourseAdminPermission();
    API::requireValues('file');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $nUsers = CourseUser::importCourseUsers($fileContents);
    API::response(array('nUsers' => $nUsers));
});
API::registerFunction('course', 'exportUsers', function(){
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $courseId = API::getValue('course');
    $courseUsers = CourseUser::exportCourseUsers($courseId);
    API::response(array('courseUsers' => $courseUsers));
});



//-------------------this functions have to be passed into the xp and levels module--------

//update list of course levels, from the levels configuration page
API::registerFunction('settings', 'courseLevels', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    if (Course::getCourse($courseId)->getModule("badges")!==null)
        $levels = Core::$systemDB->selectMultiple("level left join badge_has_level on levelId=id",
                    ["course"=>$courseId, "levelId"=>null],'number,description,goal,id',"number");
    else
        $levels = Core::$systemDB->selectMultiple("level",["course"=>$courseId],'number,description,goal,id',"number");
    //print_r($levels);
    $levelsByNum = array_combine(array_column($levels,"number") , $levels);
    $numOldLevels=sizeof($levels);
    $folder = Course::getCourseLegacyFolder($courseId);

    if (API::hasKey('levelList')) {
        $keys = array('title', 'goal');
        $levelInput=API::getValue('levelList');
        $levelList = preg_split('/[\r]?\n/', $levelInput, -1, PREG_SPLIT_NO_EMPTY);  
        $numNewLevels=sizeof($levelList);
        $updatedData=[];
        for($i=0;$i<$numNewLevels;$i++){
            //if level doesn't exit, add it to DB 
            $splitInfo =preg_split('/;/', $levelList[$i]);
            if (sizeof($splitInfo) != sizeOf($keys)) {
                echo "Level information was incorrectly formatted";
                return null;
            }
            $level = array_combine($keys, $splitInfo);
            if (!array_key_exists($i, $levelsByNum)){
                Core::$systemDB->insert("level",["number"=>$i,"goal"=>(int) $level['goal'],
                                                 "description"=>$level['title'],"course"=>$courseId]);  
                $updatedData[]= "New Level: " .$i;
            }else{
                Core::$systemDB->update("level",["goal"=>(int) $level['goal'],"description"=>$level['title']],
                                                ["id" => $levelsByNum[$i]['id']]);
            }
        }
        $lvlDiff=$numOldLevels-$numNewLevels;
        //Delete levels when given a smaller list of new levels
        if ($lvlDiff>0){
            for($i=$numNewLevels;$i<$numOldLevels;$i++){
                Core::$systemDB->delete("level",["id" => $levelsByNum[$i]['id']]);
                $updatedData[]= "Deleted Level: " .$i;
            }
        }
        file_put_contents($folder . '/levels.txt', $levelInput);
        API::response(["updatedData"=>$updatedData ]);
        return;
    }
    $file = @file_get_contents($folder . '/levels.txt');
    if ($file===FALSE){$file="";}
    API::response(array('levelList' => $levels, "file"=>$file ));
});

//returns array with all dependencies of a skill
function getSkillDependencies($skillId){
    $depArray=[];
    $deps = Core::$systemDB->selectMultiple(
        "dependency d join skill_dependency on dependencyId=id join skill s on s.id=normalSkillId",
        ["superSkillId"=>$skillId],"d.id,name");
            
    foreach ($deps as $d){
        $depArray[$d['id']][]=$d['name'];
    }
    //$depArray = array_filp($depArray);array_com
    //$depArray = array_values($depArray);   
    return $depArray;
}

function insertSkillDependencyElements($depElements,$depId,$skillsArray,$tree){
    
    foreach ($depElements as $depElement){
        if (array_key_exists($depElement, $skillsArray)){
            $requiredSkillId=$skillsArray[$depElement]['id'];
        }else{
            $requiredSkillId = Core::$systemDB->select("skill",["name"=>$depElement,"treeId"=>$tree],"id");
            if ($requiredSkillId==null){
                //echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                return null;
            }
        }
        Core::$systemDB->insert("skill_dependency",["dependencyId"=>$depId,"normalSkillId"=>$requiredSkillId]);
    }       
    return true;
}

function updateSkills($list,$tree,$replace, $folder){
    //for now names of skills are unique inside a course
    //if they start to be able to differ, this function needs to be updated
    $keys = array('tier', 'name', 'dependencies', 'color', 'xp');
    $skillTree = preg_split('/[\r]?\n/', $list, -1, PREG_SPLIT_NO_EMPTY);
    $skillsInDB= Core::$systemDB->selectMultiple("skill",["treeId"=>$tree],"id,name,tier,treeId");
    $skillsToDelete= array_column($skillsInDB,'name');
    $skilldInDBNames = array_combine($skillsToDelete,$skillsInDB);
    
    $updatedData=[];
    
    foreach($skillTree as &$skill) {
        $splitInfo =preg_split('/;/', $skill);
        if (sizeOf($splitInfo) != sizeOf($keys)) {
            echo "Skills information was incorrectly formatted";
            return null;
        }
        $skill = array_combine($keys, $splitInfo);
        if (strpos($skill['dependencies'], '|') !== FALSE) {//multiple dependency sets
            $skill['dependencies'] = preg_split('/[|]/', $skill['dependencies']);
            foreach($skill['dependencies'] as &$dependency) {
                $dependency = preg_split('/[+]/', $dependency);
            }
        } else {
            if (strpos($skill['dependencies'], '+') !== FALSE)
                $skill['dependencies'] = array(preg_split('/[+]/', $skill['dependencies']));
            elseif (strlen($skill['dependencies']) > 0) {
                $deps = [];
                $deps[] = [$skill['dependencies']];
                $skill['dependencies']=$deps;
            } 
            else
                $skill['dependencies'] = array();
        }
        
        unset($skill['xp']);//Not being used because xp is defined by tier (FIX?)

        $descriptionPage = @file_get_contents($folder . '/tree/' . str_replace(' ', '', $skill['name']) . '.html');

        if ($descriptionPage===FALSE){
            echo "Error: The skill ".$skill['name']." does not have a html file in the legacy data folder";
            return null;
        }
        $start = strpos($descriptionPage, '<td>') + 4;
        $end = stripos($descriptionPage, '</td>');
        $descriptionPage = substr($descriptionPage, $start, $end - $start);
        $skill['page'] = htmlspecialchars(utf8_encode($descriptionPage));

        if (!array_key_exists($skill["name"], $skilldInDBNames)){
            
            //new skill, insert in DB
            try{
                Core::$systemDB->insert("skill",["name"=>$skill["name"],"color"=>$skill['color'],
                                         "page"=>$skill['page'],"tier"=>$skill['tier'],"treeId"=>$tree]);
                $skillId = Core::$systemDB->getLastId();
                
                if (!empty($skill['dependencies'])){
                    for ($i=0; $i<sizeof($skill['dependencies']);$i++){
                        Core::$systemDB->insert("dependency",["superSkillId"=>$skillId]);
                        $dependencyId = Core::$systemDB->getLastId();
                        $deps=$skill['dependencies'][$i];
                        if (!insertSkillDependencyElements($deps,$dependencyId,$skilldInDBNames,$tree)){
                            echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                            return null;
                        }
                    }
                }
                $updatedData[]= "New skill: ".$skill["name"];
            } 
            catch (PDOException $e){
                echo "Error: Cannot add skills with tier=".$skill["tier"]." since it doesn't exist.";
                return;
            }
        }else{            
            //skill that exists in DB, update its info
            Core::$systemDB->update("skill",["color"=>$skill['color'],"page"=>$skill['page'],"tier"=>$skill['tier']],
                                                            ["name"=>$skill["name"],"treeId"=>$tree]);
            //update dependencies
            $skill['id'] = $skilldInDBNames[$skill["name"]]['id'];
            
            $dependenciesinDB = getSkillDependencies($skill['id']);
                    //array_column(Core::$systemDB->selectMultiple("skill_dependency",["skillName"=>$skill["name"],"course"=>$courseId],"dependencyNum"),"dependencyNum");
            
            $numOldDependencies=sizeof($dependenciesinDB);
            $numNewDependencies=sizeof($skill['dependencies']);
            foreach ($skill['dependencies'] as $depSet){
                $dependencyIndex=array_search($depSet, $dependenciesinDB);
                if ($dependencyIndex!==false){
                    unset($dependenciesinDB[$dependencyIndex]);
                }else{
                    Core::$systemDB->insert("dependency",["superSkillId"=>$skill['id']]);
                    $depSetId = Core::$systemDB->getLastId();
                    if (!insertSkillDependencyElements($depSet,$depSetId,$skilldInDBNames,$tree)){
                        echo "On skill '".$skill["name"]."' used dependecy of undefined skill";
                        return null;
                    }
                }
            }
            foreach ($dependenciesinDB as $depId => $dep){
                Core::$systemDB->delete("dependency",["id"=>$depId]);
            }
            unset($skillsToDelete[array_search($skill['name'], $skillsToDelete)]);
        }
    }
    //delete skills that weren't in the imported data
    if ($replace){
        foreach ($skillsToDelete as $skill){
            Core::$systemDB->delete("skill",["name"=>$skill,"treeId"=>$tree]);
            $updatedData[]= "Deleted skill: ".$skill;
        } 
        file_put_contents($folder . '/tree.txt', $list);
    }
    
    API::response(["updatedData"=>$updatedData ]);
    return;
}





/*register_shutdown_function(function() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
    //print_r(\GameCourse\Course::$coursesDb);
    echo 'before' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
    echo 'after' . \GameCourse\Course::$coursesDb->numQueriesExecuted();
});*/

API::processRequest();
