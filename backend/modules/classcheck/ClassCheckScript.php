<?php
namespace Modules\ClassCheck;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';
include 'modules/classcheck/ClassCheck.php';


use GameCourse\Core;
use GameCourse\Course;

Core::init();

$cc = new ClassCheck($argv[1]);
$code = $cc->getDBConfigValues();
if ($code != null) {
    if($cc->readAttendance($code)){
      Course::newExternalData($argv[1]);
    }
}
