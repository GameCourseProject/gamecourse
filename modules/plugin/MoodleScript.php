<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/Moodle.php';

use GameCourse\Core;

Core::init();

$moodle = new Moodle($argv[1]);
$values = $moodle->getVotes();
$moodle->writeVotesToDb($values);

$values = $moodle->getQuizGrades();
$moodle->writeQuizGradesToDb($values);

$values = $moodle->getLogs();
$moodle->writeLogsToDB($values);
