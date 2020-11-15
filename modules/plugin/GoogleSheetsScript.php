<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'modules/plugin/GoogleSheets.php';
include 'GameRules.php';

use GameCourse\Core;
use GameRules;

Core::init();

$moodle = new GoogleSheets($argv[1]);
if($moodle->readGoogleSheets()){
    new GameRules();
}
