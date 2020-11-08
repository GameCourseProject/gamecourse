<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/GoogleSheets.php';

use GameCourse\Core;
use GameRules;

Core::init();

$moodle = new GoogleSheets($argv[1]);
if($moodle->readGoogleSheets()){
    new GameRules();
}
