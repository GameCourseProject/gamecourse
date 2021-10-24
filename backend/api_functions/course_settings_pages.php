<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;


// TODO: either refactor if being used or remove from api

//gets module information for the configuration page
API::registerFunction('settings', 'getModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false);
    if ($course != null) {
        $module = $course->getModule(API::getValue('module'));
        $folder = Course::getCourseDataFolder($courseId);

        if ($module != null) {
            $moduleInfo = array(
                'id' => $module->getId(),
                'name' => $module->getName(),
                'description' => $module->getDescription()
            );

            $generalInputs = [];
            if ($module->has_general_inputs()) {
                $generalInputs = $module->get_general_inputs($course->getId());
            }

            $personalizedConfig = [];
            if ($module->has_personalized_config()) {
                $personalizedConfig = $module->get_personalized_function();
            }

            $listingItems = [];
            if ($module->has_listing_items()) {
                $listingItems = $module->get_listing_items($course->getId());
            }

            $tiers = [];
            if ($moduleInfo["name"] == "Skills") {
                $tiers = $module->get_tiers_items($course->getId());
            }

            $info = array(
                'generalInputs' => $generalInputs,
                'listingItems' => $listingItems,
                'personalizedConfig' => $personalizedConfig,
                'tiers' => $tiers,
                'module' => $moduleInfo,
                'courseFolder' => $folder,
            );
            API::response($info);
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

//request to save user input on the module configuration page
API::registerFunction('settings', 'saveModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if ($course != null) {
        $module = $course->getModule(API::getValue('module'));

        if ($module != null) {
            if (API::hasKey('generalInputs')) {
                $generalInputs = API::getValue('generalInputs');
                $module->save_general_inputs($generalInputs, $course->getId());
            }

            //personalized configuration should create its own API request
            //inside the currespondent module

            if (API::hasKey('listingItems')) {
                $listingItems = API::getValue('listingItems');
                $action_type = API::getValue('action_type'); //new, edit, delete
                if ($module->getName() != "Skills")
                    $module->save_listing_item($action_type, $listingItems, $course->getId());
                else {
                    if (array_key_exists("reward", $listingItems))
                        $module->save_tiers($action_type, $listingItems, $course->getId());
                    else
                        $module->save_listing_item($action_type, $listingItems, $course->getId());
                }
            }
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});



//request to change the item's active status
API::registerFunction('settings', 'activeItem', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false);
    if ($course != null) {
        $module = $course->getModule(API::getValue('module'));
        if ($module != null) {
            $itemId = API::getValue('itemId');
            $module->activeItem($itemId);
        }
    }
});

API::registerFunction('settings', 'importItem', function () {
    API::requireAdminPermission();
    API::requireValues('file');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $module = API::getValue('module');
    $course = API::getValue('course');

    $courseObject = Course::getCourse($course, false);
    $moduleObject = $courseObject->getModule($module);
    $nItems = $moduleObject->importItems($course, $fileContents, $replace);
    API::response(array('nItems' => $nItems));
});

API::registerFunction('settings', 'exportItem', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = API::getValue('course');
    $module = API::getValue('module');

    $courseObject = Course::getCourse($course, false);
    $moduleObject = $courseObject->getModule($module);
    [$fileName, $courseItems] = $moduleObject->exportItems($course);
    API::response(array('courseItems' => $courseItems, 'fileName' => $fileName));
});

API::registerFunction('settings', 'saveNewSequence', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if ($course != null) {
        $module = $course->getModule(API::getValue('module'));

        if ($module != null) {
            if (API::hasKey('oldSeq') && API::hasKey('nextSeq') && API::hasKey('itemId') && API::hasKey('table')) {
                $oldSeq = API::getValue('oldSeq');
                $nextSeq = API::getValue('nextSeq');
                $itemId = API::getValue('itemId');
                $table =  API::getValue('table');
                $courseId = API::getValue('course');
                $module->changeSeqId($courseId, $itemId, $oldSeq, $nextSeq, $table);
            }
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

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


