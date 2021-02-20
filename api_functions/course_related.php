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
        $pages = \Modules\Views\ViewHandler::getPagesOfCourse($courseId, true);
        $OldNavPages = Core::getNavigation();
        $navNames= array_column($OldNavPages,"text");
        $user = Core::getLoggedUser();
        foreach ($pages as $pageId => $page){
            
            //if ($page["roleType"]=="ROLE_INTERACTION")//not adding pages like profile to the nav bar
            //    continue;
            //pages added by modules already have navigation, the otheres need to be added
            if(!in_array($page["name"], $navNames)){
                $simpleName=str_replace(' ', '', $page["name"]);
                if ($page["roleType"]=="ROLE_INTERACTION")
                    Core::addNavigation( $page["name"], 'course.customUserPage({name: \''.$simpleName.'\',id:\''.$pageId.'\',userID:\''.$user->getId().'\'})', true);
                else
                    Core::addNavigation( $page["name"], 'course.customPage({name: \''.$simpleName.'\',id:\''.$pageId.'\'})', true);
            }
        }
    
        
        $courseUser = $course->getLoggedUser();
        $landingPage = $courseUser->getLandingPage();
        $landingPageInfo = Core::$systemDB->select("page", ["name"=>$landingPage], "id, roleType");
        $landingPageID = $landingPageInfo["id"];
        $landingPageType = $landingPageInfo["roleType"];

        $isAdmin =(($user != null && $user->isAdmin()) || $courseUser->isTeacher());
        
        if ($isAdmin){
            Core::addNavigation( "Users", 'course.users', true); 
            Core::addNavigation('Course Settings', 'course.settings', true, 'dropdown', true);
            Core::addSettings('This Course', 'course.settings.global', true);
            Core::addSettings('Roles', 'course.settings.roles', true);
            Core::addSettings('Modules', 'course.settings.modules', true);
            //se views tiver active
            if (in_array("views", $course->getEnabledModules()))
                Core::addSettings('Views', 'course.settings.views', true);
        }

        $navPages = Core::getNavigation();
        $navSettings = Core::getSettings();
        
        foreach ($navPages as $nav){
            if ($nav["restrictAcess"]===true && !$isAdmin){
                unset($navPages[array_search($nav, $navPages)]);
            }
        }
        API::response(array(
            'navigation' => $navPages,
            'settings' => $navSettings,
            'landingPage' => $landingPage,
            'landingPageID' => $landingPageID,
            'landingPageType' => $landingPageType,
            'courseName' => $course->getName(),
            'courseColor' => $course->getData("color"),
            'resources' => $course->getModulesResources(),
            'user' => $user
        ));
    }
    else{
        API::error("There is no course with that id: ". $courseId);
    }
});