<?php
namespace API;

/**
 * This is the Theme controller, which holds API endpoints for
 * theme related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Theme",
 *     description="API endpoints for theme related actions"
 * )
 */
class ThemeController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getThemes()
    {
        // FIXME: deprecated; refactor when doing themes
        //        check old gamecourse

        API::requireAdminPermission();
        $themes = [$GLOBALS["theme"]];
        API::response(["themes" => $themes, "current" => $GLOBALS["theme"]]);
    }
}
