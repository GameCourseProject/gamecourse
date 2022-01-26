<?php

namespace GameCourse;

error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use GameCourse\Views\Dictionary;
use MagicDB\SQLDB;
use Utils;

require_once 'config.php';
require_once 'cors.php';

if (!defined('CONNECTION_STRING'))
    return;

session_start();
Core::init();

// Setup already done; Delete file setup.done to allow
if (!Core::requireSetup(false))
    API::error('GameCourse setup already done', 405);

if (array_key_exists('course-name', $_POST) && array_key_exists('teacher-id', $_POST)) {
    $courseName = $_POST['course-name'];
    $courseColor = $_POST['course-color'];
    $teacherId = $_POST['teacher-id'];
    $teacherUsername = $_POST['teacher-username'];

    $db = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
    $sql = file_get_contents("setup.sql");
    $db->executeQuery($sql);

    $courseId = 1;
    $db->insert("course", ["name" => $courseName, "id" => $courseId, "color" => $courseColor]);
    Utils::deleteDirectory(COURSE_DATA_FOLDER, array(COURSE_DATA_FOLDER . DIRECTORY_SEPARATOR . 'defaultData'), false);
    $dataFolder = Course::createCourseDataFolder($courseId, $courseName);
    $roleId = Course::insertBasicCourseData($db, $courseId);

    $userId = $db->insert("game_course_user", [
        "studentNumber" => $teacherId,
        "name" => "Teacher",
        "isAdmin" => true
    ]);
    $db->insert("auth", ["id" => 1, "game_course_user_id" => $userId, "username" => $teacherUsername, "authentication_service" => "fenix"]);
    $db->insert("course_user", [
        "id" => $userId,
        "course" => $courseId,
    ]);
    $db->insert("user_role", ["id" => $userId, "course" => $courseId, "role" => $roleId]);
    // insert line in AutoGame table
    $db->insert("autogame", ["course" => $courseId]);

    // prepare autogame
    $rulesfolder = join("/", array($dataFolder, "rules"));
    $functionsFolder = "autogame/imported-functions/" . strval($courseId);
    $logsFolder = "logs";
    $functionsFileDefault = "autogame/imported-functions/defaults.py";
    $defaults = file_get_contents($functionsFileDefault);
    $defaultFunctionsFile = "/defaults.py";
    $metadataFile = "autogame/config/config_" . strval($courseId) . ".txt";
    $logsFile = "logs/log_course_" . strval($courseId) . ".txt";
    mkdir($rulesfolder);
    Utils::deleteDirectory('logs');
    mkdir($logsFolder);
    Utils::deleteDirectory('autogame/imported-functions', array(), false);
    file_put_contents($functionsFileDefault, $defaults);
    mkdir($functionsFolder);

    file_put_contents($functionsFolder . $defaultFunctionsFile, $defaults);
    Utils::deleteDirectory('autogame/config', array('autogame/config' . DIRECTORY_SEPARATOR . 'samples'), false);
    file_put_contents($metadataFile, "");
    file_put_contents($logsFile, "");

    Dictionary::init(true);
    file_put_contents('setup.done', '');

    unset($_SESSION['user']); // if the user was logged and the config folder was destroyed..
    echo json_encode(['setup' => true]);
    exit;
}

echo json_encode(['setup' => false]);

