<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/Moodle.php';

use GameCourse\Core;

Core::init();

$moodle = new Moodle($argv[1]);

//logs primeiro porque é o que tem mais registos
$values = $moodle->getLogs();
$moodle->writeLogsToDB($values);

$values = $moodle->getVotes();
$moodle->writeVotesToDb($values);

$values = $moodle->getQuizGrades();
$moodle->writeQuizGradesToDb($values);

$moodle->updateMoodleConfigTime();
