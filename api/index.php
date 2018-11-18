<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

chdir('../');

include 'classes/ClassLoader.class.php';

use \SmartBoards\Core;
use \SmartBoards\Course;

Core::init();

if(!Core::requireSetup(false))
    die('Please perform setup first!');

function err($err, $statusCode = 400) {
    http_response_code($statusCode);
    die(json_encode(array('error' => $err)));
}

$method = $_SERVER['REQUEST_METHOD'];
$values = null;
$file = null;

if ($method == 'POST') {
    if (array_key_exists('uploadFile', $_GET)) {
        if ($_SERVER['CONTENT_LENGTH'] <= 5000000)
            $file= file_get_contents('php://input');
        else
            err('FileTooBig', 413);
    } else
        $values = json_decode(file_get_contents('php://input'), true);
}

if (!array_key_exists('request', $_GET))
    err('MissingRequest');

$request = $_GET['request'];
unset($_GET['request']);

$values = ($values == null) ? $_GET : array_merge($values, $_GET);

$headers = getallheaders();

$key = null;
if (array_key_exists('Authorization', $headers)) {
    $auth = explode(' ', $headers['Authorization']);
    if (count($auth) == 2 && $auth[0] == 'Bearer')
        $key = $auth[1];
}

if ($key == null && array_key_exists('key', $values))
    $key = $values['key'];

if ($key == null)
    err('MissingAuth');

function process($thisMethod, $thisRequest, $func) {
    global $method, $request;
    if ($thisMethod == $method && $thisRequest == $request) {
        $func();
        die();
    }
}

function globalAuth($key) {
    $apiKey = Core::getApiKey()->getValue();
    if ($apiKey === false || $apiKey == null || $apiKey != $key)
        err('InvalidKey');
}

function courseAuth($values, $key) {
    if (!array_key_exists('course', $values))
        err('MissingCourse');
    try {
        $course = Course::getCourse($values['course']);
    } catch (Exception $e) {
        err('UnknownCourse');
    }
    $apiKey = $course->getWrapped('apiKey')->getValue();
    if ($apiKey === false || $apiKey != $key)
        err('InvalidKey');
    return $course;
}

function getCourseUser($course, $values) {
    if (!array_key_exists('user', $values))
        err('MissingUser');
    $user = $course->getUser($values['user']);
    if (!$user->exists())
        err('UnknownUser');
    return $user;
}

function getModule($values) {
    if (!array_key_exists('module', $values))
        err('MissingModule');
    return $values['module'];
}

process('GET', 'users', function() use ($values, $key) {
    globalAuth($key);
    echo json_encode(\SmartBoards\User::getUserDbWrapper()->getValue());
});

process('POST', 'users', function() use ($values, $key) {
    globalAuth($key);
    $data = \SmartBoards\User::getUserDbWrapper();
    if (!array_key_exists('update', $values) || !is_array($values['update']))
        err('MissingUpdate');
    foreach ($values['update'] as $k => $val) {
        if ($data->getWrapped($k)->getValue() === false)
            err('UnknownUpdateKey:' . $k);
    }
    foreach ($values['update'] as $k => $val)
        $data->getWrapped($k)->setValue($val);
});

process('GET', 'config', function() use ($values, $key) {
    globalAuth($key);
    echo json_encode(Core::getConfig()->getValue());
});

process('POST', 'config', function() use ($values, $key) {
    globalAuth($key);
    $data = Core::getConfig();
    if (!array_key_exists('update', $values) || !is_array($values['update']))
        err('MissingUpdate');
    foreach ($values['update'] as $k => $val) {
        if ($k == 'apiKey')
            err('ForbiddenKey');
        if ($data->getWrapped($k)->getValue() === false)
            err('UnknownUpdateKey:' . $k);
    }
    foreach ($values['update'] as $k => $val)
        $data->getWrapped($k)->setValue($val);
});

process('GET', 'course', function() use ($values, $key) {
    $course = courseAuth($values, $key);

    echo json_encode($course->getWrapper()->getValue());
});

process('POST', 'course', function() use ($values, $key) {
    $course = courseAuth($values, $key);
    if (!array_key_exists('update', $values) || !is_array($values['update']))
        err('MissingUpdate');
    foreach ($values['update'] as $k => $val) {
        if ($k == 'apiKey')
            err('ForbiddenKey');
        if ($course->getWrapped($k)->getValue() === false)
            err('UnknownUpdateKey:' . $k);
    }
    foreach ($values['update'] as $k => $val) {
        if ($k == 'name')
            Core::getCoursesWrapped()->set($course->getId(), $val);
        $course->getWrapped($k)->setValue($val);
    }
});

process('GET', 'course-user-data', function() use ($values, $key) {
    $course = courseAuth($values, $key);
    $user = getCourseUser($course, $values);
    echo json_encode($user->getData()->getValue());
});

process('POST', 'course-user-data', function() use ($values, $key) {
    $course = courseAuth($values, $key);
    $user = getCourseUser($course, $values);
    $userData = $user->getData();
    if (!array_key_exists('update', $values) || !is_array($values['update']))
        err('MissingUpdate');
    foreach ($values['update'] as $k => $val) {
        if ($userData->getWrapped($k)->getValue() === false)
            err('UnknownUpdateKey:' . $k);
    }
    foreach ($values['update'] as $k => $val)
        $userData->getWrapped($k)->setValue($val);
});

process('GET', 'course-module-data', function() use ($values, $key) {
    $course = courseAuth($values, $key);
    $module = getModule($values);
    $moduleData = $course->getModuleData($module);
    if ($moduleData == null)
        err('UnknownModule');
    echo json_encode($moduleData->getValue());
});

process('POST', 'course-module-data', function() use ($values, $key) {
    $course = courseAuth($values, $key);
    $module = getModule($values);
    $moduleData = $course->getModuleData($module);
    if ($moduleData == null)
        err('UnknownModule');
    if (!array_key_exists('update', $values) || !is_array($values['update']))
        err('MissingUpdate');
    foreach ($values['update'] as $k => $val) {
        if ($moduleData->getWrapped($k)->getValue() === false)
            err('UnknownUpdateKey:' . $k);
    }
    foreach ($values['update'] as $k => $val)
        $moduleData->getWrapped($k)->setValue($val);
});

err('UnknownRequest');