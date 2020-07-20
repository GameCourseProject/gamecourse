<?php

namespace GameCourse;

class GoogleAuth
{
    public function getUsername()
    {
        return $_SESSION['email'];
    }

    public function getName()
    {
        return $_SESSION['name'];
    }

    public function getEmail()
    {
        return $_SESSION['email'];
    }

    public function getRefreshToken()
    {
        return $_SESSION['refreshToken'];
    }
}
