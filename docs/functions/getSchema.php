<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(300);

chdir('../../');

include 'classes/ClassLoader.class.php';

use \GameCourse\Core;
use \GameCourse\Course;

Core::init();

if (!Core::requireSetup(false))
    die('Please perform setup first!');
$courses = array_column(Core::getCourses(), "id");
if (array_key_exists('list', $_GET)) {
    echo json_encode(array_column(Core::getCourses(), "id"));
} else {
    $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 1);
    $course = Course::getCourse($courseId, false);

    $modules = array_column(Core::$systemDB->selectMultiple("course_module", ["course" => $courseId, "isEnabled" => true], "moduleId"), "moduleId");
    $enabledLibrariesInfo = $course->getEnabledLibrariesInfo();
    $functionsList = array();

    foreach ($enabledLibrariesInfo as $library) {
        if ($library["moduleId"] == null) {
            $functionInfo = $course->getLibraryFunctions(null);
        } else {
            $functionInfo = $course->getLibraryFunctions($library["id"]);
        }

        $infoList = array();
        foreach ($functionInfo as $info) {
            $args = json_decode($info["args"]);
            $argsString = "";
            $keyArg = $info["keyword"];

            if (is_array($args)) {
                if ($library["name"] == "system" && $info["keyword"] != "if") {
                    for ($i = 0; $i < count($args); $i++) {
                        $argsString .= "%" . $args[$i]->type;
                        if ($i != count($args) - 1) {
                            $argsString .= ", ";
                        }
                    }
                } else {
                    for ($i = 0; $i < count($args); $i++) {
                        if ($args[$i]->optional == 0) {
                            $argsString .= $args[$i]->name;
                        } else {
                            $argsString .= "[" . $args[$i]->name . "]";
                        }
                        if ($i != count($args) - 1) {
                            $argsString .= ", ";
                        }
                    }
                }
                $keyArg = $info["keyword"] . "(" . $argsString . ")";
            }
            if ($info["refersToType"] == "object") {
                if ($info["refersToName"] != NULL) {
                    $infoList["%" . $info["refersToName"] . "." . $keyArg] = $info["description"];
                } else {
                    $infoList["%" . $info["refersToType"] . "." . $keyArg] = $info["description"];
                }
            } else if ($info["refersToType"] == "library") {
                $infoList[$info["name"] . "." . $keyArg] = $info["description"];
            } else {
                $infoList["%" . $info["refersToType"] . "." . $keyArg] = $info["description"];
            }
        }
        array_push(
            $functionsList,
            array(
                "name" => $library["name"],
                "desc" => $library["description"],
                "functions" => $infoList
            )
        );
    }
    echo json_encode($functionsList);
}
