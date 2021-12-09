<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';


use GameCourse\Core;
use GameCourse\Course;

Core::init();

// This is a manual instance that starts Autogame/GameRules
// without automatic invocation though the data source plugin

// To run, use the www-data user:
// sudo -u www-data php modules/plugin/AutoGameScript.php 1
// Replace "1" in the above command with the appropriate course id

// The script receives an argument corresponding to the course
// to run autogame over.

if (sizeof($argv) > 1) {
    if (sizeof($argv) == 2) {
        Course::newExternalData($argv[1]);
    }
    else if (sizeof($argv) == 3) {
        if ($argv[2] == "test") { // run as test
            Course::newExternalData($argv[1], True, null, True);
        }
    }
}
else {
    echo("\nERROR: No course information provided. Please specify course number as argument.\n");
}