<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';
include 'modules/plugin/GoogleSheets.php';

use GameCourse\Core;
use GameCourse\Course;

Core::init();

$moodle = new GoogleSheets($argv[1]);
if($moodle->readGoogleSheets()){
    Course::newExternalData($argv[1]);
}
