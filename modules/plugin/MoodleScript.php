<?php

namespace Modules\Plugin;

chdir('../..');
include 'classes/ClassLoader.class.php';
include 'modules/plugin/Moodle.php';

use GameCourse\Core;
use GameRules;

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

if($insertedLogs || $insertedVotes || $insertedQuiz){
    new GameRules();
}