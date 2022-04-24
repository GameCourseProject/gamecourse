<?php
namespace Api;

use GameCourse\Core\Core;
use GameCourse\User\User;

/**
 * This is the User controller, which holds API endpoints for
 * user related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class UserController
{
    /*** --------------------------------------------- ***/
    /*** ---------------- Logged User ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Get logged user information.
     */
    public function getLoggedUserInfo()
    {
        $user = Core::getLoggedUser();
        $userInfo = $user->getData();
        $userInfo["image"] = $user->getImage();
        API::response(["userInfo" => $userInfo]);
    }

    /**
     * Get list of active courses for logged user.
     */
    public function getLoggedUserActiveCourses()
    {
        $user = Core::getLoggedUser();
        $userCourses = $user->getCourses(true);
        API::response(["userActiveCourses" => $userCourses]);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get all users on the system.
     */
    public function getUsers()
    {
        API::requireAdminPermission();
        $users = User::getUsers();
        API::response(["users" => $users]);
    }
}


