<?php
namespace Modules\Plugin;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\CronJob;

class Plugin extends Module
{
    private $moodle;
    private $classCheck;
    private $googleSheets;

    // //Fenix variables
    // private $fenixCourseId = "1971935449711106";
    // //Moodle variables
    // private $dbserver = "localhost"; //"db.rnl.tecnico.ulisboa.pt";
    // private $dbuser = "root"; //"pcm_moodle";
    // private $dbpass = ""; //"Dkr1iRwEekJiPSHX9CeNznHlks";
    // private $db = "moodle"; //"pcm_moodle";
    // private $dbport = "3306";
    // private $prefix = "mdl_";
    // private $time = "1590790100";
    // private $course = null; //courseId no moodle
    // private $user = null;
    // //ClassCheck variables
    // private $tsvCode = "f8c691b7fc14a0455386d4cb599958d3";
    // //Google sheets variables
    // private $spreadsheetId = "19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U"; //'1gznueqlXB9EK-tesPINJ4g2dxFkZsQoXWZvPsCaG7_U';
    // private $sheetName = 'Daniel';
    // private $range = 'A1:E18'; //$range = 'Folha1!A1:B2';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData();
        $this->initDictionary();
    }

    public function initDictionary()
    {
        // FIXME: shouldn't be here; only Dictionary functions
        //do not touch bellow
        //settings page
        API::registerFunction('settings', 'coursePlugin', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');

            if (API::hasKey('fenix')) {
                $fenix = API::getValue('fenix');
                $lastFileUploaded = count($fenix) - 1;
                if(count($fenix) == 0){
                    API::error("Please fill the mandatory fields");
                }
                //place to verify input values
                $resultFenix = $this->setFenixVars($courseId, $fenix[$lastFileUploaded]);
                if (!$resultFenix) {
                    API::response(["updatedData" => ["Variables for fenix saved"]]);
                } else {
                    API::error($resultFenix);
                }

                return;
            }
            if (API::hasKey('moodle')) {
                $moodle = API::getValue('moodle');
                //place to verify input values
                if ($this->setMoodleVars($courseId, $moodle)) {
                    API::response(["updatedData" => ["Variables for moodle saved"]]);
                } else {
                    API::error("Please fill the mandatory fields");
                }
                return;
            }
            if (API::hasKey('moodlePeriodicity')) {
                $moodle = API::getValue('moodlePeriodicity');
                //place to verify input values
                $response = $this->setCronJob("Moodle", $courseId, $moodle);
                if ($response["result"]) {
                    API::response(["updatedData" => ["Plugin Moodle enabled"]]);
                } else {
                    API::error($response["errorMessage"]);
                }
                return;
            }
            if (API::hasKey('classCheckPeriodicity')) {
                $classCheck = API::getValue('classCheckPeriodicity');
                //place to verify input values
                $response = $this->setCronJob("ClassCheck", $courseId, $classCheck);
                if ($response["result"]) {
                    API::response(["updatedData" => ["Plugin Class Check enabled"]]);
                } else {
                    API::error($response["errorMessage"]);
                }
                return;
            }
            if (API::hasKey('googleSheetsPeriodicity')) {
                $googleSheets = API::getValue('googleSheetsPeriodicity');
                //place to verify input values
                if ($this->setCronJob("GoogleSheets", $courseId, $googleSheets)) {
                    API::response(["updatedData" => ["Plugin Google Sheets enabled"]]);
                } else {
                    API::error("Please select a periodicity");
                }
                return;
            }
            if (API::hasKey('disableMoodlePeriodicity')) {
                $moodle = API::getValue('moodlePeriodicity');
                //place to verify input values
                $response = $this->removeCronJob("Moodle", $courseId);
                if ($response["result"]) {
                    API::response(["updatedData" => ["Plugin Moodle disabled"]]);
                } else {
                    API::error($response["errorMessage"]);
                }
                return;
            }
            if (API::hasKey('disableClassCheckPeriodicity')) {
                //place to verify input values
                $response = $this->removeCronJob("ClassCheck", $courseId);
                if ($response["result"]) {
                    API::response(["updatedData" => ["Plugin Class Check disabled"]]);
                } else {
                    API::error([$response["errorMessage"]]);
                }
                return;
            }
            if (API::hasKey('disableGoogleSheetsPeriodicity')) {
                $googleSheets = API::getValue('googleSheetsPeriodicity');
                //place to verify input values
                if ($this->removeCronJob("GoogleSheets", $courseId)) {
                    API::response(["updatedData" => ["Plugin Google Sheets disabled"]]);
                } else {
                    API::error("Please select a periodicity");
                }
                return;
            }
            if (API::hasKey('classCheck')) {
                $classCheck = API::getValue('classCheck');
                //place to verify input values
                if ($this->setClassCheckVars($courseId, $classCheck)) {
                    API::response(["updatedData" => ["Variables for Class check saved"]]);
                } else {
                    API::error("Please fill the mandatory fields");
                }

                return;
            }
            if (API::hasKey('credentials')) {
                $credentials = API::getValue('credentials');
                if ($this->setGSCredentials($courseId, $credentials)) {
                    API::response(["authUrl" => $this->getAuthUrl($courseId)]);
                } else {
                    API::error("Please select a JSON file");
                }
                return;
            }


            if (API::hasKey('googleSheets')) {


                $googleSheets = API::getValue('googleSheets');
                //place to verify input values
                if ($this->setGoogleSheetsVars($courseId, $googleSheets)) {
                    API::response(["updatedData" => ["Variables for Google Sheets saved"]]);
                } else {
                    API::error("Please fill the mandatory fields");
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

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function setupData(){
        //if classcheck is enabled
        $this->addTables("plugin", "config_class_check", "ConfigClassCheck");
        $this->classCheck = new ClassCheck(API::getValue('course'));

        //if googleSheets is enabled
        $this->addTables("plugin", "config_google_sheets", "ConfigGoogleSheets");
        $this->googleSheets = new GoogleSheets(API::getValue('course'));

        //if moodle is enabled
        $this->addTables("plugin", "config_moodle", "ConfigMoodle");
        $this->moodle = new Moodle(API::getValue('course'));
    }

    public function update_module($module)
    {
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = MODULES_FOLDER . "/plugin/config.json";
        $contents = array();
        if (file_exists($configFile)) {
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson($courseId): array
    {
        $pluginArr = array();

        if (Core::$systemDB->tableExists("config_moodle")) {
            $moodleVarsDB_ = Core::$systemDB->selectMultiple("config_moodle", ["course" => $courseId], "*");
            if ($moodleVarsDB_) {
                $moodleArray = array();
                foreach ($moodleVarsDB_ as $moodleVarsDB) {
                    unset($moodleVarsDB["course"]);
                    unset($moodleVarsDB["id"]);
                    array_push($moodleArray, $moodleVarsDB);
                }
                $pluginArr["config_moodle"] = $moodleArray;
            }
        }
        if (Core::$systemDB->tableExists("config_class_check")) {
            $classCheckDB_ = Core::$systemDB->selectMultiple("config_class_check", ["course" => $courseId], "*");
            if ($classCheckDB_) {
                $ccArray = array();
                foreach ($classCheckDB_ as $classCheckDB) {
                    unset($classCheckDB["id"]);
                    array_push($ccArray, $classCheckDB);
                }
                $pluginArr["config_class_check"] = $ccArray;
            }
        }
        if (Core::$systemDB->tableExists("config_google_sheets")) {
            $googleSheetsDB_ = Core::$systemDB->selectMultiple("config_google_sheets", ["course" => $courseId], "*");
            if ($googleSheetsDB_) {
                $gcArray = array();
                foreach ($googleSheetsDB_ as $googleSheetsDB) {
                    unset($googleSheetsDB["id"]);
                    array_push($gcArray, $googleSheetsDB);
                }
                $pluginArr["config_google_sheets"] = $gcArray;
            }
        }
        return $pluginArr;
    }

    public function readConfigJson($courseId, $tables, $update=false): bool
    {
        $tableName = array_keys($tables);
        $i = 0;
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                if($update && $existingCourse){
                    Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                }else{
                    $entry["course"] = $courseId;
                    Core::$systemDB->insert($tableName[$i], $entry);
                }
            }
            $i++;
        }
        return false;
    }

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return "pluginPersonalizedConfig";
    }

    public function has_general_inputs(): bool
    {
        return false;
    }

    public function has_listing_items(): bool {
        return  false;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId)
    {
        new CronJob("Moodle", $courseId, null, null, true);
        new CronJob("ClassCheck", $courseId, null, null, true);
        new CronJob("GoogleSheets", $courseId, null, null, true);

        Core::$systemDB->delete("config_google_sheets", ["course" => $courseId]);
        Core::$systemDB->delete("config_class_check", ["course" => $courseId]);
        Core::$systemDB->delete("config_moodle", ["course" => $courseId]);
    }

    public function dropTables($moduleName)
    {
        $courseId = API::getValue('course');
        new CronJob("Moodle", $courseId, null, null, true);
        new CronJob("ClassCheck", $courseId, null, null, true);
        new CronJob("GoogleSheets", $courseId, null, null, true);
        parent::dropTables($moduleName);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getMoodleVars($courseId): array
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
                "time" => 0,
                "course" => "",
                "user" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes'
            ];
        } else {
            if (!$moodleVarsDB["periodicityNumber"]) {
                $moodleVarsDB["periodicityNumber"] = 0;
            }
            if (!$moodleVarsDB["periodicityTime"]) {
                $moodleVarsDB["periodicityTime"] = 'Minutes';
            }
            $moodleVars = [
                "dbserver" => $moodleVarsDB["dbServer"],
                "dbuser" => $moodleVarsDB["dbUser"],
                "dbpass" => $moodleVarsDB["dbPass"],
                "db" => $moodleVarsDB["dbName"],
                "dbport" => $moodleVarsDB["dbPort"],
                "prefix" => $moodleVarsDB["tablesPrefix"],
                "time" => $moodleVarsDB["moodleTime"],
                "course" => $moodleVarsDB["moodleCourse"],
                "user" => $moodleVarsDB["moodleUser"],
                "periodicityNumber" => intval($moodleVarsDB["periodicityNumber"]),
                "periodicityTime" => $moodleVarsDB["periodicityTime"]
            ];
        }

        return $moodleVars;
    }

    private function getClassCheckVars($courseId): array
    {
        $classCheckDB = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

        if (empty($classCheckDB)) {
            $classCheckVars = [
                "tsvCode" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes'
            ];
        } else {
            if (!$classCheckDB["periodicityNumber"]) {
                $classCheckDB["periodicityNumber"] = 0;
            }
            if (!$classCheckDB["periodicityTime"]) {
                $classCheckDB["periodicityTime"] = 'Minutes';
            }
            $classCheckVars = [
                "tsvCode" => $classCheckDB["tsvCode"],
                "periodicityNumber" => intval($classCheckDB["periodicityNumber"]),
                "periodicityTime" => $classCheckDB["periodicityTime"]
            ];
        }

        return  $classCheckVars;
    }

    private function getAuthUrl($courseId)
    {
        return Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "authUrl");
    }

    private function getGoogleSheetsVars($courseId): array
    {
        $googleSheetsDB = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");

        if (empty($googleSheetsDB)) {
            $googleSheetsVars = [
                "token" => "", 
                "spreadsheetId" => "", 
                "sheetName" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes'];
        } else {
            if (!$googleSheetsDB["periodicityNumber"]) {
                $googleSheetsDB["periodicityNumber"] = 0;
            }
            if (!$googleSheetsDB["periodicityTime"]) {
                $googleSheetsDB["periodicityTime"] = 'Minutes';
            }
            $names = explode(";", $googleSheetsDB["sheetName"]);
            $sheetNames = [];
            $ownerNames = [];
            foreach($names as $name){
                $processedName = explode(",", $name);
                array_push($sheetNames, $processedName[0]);
                if(count($processedName) > 1)
                    array_push($ownerNames, $processedName[1]);
            }

            $professors = Core::$systemDB->selectMultiple("user_role u join role r on u.role=r.id join auth a on u.id=a.game_course_user_id join game_course_user g on u.id=g.id",
                                                            ["u.course" => $courseId, "r.name" => "Teacher"],
                                                            "a.username, g.name");

            $googleSheetsVars = [
                "spreadsheetId" => $googleSheetsDB["spreadsheetId"],
                "sheetName" => $sheetNames,
                "ownerName" => $ownerNames,
                "professors" => $professors,
                "periodicityNumber" => intval($googleSheetsDB["periodicityNumber"]),
                "periodicityTime" => $googleSheetsDB["periodicityTime"]
            ];
        }

        return  $googleSheetsVars;
    }

    private function setFenixVars($courseId, $fenix): string
    {
        $course = new Course($courseId);
        $year = $course->getData("year");
        for ($line = 1; $line < sizeof($fenix) - 1; $line++) {
            $fields = explode(";", $fenix[$line]);
            if(count($fields) < 10){
                return "The number of columns is incorrect, please check the template";
            }

            $username = $fields[0];
            $studentNumber = $fields[1];
            $studentName = $fields[2];
            $email = $fields[3];
            $courseName = $fields[10];
            $major = "";

            if (strpos($courseName, 'Alameda')) {
                $major = "MEIC-A";
            } else if (strpos($courseName, 'Taguspark')) {
                $major = "MEIC-T";
            } else {
                $endpoint = "degrees";
                if($year){
                    $year = str_replace("-", "/", $year);
                    $endpoint = "degrees?academicTerm=".$year;
                }
                $listOfCourses = Core::getFenixInfo($endpoint);
                $courseFound = false;
                if($listOfCourses){
                    foreach ($listOfCourses as $courseFenix) {
                        if ($courseFound) {
                            break;
                        } else {
                            if (strpos($courseName, $courseFenix->name)) {
                                $courseFound = true;
                                foreach ($courseFenix->campus as $campusfenix) {
                                    $major = $campusfenix->name[0];
                                }
                            }
                        }
                    }
                }
            }
            $roleId = Core::$systemDB->select("role", ["name"=>"Student", "course"=>$courseId], "id");
            if($studentNumber){
                if (!User::getUserByStudentNumber($studentNumber)) {
                    User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $user = User::getUserByStudentNumber($studentNumber);
                    $courseUser = new CourseUser($user->getId(), $course);
                    $courseUser->addCourseUserToDB($roleId);
                } else {
                    $existentUser = User::getUserByStudentNumber($studentNumber);
                    $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                    $courseUser = new CourseUser($existentUser->getId(), $course);
                    if(!Core::$systemDB->select("course_user", ["id" => $existentUser->getId(), "course" => $courseId])){
                        $courseUser->addCourseUserToDB($roleId);
                    }else{
                        $courseUser->editCourseUser($existentUser->getId(), $course->getId(), $major, null);
                    }
                }
            }else{
                if (!User::getUserByUsername($username)) {
                    User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $user = User::getUserByUsername($username);
                    $courseUser = new CourseUser($user->getId(), $course);
                    $courseUser->addCourseUserToDB($roleId);
                } else {
                    $existentUser = User::getUserByUsername($username);
                    $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $courseUser = new CourseUser($existentUser->getId(), $course);
                    if (!Core::$systemDB->select("course_user", ["id" => $existentUser->getId(), "course" => $courseId])) {
                        $courseUser->addCourseUserToDB($roleId);
                    } else {
                        $courseUser->editCourseUser($existentUser->getId(), $course->getId(), $major, null);
                    }
                }
            }
        }
        return "";
    }

    private function setMoodleVars($courseId, $moodleVar): bool
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
                Core::$systemDB->update("config_moodle", $arrayToDb, ["course" => $courseId] );
            }
            return true;
        }
    }

    private function setClassCheckVars($courseId, $classCheck): bool
    {
        $classCheckVars = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

        $arrayToDb = ["course" => $courseId, "tsvCode" => $classCheck['tsvCode']];

        if (empty($classCheck["tsvCode"])) {
            return false;
        } else {
            if (empty($classCheckVars)) {
                Core::$systemDB->insert("config_class_check", $arrayToDb);
            } else {
                Core::$systemDB->update("config_class_check", $arrayToDb, ["course" => $courseId] );
            }
            return true;
        }
    }

    private function setGSCredentials($courseId, $gsCredentials): bool
    {
        if(!$gsCredentials){
            return false;
        }
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
                Core::$systemDB->update("config_google_sheets", $arrayToDb, ["course" => $courseId]);
            }
            $this->googleSheets->setCredentials();
            return true;
        }
    }

    private function setGoogleSheetsVars($courseId, $googleSheets): bool
    {
        $googleSheetsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");
        $names = "";
        $i = 0;
        foreach ($googleSheets["sheetName"] as $name) {
            if (strlen($name) != 0) {
                //$names .= $name . ";";
                $owner = $googleSheets["ownerName"][$i];
                $names .= $name . "," . $owner . ";";
            }
            $i++;
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
                Core::$systemDB->update("config_google_sheets", $arrayToDb, ["course" => $courseId]);
            }
            $this->googleSheets->saveTokenToDB();
            return true;
        }
    }

    private function setCronJob($script, $courseId, $vars)
    {        
        if(!Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])){
            return array("result" => false, "errorMessage" => "Course must be active to enable plugins");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {
            if ($script == "Moodle"){
                //verificar table config
                $moodleVars = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");
                if ($moodleVars){
                    //verificar ligaçao à bd
                    $result = Moodle::checkConnection($moodleVars["dbServer"], $moodleVars["dbUser"], $moodleVars["dbPass"], $moodleVars["dbName"], $moodleVars["dbPort"]);
                    if($result){
                        new CronJob($script, $courseId, $vars['number'], $vars['time']['name']);
                        Core::$systemDB->update("config_moodle", ["isEnabled" => 1, "periodicityNumber" => $vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                        return array("result"=> true);
                    }else{
                        return array("result" => false, "errorMessage" =>"Connection failed");
                    }
                } else{ 
                    return array("result"=> false, "errorMessage" => "Please set the moodle variables");
                }

            } else if ($script == "ClassCheck"){
                $classCheckVars = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");
                if ($classCheckVars){
                    $result = ClassCheck::checkConnection($classCheckVars["tsvCode"]);
                    if ($result){
                        new CronJob($script, $courseId, $vars['number'], $vars['time']['name']);
                        Core::$systemDB->update("config_class_check", ["isEnabled" => 1, "periodicityNumber" =>$vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                        return array("result" => true);
                    } else {
                        return array("result" => false, "errorMessage" => "Connection failed");
                    }
                } else {
                    return array("result" => false, "errorMessage" => "Please set the class check variables");
                }
            } else if ($script == "GoogleSheets"){
                $googleSheetsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");
                if ($googleSheetsVars){
                    $result = GoogleSheets::checkConnection($googleSheetsVars["course"]);
                    if ($result) {
                        new CronJob($script, $courseId, $vars['number'], $vars['time']['name']);
                        Core::$systemDB->update("config_google_sheets", ["isEnabled" => 1, "periodicityNumber" => $vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                        return array("result" => true);
                    } else {
                        return array("result" => false, "errorMessage" => "Connection failed");
                    }
                } else {
                    return array("result" => false, "errorMessage" => "Please set the class check variables");
                }
            }

           
        }
    }

    private function removeCronJob($script, $courseId): array
    {
        $tableName = "";
        if ($script == "Moodle") {
            $tableName = "config_moodle";
        } else if ($script == "ClassCheck") {
            $tableName = "config_class_check";
        } else if ($script == "GoogleSheets") {
            $tableName = "config_google_sheets";
        }
        if($tableName){
            Core::$systemDB->update($tableName, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
            new CronJob($script, $courseId, null, null, true);
            return array("result" => true);
        }else{
            return array("result" => false, "errorMessage" => "Could not find a table in DB for that ".$script. " plugin");
        }
    }

    //removes or adds all active cronjobs according to course's active state
    public function setCourseCronJobs($courseId, $active)
    {
        if(!$active){
            new CronJob("Moodle",  $courseId, null, null, true);
            new CronJob("ClassCheck", $courseId, null, null, true);
            new CronJob("GoogleSheets", $courseId, null, null, true);
        }
        else {
            $plugins = $this->moduleConfigJson($courseId);
            $pluginNames = array_keys($plugins);
            foreach($pluginNames as $name){
                $entry = $plugins[$name][0];
                if($entry["isEnabled"]){
                    $pluginName = (strcmp($name, "config_google_sheets") !== 0)? "GoogleSheets" : (strcmp($name, "config_moodle") !== 0)? "Moodle" : (strcmp($name, "config_class_check") !== 0)? "ClassCheck" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]); 
                }
            }
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => 'plugin',
    'name' => 'Plugin',
    'description' => 'Allows multiple sources of information to be automaticaly included on gamecourse.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Plugin();
    }
));
