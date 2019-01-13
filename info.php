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

API::registerFunction('core', 'getCoursesList', function() {
    $courses = Core::getCourses();
    $activeCourses = Core::getActiveCourses();
    $user = Core::getLoggedUser();

    $myCourses = array();
    foreach ($courses as $id => &$course) {
        $active = in_array($id, $activeCourses);
        if ($active || $user->isAdmin())
            $course = array('name' => $course, 'id' => $id, 'active' => $active);
    }

    API::response(array('courses' => $courses, 'myCourses' => $myCourses));
});

API::registerFunction('core', 'getCourseInfo', function() {
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

API::registerFunction('settings', 'courseApiKey', function() {
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    API::response(array('key' => $course->getWrapped('apiKey')->getValue()));
});

API::registerFunction('settings', 'courseApiKeyGen', function() {
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    $newKey = md5(mt_rand() . mt_rand() . mt_rand() . getmypid());
    $course->getWrapped('apiKey')->setValue($newKey);
    API::response(array('key' => $newKey));
});

API::registerFunction('settings', 'apiKey', function() {
    API::response(array('key' => Core::getApiKey()->getValue()));
});

API::registerFunction('settings', 'apiKeyGen', function() {
    $newKey = md5(mt_rand() . mt_rand() . mt_rand() . getmypid());
    Core::getApiKey()->setValue($newKey);
    API::response(array('key' => $newKey));
});

API::registerFunction('settings', 'setCourseState', function() {
    API::requireCourseAdminPermission();
    API::requireValues('course', 'state');

    $course = API::getValue('course');
    $state = API::getValue('state');

    if ($state)
        Core::getActiveCoursesWrapped()->push($course);
    else {
        $activeCourses = Core::getActiveCourses();
        for($i = 0; $i < count($activeCourses); ++$i) {
            if ($activeCourses[$i] == $course) {
                unset($activeCourses[$i]);
                --$i;
            }
        }
        Core::getActiveCoursesWrapped()->setValue(array_values($activeCourses));
    }

});

API::registerFunction('settings', 'roleInfo', function() {
    API::requireCourseAdminPermission();
    API::requireValues('role');
    $course = Course::getCourse(API::getValue('course'));

    $role = API::getValue('role');
    if (API::hasKey('landingPage')) {
        if ($role != 'Default')
            $course->getRoleSettings(API::getValue('role'))->set('landingPage', API::getValue('landingPage'));
        else
            $course->getWrapped('defaultRoleSettings')->set('landingPage', API::getValue('landingPage'));
    } else {
        if ($role != 'Default')
            API::response($course->getRoleSettings($role)->getValue());
        else
            API::response($course->getWrapped('defaultRoleSettings')->getValue());
    }
});

API::registerFunction('settings', 'roles', function() {
    API::requireCourseAdminPermission();

    if (API::hasKey('updateRoleHierarchy')) {
        // TODO: check deleted roles
        $hierarchy = API::getValue('updateRoleHierarchy');
        $course = Course::getCourse(API::getValue('course'));
        $course->setRoles($hierarchy['roles']);
        $course->setRolesHierarchy($hierarchy['hierarchy']);
        $roles = $hierarchy['roles'];

        $changed = false;
        $rolesSettings = $course->getRolesSettings();
        $rolesSettingsVal = $rolesSettings->getValue();
        foreach ($roles as $role) {
            if (!array_key_exists($role, $rolesSettingsVal)) {
                $rolesSettingsVal[$role] = array('landingPage' => '');
                $changed = true;
            }
        }
        if ($changed)
            $rolesSettings->setValue($rolesSettingsVal);
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
        foreach ($users as $id => $user) {
            $usersInfo[$id] = array('id' => $id, 'name' => $users->getWrapped($id)->get('name'), 'roles' => $users->getWrapped($id)->get('roles'));
        }

        $globalInfo = array(
            'users' => $usersInfo,
            'roles' => $course->getRoles(),
            'rolesHierarchy' => $course->getRolesHierarchy(),
        );
        API::response($globalInfo);
    }
});

API::registerFunction('settings', 'courseGlobal', function() {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));

    if (API::hasKey('headerLink')) {
        $course->setHeaderLink(API::getValue('headerLink'));
        http_response_code(201);
    } else if (API::hasKey('courseFenixLink')) {
        $course->getWrapper()->set('fenix-link', API::getValue('courseFenixLink'));
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

            if ($moduleEnabled != API::getValue('enabled'))
                $course->setModuleEnabled($moduleId, !$moduleEnabled);
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
            'courseFenixLink' => $course->getWrapped('fenix-link')->getValue(),
            'modules' => $modulesArr
        );
        API::response($globalInfo);
    }
});

API::registerFunction('settings', 'courseTabs', function() {
    API::requireCourseAdminPermission();
    API::response(Settings::getTabs());
});

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

API::registerFunction('settings', 'tabs', function() {
    API::requireAdminPermission();

    $activeCourses = Core::getActiveCourses();
    $courses = Core::getCourses();
    $coursesTabs = array();
    foreach ($courses as $courseId => $course) {
        $coursesTabs[] = Settings::buildTabItem($course . (in_array($courseId, $activeCourses) ? '' : ' - Inactive'), 'settings.courses.course({course:\'' . $courseId . '\'})', true);
    }
    $tabs = array(
        Settings::buildTabItem('Courses', 'settings.courses', true, $coursesTabs)
    );
    API::response($tabs);
});

API::registerFunction('settings', 'users', function() {
    API::requireAdminPermission();

    if (API::hasKey('setPermissions')) {
        $perm = API::getValue('setPermissions');
        foreach ($perm['admins'] as $admin) {
            User::getUser($admin)->setAdmin(true);
        }

        foreach ($perm['users'] as $user) {
            User::getUser($user)->setAdmin(false);
        }
        return;
    } else if (API::hasKey('createInvite')) {
        $inviteInfo = API::getValue('createInvite');
        if (User::getUser($inviteInfo['id'])->exists())
            API::error('A user with id ' . $inviteInfo['id'] . ' is already registered.');
        else if (User::getUserByUsername($inviteInfo['username']) != null)
            API::error('A user with username ' . $inviteInfo['username'] . ' is already registered.');
        Core::getPendingInvitesWrapped()->set($inviteInfo['username'], array('id' => $inviteInfo['id'], 'username' => $inviteInfo['username']));
        return;
    } else if (API::hasKey('deleteInvite')) {
        $invite = API::getValue('deleteInvite');
        if (Core::getPendingInvitesWrapped()->hasKey($invite))
            Core::getPendingInvitesWrapped()->delete($invite);
        return;
    }

    API::response(array('users' => User::getAllInfo(), 'pendingInvites' => Core::getPendingInvites()));
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

    $activeCourses = Core::getActiveCourses();
    $key = array_search($course, $activeCourses);
    if ($key !== false) {
        unset($activeCourses[$key]);
        Core::getActiveCoursesWrapped()->setValue(array_values($activeCourses));
    }

    Core::getCoursesWrapped()->delete($course);
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