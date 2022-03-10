<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\ModuleLoader;
use GameCourse\RuleSystem;
use GameCourse\User;
use GameCourse\Views\ViewHandler;
use GameCourse\Views\Views;
use VirtualCurrency\VirtualCurrency;

$MODULE = 'course';


/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get course from database.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourse', function () {
    API::requireCoursePermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);
    API::response(array('course' => $course->getData()));
});

/**
 * Get course w/ extra information such as active pages and landing page.
 * // FIXME: landing page not being set (I think)
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseWithInfo', function () {
    API::requireCoursePermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $courseUser = $course->getLoggedUser();
    $courseUser->refreshActivity();

    $activePages = $course->getPages(true);

    // Filtering pages according to user role
    // If page isn't supposed to be shown to any of the user roles, then remove
    $filteredPages = [];
    foreach ($activePages as $page) {
        $aspects = Views::getViewByViewId($page["viewId"]);
        $roleType = Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $aspects[0]["viewId"], "course" => $courseId], "roleType");

        $showPage = false;
        foreach ($aspects as $aspect) {
            $viewerRole = Views::splitRole($aspect["role"])["viewerRole"];
            if ($roleType == "ROLE_SINGLE") {
                if (($viewerRole == "Default" || $courseUser->hasRole($viewerRole))) {
                    $showPage = true;
                    break;
                }

            } else if ($roleType == "ROLE_INTERACTION") {
                $userRole = Views::splitRole($aspect["role"])["userRole"];
                if (($viewerRole == "Default" && ($courseUser->hasRole($userRole) || $userRole == "Default"))
                    || ($viewerRole != "Default" && $courseUser->hasRole($viewerRole))) {
                    $showPage = true;
                    break;
                }
            }
        }
        if ($showPage) $filteredPages[] = $page;
    }
    $activePages = $filteredPages;

    // FIXME: landing page not being set (I think) -> set for each role
    $landingPage = $course->getLandingPage();
    $landingPageInfo = Core::$systemDB->select("page", ["name" => $landingPage], "id, viewId");
    $landingPageID = $landingPageInfo["id"];
    $landingPageType = Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $landingPageInfo["viewId"], "course" => $courseId], "roleType");

    API::response(array(
        'course' => $course->getData(),
        'activePages' => $activePages,
    ));
});

/**
 * Get course global info.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseGlobal', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $globalInfo = array(
        'name' => $course->getName(),
        'theme' => $GLOBALS['theme'],
        'activeUsers' => count($course->getUsers()),
        'awards' => $course->getNumAwards(),
        'participations' => $course->getNumParticipations()
    );
    API::response($globalInfo);
});

/**
 * Get name of all course roles.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseRoles', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $roles = $course->getRoles("name");
    API::response(["courseRoles" => $roles]);
});

/**
 * Get resources of active modules in course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseResources', function () {
    API::requireCoursePermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    API::response(array('resources' => $course->getModulesResources()));
});

/**
 * Get contents of course data folder.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseDataFolderContents', function () {
    API::requireCoursePermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $folder = Course::getCourseDataFolder($courseId);
    API::response(array('contents' => Course::getDataFoldersContents($folder)));
});



/*** --------------------------------------------- ***/
/*** ------------ Course Manipulation ------------ ***/
/*** --------------------------------------------- ***/

/**
 * Create a new course on the system.
 *
 * @param string $courseName
 * @param string $creationMode
 * @param string $courseShort
 * @param string $courseYear
 * @param string $courseColor
 * @param int $courseIsVisible
 * @param int $courseIsActive
 * @param int $copyFrom (optional)
 */
API::registerFunction($MODULE, 'createCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseName', 'creationMode', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );

    if (API::getValue('creationMode') == 'similar')
        API::requireValues('copyFrom');

    $course = Course::newCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'),(API::getValue('creationMode') == 'similar') ? API::getValue('copyFrom') : null);
    API::response(array('course' => $course->getData()));
});

/**
 * Edit an existing course.
 *
 * @param int $courseId
 * @param string $courseName
 * @param string $courseShort
 * @param string $courseYear
 * @param string $courseColor
 * @param int $courseIsVisible
 * @param int $courseIsActive
 */
