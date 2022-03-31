<?php
namespace Api;

use GameCourse\User;

class UserController
{
    public function getUser()
    {
        $user = new User(API::getValue("userId"));
        API::response($user->getName());
    }
}


