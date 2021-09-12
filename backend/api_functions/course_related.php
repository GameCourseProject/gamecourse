<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\RuleSystem;

// get course from db
API::registerFunction('core', 'getCourse', function () {
    API::requireCoursePermission();
    API::requireValues('course');
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false)->getData();

    if ($course) {
        API::response(array('course' => $course));
    } else {
        API::error("There is no course with that id: " . $courseId);
    }
});

//get course information, including navbar
API::registerFunction('core', 'getCourseInfo', function () {
    API::requireCoursePermission();
    API::requireValues('course');
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false);
    if ($course != null) {
        //adding other pages to navigation
        $pages = \Modules\Views\ViewHandler::getPagesOfCourse($courseId, true);
        $OldNavPages = Core::getNavigation();
        $navNames = array_column($OldNavPages, "text");
        $user = Core::getLoggedUser();
        $courseUser = $course->getLoggedUser();
        $courseUser->refreshActivity();
        $ruleSystem = new RuleSystem($course);

        foreach ($pages as $pageId => $page) {
            // adding pages to the navbar according to their role
            // if there is no view for their role, the page is not added to the navbar

            if (!in_array($page["name"], $navNames)) {
                $simpleName = str_replace(' ', '', $page["name"]);
                $view = Core::$systemDB->select("view", ["viewId" => $page["viewId"]]);
                $template = Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $page["viewId"], "course" => $courseId], "id,name,roleType");

                if ($template["roleType"] == "ROLE_INTERACTION") {
                    $viewerRole = explode(".", explode(">", $view["role"])[1])[1];
                    $userRole = explode(".", explode(">", $view["role"])[0])[1];
                    $views = Core::$systemDB->selectMultiple("view", ["viewId" => $view["viewId"]]);
                    foreach ($views as $v) {
                        $viewerRole = explode(".", explode(">", $v["role"])[1])[1];
                        //userRole used for pages like profile - only makes sense to add if the user has info to see about himself
                        $userRole = explode(".", explode(">", $v["role"])[0])[1];
                        if ((($viewerRole == "Default" && ($courseUser->hasRole($userRole) || $userRole == "Default"))
                                || ($viewerRole != "Default" && $courseUser->hasRole($viewerRole)))
                            && !empty(Core::$systemDB->select("view_parent", ["parentId" => $v["id"]]))
                        ) {
                            Core::addNavigation($page["name"], 'course.customUserPage({name: \'' . $simpleName . '\',id:\'' . $pageId . '\',userID:\'' . $user->getId() . '\'})', true, $page["seqId"]);
                            break;
                        }
                    }
                } else {
                    $viewerRole = explode(".", $view["role"])[1];

                    $views = Core::$systemDB->selectMultiple(
                        "view",
                        ["viewId" => $view["viewId"]]
                    );
                    foreach ($views as $v) {
                        $viewerRole = explode(".", $v["role"])[1];
                        if (($viewerRole == "Default" || ($viewerRole != "Default" && $courseUser->hasRole($viewerRole)))) {
                            Core::addNavigation($page["name"], 'course.customPage({name: \'' . $simpleName . '\',id:\'' . $pageId . '\'})', true, $page["seqId"]);
                            break;
                        }
                    }
                }
            }
        }



        $landingPage = $courseUser->getLandingPage();
        $landingPageInfo = Core::$systemDB->select("page", ["name" => $landingPage], "id, viewId");
        $landingPageID = $landingPageInfo["id"];
        $landingPageType = Core::$systemDB->select("view_template vt join template t on vt.templateId=t.id", ["viewId" => $landingPageInfo["viewId"], "course" => $courseId], "roleType");

        $isAdmin = (($user != null && $user->isAdmin()) || $courseUser->isTeacher());

        if ($isAdmin) {
            Core::addNavigation("Users", 'course.users', true);
            Core::addNavigation('Course Settings', 'course.settings', true, null, 'dropdown', true);
            Core::addSettings('This Course', 'course.settings.global', true);
            Core::addSettings('Roles', 'course.settings.roles', true);
            Core::addSettings('Modules', 'course.settings.modules', true);
            Core::addSettings('Rules', 'course.settings.rules', true);
            //se views tiver active
            if (in_array("views", $course->getEnabledModules()))
                Core::addSettings('Views', 'course.settings.views', true);
        }

        $navPages = Core::getNavigation();
        $navSettings = Core::getSettings();
        $pageNames = array_column($pages, "name");

        foreach ($navPages as $nav) {
            if ($nav["restrictAcess"] === true && !$isAdmin) {
                unset($navPages[array_search($nav, $navPages)]);
            }
            //removes pages that were on nav bar and now we have to take them off
            else if (!in_array($nav["text"], $pageNames) && $nav["text"] !== "Users" && $nav["text"] !== "Course Settings") {
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
            'user' => $user,
            'ruleSystemLastRun' => $ruleSystem->getLastRunDate()
        ));
    } else {
        API::error("There is no course with that id: " . $courseId);
    }
});
//------------------ File System-----------------------------------
API::registerFunction('course', 'getDataFolders', function () {

    API::requireCoursePermission();
    API::requireValues('course');
    $courseId = API::getValue('course');
    $courseName = Course::getCourse($courseId, false)->getName();
    $dir = Course::getCourseDataFolder($courseId, $courseName);
    $folders = Course::getDataFolders($dir);
    API::response(array('folders' => $folders));
});
