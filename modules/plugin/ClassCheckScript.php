<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'modules/plugin/ClassCheck.php';
include 'GameRules.php';

use GameCourse\Core;
use GameRules;

Core::init();

$cc = new ClassCheck($argv[1]);
$code = $cc->getDBConfigValues();
if ($code != null) {
    if($cc->readAttendance($code)){
        new GameRules();
    }
}
