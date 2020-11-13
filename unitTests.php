<?php
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\GoogleHandler;
use GameCourse\ModuleLoader;
use Modules\Views\ViewHandler;

$username = $argv[1];
$course = $argv[2];
$authCode = $argv[3];
Core::init();

testPhotoDownload($username);

$courseObj = Course::getCourse($course);
if ($courseObj->getModule("plugin")) {
    testFenixPlugin($course);
    testMoodlePlugin($course);
    testClassCheckPlugin($course);
    //testGoogleSheetsPlugin($course, $authCode);
} else {
    echo "\n-----PLUGIN-----";
    echo "\nTo test the plugin, please enable the plugin module.";
}
testDictionaryManagement($course);

testUserImport();
testCourseImport();
testCourseUserImport($course);

function testPhotoDownload($username)
{
    echo "\n-----LOGIN PICTURE-----";
    checkPhoto($username);
}

function testFenixPlugin($course)
{
    echo "\n";
    echo "\n-----FENIX PLUGIN-----";
    $fenix = array();
    array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
    array_push($fenix, "ist112345;12345;João Silva;js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
    array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

    $usersInfo = checkFenix($fenix, $course);

    if ($usersInfo[0] == 2 && $usersInfo[1] == 0) {
        $gcu1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
        $gcu2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "99999"]);
        if ($gcu1 && $gcu2) {
            echo "\nSuccess: Users uploaded";

            $courseUser1 = Core::$systemDB->select("course_user", ["id" => $gcu1["id"]]);
            $courseUser2 = Core::$systemDB->select("course_user", ["id" => $gcu2["id"]]);
            if ($courseUser1 && $courseUser2) {
                echo "\nSuccess: CourseUsers uploaded";
            } else {
                echo "\nFailed: CourseUsers failed to upload";
            }
        } else {
            echo "\nFailed: Users failed to upload";
        }

        $auth1 = Core::$systemDB->select("auth", ["username" => "ist112345"]);
        $auth2 = Core::$systemDB->select("auth", ["username" => "ist199999"]);
        if ($auth1 && $auth2) {
            echo "\nSuccess: Users' authentication uploaded";
        } else {
            echo "\nFailed: Users' authentication failed to upload";
        }
    } else {
        echo "\nFailed: The users where not created correctly";
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
                echo "\nSuccess: User updated";
            } else {
                echo "\nFailed: User failed to update";
            }
        }
    } else {
        echo "\nFailed: The users where not updated correctly";
    }
}

function testMoodlePlugin($course)
{
    echo "\n";
    echo "\n-----MOODLE PLUGIN-----";
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
            echo "\nSuccess: Moodle variables were set";
        } else {
            echo "\n Moodle Variables were not inserted in the database";
        }
    } else {
        echo "\nFailed: It was not possible to set moodle variables.";
    }
}

function testClassCheckPlugin($course)
{

    echo "\n";
    echo "\n-----CLASSCHECK PLUGIN-----";
    $ccVar = ["tsvCode" => "8c691b7fc14a0455386d4cb599958d3"];
    $resultCC = setClassCheckVars($course, $ccVar);
    if ($resultCC) {
        if (Core::$systemDB->select("config_class_check", $ccVar)) {
            echo "\nSuccess: ClassCheck variables were set";
        } else {
            echo "\n ClassCheck Variables were not inserted in the database";
        }
    } else {
        echo "\nFailed: It was not possible to set classChc variables.";
    }
}