API::registerFunction($MODULE, 'editCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseId','courseName', 'courseShort', 'courseYear', 'courseColor', 'courseIsVisible', 'courseIsActive' );

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $course->editCourse(API::getValue('courseName'),API::getValue('courseShort'),API::getValue('courseYear'),API::getValue('courseColor'), API::getValue('courseIsVisible'), API::getValue('courseIsActive'));
});

/**
 * Delete an existing course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'deleteCourse', function() {
    API::requireAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    Course::deleteCourse($courseId);
});

/**
 * Set course visibility.
 *
 * @param int $courseId
 * @param int $isVisible
 */
API::registerFunction($MODULE, 'setCourseVisibility', function(){
    API::requireAdminPermission();
    API::requireValues('courseId', 'isVisible');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $isVisible = API::getValue('isVisible');
    $course->setVisibleState($isVisible);
});

/**
 * Set course active state.
 *
 * @param int $courseId
 * @param int $isActive
 */
API::registerFunction($MODULE, 'setCourseActiveState', function(){
    API::requireAdminPermission();
    API::requireValues('courseId', 'isActive');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $isActive = API::getValue('isActive');
    $course->setActiveState($isActive);
});



/*** --------------------------------------------- ***/
/*** -------------- Import / Export -------------- ***/
/*** --------------------------------------------- ***/

/**
 * Import courses into the system.
 *
 * @param $file
 * @param bool $replace (optional)
 */
API::registerFunction($MODULE, 'importCourses', function(){
    API::requireAdminPermission();
    API::requireValues('file');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nrCourses = Course::importCourses($fileContents, $replace);
    API::response(array('nrCourses' => $nrCourses));
});

/**
 * Export courses from the system.
 *
 * Pass an id value for a specific course, or null for all courses.
 * Options specify what you want to export with the course: users,
 * awards and modules.
 *
 * @param int $courseId (optional)
 * @param $options (optional)
 */
API::registerFunction($MODULE, 'exportCourses', function(){
    API::requireAdminPermission();

    $courseId = API::getValue('courseId');
    $options = API::getValue('options');
    $courses = Course::exportCourses($courseId, $options);
    API::response(array('courses' => $courses));
});



/*** --------------------------------------------- ***/
/*** --------------- Course Users ---------------- ***/
/*** --------------------------------------------- ***/

/**
 * Get users registered on the course.
 * A specific role can be set and only users with that
 * role will be returned.
 *
 * @param int $courseId
 * @param string $role (optional)
 */
API::registerFunction($MODULE, 'getCourseUsers', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    if (API::hasKey('role')) {
        $role = API::getValue('role');
        $users = $course->getUsersWithRole($role, false);

    } else $users = $course->getUsers(false);

    $usersInfo = [];

    // For security reasons, we only send what is needed
    foreach ($users as $userData) {
        $id = $userData['id'];
        $user = new CourseUser($id, $course);
        $usersInfo[] = array(
            'id' => $id,
            'name' => $user->getName(),
            'nickname' => $user->getNickname(),
            'studentNumber' => $user->getStudentNumber(),
            'roles' => $user->getRolesNames(),
            'major' => $user->getMajor(),
            'email' => $user->getEmail(),
            'lastLogin' => $user->getLastLogin(),
            'username' => $user->getUsername(),
            'authenticationService' => User::getUserAuthenticationService($user->getUsername()),
            'isActive' => $user->isActive(),
            'hasImage' => User::hasImage($user->getUsername())
        );
    }

    API::response(array('userList' => $usersInfo));
});

