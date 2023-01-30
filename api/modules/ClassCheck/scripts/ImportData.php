<?php
/**
 * This script, which runs automatically at given periods of time,
 * is responsible for importing data from ClassCheck into GameCourse.
 *
 * Imports data into table 'participation'.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\ClassCheck\ClassCheck;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);

try {
    $course = Course::getCourseById($courseId);
    $classCheck = $course->getModuleById(ClassCheck::ID);

    // Import new data
    $newData = $classCheck->importData();
    if ($newData) AutoGame::setToRun($courseId);

} catch (Exception $e) {
    ClassCheck::log($courseId, $e->getMessage());
}