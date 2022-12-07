<?php
/**
 * This is the AutoGame script, which imports new data from
 * enabled data sources into the system automatically and,
 * in case there's new information, runs AutoGame.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Course\Course;
use GameCourse\Module\ModuleType;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);
$course = Course::getCourseById($courseId);

$dataSources = array_filter(array_map(function ($moduleId) use ($course) {
    return $course->getModuleById($moduleId);
}, $course->getModules(true, true)), function ($module) {
    return $module->getType() == ModuleType::DATA_SOURCE && method_exists($module, "importData");
});

try {
    $runAutoGame = false;
    foreach ($dataSources as $dataSource) {
        $newData = $dataSource->importData();
        if ($newData) $runAutoGame = true;
    }
    if ($runAutoGame) AutoGame::run($courseId);

} catch (Exception $e) {
    AutoGame::log($courseId, $e->getMessage());
}