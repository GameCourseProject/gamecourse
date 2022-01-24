<?php
/*$f = function($abc, $def = 123, $d = 1) {
};
var_dump((new ReflectionFunction($f))->getNumberOfRequiredParameters());
var_dump((new ReflectionFunction($f))->getNumberOfParameters());*/

error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();

Core::checkAccess();

ModuleLoader::scanModules();

//$files = \GameCourse\FileSystem::listFiles('courses/0/moduleData/');
//print_r($files);

//Course::getCourse(0)->getUser(69827)->getWrapper()->set('lastActivity', null);
//Course::getCourse(0)->getUser(69827)->getWrapper()->set('previousActivity', null);

print_r(\GameCourse\FileSystem::getFile('courses/0'));

//$abc = new \GameCourse\FileWrapper('hello', array());
//$abc->set('abc', 123);

/*$db = \GameCourse\FileSystem::loadFile('hello');
$db->set('abc', 321);
echo $db->get('abc');*/

/*$moduleOld = new FlintstoneDB('views', array('dir' => 'config2/courses/0/moduleData/'));
$moduleData = \GameCourse\FileSystem::loadFile('courses/0/moduleData/views');
$moduleData->setValue($moduleOld->getAll());*/

exit();
function rectifyExp(&$exp) {
    $exp = str_replace('course.users.user', 'course.users', $exp);
    $exp = str_replace('users.user', 'users', $exp);
    $exp = str_replace('skills.list.skill', 'skills.list', $exp);
    $exp = str_replace('badges.list.badge', 'badges.list', $exp);
    $exp = str_replace('quests.quest', 'quests', $exp);
    $exp = str_replace('levels.levelNumber', 'levels', $exp);
    $exp = str_replace('xp.levels.level', 'xp.levels', $exp);
    $exp = str_replace('levelsInfo.level', 'levelsInfo', $exp);
    $exp = str_replace('levelDesc.levelDesc', 'levelDesc', $exp);
    $exp = str_replace('skills.tier', 'skills', $exp);
    $exp = str_replace('badges.badges.badge', 'badges.badges', $exp);
    $exp = str_replace('roles.role', 'roles', $exp);
    $exp = str_replace('awards.award', 'awards', $exp);
    $exp = str_replace('levelTime.time', 'levelTime', $exp);
    $exp = str_replace('progress.indicator', 'progress', $exp);
    $exp = str_replace('skills.skills.skill', 'skills.skills', $exp);
    $exp = str_replace('dependencies.dependencies', 'dependencies', $exp);
    $exp = str_replace('dependencies.dependency', 'dependencies', $exp);
    $exp = str_replace('xp.xp', 'xp', $exp);
    $exp = str_replace('count.count', 'count', $exp);
    $exp = str_replace('%dependency.dependency', '%dependency', $exp);
}

function rectifyTableRows(&$rows) {
    for($i = 0; $i < count($rows); ++$i) {
        $row = &$rows[$i];
        rectifyPart($row);
    }
}

function rectifyPart(&$part) {
    if (array_key_exists('data', $part)) {
        foreach ($part['data'] as $k => &$v)
            rectifyExp($v['value']);
    }

    if (array_key_exists('repeat', $part)) {
        rectifyExp($part['repeat']['for']);

        if (array_key_exists('filter', $part['repeat']))
            rectifyExp($part['repeat']['filter']);

        if (array_key_exists('sort', $part['repeat']))
            rectifyExp($part['repeat']['sort']['value']);
    }

    if (array_key_exists('if', $part))
        rectifyExp($part['if']);

    if (array_key_exists('link', $part))
        rectifyExp($part['link']);

    if (array_key_exists('style', $part))
        rectifyExp($part['style']);
    if (array_key_exists('class', $part))
        rectifyExp($part['class']);

    if (array_key_exists('type', $part)) {
        if (($part['type'] == 'value' || $part['type'] == 'image') && $part['valueType'] == 'expression')
            rectifyExp($part['info']);

        if ($part['type'] == 'chart' && $part['chartType'] == 'progress') {
            rectifyExp($part['info']['value']);
            rectifyExp($part['info']['max']);
        }

        if ($part['type'] == 'table') {
            rectifyTableRows($part['headerRows']);
            rectifyTableRows($part['rows']);
        }
    }
}

$course = Course::getCourse(0);
$views = array();
foreach ($course->getModule('views')->getData()->getWrapped('views') as $key => $viewDef) {
    $viewDefArr = $viewDef->getValue();
    if ($viewDef->get('settings')['type'] == \Modules\Views\ViewHandler::VT_SINGLE) {
        $view = &$viewDefArr['view'];
        foreach($view['partlist'] as &$part) {
            rectifyPart($part);
        }

    } else if ($viewDef->get('settings')['type'] == \Modules\Views\ViewHandler::VT_ROLE_SINGLE) {
        $listView = &$viewDefArr['view'];
        foreach ($listView as &$view) {
            foreach($view['partlist'] as &$part) {
                rectifyPart($part);
            }
        }

    } else if ($viewDef->get('settings')['type'] == \Modules\Views\ViewHandler::VT_ROLE_INTERACTION) {
        $listListView = &$viewDefArr['view'];
        foreach ($listListView as &$listView) {
            foreach ($listView as &$view) {
                foreach ($view['partlist'] as &$part) {
                    rectifyPart($part);
                }
            }
        }
    }
    $views[$key] = $viewDefArr;
}
$course->getModule('views')->getViewHandler()->parse('{%abc[%def]}');
$course->getModuleData('views')->set('views', $views);
?>

<html ng-app="Test">
<head>
    <title></title>
    <base href="http://localhost/gamecourse/" target="_blank">
    <script src="js/jquery.min.js"></script>
    <script src="js/angular.min.js"></script>
    <script src="js/d3.min.js"></script>
    <script src="js/angular-ui-router.min.js"></script>
    <script src="js/ocLazyLoad.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/test.js"></script>
    <style>
        textarea {
            height: 18px;
            line-height: 18px;
            resize: none;
            overflow: hidden;
            font-size: 13px;
            font-family: monospace;
        }
    </style>
</head>
<body ng-controller="TestController">
    <script>
    </script>
</body>
</html>