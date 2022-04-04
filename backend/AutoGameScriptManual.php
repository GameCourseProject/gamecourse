<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';

use GameCourse\Core;
use GameCourse\Course;

Core::init();

// This is a manual instance that starts Autogame/GameRules
// without automatic invocation through the data sources

// To run for all targets, use the www-data user:
// sudo -u www-data php /var/www/html/gamecourse/backend/AutoGameScriptManual.php 1
// Replace "1" in the above command with the appropriate course ID

// To run for specific targets
// sudo -u www-data php AutoGameScript.php 1 [138, 140]

// For testing run
// sudo -u www-data php AutoGameScriptManual.php 1 test

if (sizeof($argv) > 1) {
    if (sizeof($argv) == 2) {
        Course::newExternalData($argv[1], True);
    }
    else if (sizeof($argv) == 3) {
        if ($argv[2] == "test") { // run as test
            Course::newExternalData($argv[1], True, null, True);

        } else { // run for targets
            Course::newExternalData($argv[1], False, $argv[2]);
        }
    }
}
else {
    echo("\nERROR: No course information provided. Please specify course number as argument.\n");
}