<?php
namespace API;

use Exception;
use GameCourse\Module\Module;
use GameCourse\XPLevels\Level;

/**
 * This is the Module controller, which holds API endpoints for
 * module related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Module",
 *     description="API endpoints for module related actions"
 * )
 */
class ModuleController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getModules()
    {
        API::requireAdminPermission();
        API::response(Module::getModules());
    }


    /*** --------------------------------------------- ***/
    /*** --------------- Configuration --------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function getConfig()
    {
        API::requireValues("courseId", "moduleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        if (!$module->isEnabled())
            throw new Exception("Can't get module config information: module '" . $moduleId . "' is not enabled in course with ID = " . $courseId . ".");

        API::response([
            "generalInputs" => $module->getGeneralInputs(),
            "lists" => $module->getLists(),
            "personalizedConfig" => $module->getPersonalizedConfig()
        ]);
    }

    /**
     * @throws Exception
     */
    public function saveConfig()
    {
        API::requireValues("courseId", "moduleId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        // Save general inputs
        if (API::hasKey("generalInputs"))
            $module->saveGeneralInputs(API::getValue("generalInputs"));

        // Save listing items
        if (API::hasKey("listingItem")) {
            API::requireValues("listName", "action");
            $listName = API::getValue("listName");
            $action = API::getValue("action");
            $item = API::getValue("listingItem");
            $module->saveListingItem($listName, $action, $item);
        }

        // NOTE: personalized config should have its own API requests
        //       in custom controllers inside the module
    }

    /**
     * @throws Exception
     */
    public function importItems()
    {
        API::requireValues("courseId", "moduleId", "listName", "file", "replace");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        $listName = API::getValue("listName");
        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrItemsImported = $module->importListingItems($listName, $file, $replace);
        API::response($nrItemsImported);
    }

    /**
     * @throws Exception
     */
    public function exportItems()
    {
        API::requireValues("courseId", "moduleId", "listName");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $moduleId = API::getValue("moduleId");
        $module = API::verifyModuleExists($moduleId, $course);

        $listName = API::getValue("listName");
        $itemId = API::getValue("itemId");

        $csv = $module->exportListingItems($listName, $itemId);
        API::response($csv);
    }
}
