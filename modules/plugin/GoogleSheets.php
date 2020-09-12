<?php

namespace Modules\Plugin;

use GameCourse\GoogleHandler;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\User;

class GoogleSheets
{
    private $courseId;
    private $spreadsheetId;
    private $sheetName;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }

    public function getCredentialsFromDB()
    {
        $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");

        $uris = explode(";", $credentialDB["redirectUris"]);

        $arrayKey[$credentialDB['key_']] = array(
            'client_id' => $credentialDB['clientId'], "project_id" => $credentialDB["projectId"],
            'auth_uri' => $credentialDB['authUri'], "token_uri" => $credentialDB["tokenUri"], "auth_provider_x509_cert_url" => $credentialDB["authProvider"],
            'client_secret' => $credentialDB["clientSecret"], "redirect_uris" => $uris
        );
        return $arrayKey;
    }

    public function getTokenFromDB()
    {
        $accessExists = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "accessToken");
        if ($accessExists) {
            $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");

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

    public function setCredentials()
    {
        $credentials = $this->getCredentialsFromDB();
        GoogleHandler::setCredentials(json_encode($credentials));
    }

    public function setAuthCode()
    {
        $response = $this->handleToken();
        if ($response["auth_url"]) {
            Core::$systemDB->update(
                "config_google_sheets",
                ["authUrl" => $response["auth_url"]]
            );
        }
    }

    public function handleToken()
    {
        $credentials = $this->getCredentialsFromDB();
        $token = $this->getTokenFromDB();
        $authCode = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "authCode");
        return GoogleHandler::checkToken($credentials, $token, $authCode);
    }

    public function saveTokenToDB()
    {
        $response = $this->handleToken();
        $token = $response["access_token"];
        if ($token) {

            $arrayToDB = array(
                "course" => $this->courseId,
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

    public function getDBConfigValues()
    {
        $googleSheetsVarsDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");
        $this->spreadsheetId = $googleSheetsVarsDB["spreadsheetId"];
        $this->sheetName = $googleSheetsVarsDB["sheetName"];
    }

    public function readGoogleSheets()
    {
        $credentials = $this->getCredentialsFromDB();
        $token = $this->getTokenFromDB();
        $authCode = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "authCode");

        $service = GoogleHandler::getGoogleSheets($credentials, $token, $authCode);
        $this->getDBConfigValues();
        // $tableName = $service->spreadsheets->get($this->spreadsheetId)->properties->title;
        $names = explode(";", $this->sheetName);

        foreach ($names as $name) {
            $responseRows = $service->spreadsheets_values->get($this->spreadsheetId, $name);
            $name = substr($name, 0, -1);
            $this->writeToDB($name, $responseRows->getValues());
        }
    }

    public function writeToDB($name, $valuesRows)
    {
        $prof = User::getUserByUsername($name);
        $profId = $prof == null ? null : $prof->getId();
        for ($row = 1; $row < sizeof($valuesRows); $row++) {
            $user = User::getUserByStudentNumber($valuesRows[$row][0]);
            $action = $valuesRows[$row][3];
            if ($user) {
                if (
                    $action == "attended lecture" || $action == "attended lecture (late)" || $action == "attended lab"
                    || $action == "replied to questionnaires"
                ) {
                    $info  = $valuesRows[$row][5];
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => $info,
                            "type" =>  $action,
                            "rating" =>  0,
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "initial bonus" || $action == "presentation grade") {
                    $xp = $valuesRows[$row][4];
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => "",
                            "type" =>  $action,
                            "rating" =>  $xp,
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "suggested presentation subject" || $action == "participated in focus groups") {
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => "",
                            "type" =>  $action,
                            "rating" =>  0,
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "quiz grade" || $action == "lab grade") {
                    $info  = $valuesRows[$row][5];
                    $xp = $valuesRows[$row][4];
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => $info,
                            "type" =>  $action,
                            "rating" =>  $xp,
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "popular choice award (presentation)" || $action == "golden star award") {
                    $info  = $valuesRows[$row][5];
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => $info,
                            "type" =>  $action,
                            "moduleInstance" => "badges",
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "presentation king" || $action == "lab king" || $action == "quiz king") {
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => "",
                            "type" =>  $action,
                            "moduleInstance" => "badges",
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "hall of fame") {
                    $info  = $valuesRows[$row][5];
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => "",
                            "post" => $info,
                            "type" =>  $action,
                            "moduleInstance" => "badges",
                            "evaluator" => $profId
                        )
                    );
                } else if ($action == "course emperor") {
                    Core::$systemDB->insert(
                        "participation",
                        array(
                            "user" => $user->getId(),
                            "course" => $this->courseId,
                            "description" => "",
                            "type" =>  $action,
                            "moduleInstance" => "badges",
                            "evaluator" => $profId
                        )
                    );
                }
            }
        }
    }
}
