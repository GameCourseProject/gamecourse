<?php
namespace API;

use Exception;
use GameCourse\Core\Core;

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


    /*** --------------------------------------------- ***/
    /*** ------------------- Users ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets theme for a given user.
     *
     * @param int $userId
     * @throws Exception
     */
    public function getUserTheme()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $loggedUser = Core::getLoggedUser();
        if (!$loggedUser->isAdmin() && $userId != $loggedUser->getId())
            API::error("You don't have permission to request this information.", 403);

        API::response($user->getTheme());
    }

    /**
     * Sets a theme for a given user.
     *
     * @param int $userId
     * @param string $theme
     * @throws Exception
     */
    public function setUserTheme()
    {
        API::requireValues("userId", "theme");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $loggedUser = Core::getLoggedUser();
        if ($userId != $loggedUser->getId())
            API::error("You don't have permission to set another user's theme preference.", 403);

        $theme = API::getValue("theme");
        $user->setTheme($theme);
    }
}