function testGoogleSheetsPlugin($course, $authCode)
{

    echo "\n";
    echo "\n-----GOOGLE SHEETS PLUGIN-----";

    $configGS = array(
        "client_id" => "370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com",
        "project_id" => "pcm-script",
        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
        "token_uri" => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_secret" => "hC4zsuwH1fVIWi5k0C4zjOub",
        "redirect_uris" => array("urn:ietf:wg:oauth:2.0:oob", "http://localhost")
    );
    $gsVars = array(array("installed" => $configGS));
    $resultGS = setGSCredentials($course, $gsVars);
    if ($resultGS) {
        $checkDB_GS = array(
            "clientId" => "370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com",
            "projectId" => "pcm-script",
            "authUri" => "https://accounts.google.com/o/oauth2/auth",
            "tokenUri" => "https://oauth2.googleapis.com/token",
            "authProvider" => "https://www.googleapis.com/oauth2/v1/certs",
            "clientSecret" => "hC4zsuwH1fVIWi5k0C4zjOub",
            "redirectUris" => "urn:ietf:wg:oauth:2.0:oob;http://localhost"
        );
        if (Core::$systemDB->select("config_google_sheets", $checkDB_GS)) {
            echo "\nSuccess: Google Sheets credentials were set";
        } else {
            echo "\n Google Sheets credentials were not inserted in the database";
        }
    } else {
        echo "\nFailed: It was not possible to set Google Sheets credentials.";
    }

    $varsGS = array(
        "authCode" => $authCode,
        "spreadsheetId" => "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U",
        "sheetName" => array("ist13898_")
    );
    $resultGSVars = setGoogleSheetsVars($course, $varsGS);
    if ($resultGSVars) {
        $checkDB_GS = array(
            "authCode" => $authCode,
            "spreadsheetId" => "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U",
            "sheetName" => "ist13898_"
        );
        if (Core::$systemDB->select("config_google_sheets", $checkDB_GS)) {
            echo "\nSuccess: Google Sheets variables were set";
        } else {
            echo "\n Google Sheets variables were not inserted in the database";
        }
    } else {
        echo "\nFailed: It was not possible to set Google Sheets variables.";
    }
}

function testDictionaryManagement($course)
{

    echo "\n";
    echo "\n-----DICTIONARY-----";
    $courseObj = Course::getCourse($course);
    $viewModule = $courseObj->getModule('views');
    if ($viewModule) {

        $viewHandler = $viewModule->getViewHandler();

        //insert variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns the role of the user that is viewing the page");
        $id = Core::$systemDB->select("dictionary_library", ["name" => "users"], "id");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns the role of the user that is viewing the page"
            ])) {
                echo "\nSucess: Variable created";
            } else {
                echo "\nFailed: Variable was not created";
            }
        } else {
            echo "\nFailed: It was not possible to register the variable";
        }

        //update variable
        $viewHandler->registerVariable("%roles", "collection", "string", "users", "Returns roles");
        if ($id) {
            if (Core::$systemDB->select("dictionary_variable", [
                "libraryId" => $id, "name" => "%roles", "returnType" => "collection", "returnName" => "string",
                "description" =>  "Returns roles"
            ])) {
                echo "\nSucess: Variable updated";
            } else {
                echo "\nFailed: Variable was not updated";
            }
        } else {
            echo "\nFailed: It was not possible to register the variable";
        }

        //insert library
        $viewHandler->registerLibrary("views", "games", "This library contains information about the course games");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This library contains information about the course games"
        ])) {
            echo "\nSucess: Library created";
        } else {
            echo "\nFailed: Library was not created";
        }

        //update library
        $viewHandler->registerLibrary("views", "games", "This is a game's library");
        if (Core::$systemDB->select("dictionary_library", [
            "moduleId" => "views", "name" => "games", "description" =>  "This is a game's library"
        ])) {
            echo "\nSucess: Library updated";
        } else {
            echo "\nFailed: Library was not updated";
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
                echo "\nSucess: Function created";
            } else {
                echo "\nFailed: Function was not created";
            }
        } else {
            echo "\nFailed: It was not possible to register the function";
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
                echo "\nSucess: Function updated";
            } else {
                echo "\nFailed: Function was not updated";
            }
        } else {
            echo "\nFailed: It was not possible to register the function";
        }
    } else {
        echo "\nTo test the dictionary, please enable the views module.";
    }
}

