<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include('classes/ClassLoader.class.php');

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;
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

API::registerFunction('core', 'getUserInfo', function() {
    $user = Core::getLoggedUser();
    $userInfo = $user->getData();
    $userInfo['username'] = $user->getUsername();
    $userInfo['authenticationService'] = User::getUserAuthenticationService($userInfo['username']);
    API::response(array('userInfo' => $userInfo));
});

//------------------- main page

API::registerFunction('core', 'getUserActiveCourses', function() {
    $user = Core::getLoggedUser();

    $coursesId = $user->getCourses();
    $courses=[];
    foreach($coursesId as $cid){
        $course = Core::getCourse($cid);
        if ($course["isVisible"]){
            $courses[]=$course;
        }
    }
    array_combine(array_column($courses,'id'), $courses);

    API::response(array('userActiveCourses' => $courses));
});

//-------------------Course List related

//return a list of courses that the user is allowed to see
API::registerFunction('core', 'getCoursesList', function() {
    $user = Core::getLoggedUser();
    
    if ($user->isAdmin()) {
        $courses = Core::getCourses();
        $myCourses = false;
        //get number of students per course
        foreach($courses as &$course){
            $cOb = Course::getCourse($course['id'], false);
            $students = sizeof($cOb->getUsersWithRole("Student"));
            $course['nstudents'] = $students;
        }
    }
    else {
        $coursesId = $user->getCourses();
        
        $courses=[];
        foreach($coursesId as $cid){
            $course = Core::getCourse($cid);
            if ($course["isVisible"]){
                $courses[]=$course;
            }
        }
        array_combine(array_column($courses,'id'),$courses);
        $myCourses = true;
    }
    API::response(array('courses' => $courses, 'myCourses' => $myCourses));
});