/**
 * Get users not registered on the course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseNonUsers', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $courseUsers = $course->getUsers();
    $systemUsers = User::getAllInfo();

    $courseUsersInfo = [];
    foreach ($courseUsers as $userData) {
        $courseUsersInfo[] = array(
            'id' => $userData['id'],
            'name' => $userData['name'],
            'studentNumber' => $userData['studentNumber']
        );
    }

    $systemUsersInfo = [];
    foreach ($systemUsers as $userData) {
        $systemUsersInfo[] = array(
            'id' => $userData['id'],
            'name' => $userData['name'],
            'studentNumber' => $userData['studentNumber']
        );
    }

    $notCourseUsers = array_udiff($systemUsersInfo, $courseUsersInfo, function ($a, $b) {
        return $a['id'] - $b['id'];
    });

    $notCourseUsersArray = [];
    foreach ($notCourseUsers as $notUser) {
        $notCourseUsersArray[] = $notUser;
    }

    API::response(array('notCourseUsers' => $notCourseUsersArray));
});

/**
 * Create a new user and add it to the course users list.
 *
 * @param int $courseId
 * @param string $userName
 * @param int $userStudentNumber
 * @param string $userUsername
 * @param string $userEmail
 * @param string $userMajor
 * @param string $userAuthService
 * @param string[] $userRoles
 * @param bool $userHasImage
 * @param string $userNickname (optional)
 * @param $userImage (optional)
 */
API::registerFunction($MODULE, 'createCourseUser', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    API::requireValues('userHasImage', 'userMajor', 'userUsername', 'userAuthService', 'userName', 'userStudentNumber', 'userEmail', 'userRoles');
    $userName = API::getValue('userName');
    $userEmail = API::getValue('userEmail');
    $userStudentNumber = API::getValue('userStudentNumber');
    $userNickname = API::getValue('userNickname');
    $userRoles = API::getValue('userRoles');
    $userMajor = API::getValue('userMajor');
    $userUsername = API::getValue('userUsername');
    $userAuthService = API::getValue('userAuthService');

    if (User::getUserByStudentNumber($userStudentNumber) != null)
        API::error('There is already a student registered with studentNumber = ' . $userStudentNumber);

    // Add to system
    $userId = User::addUserToDB($userName, $userUsername, $userAuthService, $userEmail, $userStudentNumber, $userNickname, $userMajor, 0, 1);
    if (API::getValue('userHasImage') == 'true') {
        API::requireValues('userImage');
        $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
        User::saveImage($img, $userUsername);
    }

    // Add to course
    $courseUser = new CourseUser($userId, $course);
    $courseUser->addCourseUserToDB();
    $courseUser->setRoles($userRoles);
});

/**
 * Add an existing user to course.
 *
 * @param int $courseId
 * @param array $users
 * @param string $role
 */
API::registerFunction($MODULE, 'addUsersToCourse', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    API::requireValues('users', 'role');
    $users = API::getValue('users');
    $role = API::getValue('role');

    foreach ($users as $userData) {
        $userId = $userData['id'];
        $user = User::getUserByStudentNumber($userData['studentNumber']);
        if ($user->getId() != $userId)
            API::error('Not a valid user');

        $courseUser = new CourseUser($userId, $course);
        if ($courseUser->exists())
            API::error('Student \'' . $courseUser->getName() . '\' with studentNumber = ' . $courseUser->getStudentNumber() . ' is already registered in course \'' . $course->getName() . '\'');

        $courseUser->addCourseUserToDB();
        $courseUser->addRole($role);
    }
});

/**
 * Edit course user info.
 *
 * @param int $courseId
 * @param int $userId
 * @param string $userName
 * @param int $userStudentNumber
 * @param string $userUsername
 * @param string $userEmail
 * @param string $userMajor
 * @param string $userAuthService
 * @param string[] $userRoles
 * @param bool $userHasImage
 * @param string $userNickname (optional)
 * @param $userImage (optional)
 */
