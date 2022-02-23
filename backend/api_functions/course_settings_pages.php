<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;


// TODO: either refactor if being used or remove from api

API::registerFunction('settings', 'saveNewNavigationOrder', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId);
    $newNav = API::getValue('nav');
    if ($course != null) {
        Core::setNavigation($newNav);
        foreach ($newNav as $key => $nav) {
            Core::$systemDB->update("page", ["seqId" => $key + 1], ["course" => $courseId, "name" => $nav["text"]]);
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});


