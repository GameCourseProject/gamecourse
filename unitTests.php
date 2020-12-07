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

echo "<h1>Automated Test Script</h1>";

//course
if (array_key_exists("course", $_GET)) {
    $course = $_GET["course"];
    if (Core::$systemDB->select("game_course_user", ["id" => $course])) {

        if (array_key_exists("username", $_GET)) {
            echo "<h2>Login Picture</h2>";
            $username = $_GET["username"];
            if ($username) {
                testPhotoDownload($username);
            }
        } else {
            echo "<h3 style='font-weight: normal'>
            <strong style='color:#F7941D;'>Warning:</strong> If you desire to test the download of the login picture,  please specify a username as an URL parameter: ?username=istxxxxx or &username=istxxxxx
            </h3>";
        }

        $courseObj = Course::getCourse($course);
        if ($courseObj->getModule("plugin")) {
            testFenixPlugin($course);
            testMoodlePlugin($course);
            testClassCheckPlugin($course);
            testGoogleSheetsPlugin($course);
        } else {
            echo "<h2>Plugins</h2>";
            echo "<h3 style='font-weight: normal'>
            <strong style='color:#F7941D;'>Warning:</strong> To test the plugin, please enable the plugin module.
            </h3>";
        }
        testDictionaryManagement($course);

        testUserImport();
        testCourseUserImport($course);
        testCourseImport();
    } else {
        echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> There is no course with id " . $course . ".
        </h3>";

        if (array_key_exists("username", $_GET)) {
            echo "<h2>Login Picture</h2>";
            $username = $_GET["username"];
            if ($username) {
                testPhotoDownload($username);
            }
        } else {
            echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> If you desire to test the download of the login picture, please specify a username as an URL parameter: ?username=istxxxxx or &username=istxxxxx
        </h3>";
        }
        testUserImport();
    }
} else {
    echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> If you desire to test the whole script, please specify a course id as an URL parameter: ?course=1 or &course=1.
        </h3>";
    if (array_key_exists("username", $_GET)) {
        echo "<h2>Login Picture</h2>";
        $username = $_GET["username"];
        if ($username) {
            testPhotoDownload($username);
        }
    } else {
        echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> If you desire to test the download of the login picture, please specify a username as an URL parameter: ?username=istxxxxx or &username=istxxxxx
        </h3>";
    }
    testUserImport();
}



function testPhotoDownload($username)
{
    checkPhoto($username);
}

function testFenixPlugin($course)
{
    echo "<h2>Fenix Plugin</h2>";
    $fenix = array();
    array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
    array_push($fenix, "ist112345;12345;João Silva;js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
    array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

    $usersInfo = checkFenix($fenix, $course);

    if ($usersInfo[0] == 2 && $usersInfo[1] == 0) {
        $gcu1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
        $gcu2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "99999"]);
        if ($gcu1 && $gcu2) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users uploaded</h3>";
            $courseUser1 = Core::$systemDB->select("course_user", ["id" => $gcu1["id"]]);
            $courseUser2 = Core::$systemDB->select("course_user", ["id" => $gcu2["id"]]);
            if ($courseUser1 && $courseUser2) {
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users uploaded</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Course Users failed to upload</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users failed to upload</h3>";
        }

        $auth1 = Core::$systemDB->select("auth", ["username" => "ist112345"]);
        $auth2 = Core::$systemDB->select("auth", ["username" => "ist199999"]);
        if ($auth1 && $auth2) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users' authentication uploaded</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users' authentication failed to upload</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users where not created correctly</h3>";
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
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong>  User updated</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> User failed to update</h3>";
            }
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users where not updated correctly</h3>";
    }
}

function testMoodlePlugin($course)
{
    echo "\n";
    echo "<h2>Moodle Plugin</h2>";
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
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Moodle variables were set</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Moodle Variables were not inserted in the database</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to set moodle variables.</h3>";
    }
}

