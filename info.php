<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include('classes/ClassLoader.class.php');

use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\ModuleLoader;
use SmartBoards\Settings;
use SmartBoards\User;
use SmartBoards\CourseUser;

Core::denyCLI();
if (!Core::requireLogin(false))
    API::error("Not logged in!", 400);
if (!Core::requireSetup(false))
    API::error("SmartBoards is not yet setup.", 400);
Core::init();
if (!Core::checkAccess(false))
    API::error("Access denied.", 400);

ModuleLoader::scanModules();
API::gatherRequestInfo();

//return a list of courses that the user is allowed to see
API::registerFunction('core', 'getCoursesList', function() {
    $user = Core::getLoggedUser();
    
    if ($user->isAdmin()) {
        $courses = Core::getCourses();
        $myCourses = false;
    }
    else {
        $coursesId = $user->getCourses();
        
        $courses=[];
        foreach($coursesId as $cid){
            $course = Core::getCourse($cid);
            if ($course["active"]){
                $courses[]=$course;
            }
        }
        array_combine(array_column($courses,'id'),$courses);
        $myCourses = true;
    }
    API::response(array('courses' => $courses, 'myCourses' => $myCourses));
});

API::registerFunction('core', 'getCourseInfo', function() {
    API::requireCoursePermission();
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    $user = Core::getLoggedUser();
    $courseUser = $course->getLoggedUser();
    if ($user->isAdmin() || $courseUser->hasRole('Teacher'))
        Core::addNavigation('images/gear.svg', 'Settings', 'course.settings', true);
    
    API::response(array(
        'navigation' => Core::getNavigation(),
        'landingPage' => $courseUser->getLandingPage(),
        'courseName' => $course->getName(),
        'headerLink' => $course->getHeaderLink(),
        'resources' => $course->getModulesResources()
    ));
});

API::registerFunction('settings', 'courseApiKey', function() {//ToDo
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    API::response(array('key' => $course->getData('apiKey')));
});

API::registerFunction('settings', 'courseApiKeyGen', function() {//ToDo
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    $newKey = md5(mt_rand() . mt_rand() . mt_rand() . getmypid());
    $course->setData('apiKey', $newKey);
    API::response(array('key' => $newKey));
});

API::registerFunction('settings', 'apiKey', function() {//ToDo
    API::response(array('key' => Core::getApiKey()));
});

API::registerFunction('settings', 'apiKeyGen', function() {//ToDo
    $newKey = md5(mt_rand() . mt_rand() . mt_rand() . getmypid());
    Core::setApiKey($newKey);
    API::response(array('key' => $newKey));
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
    API::requireValues('role');
    $course = Course::getCourse(API::getValue('course'));

    $role = API::getValue('role');
    if (API::hasKey('landingPage')) {
        if ($role != 'Default') {
            $course->setRoleData($role,"landingPage",API::getValue('landingPage'));
        } else {
            $course->setLandingPage(API::getValue('landingPage'));
        }
    } else {
        if ($role != 'Default') {
            API::response(['landingPage'=>$course->getRoleData($role, "landingPage")]);
        } else {
            API::response(['landingPage'=>$course->getLandingPage()]);
        }
    }
});

//change user roles or role hierarchy
API::registerFunction('settings', 'roles', function() {
    API::requireCourseAdminPermission();

    if (API::hasKey('updateRoleHierarchy')) {
        $hierarchy = API::getValue('updateRoleHierarchy');
        $course = Course::getCourse(API::getValue('course'));
        //ToDo: add a prompt to confirm deleting roles (maybe just if they're assigned to users)
        $course->setRoles($hierarchy['roles']);
        $course->setRolesHierarchy($hierarchy['hierarchy']);
        $roles = $hierarchy['roles'];
        
        http_response_code(201);
    } else if (API::hasKey('usersRoleChanges')) {
        $course = Course::getCourse(API::getValue('course'));
        $usersRoleChanges = API::getValue('usersRoleChanges');
        foreach ($usersRoleChanges as $userId => $roles) {
            $course->getUser($userId)->setRoles($roles);
        }
        http_response_code(201);
    } else {
        $course = Course::getCourse(API::getValue('course'));
        $users = $course->getUsers();
        $usersInfo = [];
        foreach ($users as $userData) {
            $id = $userData['id'];
            $user = new \SmartBoards\CourseUser($id,$course);
            $usersInfo[$id] = array('id' => $id, 'name' => $user->getName(), 'roles' => $user->getRoles());
        }
        $globalInfo = array(
            'users' => $usersInfo,
            'roles' => $course->getRoles(),
            'rolesHierarchy' => $course->getRolesHierarchy(),
        );
        API::response($globalInfo);
    }
});