API::registerFunction($MODULE, 'editCourseUser', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    API::requireValues('userHasImage', 'userMajor', 'userId', 'userName', 'userStudentNumber', 'userEmail', 'userRoles');

    $userId = API::getValue('userId');
    $user = API::verifyUserExists($userId);
    $courseUser = API::verifyCourseUserExists($courseId, $userId);

    // Verify if new student number (if changed) is taken
    $studentNumber = API::getValue('userStudentNumber');
    if ($user->getStudentNumber() != $studentNumber) {
        $otherUser = User::getUserByStudentNumber($studentNumber);
        if ($otherUser != null)
            API::error('There is already a student registered with studentNumber = ' . $studentNumber);
    }

    $user->setName(API::getValue('userName'));
    $user->setEmail(API::getValue('userEmail'));
    $user->setStudentNumber(API::getValue('userStudentNumber'));
    $user->setNickname(API::getValue('userNickname'));
    $user->setUsername(API::getValue('userUsername'));
    $user->setMajor(API::getValue('userMajor'));
    $user->setAuthenticationService(API::getValue('userAuthService'));
    $courseUser->setRoles(API::getValue('userRoles'));

    if (API::getValue('userHasImage') == 'true') {
        API::requireValues('userImage');
        $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
        User::saveImage($img, API::getValue('userUsername'));
    }
});

/**
 * Remove user from course.
 *
 * @param int $courseId
 * @param int $userId
 */
API::registerFunction($MODULE, 'removeCourseUser', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'userId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $userId = API::getValue('userId');
    $courseUser = API::verifyCourseUserExists($courseId, $userId);

    Core::$systemDB->delete("course_user", ["id" => $userId, "course" => $courseId]);
});

/**
 * Change user course access.
 *
 * @param int $courseId
 * @param int $userId
 * @param int $isActive
 */
API::registerFunction($MODULE, 'setCourseUserActiveState', function () {
    API::requireCourseAdminPermission();
    API::requireValues('userId', 'courseId', 'isActive');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $userId = API::getValue('userId');
    $courseUser = API::verifyCourseUserExists($courseId, $userId);

    $courseUser->setIsActive(API::getValue('isActive'));
});

/**
 * Import users into course.
 *
 * @param int $courseId
 * @param $file
 * @param bool $replace
 */
API::registerFunction($MODULE, 'importCourseUsers', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'file');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nrUsers = CourseUser::importCourseUsers($fileContents, API::getValue('courseId'), $replace);

    API::response(array('nrUsers' => $nrUsers));
});

/**
 * Export users from course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'exportCourseUsers', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    [$fileName, $courseUsers] = CourseUser::exportCourseUsers($courseId);
    API::response(array('courseUsers' => $courseUsers, 'fileName' => $fileName));
});

/**
 * Checks if logged user is a teacher of the course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'isCourseTeacher', function() {
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $user = Core::getLoggedUser();
    $courseUser = $course->getUser($user->getId());

    API::response(['isTeacher' => in_array("Teacher", $courseUser->getRolesNames())]);
});

/**
 * Checks if logged user is a student of the course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'isCourseStudent', function() {
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $user = Core::getLoggedUser();
    $courseUser = $course->getUser($user->getId());

    API::response(['isStudent' => in_array("Student", $courseUser->getRolesNames())]);
});



/*** --------------------------------------------- ***/
/*** -------------- Course Modules --------------- ***/
/*** --------------------------------------------- ***/

/**
 * Get course modules.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseModules', function () {
    API::requireCourseAdminPermission();
    API:: requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $allModules = ModuleLoader::getModules();
    $enabledModules = $course->getEnabledModules();

    $modulesArr = [];
    foreach ($allModules as $module) {

        if (in_array($module['id'], $enabledModules)) {
            $moduleInfo = ModuleLoader::getModule($module['id']);
            $moduleObj = $moduleInfo['factory']();
            $module['hasConfiguration'] = $moduleObj->is_configurable();
            $module['enabled'] = true;
        } else {
            $module['hasConfiguration'] = false;
            $module['enabled'] = false;
        }

        $dependencies = [];
        $canBeEnabled = true;
        foreach ($module['dependencies'] as $dependency) {
            if ($dependency['mode'] != 'optional') {
                if (in_array($dependency['id'], $enabledModules)) {
                    $dependencies[] = array('id' => $dependency['id'], 'enabled' => true);
                } else {
                    $dependencies[] = array('id' => $dependency['id'], 'enabled' => false);
                    $canBeEnabled = false;
                }
            }
        }

        $mod = array(
            'id' => $module['id'],
            'name' => $module['name'],
            'dir' => $module['dir'],
            'type' => $module['type'],
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
});

/**
 * Check if virtual currency is enabled in course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'isVirtualCurrencyEnabled', function() {
    API::requireCoursePermission();
    API:: requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $enabledModules = $course->getEnabledModules();
    $isEnabled = in_array(VirtualCurrency::ID, $enabledModules);

    API::response(['isEnabled' => $isEnabled]);
});



/*** --------------------------------------------- ***/
/*** ------------------- Roles ------------------- ***/
/*** --------------------------------------------- ***/

