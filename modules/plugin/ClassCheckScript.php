<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/ClassCheck.php';

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
