<?php
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

//Check if a photo is created when logging in (by username)
$username = $argv[1];
Core::init();
if ($username) {
    $id = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
    if (!$id) {
        echo $username . " does not exist";
    }else{

        if (file_exists("photos/" . $id . ".png")) {
            echo "Photo was created";
        } else {
            echo "Photo was not created";
        }
    }
} else {
    echo $username . " does not exist";
}
