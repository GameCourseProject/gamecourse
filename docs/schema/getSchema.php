<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

chdir('../../');

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\Course;

Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

if (array_key_exists('list', $_GET)) {
    echo json_encode(array_keys(Core::getCourses()));
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 0);
    $course = Course::getCourse($courseId);
    echo json_encode(\SmartBoards\DataSchema::getFields(array('course' => $courseId)));
}
?>
