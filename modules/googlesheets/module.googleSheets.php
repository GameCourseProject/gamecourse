<?php

namespace Modules\GoogleSheets;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\CronJob;

class GoogleSheets extends Module
{
    private $googleSheets;

    private function getAuthUrl($courseId)
    {
        return Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "authUrl");
    }
    private function getGoogleSheetsVars($courseId)
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

    private function setGSCredentials($courseId, $gsCredentials)
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
    private function setGoogleSheetsVars($courseId, $googleSheets)
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

    private function setCronJob($courseId, $vars)
    {
        if (!Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])) {
            return array("result" => false, "errorMessage" => "Course must be active to enable plugins");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {
            $googleSheetsVars = Core::$systemDB->select("config_google_sheets", ["course" => $courseId], "*");
            if ($googleSheetsVars){
                $result = GoogleSheetsModule::checkConnection($googleSheetsVars["course"]);
                if ($result) {
                    new CronJob("GoogleSheets", $courseId, $vars['number'], $vars['time']['name']);
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

    private function removeCronJob($courseId)
    {
        $tableName = "config_google_sheets";
        if ($tableName) {
            Core::$systemDB->update($tableName, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
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
                    $pluginName = (strcmp($name, "config_google_sheets") !== 0)? "GoogleSheets" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]);
                }
            }
        }
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function moduleConfigJson($courseId)
    {
        $pluginArr = array();

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

    public function readConfigJson($courseId, $tables, $update=false){
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

    public function init()
    {

        $this->addTables("googlesheets", "config_google_sheets", "ConfigGoogleSheets");
        $this->googleSheets = new GoogleSheets(API::getValue('course'));

        API::registerFunction('settings', 'courseGoogleSheets', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');

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

            $googleSheetsVars = $this->getGoogleSheetsVars($courseId);
            API::response(array('googleSheetsVars' => $googleSheetsVars));
        });

    }

    public function dropTables($moduleName)
    {
        $courseId = API::getValue('course');
        new CronJob("GoogleSheets", $courseId, null, null, true);
        parent::dropTables($moduleName);
    }

    public function deleteDataRows($courseId)
    {
        new CronJob("GoogleSheets", $courseId, null, null, true);
        Core::$systemDB->delete("config_google_sheets", ["course" => $courseId]);
    }


    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function is_configurable()
    {
        return true;
    }

    public function has_personalized_config()
    {
        return true;
    }

    public function get_personalized_function()
    {
        return "googleSheetsPersonalizedConfig";
    }

    public function has_general_inputs()
    {
        return false;
    }

    public function has_listing_items()
    {
        return false;
    }
}

ModuleLoader::registerModule(array(
    'id' => 'googlesheets',
    'name' => 'GoogleSheets',
    'description' => 'Allows GoogleSheets to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new GoogleSheets();
    }
));


