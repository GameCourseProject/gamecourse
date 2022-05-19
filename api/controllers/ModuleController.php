<?php
namespace API;

use GameCourse\Module\Module;

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

    public function getModules()
    {
        API::requireAdminPermission();
        $modules = Module::getModules();
        API::response($modules);
    }
}
