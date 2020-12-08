<?php
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\GoogleHandler;
use GameCourse\ModuleLoader;
use Modules\Views\ViewHandler;

Core::init();

echo "<h2>Automated Test Script</h2>";

global $success;
global $fail;
$GLOBALS['success'] = 0;
$GLOBALS['fail'] = 0;
global $courseInfo;
$GLOBALS['courseInfo'] = null;

global $pluginInfo;
$GLOBALS['pluginInfo'] = null;

global $lg_1;
$GLOBALS['lg_1'] = [];

global $fi_1;
$GLOBALS['fi_1'] = [];
global $fi_2;
$GLOBALS['fi_2'] = [];
global $fi_3;
$GLOBALS['fi_3'] = [];
global $fi_4;
$GLOBALS['fi_4'] = [];

global $p_1;
$GLOBALS['p_1'] = [];
global $p_2;
$GLOBALS['p_2'] = [];
global $p_3;
$GLOBALS['p_3'] = [];

global $dl_1;
$GLOBALS['dl_1'] = [];
global $dl_2;
$GLOBALS['dl_2'] = [];
global $dl_3;
$GLOBALS['dl_3'] = [];

global $df_1;
$GLOBALS['df_1'] = [];
global $df_2;
$GLOBALS['df_2'] = [];
global $df_3;
$GLOBALS['df_3'] = [];

global $dv_1;
$GLOBALS['dv_1'] = [];
global $dv_2;
$GLOBALS['dv_2'] = [];
global $dv_3;
$GLOBALS['dv_3'] = [];

global $u_1;
$GLOBALS['u_1'] = [];
global $u_2;
$GLOBALS['u_2'] = [];
global $u_3;
$GLOBALS['u_3'] = [];

global $cou_1;
$GLOBALS['cou_1'] = [];
global $cou_2;
$GLOBALS['cou_2'] = [];
global $cou_3;
$GLOBALS['cou_3'] = [];

global $c_1;
$GLOBALS['c_1'] = [];
global $c_2;
$GLOBALS['c_2'] = [];
global $c_3;
$GLOBALS['c_3'] = [];

$GLOBALS['courseInfo'] = testCourse();
$GLOBALS['pluginInfo'] = testPlugin();
$GLOBALS['dictionaryInfo'] = testDictionary();

