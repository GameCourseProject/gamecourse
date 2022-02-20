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

API::registerFunction('settings', 'upload', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = API::getValue('course');
    $module = API::getValue('module');
    $file = API::getValue('newFile');
    $fileName = API::getValue('fileName');
    $subfolder = API::getValue('subfolder');

    $courseObject = Course::getCourse($course, false);
    $result = $courseObject->upload($file, $fileName, $module, $subfolder);
    API::response(array('url' => $result));
});

API::registerFunction('settings', 'deleteFile', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course', 'path');
    $course = API::getValue('course');
    $path =  API::getValue('path');
    $courseObject = Course::getCourse($course, false);
    $courseObject->deleteFile($path);
    http_response_code(201);
    return;
});