// TODO: refactor, should be a get and set separated
//change user roles or role hierarchy
API::registerFunction($MODULE, 'roles', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

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
            'pages' => $course->getPages(true),
            'roles' => $course->getRoles("name, landingPage"),
            'roles_obj' => $course->getRoles('id, name, landingPage'),
            'rolesHierarchy' => $course->getRolesHierarchy(),
        );
        API::response($globalInfo);
    }
});



/*** --------------------------------------------- ***/
/*** --------------- Rules System ---------------- ***/
/*** --------------------------------------------- ***/

/**
 * Get datetime of when rules system was last run.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getRulesSystemLastRun', function () {
    API::requireCoursePermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $ruleSystem = new RuleSystem($course);
    API::response(array('ruleSystemLastRun' => $ruleSystem->getLastRunDate()));
});

// TODO: refactor
// General Actions: system actions perfomed in the rule list UI
API::registerFunction('settings', 'ruleGeneralActions', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();

    if (API::hasKey('newSection')) {
        if (API::hasKey('sectionName') && API::hasKey('sectionPrecedence')) {
            $sectionName = API::getValue('sectionName');
            $sectionPrecedence = API::getValue('sectionPrecedence');
            $rs->createNewRuleFile($sectionName, $sectionPrecedence);
        }
    } else if (API::hasKey('submitTagsEdit')) {
        if (API::hasKey('tags')) {
            $tags = API::getValue('tags');
            $rs->editTags($tags);
        }
    } else if (API::hasKey('swapTags')) {
        if (API::hasKey('rules')) {
            $rules = API::getValue('rules');
            $txt = $rs->swapTags($rules);
        }
    } else if (API::hasKey('deleteTag')) {
        if (API::hasKey('tag')) {
            $tag = API::getValue('tag');

            $rs->deleteTag($tag);
        }
    } else if (API::hasKey('importFile')) {
        if (API::hasKey('filename') && API::hasKey('replace') && API::hasKey('file')) {
            $filename = API::getValue('filename');
            $replace = API::getValue('replace');
            $file = API::getValue('file');
            $fileContent = explode(",", $file);
            if (sizeof($fileContent) == 2)
                $rs->importFile($filename, base64_decode($fileContent[1]), $replace);

            $rules = $rs->getRules();
            $globalInfo = array(
                'rules' => $rules
            );

            API::response($globalInfo);
            // send response
        }
    } else if (API::hasKey('exportRuleFiles')) {
        if (API::hasKey('filename')) {
            $filename = API::getValue('filename');
            $zipname = $rs->exportRuleFiles($filename);
        }
    } else if (API::hasKey('getTargets')) {
        $targets = $rs->getTargets();
        $data = array(
            'targets' => $targets
        );
        API::response($data);
    } else if (API::hasKey('getAutoGameStatus') && API::hasKey('getMetadataVars')) {
        $autogame = $rs->getAutoGameStatus();
        $metadata = $rs->getAutoGameMetadata();
        $data = array(
            'autogameStatus' => $autogame,
            'autogameMetadata' => $metadata
        );
        API::response($data);
    } else if (API::hasKey('resetSocket')) {
        $res = $rs->resetSocketStatus();
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'socketUpdated' => $res,
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else if (API::hasKey('resetCourse')) {
        $rs->resetCourseStatus();
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else {
        // TO DO
    }
});

// RuleSystem Settings Actions: actions performed in the settings menu of the rule system
API::registerFunction('settings', 'ruleSystemSettings', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();

    if (API::hasKey('getAutoGameStatus') && API::hasKey('getMetadataVars')) {
        $autogame = $rs->getAutoGameStatus();
        $metadata = $rs->getAutoGameMetadata();
        $data = array(
            'autogameStatus' => $autogame,
            'autogameMetadata' => $metadata
        );
        API::response($data);
    } else if (API::hasKey('resetSocket')) {
        $res = $rs->resetSocketStatus();
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'socketUpdated' => $res,
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else if (API::hasKey('resetCourse')) {
        $rs->resetCourseStatus();
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else if (API::hasKey('getLogs')) {
        $logs = $rs->getLogs();
        $data = array(
            'logs' => $logs
        );
        API::response($data);
    } else if (API::hasKey('getAvailableRoles')) {
        $roles = $course->getRoles();
        $data = array(
            'availableRoles' => $roles
        );
        API::response($data);
    } else if (API::hasKey('saveVariable')) {
        $variables = API::getValue('variables');
        $rs->setMetadataVariables($variables);
    } else if (API::hasKey('getTags')) {
        $tags = $rs->getTags();
        $data = array(
            'tags' => $tags
        );
        API::response($data);
    } else if (API::hasKey('getFuncs')) {
        $funcs = $rs->getGameRulesFuncs();
        $data = array(
            'funcs' => $funcs
        );
        API::response($data);
    } else {
        // TO DO
    }
});

// Rule Section Actions: actions perfomed over sections in the rule list UI
API::registerFunction('settings', 'ruleSectionActions', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();

    if (API::hasKey('newRule')) {
        if (API::hasKey('module')) {
            $module = API::getValue('module');
            $rule = API::getValue('rule');
            $rs->newRule($module, $rule);
        }
    } else if (API::hasKey('exportRuleFile')) {
        if (API::hasKey('module')) {
            $fileData = $rs->exportRuleFile(API::getValue('module'));
            API::response($fileData);
        }
    } else if (API::hasKey('increasePriority')) {
        if (API::hasKey('filename')) {
            $module = API::getValue('module');
            $filename = API::getValue('filename');
            $rs->increasePriority($filename);
        }
    } else if (API::hasKey('decreasePriority')) {
        if (API::hasKey('filename')) {
            $filename = API::getValue('filename');
            $rs->decreasePriority($filename);
        }
    } else if (API::hasKey('deleteSection')) {
        if (API::hasKey('filename')) {
            $filename = API::getValue('filename');
            $rs->deleteSection($filename);
        }
    } else {
        // TO DO
    }
});

// Rule Actions: actions perfomed over rules in the rule list UI
API::registerFunction('settings', 'ruleSpecificActions', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();

    if (API::hasKey('toggleRule')) {
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $rs->toggleRule($rule, $index);
        }
    } else if (API::hasKey('duplicateRule')) {
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $rs->duplicateRule($rule, $index);
        }
    } else if (API::hasKey('deleteRule')) {
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $rs->removeRule($rule, $index);
        }
    } else if (API::hasKey('moveUpRule')) {
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $rs->moveUpRule($rule, $index);
        }
    } else if (API::hasKey('moveDownRule')) {
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $rs->moveDownRule($rule, $index);
        }
    } else {
        // TO DO
    }
});

// Rule Editor Actions: actions perfomed over rules in the add/edit rule page
API::registerFunction('settings', 'ruleEditorActions', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();
    $tags = $rs->getTags();

    if (API::hasKey('submitRule')) {
        // Saves a rule after editing or creating
        if (API::hasKey('rule')) {
            $rule = API::getValue('rule');
            $index = API::getValue('index');
            $ruletxt = $rs->generateRule($rule);

            if (API::hasKey('add')) {
                $rs->updateTags($rule["tags"]);
                $rs->addRule($ruletxt, $index, $rule);
            } else {
                $rs->updateTags($rule["tags"]);
                $rs->replaceRule($ruletxt, $index, $rule);
            }
        }
    } else if (API::hasKey('getLibraries')) {
        // Gets the list of libraries that can be used in rule writing
        $libs = $course->getEnabledLibrariesInfo();

        API::response($libs);
    } else if (API::hasKey('getFunctions')) {
        // Gets the list of functions that can be used in rule writing
        $funcs = $course->getFunctions();
        API::response($funcs);
    } else if (API::hasKey('previewFunction')) {
        // Previews a function being used in rule writing
        if (API::hasKey('lib') && API::hasKey('func') && API::hasKey('args')) {
            $lib = API::getValue('lib');
            $func = API::getValue('func');
            $args = API::getValue('args');

            $res = null;

            if (!empty($args)) {
                $objs = ViewHandler::callFunction($lib, $func, $args);
                $obj = $objs->getValue();
                $objtype = getType($obj);
            } else {
                $objs = ViewHandler::callFunction($lib, $func, []);
                $obj = $objs->getValue();
                $objtype = getType($obj);
            }

            if ($objtype == "bool") {
                $res = $obj;
            } else if ($objtype == "string") {
                $res = $obj;
            } else if ($objtype == "object") {
                $res = $obj;
            } else if ($objtype == "integer") {
                $res = $obj;
            } else if ($objtype == "array") {
                if ($obj["type"] == "collection") {
                    $res = json_encode($obj["value"]);
                }
            } else {
                $res = get_object_vars($obj);
            }
            API::response($res);
        }
    } else if (API::hasKey('previewRule')) {
        $rule = API::getValue('rule');
        $res = $rs->generateRule($rule);

        // write rule to file
        $rs->writeTestRule($res);
        try {
            // args: [course] [all_targets] [targets] [test_mode]
            $resError = Course::newExternalData(API::getValue('courseId'), True, null, True);
            // get results
            $res = Core::$systemDB->selectMultiple("award_test", null, "*");
            if ($resError != null) {
                $data = array("result" => null, "error" => $resError);
            } else {
                $data = array("result" => $res, "error" => null);
            }
        } catch (\Exception $e) {
        }
        $rs->clearRuleOutput(); // clear output
        Core::$systemDB->deleteAll("award_test"); // clear DB

        //$data = array("result" => array(), "error" => null);
        API::response($data);
    } else {
        // TO DO
    }
});

API::registerFunction('settings', 'runRuleSystem', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    if (API::hasKey('runSelectedTargets')) {
        $selectedTargets = API::getValue('selectedTargets');
        Course::newExternalData(API::getValue('courseId'), False, $selectedTargets);
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else if (API::hasKey('runRuleSystem')) {
        Course::newExternalData(API::getValue('courseId'), False);
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else if (API::hasKey('runAllTargets')) {
        Course::newExternalData(API::getValue('courseId'), True);
        $autogame = $rs->getAutoGameStatus();
        $data = array(
            'autogameStatus' => $autogame
        );
        API::response($data);
    } else {
        // TO DO
    }
});

// rules page
API::registerFunction('settings', 'getRulesFromCourse', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $rs = new RuleSystem($course);
    $rules = $rs->getRules();
    $tags = $rs->getTags();
    $funcs = $rs->getGameRulesFuncs();

    $globalInfo = array(
        'rules' => $rules,
        'tags' => $tags,
        'funcs' => $funcs
    );
    API::response($globalInfo);
});



/*** --------------------------------------------- ***/
/*** ------------------- Styles ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get course style file.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'getCourseStyleFile', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $response = $course->getStyleFile();

    if ($response)
        API::response(array('styleFile' => $response[0], 'url' => $response[1]));
    else
        API::response(array('styleFile' => $response));
});

/**
 * Create course style file.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'createCourseStyleFile', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $result = $course->createStyleFile();
    API::response(array('url' => $result));
});

/**
 * Update course style file.
 *
 * @param int $courseId
 * @param string $content
 */