function testCourse()
{
    if (array_key_exists("course", $_GET)) {
        if ($_GET["course"] != "") {
            $course = $_GET["course"];
            if (Core::$systemDB->select("course", ["id" => $course])) {
                return 1;
            } else {
                return -1;
            }
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}
function testPlugin()
{
    $course = $_GET["course"];
    $courseObj = Course::getCourse($course);
    if ($courseObj->getModule("plugin")) {
        return 1;
    } else {
        return 0;
    }
}
function testDictionary()
{
    $course = $_GET["course"];
    $courseObj = Course::getCourse($course);
    if ($courseObj->getModule("views")) {
        return 1;
    } else {
        return 0;
    }
}
testPhotoDownload();
if ($GLOBALS['courseInfo'] == 0) {
    testUserImport();
    // return "<strong style='color:#F7941D;'>Warning:</strong> If you desire to test the whole script, please specify a course id as an URL parameter: ?course=1 or &course=1.";
} else if ($GLOBALS['courseInfo'] == -1) {
    testUserImport();
    // return "<strong style='color:#F7941D;'>Warning:</strong> There is no course with id " . $_GET["course"];
} else if ($GLOBALS['courseInfo'] == 1) {
    $course = $_GET["course"];
    $courseObj = Course::getCourse($course);
    if ($courseObj->getModule("plugin")) {
        testFenixPlugin($course);
        testMoodlePlugin($course);
        testClassCheckPlugin($course);
        testGoogleSheetsPlugin($course);
    }
    testDictionaryManagement($course);
    testUserImport();
    testCourseUserImport($course);
    testCourseImport();
}

function testPhotoDownload()
{
    if (array_key_exists("username", $_GET)) {
        $username = $_GET["username"];

        if ($username) {
            $id = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
            if (!$id) {
                // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist.</h3>";
                $GLOBALS['lg_1'] = ["warning", "<strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist."];
            } else {

                if (file_exists("photos/" . $id . ".png")) {

                    // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Photo was created.</h3>";
                    $GLOBALS['lg_1'] =  ["success", "<strong style='color:green'>Success:</strong> Photo was created."];
                    $GLOBALS['success']++;
                } else {
                    // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Photo was not created.</h3>";
                    $GLOBALS['lg_1'] =  ["fail", "<strong style='color:red'>Fail:</strong> Photo was not created."];
                    $GLOBALS['fail']++;
                }
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist.</h3>";
            $GLOBALS['lg_1'] =  ["warning", "<strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist."];
        }
    } else {
        // " If you desire to test the download of the login picture,  please specify a username as an URL parameter: ?username=istxxxxx or &username=istxxxxx"
        $GLOBALS['lg_1'] =  ["warning", "<strong style='color:#F7941D; '>Warning:</strong> If you desire to test the download of the login picture,  please specify a username as an URL parameter: ?username=istxxxxx or &username=istxxxxx"];
    }
}
function testFenixPlugin($course)
{
    $fenix = array();
    array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
    array_push($fenix, "ist112345;12345;João Silva;js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
    array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

    $usersInfo = checkFenix($fenix, $course);

    if ($usersInfo[0] == 2 && $usersInfo[1] == 0) {
        $gcu1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
        $gcu2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "99999"]);
        if ($gcu1 && $gcu2) {
            $GLOBALS['success']++;
            $GLOBALS['fi_1'] =  ["success", "<strong style='color:green; '>Success:</strong> Users uploaded."];

            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users uploaded.</h3>";
            $courseUser1 = Core::$systemDB->select("course_user", ["id" => $gcu1["id"]]);
            $courseUser2 = Core::$systemDB->select("course_user", ["id" => $gcu2["id"]]);
            if ($courseUser1 && $courseUser2) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users uploaded.</h3>";
                $GLOBALS['fi_2'] =  ["success", "<strong style='color:green; '>Success:</strong> Course Users uploaded."];

                $GLOBALS['success']++;
            } else {
                $GLOBALS['fi_2'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Course Users failed to upload."];

                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Course Users Fail to upload.</h3>";
                $GLOBALS['fail']++;
            }
        } else {
            $GLOBALS['fi_1'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Users failed to upload."];

            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users Fail to upload.</h3>";
            $GLOBALS['fail']++;
        }

        $auth1 = Core::$systemDB->select("auth", ["username" => "ist112345"]);
        $auth2 = Core::$systemDB->select("auth", ["username" => "ist199999"]);
        if ($auth1 && $auth2) {
            $GLOBALS['fi_3'] =  ["success", "<strong style='color:green; '>Success:</strong> Users' authentication uploaded."];
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users' authentication uploaded.</h3>";
            $GLOBALS['success']++;
        } else {
            $GLOBALS['fi_3'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Users' authentication failed to upload."];
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users' authentication Fail to upload.</h3>";
            $GLOBALS['fail']++;
        }
    } else {
        $GLOBALS['fi_1'] =  ["fail", "<strong style='color:red;'>Fail:</strong> Users were not created correctly."];
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not created correctly.</h3>";
        $GLOBALS['fail']++;
    }

    $fenix = array();
    $updatedName = "João Silvestre";
    array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
    array_push($fenix, "ist112345;12345;" . $updatedName . ";js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
    array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

    $usersInfo = checkFenix($fenix, $course);
    if ($usersInfo[0] == 0 && $usersInfo[1] == 2) {
        $gcu = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
        if ($gcu) {
            if ($gcu["name"] == $updatedName) {
                $GLOBALS['fi_4'] =  ["success", "<strong style='color:green;'>Success:</strong> Users updated."];
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong>  User updated.</h3>";
                $GLOBALS['success']++;
            } else {
                $GLOBALS['fi_4'] =  ["fail", "<strong style='color:red;'>Fail:</strong> Users failed to update."];
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> User Fail to update</h3>";
                $GLOBALS['fail']++;
            }
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users where not updated correctly</h3>";
        $GLOBALS['fail']++;
    }
}

function testMoodlePlugin($course)
{
    echo "\n";
    $moodleVar = [
        "dbserver" => "localhost",
        "dbuser" => "root",
        "dbpass" => null,
        "db" => "moodle",
        "dbport" => "3306",
        "prefix" => "mdl_",
        "time" => null,
        "course" => null,
        "user" => null
    ];
    $resultMoodle = setMoodleVars($course, $moodleVar);
    if ($resultMoodle) {
        if (Core::$systemDB->select("config_moodle", [
            "dbServer" => "localhost",
            "dbUser" => "root",
            "dbPass" => null,
            "dbName" => "moodle",
            "dbPort" => "3306",
            "tablesPrefix" => "mdl_",
            "moodleTime" => null,
            "moodleCourse" => null,
            "moodleUser" => null
        ])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Moodle variables were set.</h3>";
            $GLOBALS['p_1'] =  ["success", "<strong style='color:green; '>Success:</strong> Moodle variables were set."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Moodle Variables were not inserted in the database</h3>";
            $GLOBALS['p_1'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Moodle variables were not inserted in the database."];
            $GLOBALS['fail']++;
        }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to set moodle variables.</h3>";
        $GLOBALS['p_1'] =  ["fail", "<strong style='color:red; '>Fail:</strong> It was not possible to set the Moodle variables."];
        $GLOBALS['fail']++;
    }
}

function testClassCheckPlugin($course)
{

    $ccVar = ["tsvCode" => "8c691b7fc14a0455386d4cb599958d3"];
    $resultCC = setClassCheckVars($course, $ccVar);
    if ($resultCC) {
        if (Core::$systemDB->select("config_class_check", $ccVar)) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Class Check variables were set.</h3>";
            $GLOBALS['p_2'] =  ["success", "<strong style='color:green; '>Success:</strong> Class Check variables were set."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Class Check Variables were not inserted in the database</h3>";
            $GLOBALS['p_2'] =  ["fail", "<strong style='color:red;'>Fail:</strong> Class Check variables were not inserted in the database."];
            $GLOBALS['fail']++;
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to set classChc variables.</h3>";
        $GLOBALS['p_2'] =  ["fail", "<strong style='color:red;'>Fail:</strong> It was not possible to set the Class Check variables."];
        $GLOBALS['fail']++;
    }
}

function testGoogleSheetsPlugin($course)
{
    if (Core::$systemDB->select("config_google_sheets", ["course" => $course])) {


        $resultGSVars = setGoogleSheetsVars($course, array(
            "spreadsheetId" => "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U",
            "sheetName" => array("ist13898_")
        ));
        if ($resultGSVars) {
            $checkDB_GS = array(
                "spreadsheetId" => "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U",
                "sheetName" => "ist13898_"
            );
            if (Core::$systemDB->select("config_google_sheets", $checkDB_GS)) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Google Sheets variables were set.</h3>";
                $GLOBALS['p_3'] =  ["success", "<strong style='color:green; '>Success:</strong> Google Sheets variables were set."];
                $GLOBALS['success']++;
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Google Sheets variables were not inserted in the database.</h3>";
                $GLOBALS['p_3'] =  ["fail", "<strong style='color:red; '>Fail:</strong> Google Sheets variables were not inserted in the database."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to set Google Sheets variables.</h3>";
            $GLOBALS['p_3'] =  ["fail", "<strong style='color:red; '>Fail:</strong> It was not possible to set the Google Sheets variables."];
            $GLOBALS['fail']++;
        }
    } else {
        // echo "<h3 style='font-weight: normal'>
        // <strong style='color:#F7941D;'>Warning:</strong> Make sure you authenticate to access to Google Sheets for course " . $course . "."
        //     . "</h3>";
        $GLOBALS['p_3'] =  ["warning", "<strong style='color:#F7941D; '>Warning:</strong>Make sure you authenticate to access to Google Sheets for course " . $course . "."];
    }
}

function testDictionaryManagement($course)
{
    // echo "<h2>Dictionary</h2>";
    $courseObj = Course::getCourse($course);
    $viewModule = $courseObj->getModule('views');
    if ($viewModule) {

        $viewHandler = $viewModule->getViewHandler();

        //insert library
        $viewHandler->registerLibrary("views", "games", "This library contains information about the course games");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This library contains information about the course games"
        ])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library created.</h3>";
            $GLOBALS['dl_1'] = ["success", "<strong style='color:green'>Success:</strong> Library created."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Library was not created.</h3>";
            $GLOBALS['dl_1'] = ["fail", "<strong style='color:red'>Fail:</strong> Library was not created."];
            $GLOBALS['fail']++;
        }

        //update library
        $viewHandler->registerLibrary("views", "games", "This is a game's library");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This is a game's library"
        ])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library updated.</h3>";
            $GLOBALS['dl_2'] = ["success", "<strong style='color:green'>Success:</strong> Library updated."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Library was not updated.</h3>";
            $GLOBALS['dl_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Library was not updated."];
            $GLOBALS['fail']++;
        }

        //delete library
        $viewHandler->unregisterLibrary("views", "games");
        if (!Core::$systemDB->select("dictionary_library", ["name" => "games", "moduleId" => "views"])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library deleted.</h3>";
            $GLOBALS['dl_3'] = ["success", "<strong style='color:green'>Success:</strong> Library deleted."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Library was not deleted.</h3>";
            $GLOBALS['dl_3'] = ["fail", "<strong style='color:red'>Fail:</strong> Library was not deleted."];
            $GLOBALS['fail']++;
        }

        //insert function
        $viewHandler->registerFunction(
            "courses",
            "color",
            function ($course) {
                return $this->basicGetterFunction($course, "color");
            },
            "Returns the course color.",
            "string",
            null,
            "object",
            "course"
        );

        $id = Core::$systemDB->select("dictionary_library", ["name" => "courses"], "id");
        if ($id) {
            if (Core::$systemDB->select("dictionary_function", [
                "libraryId" => $id, "returnType" => "string", "returnName" => null, "refersToType" => "object",
                "refersToName" => "course", "keyword" => "color", "args" => null,
                "description" =>  "Returns the course color."
            ])) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function created.</h3>";
                $GLOBALS['df_1'] = ["success", "<strong style='color:green'>Success:</strong> Function created."];
                $GLOBALS['success']++;
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Function was not created.</h3>";
                $GLOBALS['df_1'] = ["fail", "<strong style='color:red'>Fail:</strong> Function was not created."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to register the function.</h3>";
            $GLOBALS['df_1'] = ["fail", "<strong style='color:red'>Fail:</strong> It was not possible to register the function."];
            $GLOBALS['fail']++;
        }

        //update function
        $func = function ($course, bool $toRGB = false) {
            $color = $this->basicGetterFunction($course, "color");
            if ($toRGB) {
                $color = "(255,255,255)";
            }
        };

        $viewHandler->registerFunction(
            "courses",
            "color",
            $func,
            "Color RGB or HEX.",
            "string",
            null,
            "object",
            "course"
        );
        $args = argsToJSON($func, "object", "course");
        if ($id) {
            if (Core::$systemDB->select("dictionary_function", [
                "libraryId" => $id, "returnType" => "string", "returnName" => null, "refersToType" => "object",
                "refersToName" => "course", "keyword" => "color", "args" => $args,
                "description" =>  "Color RGB or HEX."
            ])) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function updated.</h3>";
                $GLOBALS['df_2'] = ["success", "<strong style='color:green'>Success:</strong> Function updated."];
                $GLOBALS['success']++;
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Function was not updated.</h3>";
                $GLOBALS['df_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Function was not updated."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to register the function</h3>";
            $GLOBALS['df_2'] = ["fail", "<strong style='color:red'>Fail:</strong> It was not possible to register the function."];
            $GLOBALS['fail']++;
        }
        //delete function
        $viewHandler->unregisterFunction("color", "views");
        if (!Core::$systemDB->select("dictionary_function", ["keyword" => "games", "libraryId" => $id])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function deleted.</h3>";
            $GLOBALS['df_3'] = ["success", "<strong style='color:green'>Success:</strong> Function deleted."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Function was not deleted.</h3>";
            $GLOBALS['df_3'] = ["fail", "<strong style='color:red'>Fail:</strong> Function was not deleted."];
            $GLOBALS['fail']++;
        }

        //insert variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns the role of the user that is viewing the page");
        $id = Core::$systemDB->select("dictionary_library", ["name" => "users"], "id");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns the role of the user that is viewing the page"
            ])) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable created.</h3>";
                $GLOBALS['dv_1'] = ["success", "<strong style='color:green'>Success:</strong> Variable created."];
                $GLOBALS['success']++;
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Variable was not created.</h3>";
                $GLOBALS['dv_1'] = ["fail", "<strong style='color:red'>Fail:</strong> Variable was not created."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to register the variable.</h3>";
            $GLOBALS['dv_1'] = ["fail", "<strong style='color:red'>Fail:</strong>It was not possible to register the variable."];
            $GLOBALS['fail']++;
        }

        //update variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns roles");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns roles"
            ])) {
                // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable updated.</h3>";
                $GLOBALS['dv_2'] = ["success", "<strong style='color:green'>Success:</strong> Variable updated."];
                $GLOBALS['success']++;
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Variable was not updated.</h3>";
                $GLOBALS['dv_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Variable was not updated."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> It was not possible to register the variable.</h3>";
            $GLOBALS['dv_2'] = ["fail", "<strong style='color:red'>Fail:</strong>It was not possible to register the variable."];
            $GLOBALS['fail']++;
        }
        //delete variable
        $viewHandler->unregisterVariable("%roles");
        if (!Core::$systemDB->select("dictionary_variable", ["name" => "%roles"])) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable deleted.</h3>";
            $GLOBALS['dv_3'] = ["success", "<strong style='color:green'>Success:</strong> Variable deleted."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Variable was not deleted.</h3>";
            $GLOBALS['dv_3'] = ["fail", "<strong style='color:red'>Fail:</strong> Variable was not deleted."];
            $GLOBALS['fail']++;
        }
    } else {
        // echo "<h3 style='font-weight: normal'>
        // <strong style='color:#F7941D;'>Warning:</strong> To test the dictionary, please enable the views module.
        // </h3>";

    }
}

function testUserImport()
{
    // echo "<h2>Import/Export Users</h2>";
    //adds users
    $csvUsers = CourseUser::exportUsers();
    file_put_contents("UsersCSVTesteUnit.csv", $csvUsers);
    $usersCSV = file_get_contents("UsersCSVTesteUnit.csv");

    if ($usersCSV) {
        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users exported.</h3>";
        $GLOBALS['u_1'] = ["success", "<strong style='color:green'>Success:</strong> Users exported."];
        $GLOBALS['success']++;
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users not exported.</h3>";
        $GLOBALS['u_1'] = ["fail", "<strong style='color:red'>Fail:</strong> Users not exported."];
        $GLOBALS['fail']++;
    }
    $usersCSV .= "\nJoão Bernardo,,,98765,0,1,jb@gmail.pt,google\n";
    $usersCSV .= "Olivia Nogueira,olivia.nogueira@mail.pt,,56789,0,1,olivia.nog@gmail.com,google\n";
    User::importUsers($usersCSV, false);

    $user1Id = Core::$systemDB->select("game_course_user", [
        "name" => "João Bernardo",
        "email" => "",
        "nickname" => "",
        "studentNumber" => "98765",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");

    $user2Id = Core::$systemDB->select("game_course_user", [
        "name" => "Olivia Nogueira",
        "email" => "olivia.nogueira@mail.pt",
        "nickname" => "",
        "studentNumber" => "56789",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    if ($user1Id && $user2Id) {
        $user1Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user1Id,
            "username" => "jb@gmail.pt",
            "authentication_service" => "google"
        ]);
        $user2Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user2Id,
            "username" => "olivia.nog@gmail.com",
            "authentication_service" => "google"
        ]);
        // if ($user1Auth && $user2Auth) {
        //     echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users imported - created.</h3>";
        // } else {
        //     echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not correctly imported - not created.</h3>";
        // }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not correctly imported - not created.</h3>";
    }
    //does not update users
    $usersCSV = "name,email,nickname,studentNumber,isAdmin,isActive,username,auth\n";
    $usersCSV .= "Joaquim Duarte,,,98765,0,1,jb@gmail.pt,google\n";
    $usersCSV .= "Olivia Nogueira,olivia.nogueira@mail.pt,,56789,1,1,olivia.nog@gmail.com,google\n";

    User::importUsers($usersCSV, false);

    if ($user1Id && $user2Id) {
        $user1Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user1Id,
            "username" => "jb@gmail.pt",
            "authentication_service" => "google"
        ]);
        $user2Auth =  Core::$systemDB->select("game_course_user", [
            "id" => $user2Id,
            "isAdmin" => "0"
        ]);
        if ($user1Auth && $user2Auth) {
            // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users imported - created and updated (not replaced).</h3>";
            $GLOBALS['u_2'] = ["success", "<strong style='color:green'>Success:</strong> Users imported - created and updated (not replaced)."];
            $GLOBALS['success']++;
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not correctly imported - not updated without replace.</h3>";
            $GLOBALS['u_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Users were not correctly imported -  not updated without replace."];
            $GLOBALS['fail']++;
        }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not correctly imported - not updated without replace.</h3>";
        $GLOBALS['u_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Users were not correctly imported -  not updated without replace."];
        $GLOBALS['fail']++;
    }
    //update users
    User::importUsers($usersCSV, true);

    $user3Id =  Core::$systemDB->select("auth", [
        "username" => "jb@gmail.pt",
        "authentication_service" => "google"
    ], "game_course_user_id");

    $user3 = Core::$systemDB->select("game_course_user", [
        "id" => $user3Id,
        "name" => "Joaquim Duarte",
        "studentNumber" => "98765",
        "isAdmin" => "0",
        "isActive" => "1"
    ]);

    $user4Id = Core::$systemDB->select("game_course_user", [
        "name" => "Olivia Nogueira",
        "email" => "olivia.nogueira@mail.pt",
        "studentNumber" => "56789",
        "isAdmin" => "1",
    ], "id");
    if ($user3 && $user4Id) {
        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users imported - updated (and replaced).</h3>";
        $GLOBALS['u_3'] = ["success", "<strong style='color:green'>Success:</strong> Users imported - updated (and replaced)."];
        $GLOBALS['success']++;
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Users were not correctly imported - not updated with replace.</h3>";
        $GLOBALS['u_3'] = ["fail", "<strong style='color:red'>Fail:</strong> Users were not correctly imported - not updated with replace."];
        $GLOBALS['fail']++;
    }
    Core::$systemDB->delete("game_course_user", ["id" => $user1Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user2Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user3Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user4Id]);
    unlink("UsersCSVTesteUnit.csv");
}
function testCourseImport()
{
    // echo "<h2>Import/Export Courses</h2>";

    $courseID = Core::$systemDB->select("course", ["name" => "Course X", "year" => "2017-2018"], "id");
    $courseObj = null;
    if ($courseID) {
        $courseObj = Course::getCourse($courseID, false);
        if (in_array("views", $courseObj->getEnabledModules()) && in_array("badges", $courseObj->getEnabledModules())) {
            $viewPage = Core::$systemDB->select("page", ["course" => $courseID], "viewId");
            $templateId = Core::$systemDB->select("template", ["course" => $courseID, "roleType" => "ROLE_SINGLE"], "id");
            if ($viewPage) {
                if (Core::$systemDB->select("view", ["partType" => "text", "parent" => $viewPage])) {
                    if ($templateId) {
                        $viewIdTemplate =  Core::$systemDB->select("view_template", ["templateId" => $templateId], "viewId");
                        if ($viewIdTemplate) {
                            if (Core::$systemDB->select("view", ["partType" => "image", "parent" => $viewIdTemplate])) {
                                if (Core::$systemDB->select("badges_config", ["maxBonusReward" => "2000", "course" => $courseID])) {
                                    $json = Course::exportCourses();
                                    // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses exported.</h3>";
                                    $GLOBALS['c_1'] = ["success", "<strong style='color:green'>Success:</strong> Courses exported."];
                                    $GLOBALS['success']++;
                                    importCourses($json, $viewPage, $viewIdTemplate, $courseID, $templateId);
                                    unlink("coursesJSONTesteUnit.json");
                                } else {
                                    // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D;'>Warning: </strong>Enable badges and set maxReward to 2000</h3>";
                                    $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong>Enable badges and set maxReward to 2000."];
                                }
                            } else {
                                // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>You created a template (role single), but it does not contain a image in 'Course X'</h3>";
                                $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>You created a template (role single), but it does not contain a image in 'Course X'."];
                            }
                        } else {
                            // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>Create a template (role single) containing an image</h3>";
                            $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>Create a template (role single) containing an image."];
                        }
                    } else {
                        // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>Create a template (role single) containing an image</h3>";
                        $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>Create a template (role single) containing an image."];
                    }
                } else {
                    // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>You created a page (role single), but it does not contain a text in 'Course X'</h3>";
                    $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>You created a page (role single), but it does not contain a text in 'Course X'."];
                }
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>Create a page (role single) contaning a text in 'Course X'</h3>";
                $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>Create a page (role single) contaning a text in 'Course X'."];
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong>Enable module views and badges</h3>";
            $GLOBALS['c_1'] = ["warning", "<strong style='color:#F7941D;'>Warning: </strong><strong style='color:#F7941D; '>Warning: </strong>Enable module views and badges."];
        }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning: </strong> To test the course's import and export, do the following:</h3>";
        // echo "<ul>";
        // echo "<li><h3 style='padding:0px;font-weight:normal'>Create a course named 'Course X' and year '2017/2018'.</h3></li>";
        // echo "<li><h3 style='padding:0px;font-weight:normal'>Inside that course, enable views and create a page contaning a text (role single).</h3></li>";
        // echo "<li><h3 style='padding:0px;font-weight:normal'>Create a template containing an image (role single).</h3></li>";
        // echo "<li><h3 style='padding:0px;font-weight:normal'>Enable badges and set maxReward to 2000.</h3></li>";
        // echo "</ul>";

        $GLOBALS['c_1'] = ["warning", "
        <strong style='color:#F7941D;'>Warning: </strong>To test the course's import and export, do the following:
        <ul>
        <li>Create a course named 'Course X' and year '2017/2018'.</li>
        <li>Inside that course, enable views and create a page contaning a text (role single).</li>
        <li>Create a template containing an image (role single).</li>
        <li>Enable badges and set maxReward to 2000.</li>
        </ul>"];
    }
}
function testCourseUserImport($course)
{
    // echo "<h2>Import/Export Course Users</h2>";
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "77777"]);
    $id = User::addUserToDB("Hugo Sousa", "ist11111", "fenix", "hugo@mail.com", "77777",  null, 0, 1);
    $courseUser = new CourseUser($id, new Course($course));
    $roleId = Core::$systemDB->select("role", ["course" => $course, "name" => "student"], "id");
    $courseUser->addCourseUserToDB($roleId, "");
    $csvCourseUsers = CourseUser::exportCourseUsers($course);
    file_put_contents("courseUsersCSVTesteUnit.csv", $csvCourseUsers);
    $usersCSV = file_get_contents("courseUsersCSVTesteUnit.csv");
    if ($usersCSV) {
        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users exported.</h3>";
        $GLOBALS['cou_1'] = ["success", "<strong style='color:green'>Success:</strong>  Course Users exported."];
        $GLOBALS['success']++;
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Course Users not exported.</h3>";
        $GLOBALS['cou_1'] = ["fail", "<strong style='color:red'>Fail:</strong>  Course Users not exported."];
        $GLOBALS['fail']++;
    }
    //adds users
    $usersCSV = "name,email,nickname,studentNumber,isAdmin,isActive,campus,roles,username,auth\n";
    $usersCSV .= "Hugo Sousa Silva,hugo@mail.com,,77777,0,1,A,Student,ist11111,fenix\n";
    $usersCSV .= "Joaquim Duarte,,,98765,0,1,A,Student,jb@gmail.pt,linkedin\n";
    $usersCSV .= "Mónica Trindade,,,55555,0,1,T,Student,m.trindade@mail.com,google\n";
    file_put_contents("courseUsersCSVTesteUnit.csv", $usersCSV);

    CourseUser::importCourseUsers($usersCSV, $course, false);

    $user1Id = Core::$systemDB->select("game_course_user", [
        "name" => "Joaquim Duarte",
        "studentNumber" => "98765",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser1Id = Core::$systemDB->select("course_user", [
        "id" => $user1Id,
        "course" => $course
    ]);
    $user2Id = Core::$systemDB->select("game_course_user", [
        "name" => "Hugo Sousa",
        "email" => "hugo@mail.com",
        "studentNumber" => "77777",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser2Id = Core::$systemDB->select("course_user", [
        "id" => $user2Id,
        "course" => $course
    ]);
    $user3Id = Core::$systemDB->select("game_course_user", [
        "name" => "Mónica Trindade",
        "studentNumber" => "55555",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser3Id = Core::$systemDB->select("course_user", [
        "id" => $user3Id,
        "course" => $course
    ]);
    if ($courseUser1Id && $courseUser2Id && $courseUser3Id) {
        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users imported - created and updated (not replaced).</h3>";
        $GLOBALS['cou_2'] = ["success", "<strong style='color:green'>Success:</strong>  Course Users imported - created and updated (not replaced)."];
        $GLOBALS['success']++;
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Course Users not imported - created and updated (not replaced).</h3>";
        $GLOBALS['cou_2'] = ["fail", "<strong style='color:red'>Fail:</strong> Course Users not imported - created and updated (not replaced)."];
        $GLOBALS['fail']++;
    }

    CourseUser::importCourseUsers($usersCSV, $course, true);

    $user1Id = Core::$systemDB->select("game_course_user", [
        "name" => "Joaquim Duarte",
        "studentNumber" => "98765",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser1Id = Core::$systemDB->select("course_user", [
        "id" => $user1Id,
        "course" => $course
    ]);
    $user2Id = Core::$systemDB->select("game_course_user", [
        "name" => "Hugo Sousa Silva",
        "email" => "hugo@mail.com",
        "studentNumber" => "77777",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser2Id = Core::$systemDB->select("course_user", [
        "id" => $user2Id,
        "course" => $course
    ]);
    $user3Id = Core::$systemDB->select("game_course_user", [
        "name" => "Mónica Trindade",
        "studentNumber" => "55555",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    $courseUser3Id = Core::$systemDB->select("course_user", [
        "id" => $user3Id,
        "course" => $course
    ]);

    if ($courseUser1Id && $courseUser2Id && $courseUser3Id) {
        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users imported - updated (and replaced).</h3>";
        $GLOBALS['cou_3'] = ["success", "<strong style='color:green'>Success:</strong>  Course Users imported - updated (and replaced)."];
        $GLOBALS['success']++;
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Course Users not imported.</h3>";
        $GLOBALS['cou_3'] = ["fail", "<strong style='color:red'>Fail:</strong> Course Users not imported."];
        $GLOBALS['fail']++;
    }
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "12345"]);
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "99999"]);
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "77777"]);
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "98765"]);
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "55555"]);
    unlink("courseUsersCSVTesteUnit.csv");
}

function importCourses($json, $viewPage, $viewIdTemplate, $courseID, $templateId)
{
    file_put_contents("coursesJSONTesteUnit.json", $json);
    if (file_exists("coursesJSONTesteUnit.json")) {
        $jsonContent = file_get_contents("coursesJSONTesteUnit.json");
        Course::importCourses($jsonContent, false);
        if (Core::$systemDB->select("view", ["partType" => "text", "parent" => $viewPage])) {
            if (Core::$systemDB->select("view", ["partType" => "image", "parent" => $viewIdTemplate])) {
                if (Core::$systemDB->select("badges_config", ["maxBonusReward" => "2000", "course" => $courseID])) {
                    // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses imported - updated (not replaced).</h3>";
                    $GLOBALS['c_2'] = ["success", "<strong style='color:green;'>Success: </strong>Courses imported - updated (not replaced)."];
                    $GLOBALS['success']++;
                    //mudar o maxReward
                    Core::$systemDB->update("badges_config", ["maxBonusReward" => "1000"], ["maxBonusReward" => "2000", "course" => $courseID]);
                    //mudar de text para image (dentro da page)
                    Core::$systemDB->update("view", ["partType" => "image"], ["partType" => "text", "parent" => $viewPage]);
                    //mudar de image para text (dentro template)
                    Core::$systemDB->update("view", ["partType" => "text"], ["partType" => "image", "parent" => $viewIdTemplate]);
                    Course::importCourses($jsonContent, true);
                    if (
                        Core::$systemDB->select("badges_config", ["maxBonusReward" => "2000", "course" => $courseID])
                        // && Core::$systemDB->select("view", ["partType" => "text", "parent" => $viewPage])
                        // && Core::$systemDB->select("view", ["partType" => "image", "parent" => $viewIdTemplate])
                    ) {
                        // echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses imported - updated (and replaced).</h3>";
                        $GLOBALS['c_3'] = ["success", "<strong style='color:green;'>Success: </strong>Courses imported - updated (and replaced)."];
                        $GLOBALS['success']++;
                    } else {
                        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Courses could not be imported with replace.</h3>";
                        $GLOBALS['c_3'] = ["fail", "<strong style='color:red;'>Fail: </strong>could not be imported with replace."];
                        $GLOBALS['fail']++;
                    }
                } else {
                    // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Courses could not be imported without replace.</h3>";
                    $GLOBALS['c_2'] = ["fail", "<strong style='color:red;'>Fail: </strong>Courses could not be imported without replace."];
                    $GLOBALS['fail']++;
                }
            } else {
                // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Courses could not be imported without replace.</h3>";
                $GLOBALS['c_2'] = ["fail", "<strong style='color:red;'>Fail: </strong>Courses could not be imported without replace."];
                $GLOBALS['fail']++;
            }
        } else {
            // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> Could not import courses without replace.</h3>";
            $GLOBALS['c_2'] = ["fail", "<strong style='color:red;'>Fail: </strong>Courses could not be imported without replace."];
            $GLOBALS['fail']++;
        }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Fail:</strong> No file to import found.</h3>";
        $GLOBALS['c_2'] = ["fail", "<strong style='color:red;'>Fail: </strong>No file to import found."];

        $GLOBALS['fail']++;
    }
}



/*************************** Auxiliar functions ***************************/



//Check if users where created/updated
function checkFenix($fenix, $course)
{
    $newUsers = 0;
    $updatedUsers = 0;
    $course = new Course($course);
    for ($line = 1; $line < sizeof($fenix); $line++) {
        $fields = explode(";", $fenix[$line]);
        $username = $fields[0];
        $studentNumber = $fields[1];
        $studentName = $fields[2];
        $email = $fields[3];
        $courseName = $fields[10];
        $campus = "";
        if (strpos($courseName, 'Alameda')) {
            $campus = "A";
        } else if (strpos($courseName, 'Taguspark')) {
            $campus = "T";
        } else {
            $endpoint = "degrees?academicTerm=2019/2020";
            $listOfCourses = Core::getFenixInfo($endpoint);
            $courseFound = false;
            foreach ($listOfCourses as $courseFenix) {
                if ($courseFound) {
                    break;
                } else {
                    if (strpos($courseName, $courseFenix->name)) {
                        $courseFound = true;
                        foreach ($courseFenix->campus as $campusfenix) {
                            $campus = $campusfenix->name[0];
                        }
                    }
                }
            }
        }
        if ($studentNumber) {
            if (!User::getUserByStudentNumber($studentNumber)) {
                User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $user = User::getUserByStudentNumber($studentNumber);
                $courseUser = new CourseUser($user->getId(), $course);
                $courseUser->addCourseUserToDB(2, $campus);
                $newUsers++;
            } else {
                $existentUser = User::getUserByStudentNumber($studentNumber);
                $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $updatedUsers++;
            }
        } else {
            if (!User::getUserByEmail($email)) {
                User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $user = User::getUserByEmail($email);
                $courseUser = new CourseUser($user->getId(), $course);
                $courseUser->addCourseUserToDB(2, $campus);
                $newUsers++;
            } else {
                $existentUser = User::getUserByEmail($email);
                $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $updatedUsers++;
            }
        }
    }

    return [$newUsers, $updatedUsers];
}
//moodle plugin
function setMoodleVars($courseId, $moodleVar)
{
    $moodleVars = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");

    $arrayToDb = [
        "course" => $courseId,
        "dbServer" => $moodleVar['dbserver'],
        "dbUser" => $moodleVar['dbuser'],
        "dbPass" => $moodleVar['dbpass'],
        "dbName" => $moodleVar['db'],
        "dbPort" => $moodleVar["dbport"],
        "tablesPrefix" => $moodleVar["prefix"],
        "moodleTime" => $moodleVar["time"],
        "moodleCourse" => $moodleVar["course"],
        "moodleUser" => $moodleVar["user"]
    ];
    if (empty($moodleVar['dbserver']) || empty($moodleVar['dbuser']) || empty($moodleVar['db'])) {
        return false;
    } else {
        if (empty($moodleVars)) {
            Core::$systemDB->insert("config_moodle", $arrayToDb);
        } else {
            Core::$systemDB->update("config_moodle", $arrayToDb);
        }
        return true;
    }
}

//classcheck plugin
function setClassCheckVars($courseId, $classCheck)
{
    $classCheckVars = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

    $arrayToDb = ["course" => $courseId, "tsvCode" => $classCheck['tsvCode']];

    if (empty($classCheck["tsvCode"])) {
        return false;
    } else {
        if (empty($classCheckVars)) {
            Core::$systemDB->insert("config_class_check", $arrayToDb);
        } else {
            Core::$systemDB->update("config_class_check", $arrayToDb);
        }
        return true;
    }
}

//google sheets plugin - credentials
function setGSCredentials($courseId, $gsCredentials)
{
    $credentialKey = key($gsCredentials[0]);
    $credentials = $gsCredentials[0][$credentialKey];
    $googleSheetCredentialsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

    $uris = "";
    for ($uri = 0; $uri < sizeof($credentials["redirect_uris"]); $uri++) {
        $uris .= $credentials["redirect_uris"][$uri];
        if ($uri != sizeof($credentials["redirect_uris"]) - 1) {
            $uris .= ";";
        }
    }

    $arrayToDb = [
        "course" => $courseId, "key_" => $credentialKey, "clientId" => $credentials["client_id"], "projectId" => $credentials["project_id"],
        "authUri" => $credentials["auth_uri"], "tokenUri" => $credentials["token_uri"], "authProvider" => $credentials["auth_provider_x509_cert_url"],
        "clientSecret" => $credentials["client_secret"], "redirectUris" => $uris
    ];

    if (empty($credentials)) {
        return false;
    } else {
        if (empty($googleSheetCredentialsVars)) {
            Core::$systemDB->insert("config_google_sheets", $arrayToDb);
        } else {
            Core::$systemDB->update("config_google_sheets", $arrayToDb);
        }
        setCredentials($courseId);
        return true;
    }
}
function setCredentials($courseId)
{
    $credentials = getCredentialsFromDB($courseId);
    GoogleHandler::setCredentials(json_encode($credentials));
}

function getCredentialsFromDB($courseId)
{
    $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

    $uris = explode(";", $credentialDB["redirectUris"]);

    $arrayKey[$credentialDB['key_']] = array(
        'client_id' => $credentialDB['clientId'], "project_id" => $credentialDB["projectId"],
        'auth_uri' => $credentialDB['authUri'], "token_uri" => $credentialDB["tokenUri"], "auth_provider_x509_cert_url" => $credentialDB["authProvider"],
        'client_secret' => $credentialDB["clientSecret"], "redirect_uris" => $uris
    );
    return $arrayKey;
}


function handleToken($courseId)
{
    $credentials = getCredentialsFromDB($courseId);
    $token = getTokenFromDB($courseId);
    // return GoogleHandler::checkToken($credentials, $token, null, $courseId);
}

function getTokenFromDB($courseId)
{
    $accessExists = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "accessToken");
    if ($accessExists) {
        $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

        $arrayToken = array(
            'access_token' => $credentialDB['accessToken'], "expires_in" => $credentialDB["expiresIn"],
            'scope' => $credentialDB['scope'], "token_type" => $credentialDB["tokenType"],
            "created" => $credentialDB["created"], 'refresh_token' => $credentialDB["refreshToken"]
        );
        return json_encode($arrayToken);
    } else {
        return null;
    }
}

//google sheets plugin - vars
function setGoogleSheetsVars($courseId, $googleSheets)
{
    $googleSheetsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");
    $names = "";
    foreach ($googleSheets["sheetName"] as $name) {
        if (strlen($name) != 0) {
            $names .= $name . ";";
        }
    }

    if ($names != "" && substr($names, -1) == ";") {
        $names = substr($names, 0, -1);
    }
    $arrayToDb = ["course" => $courseId, "spreadsheetId" => $googleSheets["spreadsheetId"], "sheetName" => $names];
    if (empty($googleSheets["spreadsheetId"])) {
        return false;
    } else {
        if (empty($googleSheetsVars)) {
            Core::$systemDB->insert("config_google_sheets", $arrayToDb);
        } else {
            Core::$systemDB->update("config_google_sheets", $arrayToDb);
        }
        saveTokenToDB($courseId);
        return true;
    }
}

function saveTokenToDB($courseId)
{
    $response = handleToken($courseId);
    $token = $response["access_token"];
    if ($token) {

        $arrayToDB = array(
            "course" => $courseId,
            "accessToken" => $token["access_token"],
            "expiresIn" => $token["expires_in"],
            "scope" => $token["scope"],
            "tokenType" => $token["token_type"],
            "created" => $token["created"],
            "refreshToken" => $token["refresh_token"]
        );
        Core::$systemDB->update("config_google_sheets", $arrayToDB);
    }
}

//dictionary
function argsToJSON($func, $refersToType, $funcLib)
{
    $reflection = new \ReflectionFunction($func);
    $arg = null;
    $arguments  = $reflection->getParameters();
    if ($arguments) {
        $arg = [];
        $i = -1;
        foreach ($arguments as $argument) {
            $i++;
            if ($i == 0 && ($refersToType == "object" || $funcLib == null)) {
                continue;
            }
            $optional = $argument->isOptional() ? "1" : "0";
            $tempArr = [];
            $tempArr["name"] = $argument->getName();
            $type = (string)$argument->getType();
            if ($type == "int") {
                $tempArr["type"] = "integer";
            } elseif ($type == "bool") {
                $tempArr["type"] = "boolean";
            } else {
                $tempArr["type"] = $type;
            }
            $tempArr["optional"] = $optional;
            array_push($arg, $tempArr);
        }
        if (empty($arg)) {
            $arg = null;
        } else {
            $arg = json_encode($arg);
        }
    }
    return $arg;
}
// echo "<hr>";
// echo "<h2 style='margin-bottom:2px;'>Results: </h2>";
// $percentage = ($GLOBALS['success'] / ($GLOBALS['success'] + $GLOBALS['fail'])) * 100;
// if ($percentage < 50) {
//     echo "<h3 style='margin-bottom:2px;'>Unit Tests Score: <span style='padding-left:2px;padding-right:2px;background-color:#e34a4a;'>" . round($percentage, 2) . "%</span></h3>";
// } else {
//     echo "<h3 style='margin-bottom:2px;'>Unit Tests Score: <span style='padding-left:2px;padding-right:2px;background-color:#6aae6f;'>" . round($percentage, 2) . "%</span></h3>";
// }

// echo "<h3 style='font-weight: normal;margin:2px'> - <strong style='color:green'> Succeded: </strong>" . $GLOBALS['success'] . "</h3>";
// echo "<h3 style='font-weight: normal;margin:2px'> - <strong style='color:red'> Failed: </strong>" . $GLOBALS['fail'] . "</h3>";
// $percentageCoverage = (($GLOBALS['success'] + $GLOBALS['fail']) / 26) * 100;
// if ($percentageCoverage < 50) {
//     echo "<h3 style='margin-bottom:2px;'>Coverage: <span style='padding-left:2px;padding-right:2px;background-color:#e34a4a;'>" .   round($percentageCoverage, 2) . "%</span></h3>";
// } else if ($percentageCoverage == 100) {
//     echo "<h3 style='margin-bottom:2px;'>Coverage: <span style='padding-left:2px;padding-right:2px;background-color:#6aae6f;'>" .   round($percentageCoverage, 2) . "%</span></h3>";
// } else {
//     echo "<h3 style='margin-bottom:2px;'>Coverage: <span style='padding-left:2px;padding-right:2px;background-color:#F7941D;'>" .   round($percentageCoverage, 2) . "%</span></h3>";
// }
// echo "<h3 style='font-weight: normal;margin:2px'> - <strong> Run: </strong>" . ($GLOBALS['success'] + $GLOBALS['fail']) . "</h3>";
// echo "<h3 style='font-weight: normal;margin:2px'> - <strong> Total: </strong>26</h3>";



echo "<table style=' border: 1px solid black; border-collapse: collapse;'>";
//Nome das colunas
echo "<tr>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Group</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Test</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Score</strong></th>";
echo "<th style='border: 1px solid black; padding: 5px;'><strong>Coverage</strong></th>";
echo "</tr>";
// Login Picture
echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>Login Picture</td>";
echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS['lg_1'][1] . "</td>";
if ($GLOBALS['lg_1'][0] == "warning") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5;'>0%</br>(0/1)</td>";
} else if ($GLOBALS['lg_1'][0] == "success") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'>100%</br>(1/1)</td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897;'>100%</br>(1/1)</td>";
} else if ($GLOBALS['lg_1'][0] == "fail") {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;'>0%</br>(0/1)</td>";
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color: #FFA5A5;'>1000%</br>(1/1)</td>";
}
echo "</tr>";
// Fénix Import
echo "<tr>";
if ($GLOBALS['courseInfo'] == 1) {
    if ($GLOBALS["pluginInfo"] == 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Fénix Import</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'><strong style='color:#F7941D;'>Warning:</strong> To test the plugin, please enable the plugin module.</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/4)</td>";
        echo "</tr>";
    } else {
        $info = $GLOBALS['fi_1'][0] . $GLOBALS["fi_2"][0] . $GLOBALS["fi_3"][0] . $GLOBALS["fi_4"][0];
        $countedInfo = countInfos($info, 4);
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;'>Fénix Import</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS['fi_1'][1] . "</td>";
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/4)</td>";
        echo "<td rowspan='4' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (4 - $countedInfo[0]) . "/4)</td>";
        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["fi_2"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["fi_3"][1] . " </td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px'>" . $GLOBALS["fi_4"][1] . "</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Fénix Import", 4);
}

//Plugin
echo "<tr>";
if ($GLOBALS['courseInfo'] == 1) {
    if ($GLOBALS["pluginInfo"] == 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Plugins</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'><strong style='color:#F7941D;'>Warning:</strong> To test the plugin, please enable the plugin module.</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/3)</td>";
        echo "</tr>";
    } else {
        $info = $GLOBALS["p_1"][0] . $GLOBALS["p_2"][0] . $GLOBALS["p_3"][0];
        $countedInfo = countInfos($info, 3);
        // return [$warningCount, $successCount, $percentageScore, $percentageCover, $colorScore, $colorCover];
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Plugins</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["p_1"][1] . "</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["p_2"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["p_3"][1] . "</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Plugins", 3);
}
// Dictionary
if ($GLOBALS['courseInfo'] == 1) {
    if ($GLOBALS['dictionaryInfo'] == 1) {

        $info = $GLOBALS["dl_1"][0] . $GLOBALS["dl_2"][0] . $GLOBALS["dl_3"][0] . $GLOBALS["df_1"][0] . $GLOBALS["df_2"][0] . $GLOBALS["df_3"][0] . $GLOBALS["dv_1"][0] . $GLOBALS["dv_2"][0] . $GLOBALS["dv_3"][0];
        $countedInfo = countInfos($info, 9);
        echo "<tr>";
        echo "<td rowspan='9' style='border: 1px solid black; padding: 5px;'>Dictionary</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dl_1"][1] . "</td>";
        echo "<td rowspan='9' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/9)</td>";
        echo "<td rowspan='9' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (9 - $countedInfo[0]) . "/9)</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dl_2"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dl_3"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["df_1"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["df_1"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["df_1"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dv_1"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dv_2"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["dv_3"][1] . "</td>";
        echo "</tr>";
    } else {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Dictionary</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'><strong style='color:#F7941D;'>Warning:</strong> To test the dictionary, please enable the views module.</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/3)</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Dictionary", 9);
}
//Import/Export Users
$info = $GLOBALS["u_1"][0] . $GLOBALS["u_2"][0] . $GLOBALS["u_3"][0];
$countedInfo = countInfos($info, 3);
echo "<tr>";
echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Import/Export Users</td>";
echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["u_1"][1] . "</td>";
echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["u_2"][1] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["u_3"][1] . "</td>";
echo "</tr>";

//Import/Export Course Users
if ($GLOBALS['courseInfo'] == 1) {
    $info = $GLOBALS["cou_1"][0] . $GLOBALS["cou_2"][0] . $GLOBALS["cou_3"][0];
    $countedInfo = countInfos($info, 3);
    echo "<tr>";
    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Import/Export Course Users</td>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_1"][1] . "</td>";

    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
    echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_2"][1] . "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["cou_3"][1] . "</td>";
    echo "</tr>";
} else {
    checkCourseTable("Import/Export Course Users", 3);
}
//Import/Export Courses
if ($GLOBALS['courseInfo'] == 1) {
    $info = $GLOBALS["c_1"][0] . $GLOBALS["c_2"][0] . $GLOBALS["c_3"][0];
    $countedInfo = countInfos($info, 3);
    echo "<tr>";
    if ($countedInfo[0] > 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>Import/Export Courses</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_1"][1] . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/3)</td>";
    } else {
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;'>Import/Export Courses</td>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_1"][1] . "</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[4] . ";'>" . $countedInfo[2] . "%</br>(" . $countedInfo[1] . "/3)</td>";
        echo "<td rowspan='3' style='border: 1px solid black; padding: 5px;text-align:center;background-color:" . $countedInfo[5] . ";'>" . $countedInfo[3] . "%</br>(" . (3 - $countedInfo[0]) . "/3)</td>";

        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_3"][1] . "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td style='border: 1px solid black; padding: 5px;'>" . $GLOBALS["c_3"][1] . "</td>";
        echo "</tr>";
    }
} else {
    checkCourseTable("Import/Export Courses", 3);
}
//total

$percentage = ($GLOBALS['success'] / ($GLOBALS['success'] + $GLOBALS['fail'])) * 100;
$percentageCoverage = (($GLOBALS['success'] + $GLOBALS['fail']) / 26) * 100;

echo "<tr>";
echo "<td colspan='2' style='border: 1px solid black; padding: 5px;'><strong>Total</strong></td>";
if ($percentage == 100) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else if ($percentage < 50) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
} else {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFF1AA'><strong>" . round($percentage, 2) . "%</br>(" . $GLOBALS['success'] . "/" . ($GLOBALS['success'] + $GLOBALS['fail']) . ")</strong></td>";
}

if ($percentageCoverage == 100) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#C7E897'><strong>" . round($percentageCoverage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
} else if ($percentageCoverage < 50) {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'><strong>" . round($percentageCoverage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
} else {
    echo "<td style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFF1AA'><strong>" . round($percpercentageCoverageentage, 2) . "%</br>(" . ($GLOBALS['success'] + $GLOBALS['fail']) . "/26)</strong></td>";
}
echo "</tr>";
echo "</table>";


function countInfos($info, $nrTotal)
{
    $warningCount = substr_count($info, "warning");
    $successCount = substr_count($info, "success");
    $percentageScore =  round(($successCount / $nrTotal) * 100, 2);
    $percentageCover = round((($nrTotal - $warningCount) / $nrTotal) * 100, 2);

    $colorScore = null;
    $colorCover = null;

    if ($percentageScore < 50) {
        $colorScore = "#FFA5A5";
    } else if ($percentageScore == 100) {
        $colorScore = "#C7E897";
    } else {
        $colorScore = "#FFF1AA";
    }
    if ($percentageCover < 50) {
        $colorCover = "#FFA5A5";
    } else if ($percentageScore == 100) {
        $colorCover = "#C7E897";
    } else {
        $colorCover = "#FFF1AA";
    }
    return [$warningCount, $successCount, $percentageScore, $percentageCover, $colorScore, $colorCover];
}

function checkCourseTable($name, $nrTests)
{

    $semCurso = "<strong style='color:#F7941D;'>Warning:</strong> If you desire to test the whole script, please specify a course id as an URL parameter: ?course=1 or &course=1.";
    $cursoNaoExiste = "<strong style='color:#F7941D;'>Warning:</strong> There is no course with id " . $_GET["course"];

    if ($GLOBALS['courseInfo'] == 0) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $name . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $semCurso . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/1)</td>";
        echo "</tr>";
    } else if ($GLOBALS['courseInfo'] == -1) {
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $name . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;'>" . $cursoNaoExiste . "</td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;'></td>";
        echo "<td rowspan='1' style='border: 1px solid black; padding: 5px;text-align:center;background-color:#FFA5A5'>0%</br>(0/" . $nrTests . ")</td>";
        echo "</tr>";
    }
}
