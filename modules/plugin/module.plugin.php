<?php

namespace Modules\Plugin;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;

class Plugin extends Module
{
    private $fenix;
    private $moodle;
    private $classCheck;
    private $googleSheets;

    //passo 1 criacao da(s) tabela(s) com a info a registar
    //passo 2 substituir nas funcoes gets o acesso a variavel local pelo acesso a DB
    //passo 3 substituir nas funcoes sets o registo na variavel local pelo registo na DB
    //any question just ask and I'll help ^^

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
    private $spreadsheetId = "1N8PKwi3jgQrCA8KJ1KSnj_MDk2-E_d_RWbVfnKzrpgs"; //'1gznueqlXB9EK-tesPINJ4g2dxFkZsQoXWZvPsCaG7_U';
    private $sheetName = 'Folha1';
    private $range = 'A1:B2'; //$range = 'Folha1!A1:B2';


    private function getFenixVars($courseId)
    {
        $fenixVarsDB = Core::$systemDB->select("config_fenix", ["course" => $courseId], "*");

        if (empty($fenixVarsDB)) {
            $fenixVars = ["fenixCourseId" => ""];
        } else {
            $fenixVars = ["fenixCourseId" => $fenixVarsDB["fenixCourseId"]];
        }
        return $fenixVars;
    }
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
    private function getGoogleSheetsVars($courseId)
    {
        $googleSheetsDB = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

        if (empty($googleSheetsDB)) {
            $googleSheetsVars = ["spreadsheetId" => "", "sheetName" => "", "sheetRange" => ""];
        } else {
            $googleSheetsVars = ["spreadsheetId" => $googleSheetsDB["spreadsheetId"], "sheetName" => $googleSheetsDB["sheetName"], "range" => $googleSheetsDB["sheetRange"]];
        }
        return  $googleSheetsVars;
    }

    private function setFenixVars($courseId, $fenix)
    {
        $fenixVars = Core::$systemDB->select("config_fenix", ["course" => $courseId], "*");

        $arrayToDb = ["course" => $courseId, "fenixCourseId" => $fenix['fenixCourseId']];

        if (empty($fenix["fenixCourseId"])) {
            return false;
        } else {
            if (empty($fenixVars)) {
                Core::$systemDB->insert("config_fenix", $arrayToDb);
            } else {
                Core::$systemDB->update("config_fenix", $arrayToDb);
            }
            return true;
        }
    }
    private function setMoodleVars($courseId, $moodle)
    {
        $moodleVars = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");

        $arrayToDb = [
            "course" => $courseId,
            "dbServer" => $moodle['dbserver'],
            "dbUser" => $moodle['dbuser'],
            "dbPass" => $moodle['dbpass'],
            "dbName" => $moodle['db'],
            "dbPort" => $moodle["dbport"],
            "tablesPrefix" => $moodle["prefix"],
            "moodleTime" => $moodle["time"],
            "moodleCourse" => $moodle["course"],
            "moodleUser" => $moodle["user"]
        ];

        if (empty($moodle['dbserver']) || empty($moodle['dbuser']) || empty($moodle['db'])) {
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
            return true;
        }
    }
    private function setGoogleSheetsVars($courseId, $googleSheets)
    {
        $googleSheetsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

        $arrayToDb = ["course" => $courseId, "spreadsheetId" => $googleSheets["spreadsheetId"], "sheetName" => $googleSheets["sheetName"], "sheetRange" => $googleSheets["range"]];
        if (empty($googleSheets["spreadsheetId"])) {
            return false;
        } else {
            if (empty($googleSheetsVars)) {
                Core::$systemDB->insert("config_google_sheets", $arrayToDb);
            } else {
                Core::$systemDB->update("config_google_sheets", $arrayToDb);
            }
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
        // if fenix is enabled
        $this->addTables("plugin", "config_fenix", "ConfigFenix");
        $this->fenix = new Fenix($this);
        $parsedHTML = $this->fenix->parseHTML();
        $this->fenix->writeUsersToDB($parsedHTML);
        //$this->fenix = new Fenix($this);
        // $listOfStudents = $this->fenix->getStudents($this->fenixCourseId);

        //if moodle is enabled
        $this->addTables("plugin", "config_moodle", "ConfigMoodle");
        // $this->addTables("plugin", "moodle_logs", "Logs");
        // $this->addTables("plugin", "moodle_votes", "Votes");
        // $this->addTables("plugin", "moodle_quiz_grades", "QuizGrades");
        // $this->moodle = new Moodle($this);

        //  $logs = $this->moodle->getLogs($this->$time, $this->$user, $this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        //  $this->moodle->writeLogsToDB($logs);

        // $votes = $this->moodle->getVotes($this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        // $this->moodle->writeVotesToDb($votes);

        // $quiz_grades = $this->moodle->getQuizGrades($this->$course, $this->$prefix, $this->$dbserver, $this->$dbuser, $this->$dbpass, $this->$db, $this->$dbport);
        // $this->moodle->writeQuizGradesToDb($quiz_grades);


        // //if classcheck is enabled
        $this->addTables("plugin", "config_class_check", "ConfigClassCheck");
        // $this->classCheck = new ClassCheck($this);
        // $this->addTables("plugin", "attendance", "Attendance");
        // $this->classCheck->readAttendance($this->$tsvCode);


        //if googleSheets is enabled
        $this->addTables("plugin", "config_google_sheets", "ConfigGoogleSheets");
        // $this->googleSheets = new GoogleSheets($this);
        // $this->googleSheets->readGoogleSheets($this->$spreadsheetId, $this->$sheetName, $this->$range);


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
            if (API::hasKey('googleSheets')) {
                $googleSheets = API::getValue('googleSheets');
                //place to verify input values
                if ($this->setGoogleSheetsVars($courseId, $googleSheets)) {
                    API::response(["updatedData" => ["Variables for Google Sheets saved"]]);
                } else {
                    API::response(["updatedData" => ["Please fill the mandatory fields"]]);
                }

                return;
            }

            //All variables
            $fenixVars = $this->getFenixVars($courseId);
            $moodleVars = $this->getMoodleVars($courseId);
            $classCheckVars = $this->getClassCheckVars($courseId);
            $googleSheetsVars = $this->getGoogleSheetsVars($courseId);

            API::response(array('fenixVars' => $fenixVars, 'moodleVars' => $moodleVars, 'classCheckVars' => $classCheckVars, 'googleSheetsVars' => $googleSheetsVars));
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
