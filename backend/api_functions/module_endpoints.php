<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;

$MODULE = 'module';


/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Set module state in course: either enabled or disabled.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param bool $isEnabled
 */
API::registerFunction($MODULE, 'setModuleState', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId', 'isEnabled');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId);

    $toEnable = API::getValue('isEnabled');

    $moduleObject = $module['factory']();
    $moduleEnabled = Core::$systemDB->select("course_module", ["course" => $courseId, "moduleId" => $moduleId], "isEnabled");

    if ($moduleEnabled && !$toEnable) { //disabling module
        $modules = $course->getModules();
        foreach ($modules as $mod) {
            $dependencies = $mod->getDependencies();
            foreach ($dependencies as $dependency) {
                if ($dependency['id'] == $moduleId && $dependency['mode'] != 'optional')
                    API::error('Must disable all modules that depend on this one first.');
            }
        }

        if (Core::$systemDB->select("course_module", ["moduleId" => $moduleId, "isEnabled" => 1], "count(*)") == 1) {
            //only drop the tables of the module data if this is the last course where it is enabled
            $moduleObject->dropTables($moduleId); //deletes tables associated with the module
        } else {
            $moduleObject->deleteDataRows($courseId);
        }

    } else if (!$moduleEnabled && $toEnable) { //enabling module
        foreach ($module['dependencies'] as $dependency) {
            // FIXME: BUG - can enable module without dependencies enabled
            if ($dependency['mode'] != 'optional' && ModuleLoader::getModules($dependency['id']) == null)
                API::error('Must enable all dependencies first.');
        }
    }

    if ($moduleEnabled != $toEnable) {
        $course->setModuleEnabled($moduleId, !$moduleEnabled);
    }
});



/*** --------------------------------------------- ***/
/*** --------------- Configuration --------------- ***/
/*** --------------------------------------------- ***/

/**
 * Gets module information for the configuration page.
 *
 * @param int $courseId
 * @param string $moduleId
 */
API::registerFunction($MODULE, 'getModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    // Get module info
    $moduleInfo = [
        'id' => $moduleId,
        'name' => $module->getName(),
        'description' => $module->getDescription()
    ];

    API::response([
        'generalInputs' => $module->has_general_inputs() ? $module->get_general_inputs($courseId) : [],
        'listingItems' => $module->has_listing_items() ? $module->get_listing_items($courseId) : [],
        'personalizedConfig' => $module->has_personalized_config() ? $module->get_personalized_function() : [],
        'tiers' => $moduleId === "skills" ? $module->get_tiers_items($courseId) : [],
        'module' => $moduleInfo,
        'courseFolder' => Course::getCourseDataFolder($courseId)
    ]);
});

/**
 * Save user input on the module configuration page.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param $generalInputs (optional)
 * @param $listingItem (optional)
 * @param string $actionType (optional)
 */
API::registerFunction($MODULE, 'saveModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    // Save general inputs
    if (API::hasKey('generalInputs'))
        $module->save_general_inputs(API::getValue('generalInputs'), $courseId);

    //personalized configuration should create its own API request
    //inside the currespondent module

    // Save listing items
    if (API::hasKey('listingItem')) {
        $listingItem = API::getValue('listingItem');
        $actionType = API::getValue('actionType'); // new, edit, delete, duplicate

        if ($module->getId() !== "skills") {
            $module->save_listing_item($actionType, $listingItem, $courseId);

        } else {
            if (array_key_exists("reward", $listingItem)) $module->save_tiers($actionType, $listingItem, $courseId);
            else $module->save_listing_item($actionType, $listingItem, $courseId);
        }
    }
});

/**
 * Toggle module item param.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param int $itemId
 * @param string $param
 */
API::registerFunction($MODULE, 'toggleItemParam', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId', 'itemId', 'param');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    $module->toggleItemParam(API::getValue('itemId'), API::getValue('param'));
});

/**
 * Import items into the module.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param $file
 * @param bool $replace (optional)
 */
API::registerFunction($MODULE, 'importItems', function () {
    API::requireAdminPermission();
    API::requireValues('courseId', 'moduleId', 'file', 'replace');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nrItems = $module->importItems($fileContents, $replace);
    API::response(array('nrItems' => $nrItems));
});

/**
 * Export items from the module.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param int $itemId (optional)
 */
API::registerFunction($MODULE, 'exportItems', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    [$fileName, $items] = $module->exportItems(API::getValue("itemId"));
    API::response(array('items' => $items, 'fileName' => $fileName));
});
