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

// The script receives an argument corresponding to the course
// to run autogame over.

if ($argv[1]) {
    Course::newExternalData($argv[1]);
}
