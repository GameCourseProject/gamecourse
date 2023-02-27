<?php
/**
 * This is a manual script that imports data from Moodle into GameCourse without automatic invocation.
 *
 * HOW TO USE:
 * Command format: sudo -u www-data php <path-to-import-data-script> <course-ID>
 * (always use the www-data user)
 *
 *  -> Running for all targets:
 *     e.g.: sudo -u www-data php /var/www/html/gamecourse/api/modules/Moodle/scripts/ImportDataManual.php 1
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\Moodle\Moodle;

require __DIR__ . "/../../../inc/bootstrap.php";

$nrArgs = sizeof($argv) - 2;
if ($nrArgs >= 0) {
    $courseId = intval($argv[1]);

    try {
        $course = Course::getCourseById($courseId);
        $moodle = $course->getModuleById(Moodle::ID);

        // Import new data
        $newData = $moodle->importData();
        if ($newData) AutoGame::setToRun($courseId);

    } catch (Throwable $e) {
        Moodle::log($courseId, $e->getMessage() . "\n" . $e->getTraceAsString(), "ERROR");
    }

} else {
    echo ("\nERROR: No course information provided. Please specify course ID as 1st argument.");
}