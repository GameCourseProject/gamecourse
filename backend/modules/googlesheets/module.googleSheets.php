<?php

namespace Modules\GoogleSheets;

use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;

class GoogleSheetsModule extends Module
{
    const ID = 'googlesheets';

    const TABLE_CONFIG = 'config_google_sheets';

    private $googleSheets;

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        /**
         * TODO: what does this function do?
         *
         * @param int $courseId
         * @param $periodicity (optional) // TODO: type?
         * @param $disablePeriodicity (optional) // TODO: type?
         * @param $credentials (optional) // TODO: type?
         * @param $googleSheets (optional) // TODO: type?
         */
        API::registerFunction(self::ID, 'courseGoogleSheets', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = Course::getCourse($courseId, false);

            if (!$course->exists())
                API::error('There is no course with id = ' . $courseId);

            if (API::hasKey('periodicity')) {
                $periodicity = API::getValue('periodicity');
                if ($this->setCronJob( $courseId, $periodicity)) API::response(["updatedData" => ["Plugin Google Sheets enabled"]]);
                else API::error("Please select a periodicity");
                return;
            }

            if (API::hasKey('disablePeriodicity')) {
                if ($this->removeCronJob($courseId)) API::response(["updatedData" => ["Plugin Google Sheets disabled"]]);
                else API::error("Please select a periodicity");
                return;
            }

            if (API::hasKey('credentials')) {
                $credentials = API::getValue('credentials');
                if ($this->setGSCredentials($courseId, $credentials)) API::response(["authUrl" => $this->getAuthUrl($courseId)]);
                else API::error("Please select a JSON file");
                return;
            }

            if (API::hasKey('googleSheets')) {
                $googleSheets = API::getValue('googleSheets');
                if ($this->setGoogleSheetsVars($courseId, $googleSheets)) API::response(["updatedData" => ["Variables for Google Sheets saved"]]);
                else API::error("Please fill the mandatory fields");
                return;
            }

            $googleSheetsVars = $this->getGoogleSheetsVars($courseId);
            API::response(array('googleSheetsVars' => $googleSheetsVars));
        });
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        $this->googleSheets = new GoogleSheets($courseId);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
    {
        $pluginArr = array();

        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $googleSheetsDB_ = Core::$systemDB->selectMultiple(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($googleSheetsDB_) {
                $gcArray = array();
                foreach ($googleSheetsDB_ as $googleSheetsDB) {
                    unset($googleSheetsDB["id"]);
                    array_push($gcArray, $googleSheetsDB);
                }
                $pluginArr[self::TABLE_CONFIG] = $gcArray;
            }
        }
        return $pluginArr;

    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false){
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
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function dropTables(string $moduleId)
    {
        $courseId = $this->getCourseId();
        new CronJob("GoogleSheets", $courseId, null, null, true);
        parent::dropTables($moduleId);
    }

    public function deleteDataRows(int $courseId)
    {
        new CronJob("GoogleSheets", $courseId, null, null, true);
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getAuthUrl($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "authUrl");
    }

    private function getGoogleSheetsVars($courseId)
    {
        $googleSheetsDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

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

    private function setGSCredentials($courseId, $gsCredentials)
    {
        if(!$gsCredentials){
            return false;
        }
        $credentialKey = key($gsCredentials[0]);
        $credentials = $gsCredentials[0][$credentialKey];
        $googleSheetCredentialsVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

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
                Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
            } else {
                Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
            }
            $this->googleSheets->setCredentials();
            return true;
        }
    }

    private function setGoogleSheetsVars($courseId, $googleSheets)
    {
        $googleSheetsVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
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
                Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
            } else {
                Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
            }
            $this->googleSheets->saveTokenToDB();
            return true;
        }
    }

    private function setCronJob($courseId, $vars)
    {
        if (!Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])) {
            return array("result" => false, "errorMessage" => "Course must be active to enable plugins");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {
            $googleSheetsVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($googleSheetsVars){
                $result = GoogleSheets::checkConnection($googleSheetsVars["course"]);
                if ($result) {
                    new CronJob("GoogleSheets", $courseId, $vars['number'], $vars['time']['name']);
                    Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 1, "periodicityNumber" => $vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                    return array("result" => true);
                } else {
                    return array("result" => false, "errorMessage" => "Connection failed");
                }
            } else {
                return array("result" => false, "errorMessage" => "Please set the class check variables");
            }
        }
    }

    private function removeCronJob($courseId)
    {
        if (self::TABLE_CONFIG) {
            Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
            new CronJob("GoogleSheets", $courseId, null, null, true);
            return array("result" => true);
        } else {
            return array("result" => false, "errorMessage" => "Could not find a table in DB for that " . "GoogleSheets" . " plugin");
        }
    }

    public function setCourseCronJobs($courseId, $active)
    {
        if(!$active){
            new CronJob("GoogleSheets", $courseId, null, null, true);
        }
        else {
            $plugins = $this->moduleConfigJson($courseId);
            $pluginNames = array_keys($plugins);
            foreach($pluginNames as $name){
                $entry = $plugins[$name][0];
                if($entry["isEnabled"]){
                    $pluginName = (strcmp($name, self::TABLE_CONFIG) !== 0)? "GoogleSheets" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]);
                }
            }
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => 'googlesheets',
    'name' => 'GoogleSheets',
    'description' => 'Allows GoogleSheets to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new GoogleSheetsModule();
    }
));