function testUserImport()
{
    echo "\n";
    echo "\n-----USERS IMPORT-----";
    //adds users
    $usersCSV = "name,email,nickname,studentNumber,isAdmin,isActive,username,auth\n";
    $usersCSV .= "João Bernardo,,,98765,0,1,jb@gmail.pt,google\n";
    $usersCSV .= "Olivia Nogueira,olivia.nogueira@mail.pt,,56789,0,1,,\n";

    User::importUsers($usersCSV, true);

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
            "username" => "",
            "authentication_service" => ""
        ]);
        if ($user1Auth && $user2Auth) {
            echo "\nSuccess: Users imported - created";
        } else {
            echo "\nFailed: Users were not correctly imported - not created";
        }
    } else {
        echo "\nFailed: Users were not correctly imported - not created";
    }
    //does not update users
    $usersCSV = "name,email,nickname,studentNumber,isAdmin,isActive,username,auth\n";
    $usersCSV .= "Joaquim Duarte,,,98765,0,1,jb@gmail.pt,google\n";
    $usersCSV .= "Olivia Nogueira,olivia.nogueira@mail.pt,,56789,0,1,ist156789,fenix\n";

    User::importUsers($usersCSV, false);

    if ($user1Id && $user2Id) {
        $user1Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user1Id,
            "username" => "jb@gmail.pt",
            "authentication_service" => "google"
        ]);
        $user2Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user2Id,
            "username" => "",
            "authentication_service" => ""
        ]);
        if ($user1Auth && $user2Auth) {
            echo "\nSuccess: Users imported - updated (not replaced)";
        } else {
            echo "\nFailed: Users were not correctly imported - not updated without replace";
        }
    } else {
        echo "\nFailed: Users were not correctly imported - not updated without replace";
    }
    //update users
    User::importUsers($usersCSV, true);

    $user3Id = Core::$systemDB->select("game_course_user", [
        "name" => "Joaquim Duarte",
        "email" => "",
        "nickname" => "",
        "studentNumber" => "98765",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");

    $user4Id = Core::$systemDB->select("game_course_user", [
        "name" => "Olivia Nogueira",
        "email" => "olivia.nogueira@mail.pt",
        "nickname" => "",
        "studentNumber" => "56789",
        "isAdmin" => "0",
        "isActive" => "1"
    ], "id");
    if ($user3Id && $user4Id) {
        $user3Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user3Id,
            "username" => "jb@gmail.pt",
            "authentication_service" => "google"
        ]);
        $user4Auth =  Core::$systemDB->select("auth", [
            "game_course_user_id" => $user4Id,
            "username" => "ist156789",
            "authentication_service" => "fenix"
        ]);
        if ($user3Auth && $user4Auth) {
            echo "\nSuccess: Users imported - updated (and replaced)";
        } else {
            echo "\nFailed: Users were not correctly imported - not updated with replace";
        }
    } else {
        echo "\nFailed: Users were not correctly imported - not updated with replace";
    }
}
function testCourseImport()
{
    echo "\n";
    echo "\n-----COURSES IMPORT-----";
    $courseID = Core::$systemDB->select("course", ["name" => "Course X", "year" => "2017-2018"], "id");
    $courseObj = null;
    if ($courseID) {
        $courseObj = Course::getCourse($courseID);
        if ($courseObj->getModule("views") && $courseObj->getModule("badges")) {
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
                                    echo "\nSuccess: courses exported";
                                    importCourses($json, $viewPage, $viewIdTemplate, $courseID, $templateId);
                                    unlink("coursesJSONTesteUnit.json");
                                } else {
                                    echo "\nEnable badges and set maxReward to 2000";
                                }
                            } else {
                                echo "\nYou created a template (role single), but it does not contain a image in 'Course X'";
                            }
                        } else {
                            echo "\nCreate a template (role single) containing an image";
                        }
                    } else {
                        echo "\nCreate a template (role single) containing an image";
                    }
                } else {
                    echo "\nYou created a page (role single), but it does not contain a text in 'Course X'";
                }
            } else {
                echo "\nCreate a page (role single) contaning a text in 'Course X'";
            }
        } else {
            echo "\nEnable module views and badges";
        }
    } else {
        echo "\nTo test the course import and export, do the following:";
        echo "\n - Create a course named 'Course X' and year '2017/2018'";
        echo "\n - Inside that course, enable views and create a page contaning a text (role single)";
        echo "\n - Create a template containing an image (role single)";
        echo "\n - Enable badges and set maxReward to 2000";
    }
}
function testCourseUserImport($course)
{
    echo "\n";
    echo "\n-----COURSE USERS IMPORT-----";
    Core::$systemDB->delete("game_course_user", ["studentNumber" => "77777"]);
    $id = User::addUserToDB("Hugo Sousa", null, null, "hugo@mail.com", "77777",  null, 0, 1);
    $courseUser = new CourseUser($id, new Course($course));
    $roleId = Core::$systemDB->select("role", ["course" => $course, "name" => "student"], "id");
    $courseUser->addCourseUserToDB($roleId, "");
    $csvCourseUsers = CourseUser::exportCourseUsers($course);
    file_put_contents("courseUsersCSVTesteUnit.csv", $csvCourseUsers);
    $usersCSV = file_get_contents("courseUsersCSVTesteUnit.csv");

    //adds users
    $usersCSV = "course,name,nickname,email,campus,studentNumber,isAdmin,isActive,roles,username,auth\n";
    $usersCSV .= $course . ", Hugo Sousa Silva,,hugo@mail.com,,77777,0,1,Student,12,fenix\n";
    $usersCSV .= $course . ", Joaquim Duarte,,,A,98765,0,1,,jb@gmail.pt,linkedin\n";
    $usersCSV .= $course . ", Mónica Trindade,,,T,55555,0,1,,,\n";
    file_put_contents("courseUsersCSVTesteUnit.csv", $usersCSV);

    CourseUser::importCourseUsers($usersCSV, false);

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
        echo "\nSuccess: CourseUsers imported - created and updated without replace";
    }

    CourseUser::importCourseUsers($usersCSV, true);

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
    $auth1 = Core::$systemDB->select("auth", ["game_course_user_id" => $user1Id, "username" => "jb@gmail.pt", "authentication_service" => "linkedin"]);
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

    if ($courseUser1Id && $courseUser2Id && $courseUser3Id && $auth1) {
        echo "\nSuccess: CourseUsers imported - updated with replace";
    }

    Core::$systemDB->delete("course_user", ["id" => $user1Id]);
    Core::$systemDB->update("auth", ["game_course_user_id" => $user1Id, "username" => "jb@gmail.pt", "authentication_service" => "facebook"]);
    Core::$systemDB->delete("game_course_user", ["name" => "Hugo Sousa Silva"]);
    Core::$systemDB->delete("game_course_user", ["name" => "Mónica Trindade"]);
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

                    echo "\nSuccess: Courses imported without replace";
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
                        echo "\nSuccess: Courses imported with replace.";
                    } else {
                        echo "\nFailed: Courses could not be imported with replace.";
                    }
                } else {
                    echo "\nFailed: Courses could not be imported without replace.";
                }
            } else {
                echo "\nFailed:Courses could not be imported without replace.";
            }
        } else {
            echo "\nFailed: import courses without replace.";
        }
    } else {
        echo "\nFailed: no file to import found.";
    }
}
function testCourseUsersImport()
{
    // CourseUser::importUsers()
}


/*************************** Auxiliar functions ***************************/

//Check if a photo is created when logging in (by username)
function checkPhoto($username)
{
    if ($username) {
        $id = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
        if (!$id) {
            echo "\nFailed: " . $username . " does not exist";
        } else {

            if (file_exists("photos/" . $id . ".png")) {
                echo "\nSuccess: Photo created";
            } else {
                echo "\nFailed: Photo was not created";
            }
        }
    } else {
        echo "\nFailed: " . $username . " does not exist";
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

function setAuthCode($courseId)
{
    $response = handleToken($courseId);
    if ($response["auth_url"]) {
        Core::$systemDB->update(
            "config_google_sheets",
            ["authUrl" => $response["auth_url"]]
        );
    }
}

function handleToken($courseId)
{
    $credentials = getCredentialsFromDB($courseId);
    $token = getTokenFromDB($courseId);
    $authCode = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "authCode");
    return GoogleHandler::checkToken($credentials, $token, $authCode);
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
    $arrayToDb = ["course" => $courseId, "spreadsheetId" => $googleSheets["spreadsheetId"], "sheetName" => $names, "authCode" => $googleSheets["authCode"]];
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
