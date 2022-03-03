<?php

namespace Modules\GoogleSheets;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;

class GoogleSheetsModule extends Module
{
    const ID = 'googlesheets';

    const TABLE_CONFIG = self::ID . '_config';

    static $googleSheets;

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        /**
         * Gets googlesheet variables.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getGoogleSheetsVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(array('googleSheetsVars' => $this->getGoogleSheetsVars($courseId)));
        });

        /**
         * Sets googlesheet variables.
         *
         * @param int $courseId
         * @param $googleSheets
         */
        API::registerFunction(self::ID, 'setGoogleSheetsVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'googleSheets');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $googleSheets = API::getValue('googleSheets');
            $this->setGoogleSheetsVars($courseId, $googleSheets);
        });

        /**
         * Sets googlesheet credentials.
         *
         * @param int $courseId
         * @param $credentials
         */
        API::registerFunction(self::ID, 'setGoogleSheetsCredentials', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'credentials');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $credentials = API::getValue('credentials');
            $this->setGSCredentials($courseId, $credentials);
            API::response(['authUrl' => $this->getAuthUrl($courseId)]);
        });
    }

    public function setupResources()
    {
        parent::addResources('css/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        self::$googleSheets = new GoogleSheets($courseId);
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

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getGoogleSheetsVars($courseId): array
    {
        $googleSheetsDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
        $isEmpty = empty($googleSheetsDB);

        $names = explode(";", $googleSheetsDB["sheetName"]);
        $sheetNames = [];
        $ownerNames = [];
        foreach($names as $name){
            $processedName = explode(",", $name);
            array_push($sheetNames, $processedName[0]);
            if(count($processedName) > 1)
                array_push($ownerNames, $processedName[1]);
        }

        return [
            "spreadsheetId" => $isEmpty ? "" : $googleSheetsDB["spreadsheetId"],
            "sheetName" => $isEmpty ? [] : $sheetNames,
            "ownerName" => $isEmpty ? [] : $ownerNames
        ];
    }

    private function getAuthUrl($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "authUrl");
    }

    private function setGSCredentials($courseId, $gsCredentials)
    {
        if (!$gsCredentials) API::error('No credentials key found.');;

        $credentialKey = key($gsCredentials);
        $credentials = $gsCredentials[$credentialKey];
        $googleSheetCredentialsVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

        $uris = "";
        for ($uri = 0; $uri < sizeof($credentials["redirect_uris"]); $uri++) {
            $uris .= $credentials["redirect_uris"][$uri];
            if ($uri != sizeof($credentials["redirect_uris"]) - 1) {
                $uris .= ";";
            }
        }

        $arrayToDb = [
            "course" => $courseId,
            "key_" => $credentialKey,
            "clientId" => $credentials["client_id"],
            "projectId" => $credentials["project_id"],
            "authUri" => $credentials["auth_uri"],
            "tokenUri" => $credentials["token_uri"],
            "authProvider" => $credentials["auth_provider_x509_cert_url"],
            "clientSecret" => $credentials["client_secret"],
            "redirectUris" => $uris
        ];

        if (empty($credentials)) {
            API::error('No credentials found.');

        } else {
            if (empty($googleSheetCredentialsVars)) {
                Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
            } else {
                Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
            }
            self::$googleSheets->setCredentials();
        }
    }

    private function setGoogleSheetsVars($courseId, $googleSheets)
    {
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

        $arrayToDb = [
            "course" => $courseId,
            "spreadsheetId" => $googleSheets["spreadsheetId"],
            "sheetName" => $names,
        ];

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
        }

        // Verify connection to Google sheet
        if (!GoogleSheets::checkConnection($courseId))
            API::error("GoogleSheets connection failed.");

        self::$googleSheets->saveTokenToDB();
    }
}

ModuleLoader::registerModule(array(
    'id' => GoogleSheetsModule::ID,
    'name' => 'GoogleSheets',
    'description' => 'Allows GoogleSheets to be automaticaly included on GameCourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new GoogleSheetsModule();
    }
));


