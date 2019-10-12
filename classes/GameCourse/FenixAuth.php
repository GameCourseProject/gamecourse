<?php
namespace GameCourse;

class FenixAuth {
    public function getUsername() {
        return $_SESSION['username'];
    }

    public function getName() {
        return $_SESSION['name'];
    }

    public function getEmail() {
        return $_SESSION['email'];
    }

    public function getRefreshToken() {
        return $_SESSION['refreshToken'];
    }
}
