<?php

namespace Modules\Plugin;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';
include 'modules/plugin/Moodle.php';

use GameCourse\Core;
use GameCourse\Course;

Core::init();

$moodle = new Moodle($argv[1]);

//logs primeiro porque é o que tem mais registos
$values = $moodle->getLogs();
$insertedLogs = $moodle->writeLogsToDB($values);

$values = $moodle->getVotes();
$insertedVotes = $moodle->writeVotesToDb($values);

$values = $moodle->getQuizGrades();
$insertedQuiz = $moodle->writeQuizGradesToDb($values);

$moodle->updateMoodleConfigTime();

if ($insertedLogs || $insertedVotes || $insertedQuiz) {
    Course::newExternalData();
}