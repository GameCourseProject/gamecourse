<?php
/**
 * This script, which runs automatically at given periods of time,
 * is responsible for importing data from Moodle into GameCourse.
 *
 * Imports data into table 'participation'.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\Moodle\Moodle;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);

try {
    $course = Course::getCourseById($courseId);
    $moodle = $course->getModuleById(Moodle::ID);

    // Import new data
    $targets = $moodle->importData();
    if (!empty($targets)) AutoGame::setToRun($courseId, $targets);

} catch (Throwable $e) {
    Moodle::log($courseId, $e->getMessage() . "\n" . $e->getTraceAsString(), "ERROR");
}