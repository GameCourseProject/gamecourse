<?php
/**
 * This script is used to import Moodle data automatically
 * into the system when AutoGame runs.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Course\Course;
use GameCourse\Module\Moodle\Moodle;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);
$course = Course::getCourseById($courseId);

$moodle = new Moodle($course);
$moodle->setStartedRunning(date("Y-m-d H:i:s", time()));
$moodle->setIsRunning(true);

try {
    return $moodle->importData();

} finally {
    $moodle->setIsRunning(false);
    $moodle->setFinishedRunning(date("Y-m-d H:i:s", time()));
}
