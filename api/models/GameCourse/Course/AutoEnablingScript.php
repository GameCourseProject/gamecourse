<?php
/**
 * This script is used to enable a given course automatically
 * once it reaches its defined start date.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Course\Course;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);
$course = Course::getCourseById($courseId);

$course->setActive(true);
$course->setAutoEnabling(null);

