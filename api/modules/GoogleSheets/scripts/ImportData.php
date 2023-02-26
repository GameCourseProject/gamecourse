<?php
/**
 * This script, which runs automatically at given periods of time,
 * is responsible for importing data from GoogleSheets into GameCourse.
 *
 * Imports data into table 'participation'.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\GoogleSheets\GoogleSheets;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);

try {
    $course = Course::getCourseById($courseId);
    $googlesheets = $course->getModuleById(GoogleSheets::ID);

    // Import new data
    $newData = $googlesheets->importData();
    if ($newData) AutoGame::setToRun($courseId);

} catch (Throwable $e) {
    GoogleSheets::log($courseId, $e->getMessage() . "\n" . $e->getTraceAsString(), "ERROR");
}