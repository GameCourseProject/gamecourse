<?php
namespace Api;

use GameCourse\User;

/**
 * This is the User controller, which holds API endpoints
 * for user related actions.
 */
class UserController
{
    public function getUser()
    {
        $user = new User(API::getValue("userId"));
        API::response($user->getName());
    }
}


