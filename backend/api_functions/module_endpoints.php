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
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $toEnable = API::getValue('isEnabled');
    $moduleId = API::getValue('moduleId');
    $module = ModuleLoader::getModule($moduleId);

    if ($module == null)
        API::error('There is no module with id = ' . $moduleId);

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
 */
API::registerFunction($MODULE, 'getModuleConfigInfo', function () {
    API::requireCourseAdminPermission();

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

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
});

/**
 * Save user input on the module configuration page.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'saveModuleConfigInfo', function () {
    API::requireCourseAdminPermission();

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

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
});
