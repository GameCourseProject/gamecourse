<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Api\API;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\User\User;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();

// Setup already done; Delete file setup.done to allow
if (!Core::requireSetup(false))
    API::error('GameCourse setup already done', 405);

if (array_key_exists('course-name', $_POST) && array_key_exists('teacher-id', $_POST)) {
    $courseName = $_POST['course-name'];
    $courseColor = $_POST['course-color'];
    $teacherId = $_POST['teacher-id'];
    $teacherUsername = $_POST['teacher-username'];

    Core::resetGameCourse();

    // Init database
    $sql = file_get_contents(ROOT_PATH . "setup/setup.sql");
    Core::database()->executeQuery($sql);

    // Create user in the system
    $user = User::addUser("Teacher", $teacherUsername, "fenix", null, $teacherId, null,
        null, true, true);
    Core::setLoggedUser($user);

    // Register modules available
    Module::setupModules();

    // Create course
    // NOTE: user is automatically added as a teacher of the course
    $course = Course::addCourse($courseName, null, null, $courseColor, null, null,
        true, true);

    file_put_contents("./setup.done", "");
    echo json_encode(['setup' => true]);
    exit;
}

echo json_encode(['setup' => false]);

