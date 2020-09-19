<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/ClassCheck.php';

use GameCourse\Core;

Core::init();

$cc = new ClassCheck(1);
$code = $cc->getDBConfigValues();
if ($code != null) {
    $cc->readAttendance($code);
}
