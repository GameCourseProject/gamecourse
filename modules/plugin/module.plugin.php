<?php

namespace Modules\Plugin;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;

class Plugin extends Module
{
    private $moodle;
    private $classCheck;
    private $googleSheets;

    //Fenix variables
    private $fenixCourseId = "1971935449711106";
    //Moodle variables
    private $dbserver = "localhost"; //"db.rnl.tecnico.ulisboa.pt";
    private $dbuser = "root"; //"pcm_moodle";
    private $dbpass = ""; //"Dkr1iRwEekJiPSHX9CeNznHlks";
    private $db = "moodle"; //"pcm_moodle";
    private $dbport = "3306";
    private $prefix = "mdl_";
    private $time = "1590790100";
    private $course = null; //courseId no moodle
    private $user = null;
    //ClassCheck variables
    private $tsvCode = "f8c691b7fc14a0455386d4cb599958d3";
    //Google sheets variables
    private $spreadsheetId = "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U"; //'1gznueqlXB9EK-tesPINJ4g2dxFkZsQoXWZvPsCaG7_U';
    private $sheetName = 'Daniel';
    private $range = 'A1:E18'; //$range = 'Folha1!A1:B2';


    private function getMoodleVars($courseId)
    {
        $moodleVarsDB = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");

        if (empty($moodleVarsDB)) {
            $moodleVars = [
                "dbserver" => "localhost",
                "dbuser" => "root",
                "dbpass" => "",
                "db" => "moodle",
                "dbport" => "3306",
                "prefix" => "mdl_",
                "time" => "",
                "course" => "",
                "user" => ""
            ];
        } else {
            $moodleVars = [
                "dbserver" => $moodleVarsDB["dbServer"],
                "dbuser" => $moodleVarsDB["dbUser"],
                "dbpass" => $moodleVarsDB["dbPass"],
                "db" => $moodleVarsDB["dbName"],
                "dbport" => $moodleVarsDB["dbPort"],
                "prefix" => $moodleVarsDB["tablesPrefix"],
                "time" => $moodleVarsDB["moodleTime"],
                "course" => $moodleVarsDB["moodleCourse"],
                "user" => $moodleVarsDB["moodleUser"]
            ];
        }

        return $moodleVars;
    }
    private function getClassCheckVars($courseId)
    {
        $classCheckDB = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

        if (empty($classCheckDB)) {
            $classCheckVars = ["tsvCode" => ""];
        } else {
            $classCheckVars = ["tsvCode" => $classCheckDB["tsvCode"]];
        }

        return  $classCheckVars;
    }
    private function getAuthUrl($courseId)
    {
        return Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "authUrl");
    }
    private function getGoogleSheetsVars($courseId)
    {
        $googleSheetsDB = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

        if (empty($googleSheetsDB)) {
            $googleSheetsVars = ["token" => "", "spreadsheetId" => "", "sheetName" => ""];
        } else {
            $names = explode(";", $googleSheetsDB["sheetName"]);
            $googleSheetsVars = ["authCode" => $googleSheetsDB["authCode"], "spreadsheetId" => $googleSheetsDB["spreadsheetId"], "sheetName" => $names];
        }

        return  $googleSheetsVars;
    }


    private function setFenixVars($courseId, $fenix)
    {
        $course = new Course($courseId);
        for ($line = 1; $line < sizeof($fenix[0]) - 1; $line++) {
            $fields = explode(";", $fenix[0][$line]);

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
            if (!User::getUserByStudentNumber($studentNumber)) {
                User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $user = User::getUserByStudentNumber($studentNumber);
                $courseUser = new CourseUser($user->getId(), $course);
                $courseUser->addCourseUserToDB("", $campus);
            }
        }
        return true;
    }
    private function setMoodleVars($courseId, $moodleVar)
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

            //QUANDO QUISERMOS ATUALIZAR A BD COM OS DADOS DO MOODLE:

            // $quizGrades = $this->moodle->getQuizGrades();
            // $this->moodle->writeQuizGradesToDb($quizGrades);

            // $votes = $this->moodle->getVotes();
            // $this->moodle->writeVotesToDb($votes);

            // $logs = $this->moodle->getLogs();
            // $this->moodle->writeLogsToDB($logs);
            new CronJob("Moodle", $this->course);
            return true;
        }
    }
    private function setClassCheckVars($courseId, $classCheck)
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
            //QUANDO QUISERMOS ATUALIZAR A BD COM OS DADOS DO CLASSCHECK:
            // $this->classCheck->readAttendance();

            return true;
        }
    }
    private function setGSCredentials($courseId, $gsCredentials)
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
            $this->googleSheets->setCredentials();
            $this->googleSheets->setAuthCode();
            return true;
        }
    }
    private function setGoogleSheetsVars($courseId, $googleSheets)
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
            $this->googleSheets->saveTokenToDB();

            //QUANDO QUISERMOS ATUALIZAR A BD COM OS DADOS DO MOODLE:
            $this->googleSheets->readGoogleSheets();
            return true;
        }
    }

    public function setupResources()
    {
        parent::addResources('js/');
        //parent::addResources('css/plugins.css');
    }
    public function init()
    {
        //if classcheck is enabled
        $this->addTables("plugin", "config_class_check", "ConfigClassCheck");
        $this->classCheck = new ClassCheck(API::getValue('course'));

        //if googleSheets is enabled
        $this->addTables("plugin", "config_google_sheets", "ConfigGoogleSheets");
        $this->googleSheets = new GoogleSheets(API::getValue('course'));

        //if moodle is enabled
        $this->addTables("plugin", "config_moodle", "ConfigMoodle");
        $this->moodle = new Moodle(API::getValue('course'));

        //do not touch bellow
        //settings page
        API::registerFunction('settings', 'coursePlugin', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');

            if (API::hasKey('fenix')) {
                $fenix = API::getValue('fenix');
                //place to verify input values
                if ($this->setFenixVars($courseId, $fenix)) {
                    API::response(["updatedData" => ["Variables for fenix saved"]]);
                } else {
                    API::response(["updatedData" => ["Please fill the mandatory fields"]]);
                }

                return;
            }
            if (API::hasKey('moodle')) {
                $moodle = API::getValue('moodle');
                //place to verify input values
                if ($this->setMoodleVars($courseId, $moodle)) {
                    API::response(["updatedData" => ["Variables for moodle saved"]]);
                } else {
                    API::response(["updatedData" => ["Please fill the mandatory fields"]]);
                }
                return;
            }
            if (API::hasKey('classCheck')) {
                $classCheck = API::getValue('classCheck');
                //place to verify input values
                if ($this->setClassCheckVars($courseId, $classCheck)) {
                    API::response(["updatedData" => ["Variables for Class check saved"]]);
                } else {
                    API::response(["updatedData" => ["Please fill the mandatory fields"]]);
                }

                return;
            }
            if (API::hasKey('credentials')) {
                $credentials = API::getValue('credentials');
                if ($this->setGSCredentials($courseId, $credentials)) {
                    API::response(["updatedData" => ["Credentials saved"], "authUrl" => $this->getAuthUrl($courseId)]);
                } else {
                    API::response(["updatedData" => ["Please select a JSON file"]]);
                }
                return;
            }


            if (API::hasKey('googleSheets')) {


                $googleSheets = API::getValue('googleSheets');
                //place to verify input values
                if ($this->setGoogleSheetsVars($courseId, $googleSheets)) {
                    API::response(["updatedData" => ["Variables for Google Sheets saved"]]);
                } else {
                    echo "false";
                    API::response(["updatedData" => ["Please fill the mandatory fields"]]);
                }

                return;
            }

            //All variables
            $moodleVars = $this->getMoodleVars($courseId);
            $classCheckVars = $this->getClassCheckVars($courseId);
            $googleSheetsVars = $this->getGoogleSheetsVars($courseId);
            API::response(array('moodleVars' => $moodleVars, 'classCheckVars' => $classCheckVars, 'googleSheetsVars' => $googleSheetsVars));
        });
    }
}
ModuleLoader::registerModule(array(
    'id' => 'plugin',
    'name' => 'Plugin',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Plugin();
    }
));
