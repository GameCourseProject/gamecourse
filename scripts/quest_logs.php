<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ensure currect path for autoloader
chdir(__DIR__ . '/../');

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();
Core::init();
Core::checkAccess();

ModuleLoader::scanModules();

if (!Core::getLoggedUser()->isAdmin())
    die('Must be admin to run this script.');

if (!array_key_exists('run', $_POST) && !array_key_exists('course', $_POST)) {
    echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
    echo '<label for="course">Course: </label><input type="number" id="course" name="course">';
    echo '<input type="submit" name="run" value="RUN">';
    echo '</form>';
    exit();
}

function ksortRecursive(&$array, $sort_flags = SORT_REGULAR) {
    if (!is_array($array))
        return false;
    ksort($array, $sort_flags);

    foreach ($array as &$arr)
        ksortRecursive($arr, $sort_flags);
    return true;
}

$course = intval($_POST['course']);

$data = Course::getCourse($course, false)->getModuleData('quest');
if ($data->get('activeQuest') > -1) {
    $quest = $data->getWrapped('quests')->getWrapped($data->get('activeQuest'));
    $info = $quest->get('info');
    ksortRecursive($info);

    echo '<pre>';
    print_r($info);
    echo '</pre>';
} else {
    echo 'No active quest for course ' . $course . '!';
}


