<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';
include 'modules/googlesheets/GoogleSheets.php';

use GameCourse\Core;
use GameCourse\Course;
use Modules\GoogleSheets\GoogleSheetsModule;

Core::init();

$googleSheets = new GoogleSheetsModule($argv[1]);
if($googleSheets->readGoogleSheets()){
    Course::newExternalData($argv[1]);
}