API::registerFunction($MODULE, 'updateCourseStyleFile', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'content');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);
    $content = API::getValue('content');

    $response = $course->updateStyleFile($content);
    API::response(array('url' => $response));
});



/*** --------------------------------------------- ***/
/*** ----------------- Resources ----------------- ***/
/*** --------------------------------------------- ***/

/**
 * Upload file to course data folder.
 *
 * @param $courseId
 * @param $file
 * @param string $folder
 * @param string $fileName
 */
API::registerFunction($MODULE, 'uploadFileToCourse', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'file', 'folder', 'fileName');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    API::response(['path' => $course->uploadFile(API::getValue('file'), API::getValue('folder'), API::getValue('fileName'))]);
});

/**
 * Delete file from course data folder.
 *
 * @param $courseId
 * @param $path
 */
API::registerFunction($MODULE, 'deleteFileFromCourse', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'path');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $course->deleteFile(API::getValue('path'));
});



/*** --------------------------------------------- ***/
/*** ----------- Database Manipulation ----------- ***/
/*** --------------------------------------------- ***/

/**
 * Get data from a database table associated with a course.
 *
 * @param int $courseId
 * @param string $table
 */
API::registerFunction($MODULE, 'getTableData', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'table');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $tableName = API::getValue('table');

    if (isset(Core::$systemDB->select($tableName)["user"])) {
        $data = Core::$systemDB->selectMultiple("game_course_user g join " . $tableName . " t on g.id=t.user", ["course" => $courseId], "t.*, g.name, g.studentNumber");
        foreach ($data as &$d) {
            $exploded =  explode(' ', $d["name"]);
            $nickname = $exploded[0] . ' ' . end($exploded);
            $d["name"] = $nickname;
        }
    } else $data = Core::$systemDB->selectMultiple($tableName, ["course" => $courseId], "*");

    // Get columns in order: id , name, studentNumber, (...)
    $orderedColumns = null;
    if ($data) {
        $columns = array_keys($data[0]);
        $lastHalf = array_slice($columns, 1, -2);
        $lastTwo = array_slice($columns, -2);
        $orderedColumns = array_merge(array_merge(["id"], $lastTwo), $lastHalf);
    }

    API::response(array("entries" => $data, "columns" => $orderedColumns));
});

