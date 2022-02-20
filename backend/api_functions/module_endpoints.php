<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

$MODULE = 'module';


/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get all available modules in the system.
 */
API::registerFunction($MODULE, 'getModules', function() {
    API::requireAdminPermission();

    $allModules = ModuleLoader::getModules();

    $modulesArr = [];
    foreach ($allModules as $module) {
        $mod = array(
            'id' => $module['id'],
            'name' => $module['name'],
            'type' => $module['type'],
            'dir' => $module['dir'],
            'version' => $module['version'],
            'dependencies' => $module['dependencies'],
            'description' => $module['description']
        );
        $modulesArr[] = $mod;
    }

    API::response($modulesArr);

});



/*** --------------------------------------------- ***/
/*** ------------ Module Manipulation ------------ ***/
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

    $toEnable = filter_var(API::getValue('isEnabled'), FILTER_VALIDATE_BOOLEAN);
    $moduleEnabled = filter_var(Core::$systemDB->select("course_module", ["course" => $courseId, "moduleId" => $moduleId], "isEnabled"), FILTER_VALIDATE_BOOLEAN);

    // Check dependencies
    if ($moduleEnabled && !$toEnable) { //disabling module
        foreach ($course->getModules() as $mod) {
            $dependencies = $mod->getDependencies();
            foreach ($dependencies as $dependency) {
                if ($dependency['id'] == $moduleId && $dependency['mode'] != 'optional')
                    API::error('Must disable all modules that depend on this one first: module \'' . $dependency['id'] . '\' is enabled.');
            }
        }

    } else if (!$moduleEnabled && $toEnable) { //enabling module
        foreach ($module['dependencies'] as $dependency) {
            if ($dependency['mode'] != 'optional' && !empty(Core::$systemDB->select("course_module", ["course" => $courseId, "moduleId" => $dependency['id'], "isEnabled" => 0])))
                API::error('Must enable all dependencies first: module \'' . $dependency['id'] . '\' is disabled.');
        }
    }

    if ($moduleEnabled != $toEnable) {
        $course->setModuleEnabled($moduleId, !$moduleEnabled);
    }
});



/*** --------------------------------------------- ***/
/*** -------------- Import / Export -------------- ***/
/*** --------------------------------------------- ***/

/**
 * Import modules into the system.
 * FIXME: check if working
 *
 * @param $file
 * @param string $fileName
 */
API::registerFunction($MODULE, 'importModule', function () {
    API::requireAdminPermission();
    API::requireValues('file');
    API::requireValues('fileName');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    Module::importModules($fileContents, API::getValue("fileName"));
    API::response(array());
});

/**
 * Export modules of the system.
 * FIXME: not working.
 */
API::registerFunction($MODULE, 'exportModule', function () {
    API::requireAdminPermission();
    $zipFile = Module::exportModules();
    API::response(array("file"=> $zipFile));
    unlink($zipFile);
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
        'generalInputs' => $module->has_general_inputs() ? $module->get_general_inputs($courseId) : null,
        'listingItems' => $module->has_listing_items() ? $module->get_listing_items($courseId) : null,
        'personalizedConfig' => $module->has_personalized_config() ? $module->get_personalized_function() : null,
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
 * @param $tiersItem (optional)
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
    if (API::hasKey('listingItem'))
        $module->save_listing_item(API::getValue('actionType'), API::getValue('listingItem'), $courseId);

    // Save tiers items
    if (API::hasKey('tiersItem') && $module->getId() === "skills")
        $module->save_tiers(API::getValue('actionType'), API::getValue('tiersItem'), $courseId);
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
 * Change item order sequence.
 *
 * @param int $courseId
 * @param string $moduleId
 * @param int $itemId
 * @param int $oldSeq
 * @param int $newSeq
 * @param string $table
 */
API::registerFunction($MODULE, 'changeItemSequence', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'moduleId', 'itemId', 'oldSeq', 'newSeq', 'table');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $moduleId = API::getValue('moduleId');
    $module = API::verifyModuleExists($moduleId, $courseId);

    $module->changeItemSequence($courseId, API::getValue('itemId'), API::getValue('oldSeq'), API::getValue('newSeq'), API::getValue('table'));
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
