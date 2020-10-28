<?php
namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\User;
use GameCourse\CourseUser;



//get course information, including navbar
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