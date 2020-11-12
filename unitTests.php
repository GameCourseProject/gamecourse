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

echo "\n-----LOGIN PICTURE-----";
checkPhoto($username);

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

echo "\n";
echo "\n-----DICTIONARY-----";
$courseObj = new Course($course);
$init = ModuleLoader::initModules($courseObj);
$viewModule = $courseObj->getModule('views');
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
function argsToJSON($func, $refersToType, $funcLib){
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