function testClassCheckPlugin($course)
{
    echo "<h2>Class Check Plugin</h2>";

    $ccVar = ["tsvCode" => "8c691b7fc14a0455386d4cb599958d3"];
    $resultCC = setClassCheckVars($course, $ccVar);
    if ($resultCC) {
        if (Core::$systemDB->select("config_class_check", $ccVar)) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Class Check variables were set</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> ClassCheck Variables were not inserted in the database</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to set classChc variables.</h3>";
    }
}

function testGoogleSheetsPlugin($course)
{
    echo "<h2>Google Sheets Plugin</h1>";

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
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Google Sheets variables were set</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Google Sheets variables were not inserted in the database.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to set Google Sheets variables.</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> Make sure you authenticate to access to Google Sheets for course " . $course . "."
            . "</h3>";
    }
}

function testDictionaryManagement($course)
{
    echo "<h2>Dictionary</h2>";
    $courseObj = Course::getCourse($course);
    $viewModule = $courseObj->getModule('views');
    if ($viewModule) {

        $viewHandler = $viewModule->getViewHandler();

        //insert library
        $viewHandler->registerLibrary("views", "games", "This library contains information about the course games");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This library contains information about the course games"
        ])) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library updated.</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Library was not created.</h3>";
        }

        //update library
        $viewHandler->registerLibrary("views", "games", "This is a game's library");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This is a game's library"
        ])) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library updated.</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Library was not updated.</h3>";
        }

        //delete library
        $viewHandler->unregisterLibrary("views", "games");
        if (!Core::$systemDB->select("dictionary_library", ["name" => "games", "moduleId" => "views"])) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Library deleted.</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Library was not deleted.</h3>";
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
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function created.</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Function was not created.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to register the function.</h3>";
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
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function updated.</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Function was not updated.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to register the function</h3>";
        }
        //delete function
        $viewHandler->unregisterFunction("color", "views");
        if (!Core::$systemDB->select("dictionary_function", ["keyword" => "games", "libraryId" => $id])) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Function deleted.</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Function was not deleted.</h3>";
        }

        //insert variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns the role of the user that is viewing the page");
        $id = Core::$systemDB->select("dictionary_library", ["name" => "users"], "id");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns the role of the user that is viewing the page"
            ])) {
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable created.</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Variable was not created.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to register the variable.</h3>";
        }

        //update variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns roles");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns roles"
            ])) {
                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable updated.</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Variable was not updated.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> It was not possible to register the variable.</h3>";
        }
        //delete variable
        $viewHandler->unregisterVariable("%roles");
        if (!Core::$systemDB->select("dictionary_variable", ["name" => "%roles"])) {
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Variable deleted.</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Variable was not deleted.</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'>
        <strong style='color:#F7941D;'>Warning:</strong> To test the dictionary, please enable the views module.
        </h3>";
    }
}

