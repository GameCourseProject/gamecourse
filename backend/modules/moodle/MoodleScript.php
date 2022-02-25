<?php
namespace Modules\Moodle;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/Course.php';
include 'modules/moodle/Moodle.php';

use GameCourse\Core;
use GameCourse\Course;

Core::init();

$moodle = new Moodle($argv[1]);

//logs primeiro porque é o que tem mais registos
$values = $moodle->getLogsNew();
$insertedLogs = $moodle->writeLogsToDB($values);

$values = $moodle->getVotes();
$insertedVotes = $moodle->writeVotesToDb($values);

$values = $moodle->getPeergrades();
$insertedPeergrades = $moodle->writePeergradesToDB($values);

$values = $moodle->getProfessorRatings();
$insertedProfessorRatings = $moodle->writeVotesToDb($values, true);

$values = $moodle->getQuizGrades();
$insertedQuiz = $moodle->writeQuizGradesToDb($values);

$values = $moodle->getAssignmentGrades();
$insertedAssignment = $moodle->writeAssignmentGradesToDb($values);

$moodle->updateMoodleConfigTime();


if ($insertedLogs || $insertedVotes || $insertedQuiz || $insertedAssignment || $insertedPeergrades || $insertedProfessorRatings) {
    Course::newExternalData($argv[1]);
}
