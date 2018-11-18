<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ensure currect path for autoloader
chdir(__DIR__ . '/../');

include 'classes/ClassLoader.class.php';

use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();
Core::init();
Core::checkAccess();

ModuleLoader::scanModules();

if (!Core::getLoggedUser()->isAdmin())
    die('Must be admin to run this script.');

if (!array_key_exists('run', $_POST)) {
    echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
    echo '<label for="from">From: </label><input type="number" id="from" name="from">';
    echo '<label for="to">To: </label><input type="number" id="to" name="to">';
    echo '<input type="submit" name="run" value="RUN">';
    echo '</form>';
    exit();
}

$courseFrom = $_POST['from'];
$courseTo = $_POST['to'];

$moduleQuestTo = Course::getCourse($courseTo, false)->getModuleData('quest');
$moduleQuestFrom = Course::getCourse($courseFrom, false)->getModuleData('quest');

$moduleQuestTo->set('quests', $moduleQuestFrom->get('quests', array()));