function testUserImport()
{
    echo "<h2>Import/Export Users</h2>";
    //adds users
    $csvUsers = CourseUser::exportUsers();
    file_put_contents("UsersCSVTesteUnit.csv", $csvUsers);
    $usersCSV = file_get_contents("UsersCSVTesteUnit.csv");

    if ($usersCSV) {
        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users exported.</h3>";
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users not exported.</h3>";
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
        //     echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users were not correctly imported - not created.</h3>";
        // }
    } else {
        // echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users were not correctly imported - not created.</h3>";
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
            echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users imported - created and updated (not replaced).</h3>";
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users were not correctly imported - not updated without replace.</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users were not correctly imported - not updated without replace.</h3>";
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
        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Users imported - updated (and replaced).</h3>";
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Users were not correctly imported - not updated with replace.</h3>";
    }
    Core::$systemDB->delete("game_course_user", ["id" => $user1Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user2Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user3Id]);
    Core::$systemDB->delete("game_course_user", ["id" => $user4Id]);
    unlink("UsersCSVTesteUnit.csv");
}
function testCourseImport()
{
    echo "<h2>Import/Export Courses</h2>";

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
                                    echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses exported.</h3>";
                                    importCourses($json, $viewPage, $viewIdTemplate, $courseID, $templateId);
                                    unlink("coursesJSONTesteUnit.json");
                                } else {
                                    echo "<h3 style='font-weight: normal'><strong style='color:#F7941D;'>Warning:</strong>Enable badges and set maxReward to 2000</h3>";
                                }
                            } else {
                                echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>You created a template (role single), but it does not contain a image in 'Course X'</h3>";
                            }
                        } else {
                            echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>Create a template (role single) containing an image</h3>";
                        }
                    } else {
                        echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>Create a template (role single) containing an image</h3>";
                    }
                } else {
                    echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>You created a page (role single), but it does not contain a text in 'Course X'</h3>";
                }
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>Create a page (role single) contaning a text in 'Course X'</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong>Enable module views and badges</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong> To test the course's import and export, do the following:</h3>";
        echo "<ul>";
        echo "<li><h3 style='padding:0px;font-weight:normal'>Create a course named 'Course X' and year '2017/2018'.</h3></li>";
        echo "<li><h3 style='padding:0px;font-weight:normal'>Inside that course, enable views and create a page contaning a text (role single).</h3></li>";
        echo "<li><h3 style='padding:0px;font-weight:normal'>Create a template containing an image (role single).</h3></li>";
        echo "<li><h3 style='padding:0px;font-weight:normal'>Enable badges and set maxReward to 2000.</h3></li>";
        echo "</ul>";
    }
}
function testCourseUserImport($course)
{
    echo "<h2>Import/Export Course Users</h2>";
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "77777"]);
    $id = User::addUserToDB("Hugo Sousa", "ist11111", "fenix", "hugo@mail.com", "77777",  null, 0, 1);
    $courseUser = new CourseUser($id, new Course($course));
    $roleId = Core::$systemDB->select("role", ["course" => $course, "name" => "student"], "id");
    $courseUser->addCourseUserToDB($roleId, "");
    $csvCourseUsers = CourseUser::exportCourseUsers($course);
    file_put_contents("courseUsersCSVTesteUnit.csv", $csvCourseUsers);
    $usersCSV = file_get_contents("courseUsersCSVTesteUnit.csv");
    if ($usersCSV) {
        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users exported.</h3>";
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Course Users not exported.</h3>";
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
        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users imported - created and updated (not replaced).</h3>";
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Course Users not imported - created and updated (not replaced).</h3>";
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
        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Course Users imported - updated (and replaced).</h3>";
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Course Users not imported.</h3>";
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
                    echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses imported - updated (not replaced).</h3>";
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
                        echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Courses imported - updated (and replaced).</h3>";
                    } else {
                        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Courses could not be imported with replace.</h3>";
                    }
                } else {
                    echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Courses could not be imported without replace.</h3>";
                }
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Courses could not be imported without replace.</h3>";
            }
        } else {
            echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Import courses without replace.</h3>";
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> No file to import found.</h3>";
    }
}



/*************************** Auxiliar functions ***************************/

//Check if a photo is created when logging in (by username)
function checkPhoto($username)
{
    if ($username) {
        $id = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
        if (!$id) {
            echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist.</h3>";
        } else {

            if (file_exists("photos/" . $id . ".png")) {

                echo "<h3 style='font-weight: normal'><strong style='color:green'>Success:</strong> Photo was created</h3>";
            } else {
                echo "<h3 style='font-weight: normal'><strong style='color:red'>Failed:</strong> Photo was not created</h3>";
            }
        }
    } else {
        echo "<h3 style='font-weight: normal'><strong style='color:#F7941D; '>Warning:</strong> Username '" . $username . "' does not exist.</h3>";
    }
}

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
