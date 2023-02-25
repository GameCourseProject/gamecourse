<?php
/**
 * This is a manual script that runs AutoGame without automatic invocation.
 *
 * HOW TO USE:
 * Command format: sudo -u www-data php <path-to-autogame-script> <course-ID> <options>
 * (always use the www-data user)
 *
 *  -> Running for all targets:
 *     e.g.: sudo -u www-data php /var/www/html/gamecourse/api/models/GameCourse/AutoGame/AutoGameScriptManual.php 1
 *
 *  -> Running for specific targets:
 *     e.g.: sudo -u www-data php /var/www/html/gamecourse/api/models/GameCourse/AutoGame/AutoGameScriptManual.php 1 [138,140]
 *
 *  -> Running in test mode:
 *     e.g.: sudo -u www-data php /var/www/html/gamecourse/api/models/GameCourse/AutoGame/AutoGameScriptManual.php 1 test
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;

require __DIR__ . "/../../../inc/bootstrap.php";

$nrArgs = sizeof($argv) - 2;
if ($nrArgs >= 0) {
    $courseId = intval($argv[1]);

    try {
        if ($nrArgs == 0) { // Run for all
            AutoGame::run($courseId, true);

        } else if ($nrArgs == 1) {
            if ($argv[2] == "test") { // Run in test mode
                AutoGame::run($courseId, true, null, true);

            } else { // Run for specific targets
                $targets = array_map(function ($target) {
                    return intval(trim($target));
                }, explode(",", substr($argv[2], 1, -1)));
                AutoGame::run($courseId, false, $targets);
            }
        }

    } catch (Throwable $e) {
        AutoGame::log($courseId, $e->getMessage());
    }

} else {
    echo ("\nERROR: No course information provided. Please specify course ID as 1st argument.");
}