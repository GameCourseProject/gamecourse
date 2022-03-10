<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use Modules\ClassCheck\ClassCheckModule;
use Modules\GoogleSheets\GoogleSheetsModule;
use Modules\Moodle\MoodleModule;
use Modules\QR\QR;

Core::init();

$courseId = $argv[1];
$course = Course::getCourse($courseId);
$enabledModules = $course->getEnabledModules();

$runAutoGame = false;

// Following are scripts that insert new data from data sources
// enabled in the 'participation' table

// Run ClassCheck script
//if (in_array(ClassCheckModule::ID, $enabledModules)) {
//    $newData = require 'modules/classcheck/ClassCheckScript.php';
//    if ($newData) $runAutoGame = true;
//}

// Run GoogleSheets script
if (in_array(GoogleSheetsModule::ID, $enabledModules)) {
    $newData = require 'modules/googlesheets/GoogleSheetsScript.php';
    if ($newData) $runAutoGame = true;
}

// Run Moodle script
if (in_array(MoodleModule::ID, $enabledModules)) {
    $newData = require 'modules/moodle/MoodleScript.php';
    if ($newData) $runAutoGame = true;
}

// Run QR script
if (in_array(QR::ID, $enabledModules)) {
    $newData = require 'modules/qr/QRScript.php';
    if ($newData) $runAutoGame = true;
}

if ($runAutoGame) Course::newExternalData($courseId);