API::registerFunction('core', 'createCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseName', 'creationMode', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );
    if (API::getValue('creationMode') == 'similar')
        API::requireValues('copyFrom');

    Course::newCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'),(API::getValue('creationMode') == 'similar') ? API::getValue('copyFrom') : null);
});
API::registerFunction('core', 'editCourse', function() {
    //may be used inside course settingd on 'this course' page
    //so users with teacher role can edit information about the course if needed
    API::requireCourseAdminPermission();
    API::requireValues('course','courseName', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        $course->editCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'));
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});
API::registerFunction('core', 'deleteCourse', function() {
    API::requireAdminPermission();
    API::requireValues('course');

    $course_id = API::getValue('course');
    $course = Course::getCourse($course_id , false);
    if($course != null){
        Course::deleteCourse($course_id);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});
//set course Visibility
API::registerFunction('core', 'setCoursesvisibility', function(){
    API::requireAdminPermission();
    API::requireValues('course_id');
    API::requireValues('visibility');

    
    $course_id = API::getValue('course_id');
    $visibility = API::getValue('visibility');
    
    $cOb = Course::getCourse($course_id, false);
    if($cOb != null){
        $cOb->setVisibleState($visibility);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});
//set course ative
API::registerFunction('core', 'setCoursesActive', function(){
    API::requireAdminPermission();
    API::requireValues('course_id');
    API::requireValues('active');

    
    $course_id = API::getValue('course_id');
    $active = API::getValue('active');
    
    $cOb = Course::getCourse($course_id, false);
    if($cOb != null){
        $cOb->setActiveState($active);
    }
    else{
        API::error("There is no course with that id: ". $course_id);
    }
});

API::registerFunction('core', 'importCourses', function(){
    API::requireAdminPermission();
    API::requireValues('file');

    $nCourses = 0; //delete later
    //$nCourses = Course::importCourses(API::getValue('file')); //uncomment after import is finished
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
    if($course != null){
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
            //se views tiver active
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
    }
    else{
        API::error("There is no course with that id: ". $courseId);
    }
});


//see and/or set landing page for a role //not used anymore
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

//not used anymore
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
    if($course != null){
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
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

//course main settings page
//not used -> must be integrated on the this course page
API::registerFunction('settings', 'courseGlobal', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if($course != null){
        $globalInfo = array(
            'name' => $course->getName(),
            'theme' => $GLOBALS['theme'],
        );
        API::response($globalInfo); 
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

API::registerFunction('settings', 'courseModules', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if($course != null){
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
                    if (Core::$systemDB->select("course_module",["moduleId"=>$moduleId, "isEnabled"=>1],"count(*)")==1){
                        //only drop the tables of the module data if this is the last course where it is enabled
                        $moduleObject->dropTables($moduleId);//deletes tables associated with the module
                    }else{
                        $moduleObject->deleteDataRows();
                    }
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
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
    
});


API::registerFunction('settings', 'getModuleConfigInfo', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if($course != null){
        $module = $course->getModule(API::getValue('module'));

        if($module != null){
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
        }
        else{
            API::error("There is no module with that id: ". API::getValue('module'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }

});

API::registerFunction('settings', 'saveModuleConfigInfo', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if($course != null){
        $module = $course->getModule(API::getValue('module'));

        if($module != null){
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
        }
        else{
            API::error("There is no module with that id: ". API::getValue('module'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
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

    //edit do usernma 
    // if (API::hasKey('updateUsername')) {
    //     $updateUsername = API::getValue('updateUsername');
    //     $user = User::getUser($updateUsername['id']);
    //     if (!$user->exists())
    //         API::error('A user with id ' . $updateUsername['id'] . ' is not registered.');
    //     $userWithUsername = User::getUserByUsername($updateUsername['username']);
    //     if ($userWithUsername != null && $userWithUsername->getId() != $updateUsername['id'])
    //         API::error('A user with username ' . $updateUsername['username'] . ' is already registered.');
    //     $user->setUsername($updateUsername['username']);
    //     return;
    // }

    $users = User::getAllInfo(); //get all users
    foreach($users as &$user){
        $uOb = User::getUser($user['id']);
        $coursesIds = $uOb->getCourses();
        $courses = [];
        foreach ($coursesIds as $id) {
            $cOb = Course::getCourse($id, false);
            $c = [ "id" => $id, "name" => $cOb->getName()];
            $courses[] = $c;
        }
        $lastLogins = $uOb->getSystemLastLogin();

        $user['ncourses'] = sizeof($courses);
        $user['courses'] = $courses;
        $user['lastLogin'] = $lastLogins;
        $user['username'] = $uOb->getUsername();
        $user['authenticationService'] = User::getUserAuthenticationService($user['username']);
    }
        
    API::response(array('users' => $users));
});

API::registerFunction('core', 'setUserAdmin', function(){
    API::requireAdminPermission();
    API::requireValues('user_id');
    API::requireValues('isAdmin');

    $user_id = API::getValue('user_id');
    $isAdmin = API::getValue('isAdmin');
    
    $uOb = User::getUser($user_id);
    if($uOb != null){
        $uOb->setAdmin($isAdmin);
    }
    else{
        API::error("There is no user with that id: ". API::getValue('user_id'));
    }
});

API::registerFunction('core', 'setUserActive', function(){
    API::requireAdminPermission();
    API::requireValues('user_id');
    API::requireValues('isActive');

    $user_id = API::getValue('user_id');
    $active = API::getValue('isActive');
    
    $uOb = User::getUser($user_id);
    if($uOb != null){
        $uOb->setActive($active);
    }
    else{
        API::error("There is no user with that id: ". API::getValue('user_id'));
    }
});

API::registerFunction('core', 'deleteUser', function() {
    API::requireAdminPermission();
    API::requireValues('user_id');

    $user = API::getValue('user_id');
    $ubj = User::getUser($user); 
    if($ubj->exists()){
        User::deleteUser($user);
    }
    else{
        API::error("There is no user with that id: ". $user);
    }
    
});
API::registerFunction('core', 'createUser', function() {
    API::requireAdminPermission();
    API::requireValues('userHasImage','userName', 'userAuthService', 'userStudentNumber', 'userEmail','userUsername', 'userIsActive', 'userIsAdmin');
    $user = User::getUserByStudentNumber(API::getValue('userStudentNumber'));
    if ($user == null) {
        $id = User::addUserToDB(API::getValue('userName'),API::getValue('userUsername'),API::getValue('userAuthService'),API::getValue('userEmail'),API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));
        if(API::getValue('userHasImage') == 'true'){
            API::requireValues('userImage');
            $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
            User::saveImage($img, $id);
        }
    } else {
        API::error("There is already a student registered with the student number: ". API::getValue('userStudentNumber'));
    }
});
API::registerFunction('core', 'editUser', function() {
    API::requireAdminPermission();
    API::requireValues('userHasImage','userId','userName', 'userAuthService', 'userStudentNumber', 'userEmail','userUsername', 'userIsActive', 'userIsAdmin');

    $user = new User(API::getValue('userId'));
    if($user->exists()){
        $user->editUser(API::getValue('userName'),API::getValue('userUsername'),API::getValue('userAuthService'),API::getValue('userEmail'),API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));

        if(API::getValue('userHasImage') == 'true'){
            API::requireValues('userImage');
            $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
            User::saveImage($img, API::getValue('userId'));
        }
    }
    else{
        API::error("There is no user with that id: ". API::getValue('userId'));
    }
    
});
API::registerFunction('core', 'importUser', function(){
    API::requireAdminPermission();
    API::requireValues('file');

    $nUsers = 0; //delete later
    //$nUsers = User::importUsers(API::getValue('file')); //uncomment after import is finished
    API::response(array('nUsers' => $nUsers));
});
API::registerFunction('core', 'exportUsers', function(){
    API::requireAdminPermission();
    $users = User::exportUsers();
    API::response(array('users' => $users));
});

//------------------Users inside the course

//This updates the student or teachers of the course
//receives list of users to replace/add and updates the DB
// function updateUsers($list,$role,$course,$courseId,$replace){
//     $updatedUsers=[];
//     //vai buscar o role
//     $roleId = Course::getRoleId($role, $courseId);
//     if ($replace){
//         $prevUsers = array_column(Core::$systemDB->selectMultiple("course_user natural join user_role",
//                        ["course"=>$courseId, "role"=>$roleId],'id'), "id");
//     }
//     //lista de atributos do user
//     $keys = ['username','id', 'name', 'email'];
//     if ($role == "Student")
//         $keys = array_merge($keys,['campus']);
//     //list de users (por linha)
//     $list = preg_split('/[\r]?\n/', $list, -1, PREG_SPLIT_NO_EMPTY);
//     //para cada user
//     foreach($list as &$currUser) {
//         //lista de atributos do user
//         $splitList = preg_split('/;/', $currUser);
//         if (sizeOf($splitList) != sizeOf($keys)) {
//             echo "User information was incorrectly formatted";
//             return null;
//         }
//         //constroi user key-value
//         $currUser = array_combine($keys, $splitList);
//         if ($role == "Teacher")
//             $currUser["campus"]=null;
//         //cria user a partr do id dado
//         $user = User::getUser($currUser['id']);
//         if (!$user->exists()) {
//             //se nao user existir cria na db
//             $user->addUserToDB($currUser['name'],$currUser['username'],$currUser['email']);
//         } else {
//             //se user existir da-lhe update
//             $user->initialize($currUser['name'],$currUser['username'], $currUser['email']); 
//             if ($replace)
//                 unset($prevUsers[array_search($currUser['id'], $prevUsers)]);
//         }
//         //cria um course user
//         $courseUser= new CourseUser($currUser['id'],$course);
//         if (!$courseUser->exists()) {
//             //se ainda nao existir adiciona a db
//             $courseUser->addCourseUserToDB($roleId, $currUser['campus']);
//             $updatedUsers[]= 'New '.$role.' ' . $currUser['id'];
//         } else {
//             $courseUser->setCampus($currUser['campus']);
//             if ($courseUser->addRole($role)===true)
//                 $updatedUsers[]= "Added role of ".$role." to user ".$currUser['id'];
//         }
//     }
//     if ($replace){
//         foreach($prevUsers as $userToDelete){
//             $roles = Core::$systemDB->selectMultiple("user_role",["id"=>$userToDelete,"course"=>$courseId],"role");
//             if (sizeof($roles)>1){//delete just the role
//                 Core::$systemDB->delete("user_role",["id"=>$userToDelete,"course"=>$courseId,"role"=>$roleId]);
//                 $updatedUsers[]= "Removed role of ".$role." from user ".$userToDelete;
//             }
//             else{//delete the course_user
//                 Core::$systemDB->delete("course_user",["id"=>$userToDelete,"course"=>$courseId]);
//                 $updatedUsers[]= "Deleted ".$role." ".$userToDelete;
//             }
            
//         }
//     }
//     return $updatedUsers;
// }
API::registerFunction('course', 'courseRoles', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $roles = $course->getRoles("name");
        API::response(["courseRoles"=> $roles ]);
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
    
});
API::registerFunction('course', 'removeUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $user_id = API::getValue('user_id');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUser = new CourseUser($user_id,$course);
        if ($courseUser->exists()) {
            Core::$systemDB->delete("course_user",["id"=>$user_id, "course"=>$courseId]);
        }
        else{
            API::error("There is no user with that id: ". API::getValue('user_id'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
   
});
API::registerFunction('course', 'editUser', function() {
    API::requireAdminPermission();
    API::requireValues('userHasImage','userId','userName', 'userStudentNumber', 'userEmail', 'userCampus', 'course', 'userRoles');

    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUser = new CourseUser(API::getValue('userId'), $course);
        $user = new User(API::getValue('userId'));
        if ($courseUser->exists()) {
            $user->setName(API::getValue('userName'));
            $user->setEmail(API::getValue('userEmail'));
            $user->setStudentNumber(API::getValue('userStudentNumber'));
            $user->setNickname(API::getValue('userNickname'));
            $user->setUsername(API::getValue('userUsername'));
            $user->setAuthenticationService(API::getValue('userAuthService'));

            $courseUser->setCampus(API::getValue('userCampus'));
            $courseUser->setRoles(API::getValue('userRoles'));

            if(API::getValue('userHasImage') == 'true'){
                API::requireValues('userImage');
                $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
                User::saveImage($img, API::getValue('userId'));
            }
        }
        else{
            API::error("There is no user with that id: ". API::getValue('user_id') . "on the course:" . API::getValue('course'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }

});

API::registerFunction('course', 'createUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        API::requireValues('userHasImage','userCampus', 'userUsername', 'userAuthService','userName', 'userStudentNumber', 'userEmail', 'userRoles');
        $userName = API::getValue('userName');
        $userEmail = API::getValue('userEmail');
        $userStudentNumber = API::getValue('userStudentNumber');
        $userNickname = API::getValue('userNickname');
        $userRoles = API::getValue('userRoles');
        $userCampus = API::getValue('userCampus');
        $userUsername = API::getValue('userUsername');
        $userAuthService = API::getValue('userAuthService');

        //verifies if user exits on the system
        $user = User::getUserByStudentNumber($userStudentNumber);
        if ($user == null) {
            User::addUserToDB($userName,$userUsername,$userAuthService,$userEmail,$userStudentNumber, $userNickname, 0, 1);
            $user = User::getUserByStudentNumber($userStudentNumber);
            $courseUser = new CourseUser($user->getId(),$course);
            $courseUser->addCourseUserToDB(null, $userCampus);
            $courseUser->setRoles(API::getValue('userRoles'));
            if(API::getValue('userHasImage') == 'true'){
                API::requireValues('userImage');
                $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
                User::saveImage($img, $user->getId());
            }

        } else {
            API::error("There is already a student registered with the student number: ". $userStudentNumber);
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});
//add existing user to course
API::registerFunction('course', 'addUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        API::requireValues('users', 'role');
        $users = API::getValue('users');
        $role = API::getValue('role');
        foreach ($users as $userData) {
            //is user valid?
            $user = User::getUserByStudentNumber($userData['studentNumber']);
            if($user->getId() == $userData['id']){
                $courseUser = new CourseUser($userData['id'],$course);
                if (!$courseUser->exists()) {
                    $courseUser->addCourseUserToDB();
                    $courseUser->addRole($role);
                }
            }
            else{
                echo("not a valid user");
            }
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

//get users not registered on the course
API::registerFunction('course', 'notCourseUsers', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUsers = $course->getUsers();
        $systemUsers = User::getAllInfo();

        $courseUsersInfo = [];
        foreach ($courseUsers as $userData) {
            $courseUsersInfo[] = array(
                'id' => $userData['id'], 
                'name' => $userData['name'], 
                'studentNumber' => $userData['studentNumber']);
        }
        $systemUsersInfo = [];
        foreach ($systemUsers as $userData) {
            $systemUsersInfo[] = array(
                'id' => $userData['id'], 
                'name' => $userData['name'], 
                'studentNumber' => $userData['studentNumber']);
        }
        
        function udiffCompare($a, $b){
            return $a['id'] - $b['id'];
        }
        $notCourseUsers = array_udiff($systemUsersInfo, $courseUsersInfo, 'udiffCompare');
        
        API::response(array('notCourseUsers'=> $notCourseUsers)); 
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
    
    
    
});

//get courseUsers 
API::registerFunction('course', 'courseUsers', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    $role=API::getValue('role');
    if($course != null){
        // $folder = Course::getCourseLegacyFolder($courseId);
        // $file ="";
        
        // if (API::hasKey('role')){
        //     if ($role=="Student")
        //         $file = $folder. '/students.txt';
        //     else if ($role=="Teacher")
        //         $file = $folder . '/teachers.txt';
        // }
        
        // if (API::hasKey('fullUserList') && API::hasKey('role')) {
        //     $studentList = API::getValue('fullUserList');
        //     $updatedUsers=updateUsers($studentList,$role,$course,$courseId,true);
        //     file_put_contents($file, $studentList);
        //     if ($updatedUsers!==null)
        //         API::response(["updatedData"=>$updatedUsers ]);
        //     return;
        // }//adding new users and deleting is not available while txt files are still used to store user info
    
        if (API::hasKey('role')){
            if ($role == "allRoles") {
                $users = $course->getUsers();
            }
            else{
                $users = $course->getUsersWithRole($role);
            }
            
            $usersInfo = [];
            //for security measures we send only what is needed
            foreach ($users as $userData) {
                $id = $userData['id'];
                $user = new \GameCourse\CourseUser($id,$course);
                $usersInfo[] = array(
                    'id' => $id, 
                    'name' => $user->getName(), 
                    'nickname' => $user->getNickname(),
                    'studentNumber' => $user->getStudentNumber(),
                    'roles' => $user->getRolesNames(),
                    'campus' => $user->getCampus(),
                    'email' => $user->getEmail(),
                    'lastLogin' => $user->getLastLogin(),
                    'username' => $user->getUsername(),
                    'authenticationService' => User::getUserAuthenticationService($user->getUsername())
                );
            }
            
            // $fileData = @file_get_contents($file);
            // if ($fileData===FALSE){$fileData="";}
            // else {
            //     //this fixes cases where special chars appear as question marks
            //     if(mb_detect_encoding($fileData, "UTF-8", true) === false) {
            //         $fileData = utf8_encode($fileData);
            //     }
            // }
            API::response(array('userList' => $usersInfo));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
    
    
});
API::registerFunction('course', 'importUser', function(){
    API::requireCourseAdminPermission();
    API::requireValues('file');

    $nUsers = 0; //delete later
    //$nUsers = CourseUser::importCourseUsers(API::getValue('file')); //uncomment after import is finished
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
