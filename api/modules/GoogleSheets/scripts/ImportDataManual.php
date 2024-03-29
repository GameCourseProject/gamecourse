<?php
/**
 * This is a manual script that imports data from GoogleSheets into GameCourse without automatic invocation.
 *
 * HOW TO USE:
 * Command format: sudo -u www-data php <path-to-import-data-script> <course-ID>
 * (always use the www-data user)
 *
 *  -> Running for all targets:
 *     e.g.: sudo -u www-data php /var/www/html/gamecourse/api/modules/GoogleSheets/scripts/ImportDataManual.php 1
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\GoogleSheets\GoogleSheets;

require __DIR__ . "/../../../inc/bootstrap.php";

$nrArgs = sizeof($argv) - 2;
if ($nrArgs >= 0) {
    $courseId = intval($argv[1]);

    try {
        $course = Course::getCourseById($courseId);
        $googlesheets = $course->getModuleById(GoogleSheets::ID);

        // Import new data
        $targets = $googlesheets->importData();
        if (!empty($targets)) AutoGame::setToRun($courseId, $targets);

    } catch (Throwable $e) {
        GoogleSheets::log($courseId, $e->getMessage() . "\n" . $e->getTraceAsString(), "ERROR");
    }

} else {
    echo ("\nERROR: No course information provided. Please specify course ID as 1st argument.");
}