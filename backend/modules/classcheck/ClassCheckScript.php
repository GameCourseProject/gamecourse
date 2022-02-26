<?php
namespace Modules\ClassCheck;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

Core::init();

$classCheck = new ClassCheck($argv[1]);

$code = $classCheck->getDBConfigValues();
if ($code != null) {
    if ($classCheck->readAttendance($code)) return true;
    else return false;
} return false;