//main course settings page
API::registerFunction('settings', 'courseGlobal', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if (API::hasKey('headerLink')) {
        $course->setHeaderLink(API::getValue('headerLink'));
        http_response_code(201);
    } else if (API::hasKey('courseFenixLink')) {
        $course->setData("fenixLink",API::getValue('courseFenixLink'));
        http_response_code(201);
    } else if (API::hasKey('module') && API::hasKey('enabled')) {
        $moduleId = API::getValue('module');
        $module = ModuleLoader::getModule($moduleId);
        if ($module == null) {
            API::error('Unknown module!', 400);
            http_response_code(400);
        } else {
            $moduleEnabled = ($course->getModule($moduleId) != null);
            
            if ($moduleEnabled && !API::getValue('enabled')) {
                $modules = $course->getModules();
                foreach ($modules as $module) {
                    $dependencies = $module->getDependencies();
                    foreach ($dependencies as $dependency) {
                        if ($dependency['id'] == $moduleId && $dependency['mode'] != 'optional')
                            API::error('Must disable all modules that depend on this one first.');
                    }
                }
            } else if(!$moduleEnabled && API::getValue('enabled')) {
                foreach ($module['dependencies'] as $dependency) {
                    if ($dependency['mode'] != 'optional' && $course->getModule($dependency['id']) == null)
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
        $enabledModules = $course->getModules();
       
        $modulesArr = array();
        foreach ($allModules as $module) {
            $mod = array(
                'id' => $module['id'],
                'name' => $module['name'],
                'dir' => $module['dir'],
                'version' => $module['version'],
                'enabled' => array_key_exists($module['id'], $enabledModules),
                'dependencies' => $module['dependencies']
            );
            $modulesArr[$module['id']] = $mod;
        }

        $globalInfo = array(
            'name' => $course->getName(),
            'theme' => Core::getTheme(),
            'headerLink' => $course->getHeaderLink(),
            'courseFenixLink' => $course->getData("fenixLink"),
            'modules' => $modulesArr
        );
        API::response($globalInfo);
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

        API::response(array('theme' => Core::getTheme(), 'themes' => $themes));
    }
});

//get tabs for system settings
API::registerFunction('settings', 'tabs', function() {
    API::requireAdminPermission();
    $courses = Core::getCourses();
    $coursesTabs = array();
    foreach ($courses as $course) {
        $coursesTabs[] = Settings::buildTabItem($course['name'] . ($course['active'] ? '' : ' - Inactive'), 'settings.courses.course({course:\'' . $course['id'] . '\'})', true);
    }
    $tabs = array(
        Settings::buildTabItem('Courses', 'settings.courses', true, $coursesTabs)
    );
    
    API::response($tabs);
});

//system users settings (manage admins, create invites)
API::registerFunction('settings', 'users', function() {
    API::requireAdminPermission();

    if (API::hasKey('setPermissions')) {
        $perm = API::getValue('setPermissions');
        $prevAdmins = User::getAdmins();
        foreach ($perm['admins'] as $admin) {
            if (!in_array($admin, $prevAdmins))
                User::getUser($admin)->setAdmin(true);
        }

        foreach ($perm['users'] as $user) {
            if (in_array($user, $prevAdmins))
                User::getUser($user)->setAdmin(false);
        }
        return;
    } else if (API::hasKey('updateUsername')) {
        $updateUsername = API::getValue('updateUsername');
        $user = User::getUser($updateUsername['id']);
        if (!$user->exists())
            API::error('A user with id ' . $updateUsername['id'] . ' is not registered.');
        $userWithUsername = User::getUserByUsername($updateUsername['username']);
        if ($userWithUsername != null && $userWithUsername->getId() != $updateUsername['id'])
            API::error('A user with username ' . $updateUsername['username'] . ' is already registered.');
        $user->setUsername($updateUsername['username']);
        return;
    } else if (API::hasKey('createInvite')) {
        $inviteInfo = API::getValue('createInvite');
        if (User::getUser($inviteInfo['id'])->exists())
            API::error('A user with id ' . $inviteInfo['id'] . ' is already registered.');
        else if (User::getUserByUsername($inviteInfo['username']) != null)
            API::error('A user with username ' . $inviteInfo['username'] . ' is already registered.');
        else if (Core::pendingInviteExists($inviteInfo['id']))
            API::error('A user with username ' . $inviteInfo['username'] . ' is already invited.');
        else
            Core::addPendingInvites($inviteInfo);
        return;
    } else if (API::hasKey('deleteInvite')) {
        $invite = API::getValue('deleteInvite');
        
        if (Core::pendingInviteExists($invite))
            Core::removePendingInvites($invite);
        return;
    }
    API::response(array('users' => User::getAllInfo(), 'pendingInvites' => Core::getPendingInvites()));
});

//This updates the student or teachers of the course
//receives list of users to replace/add and updates the DB
function updateUsers($list,$role,$course,$courseId,$replace){
    $updatedUsers=[];
    if ($replace){
        $prevUsers = array_column(Core::$systemDB->selectMultiple("course_user natural join user_role",'id',
                       ["course"=>$courseId, "role"=>$role]), "id");
    }
    $keys = ['username','id', 'name', 'email'];
    if ($role == "Student")
        $keys = array_merge($keys,['campus']);
    $list = preg_split('/[\r]?\n/', $list, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach($list as &$currUser) {
        $splitList = preg_split('/;/', $currUser);
        if (sizeOf($splitList) != sizeOf($keys)) {
            echo "User information was incorrectly formatted";
            return null;
        }
        $currUser = array_combine($keys, $splitList);
        if ($role == "Teacher")
            $currUser["campus"]=null;
        
        $user = User::getUser($currUser['id']);
        if (!$user->exists()) {
            $user->addUserToDB($currUser['name'],$currUser['username'],$currUser['email']);
        } else {
            $user->initialize($currUser['name'],$currUser['username'], $currUser['email']); 
            if ($replace)
                unset($prevUsers[array_search($currUser['id'], $prevUsers)]);
        }

        $courseUser= new CourseUser($currUser['id'],$course);
        if (!$courseUser->exists()) {
            $courseUser->addCourseUserToDB($role, $currUser['campus']);
            $updatedUsers[]= 'New '.$role.' ' . $currUser['id'];
        } else {
            $courseUser->setCampus($currUser['campus']);
            $courseUser->addRole($role);
        }
    }
    if ($replace){
        foreach($prevUsers as $userToDelete){
            $roles = Core::$systemDB->selectMultiple("user_role","role",["id"=>$userToDelete,"course"=>$courseId]);
            if (sizeof($roles)>1){//delete just the role
                Core::$systemDB->delete("user_role",["id"=>$userToDelete,"course"=>$courseId,"role"=>$role]);
                $updatedUsers[]= "Removed role of ".$role." from user ".$userToDelete;
            }
            else{//delete the course_user
                Core::$systemDB->delete("course_user",["id"=>$userToDelete,"course"=>$courseId]);
                $updatedUsers[]= "Deleted ".$role." ".$userToDelete;
            }
            
        }
    }
    return $updatedUsers;
}

//update courseUsers from the Students or Teacher configuration pages
API::registerFunction('settings', 'courseUsers', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    $folder = Course::getCourseLegacyFolder($courseId);
    $file ="";
    $role="";
    if (API::hasKey('role')){
        $role=API::getValue('role');
        if ($role=="Student")
            $file = $folder. '/students.txt';
        else if ($role=="Teacher")
            $file = $folder . '/teachers.txt';
    }
    
    if (API::hasKey('fullUserList') && API::hasKey('role')) {
        $studentList = API::getValue('fullUserList');
        $updatedUsers=updateUsers($studentList,$role,$course,$courseId,true);
        file_put_contents($file, $studentList);
        if ($updatedUsers!==null)
            API::response(["updatedData"=>$updatedUsers ]);
        return;
    }//adding new users and deleting is not available while txt files are still used to store user info
    else if (API::hasKey('newUsers') && API::hasKey('role')) {
        $studentList = API::getValue('newUsers');
        $updatedUsers=updateUsers($studentList,$role,$course,$courseId,false);
        if ($updatedUsers!==null)
            API::response(["updatedData"=>$updatedUsers ]);
        return;
    }else if (API::hasKey('deleteCourseUser')) {
        $userToDelete = API::getValue('deleteCourseUser');
        $courseUser= new CourseUser($userToDelete,$course);
        if ($courseUser->exists()) 
            Core::$systemDB->delete("course_user",["id"=>$userToDelete, "course"=>$courseId]);
        API::response(["updatedData"=>"" ]);
        return;
    }
    
    if (API::hasKey('role')){
        $users = $course->getUsersWithRole($role);
        $usersInfo = [];
        foreach ($users as $userData) {
            $id = $userData['id'];
            $user = new \SmartBoards\CourseUser($id,$course);
            $usersInfo[$id] = array('id' => $id, 'name' => $user->getName(), 'username' => $user->getUsername());
        }
        
        $fileData = @file_get_contents($file);
        if ($fileData===FALSE){$fileData="";}
        else {
            //this fixes cases where special chars appear as question marks
            if(mb_detect_encoding($fileData, "UTF-8", true) === false) {
                $fileData = utf8_encode($fileData);
            }
        }
        API::response(array('userList' => $usersInfo,"file"=>$fileData ));
    }
});

//update list of course levels, from the levels configuration page
API::registerFunction('settings', 'courseLevels', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $levels = Core::$systemDB->selectMultiple("level",'lvlNum,title,minXP',["course"=>$courseId],"lvlNum");
    $numOldLevels=sizeof($levels);
    $folder = Course::getCourseLegacyFolder($courseId);

    if (API::hasKey('levelList')) {
        $keys = array('title', 'minxp');
        $levelInput=API::getValue('levelList');
        $levelList = preg_split('/[\r]?\n/', $levelInput, -1, PREG_SPLIT_NO_EMPTY);  
        $numNewLevels=sizeof($levelList);
        $updatedData=[];
        $availableLevels = array_column($levels, "lvlNum");
        for($i=0;$i<$numNewLevels;$i++){
            //if level doesn't exit, add it to DB 
            $splitInfo =preg_split('/;/', $levelList[$i]);
            if (sizeof($splitInfo) != sizeOf($keys)) {
                echo "Level information was incorrectly formatted";
                return null;
            }
            $level = array_combine($keys, $splitInfo);
            if (!in_array($i, $availableLevels)){
                Core::$systemDB->insert("level",["lvlNum"=>$i,"minXP"=>(int) $level['minxp'],
                                                 "title"=>$level['title'],"course"=>$courseId]);  
                $updatedData[]= "New Level: " .$i;
            }else{
                Core::$systemDB->update("level",["minXP"=>(int) $level['minxp'],"title"=>$level['title']],
                                                ["course"=>$courseId,"lvlNum"=>$i]);
            }
        }
        $lvlDiff=$numOldLevels-$numNewLevels;
        //Delete levels when given a smaller list of new levels
        if ($lvlDiff>0){
            for($i=$numNewLevels;$i<$numOldLevels;$i++){
                Core::$systemDB->delete("level",["lvlNum"=>$i, "course"=>$courseId]);
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

function updateSkills($list,$courseId,$replace, $folder){
    $keys = array('tier', 'name', 'dependencies', 'color', 'xp');
    $skillTree = preg_split('/[\r]?\n/', $list, -1, PREG_SPLIT_NO_EMPTY);
    $skillsInDB= array_column(Core::$systemDB->selectMultiple("skill","name",["course"=>$courseId]),'name');
    $skillsToDelete= $skillsInDB;
    $updatedData=[];
    
    foreach($skillTree as &$skill) {
        $splitInfo =preg_split('/;/', $skill);
        if (sizeOf($splitInfo) != sizeOf($keys)) {
            echo "Skills information was incorrectly formatted";
            return null;
        }
        $skill = array_combine($keys, preg_split('/;/', $skill));
        if (strpos($skill['dependencies'], '|') !== FALSE) {//2 possible dependencies
            $skill['dependencies'] = preg_split('/[|]/', $skill['dependencies']);
            foreach($skill['dependencies'] as &$dependency) {
                $dependency = preg_split('/[+]/', $dependency);
            }
        } else {
            if (strpos($skill['dependencies'], '+') !== FALSE)
                $skill['dependencies'] = array(preg_split('/[+]/', $skill['dependencies']));
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

        if (!in_array($skill["name"], $skillsInDB)){
            //new skill, insert in DB
            try{
                Core::$systemDB->insert("skill",["name"=>$skill["name"],"color"=>$skill['color'],
                                         "page"=>$skill['page'],"tier"=>$skill['tier'],"course"=>$courseId]);
                if (!empty($skill['dependencies'])){
                    for ($i=0; $i<sizeof($skill['dependencies']);$i++){
                        $dep=$skill['dependencies'][$i];
                        Core::$systemDB->insert("skill_dependency",["dependencyNum"=>$i,"skillName"=>$skill["name"],"course"=>$courseId,
                                                                    "dependencyA"=>$dep[0],"dependencyB"=>$dep[1]]);
                    }
                }
                $updatedData[]= "New skill: ".$skill["name"];
            } catch (PDOException $e){
                echo "Error: Cannot add skills with tier=".$skill["tier"]." since it doesn't exist.";
                return;
            }
        }else{
            //skill that exists in DB, update its info
            Core::$systemDB->update("skill",["color"=>$skill['color'],"page"=>$skill['page'],"tier"=>$skill['tier']],
                                                            ["name"=>$skill["name"],"course"=>$courseId]);
            //update dependencies
            $dependenciesinDB= array_column(Core::$systemDB->selectMultiple("skill_dependency","dependencyNum",["skillName"=>$skill["name"],"course"=>$courseId]),"dependencyNum");

            $numOldDependencies=sizeof($dependenciesinDB);
            $numNewDependencies=sizeof($skill['dependencies']);
            for($i=0;$i<$numNewDependencies;$i++){
                $dep=$skill['dependencies'][$i];
                if (!in_array($i, $dependenciesinDB)){
                    Core::$systemDB->insert("skill_dependency",["dependencyNum"=>$i,"skillName"=>$skill["name"],"course"=>$courseId,
                                                                "dependencyA"=>$dep[0],"dependencyB"=>$dep[1]]);
                }else{
                    Core::$systemDB->update("skill_dependency",["dependencyA"=>$dep[0],"dependencyB"=>$dep[1]],
                                ["dependencyNum"=>$i,"skillName"=>$skill["name"],"course"=>$courseId]);
                }
            }
            $depDiff=$numOldDependencies-$numNewDependencies;

            if ($depDiff>0){
                for($i=$numNewDependencies;$i<$numOldDependencies;$i++){
                    Core::$systemDB->delete("skill_dependency",["dependencyNum"=>$i,"skillName"=>$skill["name"],"course"=>$courseId]);

                }
            }
            unset($skillsToDelete[array_search($skill['name'], $skillsToDelete)]);
        }
    }
    //delete skills that wheren't in the imported data
    if ($replace){
        foreach ($skillsToDelete as $skill){
            Core::$systemDB->delete("skill",["name"=>$skill,"course"=>$courseId]);
            $updatedData[]= "Deleted skill: ".$skill;
        } 
        file_put_contents($folder . '/tree.txt', $list);
    }
    
    API::response(["updatedData"=>$updatedData ]);
    return;
}
//update list of skills of the course skill tree, from the skills configuration page
API::registerFunction('settings', 'courseSkills', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $folder = Course::getCourseLegacyFolder($courseId);
    
    if (API::hasKey('skillsList')) {
        updateSkills(API::getValue('skillsList'), $courseId, true, $folder);
        return;
    }if (API::hasKey('tiersList')) {
        $keys = array('tier', 'reward');
        $tiers = preg_split('/[\r]?\n/', API::getValue('tiersList'), -1, PREG_SPLIT_NO_EMPTY);
        $tiersInDB= array_column(Core::$systemDB->selectMultiple("skill_tier","tier",["course"=>$courseId]),'tier');
        $tiersToDelete= $tiersInDB;
        $updatedData=[];

        foreach($tiers as &$tier) {
            $splitInfo =preg_split('/;/', $tier);
            if (sizeOf($splitInfo) != sizeOf($keys)) {
                echo "Tier information was incorrectly formatted";
                return null;
            }
            $tier = array_combine($keys, preg_split('/;/', $tier));
            if (!in_array($tier["tier"], $tiersInDB)){
                Core::$systemDB->insert("skill_tier",["tier"=>$tier["tier"],"course"=>$courseId,"reward"=>$tier["reward"]]);
                $updatedData[]= "Added Tier ".$tier["tier"];
            }else{
                Core::$systemDB->update("skill_tier",["reward"=>$tier["reward"]],["tier"=>$tier["tier"],"course"=>$courseId]);           
                unset($tiersToDelete[array_search($tier['tier'], $tiersToDelete)]);
            }
        }
        foreach ($tiersToDelete as $tierToDelete){
            Core::$systemDB->delete("skill_tier",["tier"=>$tierToDelete,"course"=>$courseId]);
            $updatedData[]= "Deleted Tier ".$tierToDelete." and all its skills. The Skill List may need to be updated";
        }
        API::response(["updatedData"=>$updatedData ]);
        return;
    }/*else if (API::hasKey('newSkillsList')) {
        updateSkills(API::getValue('newSkillsList'), $courseId, false, $folder);
        return;
    }*/
    $tierText="";
    $tiers = Core::$systemDB->selectMultiple("skill_tier",'tier,reward',["course"=>$courseId],"tier");
    $tiersAndSkills=[];
    foreach ($tiers as &$t){
        $skills = Core::$systemDB->selectMultiple("skill",'tier,name,color',["course"=>$courseId, "tier"=>$t["tier"]],"name");
        $tiersAndSkills[$t["tier"]]=array_merge($t,["skills" => $skills]);
        $tierText.=$t["tier"].';'.$t["reward"]."\n";
    }
    foreach ($tiersAndSkills as &$t){
        foreach ($t["skills"] as &$s){
            $deps=Core::$systemDB->selectMultiple("skill_dependency",'*',["course"=>$courseId,"skillName"=>$s["name"]]);
            foreach ($deps as $d){
                $s['dependencies'][]=[$d["dependencyA"],$d["dependencyB"]];
            }
        }
    }
    $file = @file_get_contents($folder . '/tree.txt');
    if ($file===FALSE){$file="";}
    API::response(array('skillsList' => $tiersAndSkills, "file"=>$file, "file2"=>$tierText));
});

//update list of badges for course, from the badges configuration page
API::registerFunction('settings', 'courseBadges', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $folder = LEGACY_DATA_FOLDER;// Course::getCourseLegacyFolder($courseId);
    
    if (API::hasKey('badgesList')) {
        $keys = ['name', 'description', 'desc1', 'desc2', 'desc3', 'xp1', 'xp2', 'xp3', 
            'countBased', 'postBased', 'pointBased','count1', 'count2', 'count3'];
        $achievements = preg_split('/[\r]?\n/', API::getValue('badgesList'), -1, PREG_SPLIT_NO_EMPTY);
        $badgesInDB = array_column(Core::$systemDB->selectMultiple("badge",'name',["course"=>$courseId]),"name");
        $badgesToDelete = $badgesInDB;
        $totalLevels = 0;
        $updatedData=[];

        foreach($achievements as &$achievement) {
            $achievement = array_combine($keys, preg_split('/;/', $achievement));
            $maxLevel= empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
            //if badge doesn't exit, add it to DB
            $badgeData = ["maxLvl"=>$maxLevel,"name"=>$achievement['name'],
                          "course"=>$courseId,"description"=>$achievement['description'],
                          "isExtra"=> ($achievement['xp1'] < 0),
                          "isBragging"=>($achievement['xp1'] == 0),
                          "isCount"=>($achievement['countBased'] == 'True'),
                          "isPost"=>($achievement['postBased'] == 'True'),
                          "isPoint"=>($achievement['pointBased'] == 'True')];
            if (!in_array($achievement['name'],$badgesInDB)){
            //if (empty(Core::$systemDB->select("badge","*",["name"=>$achievement['name'],"course"=>$courseId]))){
                Core::$systemDB->insert("badge",$badgeData);
                for ($i=1;$i<=$maxLevel;$i++){
                    Core::$systemDB->insert("badge_level",["level"=>$i,"course"=>$courseId,
                                            "xp"=>abs($achievement['xp'.$i]),
                                            "description"=>$achievement['desc'.$i],
                                            "progressNeeded"=>$achievement['count'.$i],
                                            "badgeName"=>$achievement['name']]);
                }  
                $updatedData[]= "New badge: ".$achievement["name"];
            }else{
                Core::$systemDB->update("badge",$badgeData,["course"=>$courseId,"name"=>$achievement["name"]]);
                
                for ($i=1;$i<=$maxLevel;$i++){
                    Core::$systemDB->update("badge_level",["xp"=>abs($achievement['xp'.$i]),
                                            "description"=>$achievement['desc'.$i],
                                            "progressNeeded"=>$achievement['count'.$i]],
                            ["course"=>$courseId,"badgeName"=>$achievement["name"],"level"=>$i]);
                }
                unset($badgesToDelete[array_search($achievement['name'], $badgesToDelete)]);
            }
            $totalLevels += $maxLevel; 
        }
        foreach ($badgesToDelete as $badgeToDelete){
            Core::$systemDB->delete("badge",["course"=>$courseId,"name"=>$badgeToDelete]);
            $updatedData[]= "Deleted badge: ".$badgeToDelete;
        }
        Core::$systemDB->update("course",["numBadges"=>$totalLevels],["id"=>$courseId]);
        
        file_put_contents($folder . '/achievements.txt',API::getValue('badgesList'));
        API::response(["updatedData"=>$updatedData ]);
        return;
    }
    
    $badges = Core::$systemDB->selectMultiple("badge",'*',["course"=>$courseId],"name");
    foreach($badges as &$badge){
        $levels = Core::$systemDB->selectMultiple("badge_level",'*',["course"=>$courseId, "badgeName"=>$badge["name"]],"level");
        foreach ($levels as $level){
            $badge["levels"][]=$level;
        }
    }
    
    $file = @file_get_contents($folder . '/achievements.txt');
    if ($file===FALSE){$file="";}
    API::response(array('badgesList' => $badges, "file"=>$file));
});

API::registerFunction('settings', 'createCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseName', 'creationMode');
    if (API::getValue('creationMode') == 'similar')
        API::requireValues('copyFrom');

    Course::newCourse(API::getValue('courseName'), (API::getValue('creationMode') == 'similar') ? API::getValue('copyFrom') : null);
});

API::registerFunction('settings', 'deleteCourse', function() {
    API::requireAdminPermission();
    API::requireValues('course');

    $course = API::getValue('course');

    Course::deleteCourse($course);
});

/*register_shutdown_function(function() {
    echo '<pre>';
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
    echo '</pre>';
    //print_r(\SmartBoards\Course::$coursesDb);
    echo 'before' . \SmartBoards\Course::$coursesDb->numQueriesExecuted();
    echo 'after' . \SmartBoards\Course::$coursesDb->numQueriesExecuted();
});*/

API::processRequest();
?>