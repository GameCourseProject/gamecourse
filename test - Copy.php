<?php
/*$f = function($abc, $def = 123, $d = 1) {
};
var_dump((new ReflectionFunction($f))->getNumberOfRequiredParameters());
var_dump((new ReflectionFunction($f))->getNumberOfParameters());*/

error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();

Core::checkAccess();

ModuleLoader::scanModules();

$course = Course::getCourse(0);

//$course->getModuleData('views')->set('views', $course->getViews()->getValue());

function processValue(&$value) {
    $value['pid'] = md5(microtime(true) . rand());
}

function processRepeat(&$container, $func) {
    foreach($container as &$child) {
        $func($child);
    }
}

function processRows(&$rows) {
    processRepeat($rows, function(&$row) {
        foreach($row['values'] as &$cell) {
            processPart($cell['value']);
        }
    });
}

function processPart(&$part) {
    if (array_key_exists('header', $part)) {
        processValue($part['header']['title']);
        if (!array_key_exists('type', $part['header']['title']))
            $part['header']['title']['type'] = 'value';
        processValue($part['header']['image']);
        if (!array_key_exists('type', $part['header']['image']))
            $part['header']['image']['type'] = 'image';
    }

    if (array_key_exists('children', $part)) {

        processRepeat($part['children'], function(&$child) {
            processPart($child);
        });
    }

    if ($part['type'] == 'value' || $part['type'] == 'image')
        processValue($part);
    else if ($part['type'] == 'table') {
        $part['pid'] = md5(microtime(true) . rand());
        processRows($part['headerRows']);
        processRows($part['rows']);
    } else
        $part['pid'] = md5(microtime(true) . rand());
}

function processView(&$view) {
    $k = $view;
    $view = array(
        'type' => 'view',
        'pid' => md5(microtime(true) . rand()),
        'content' => $k
    );
    foreach ($view['content'] as &$part) {
        processPart($part);
    }
}


/*echo '<pre>';
$views = $course->getModuleData('views')->get('views');
$course->getModuleData('views')->set('views', $views);
foreach($views as $id => &$v) {
    $allviews = &$v['view'];
    $settings = $v['settings'];
    if ($settings['type'] == \Modules\Views\ViewHandler::VT_ROLE_SINGLE) {
        foreach ($allviews as &$view) {
            processView($view);
        }
    } else if ($settings['type'] == \Modules\Views\ViewHandler::VT_ROLE_INTERACTION) {
        foreach ($allviews as &$viewone) {
            foreach ($viewone as &$view) {
                processView($view);
            }
        }

    }
}*/

$view = unserialize(file_get_contents('views'));
$view = $view['testview']['view']['role.Default'];
processView($view['role.Default']);
echo '<pre>';
print_r($view);
$view = $view['role.Default'];

//$view = $course->getModuleData('views')->getWrapped('views')->getWrapped('testview')->getWrapped('view')->getWrapped('role.Default')->get('role.Default');
$view = \Modules\Views\ViewEditHandler::breakView($view, array());
echo '<pre>';
print_r($view);
//$course->getModuleData('views')->getWrapped('views')->getWrapped('testview')->getWrapped('view')->getWrapped('role.Default')->set('role.Default', $view);

exit();
$viewCopy = unserialize(serialize($view));

$view['replacements'] = array(
    '954787ad46cc9998f14a141c90eebb53' => 'abc',
);

$view['partlist']['abc'] = array(
    'pid' => 'abc',
    'type' => 'block',
    'header' => array(
        'title' => '096d8eee9acd7c90f14bff105c1564cc',
        'image' => '099448d2fbd852031c764658b7620f5f'
    ),
    'children' => array()
);

$view = \Modules\Views\ViewEditHandler::putTogetherView($view, array());
echo '<pre>';
print_r($view);

$broken = \Modules\Views\ViewEditHandler::breakView($view, $viewCopy['partlist']);

echo '<pre>';
print_r($broken);

$fixed = \Modules\Views\ViewEditHandler::putTogetherView($broken, $viewCopy['partlist']);
echo '<pre>';
print_r($fixed);

//\Modules\Views\ViewEditHandler::breakView()

//$course->getModuleData('views')->set('views2', $views);
//print_r($views);
//print_r($course->getModuleData('views')->get('views'));//->getModule('views');

?>

<html ng-app="Test">
<head>
    <title></title>
    <script src="js/jquery.min.js"></script>
    <script src="js/angular.min.js"></script>
    <script src="js/d3.min.js"></script>
    <script src="js/test.js"></script>
</head>
<body ng-controller="TestController">
    <!--<ui-block-view view="profileView" fields="fields" editable="1"></ui-block-view>-->
    <script>
    </script>
</body>
</html>