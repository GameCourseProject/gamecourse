<?php
/**
 * This file deals with setup: if the request method is a 'GET' it will
 * check whether setup has been already performed; if not, it will set
 * up GameCourse.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use API\API;
use GameCourse\Core\AuthService;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\User\User;
use GameCourse\Views\ViewHandler;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();

if ($_SERVER['REQUEST_METHOD'] == "GET") {  // check setup
    $needsSetup = Core::requireSetup(false);
    echo json_encode(['isSetupDone' => !$needsSetup]);

} else {  // do setup
    // Setup already done; Delete file setup.done to allow
    if (!Core::requireSetup(false))
        API::error('GameCourse setup already done.', 405);

    if (array_key_exists('course-name', $_POST) && array_key_exists('course-color', $_POST) &&
        array_key_exists('teacher-id', $_POST) && array_key_exists('teacher-username', $_POST)) {

        $courseName = $_POST['course-name'];
        $courseColor = $_POST['course-color'];
        $teacherId = $_POST['teacher-id'];
        $teacherUsername = $_POST['teacher-username'];

        Core::resetGameCourse();

        // Init database
        $sql = file_get_contents(ROOT_PATH . "setup/setup.sql");
        Core::database()->executeQuery($sql);

        // Create user in the system
        $user = User::addUser("Teacher", $teacherUsername, AuthService::FENIX, null, $teacherId, null,
            null, true, true);
        Core::setLoggedUser($user);

        // Register default system roles
        Role::setupRoles();

        // Register modules available
        Module::setupModules();

        // Init views
        ViewHandler::setupViews();

        // Create course
        // NOTE: logged user is automatically added as a teacher of the course
        $course = Course::addCourse($courseName, null, date("Y", time()) . "-" . date("Y", strtotime("+1 year")),
            $courseColor, null, null, true, true);

        file_put_contents(ROOT_PATH . "setup/setup.done", "");
        echo json_encode(['setup' => true]);
        exit;
    }

    API::error('Some information is missing. Please fill-in all the fields.', 400);
}

