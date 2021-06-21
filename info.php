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

API::processRequest();