/**
 * Create or edit row from a database table associated with a course.
 *
 * @param int $courseId
 * @param string $table
 * @param $update
 * @param $newData
 */
API::registerFunction($MODULE, 'submitTableEntry', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'table', 'rowData');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $tableName = API::getValue('table');

    if ($tableName != null) {
        $update = API::getValue('update');
        $newData = API::getValue('newData');
        $newData['courseId'] = $courseId;
        $newStudentNumber = $newData['studentNumber'];

        $newStudent = Core::$systemDB->select("course_user c join game_course_user g on c.id=g.id", ["course" => $courseId, "studentNumber" => $newStudentNumber], "g.id, name");
        if (!$newStudent) {
            API::error('There are no students in this course with student number ' . $newStudentNumber, 400);
        }
        // only keep keys that are columns on the target table
        unset($newData['name']);
        unset($newData['studentNumber']);

        $exploded =  explode(' ', $newStudent["name"]);
        $nickname = $exploded[0] . ' ' . end($exploded);

        $newData['user'] = $newStudent['id'];

        if ($update) {
            $where = API::getValue('rowData');
            if ($newData != null and $where != null) {
                // only keep keys that are columns on the target table
                unset($where['name']);
                unset($where['studentNumber']);

                Core::$systemDB->update($tableName, $newData, $where);
                $newData['name'] = $nickname;
                $newData['studentNumber'] = $newStudentNumber;

                API::response(array("newRecord" => $newData));
            }
        } else {
            $id = Core::$systemDB->insert($tableName, $newData);
            $newRecord = Core::$systemDB->select($tableName, ["id" => $id]);
            $newRecord['name'] = $nickname;
            $newRecord['studentNumber'] = $newStudentNumber;

            API::response(array("newRecord" => $newRecord));
        }
    }
});

/**
 * Delete row from a database table associated with a course.
 *
 * @param int $courseId
 * @param string $table
 * @param $rowData
 */
API::registerFunction($MODULE, 'deleteTableEntry', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'table', 'rowData');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $tableName = API::getValue('table');
    $row = API::getValue('rowData');

    // Only keep keys that are columns on the target table
    unset($row['name']);
    unset($row['studentNumber']);

    Core::$systemDB->delete($tableName, $row);
});