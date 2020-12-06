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

    public static function checkConnection($courseId){
        $gs = new GoogleSheets($courseId);
        $credentials = $gs->getCredentialsFromDB();
        $token = $gs->getTokenFromDB();
        $authCode = Core::$systemDB->select("config_google_sheets", ["course" => $gs->courseId], "authCode");
        $service = GoogleHandler::getGoogleSheets($credentials, $token, $authCode, $gs->courseId);
        $gs->getDBConfigValues();
        $names = explode(";", $gs->sheetName);

        foreach ($names as $name) {
              $service->spreadsheets_values->get($gs->spreadsheetId, $name);
        }
        return true;
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
        $cli = GoogleHandler::setCredentials(json_encode($credentials), $this->courseId);
        Core::$systemDB->update("config_google_sheets", ["authUrl" => $cli->createAuthUrl()], ["course" => $this->courseId]);
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
        return GoogleHandler::checkToken($credentials, $token, $authCode, $this->courseId);
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

        $service = GoogleHandler::getGoogleSheets($credentials, $token, $authCode, $this->courseId);
        $this->getDBConfigValues();
        // $tableName = $service->spreadsheets->get($this->spreadsheetId)->properties->title;
        $names = explode(";", $this->sheetName);

        $insertedOrUpdated = false;
        foreach ($names as $name) {
            $responseRows = $service->spreadsheets_values->get($this->spreadsheetId, $name);
            $name = substr($name, 0, -1);
            if($this->writeToDB($name, $responseRows->getValues())){
                $insertedOrUpdated = true;
            }
        }
        return $insertedOrUpdated;
    }

    public function writeToDB($name, $valuesRows)
    {
        $insertedOrUpdated = false;
        $profId = User::getUserByUsername($name)->getId();
        for ($row = 1; $row < sizeof($valuesRows); $row++) {
            $user = User::getUserByStudentNumber($valuesRows[$row][0]);
            $action = $valuesRows[$row][3];
            if ($user) {
                if (Core::$systemDB->select("course_user", ["course" => $this->courseId, "id" => $user->getId()])) {
                    if (
                            $action == "attended lecture" || $action == "attended lecture (late)" || $action == "attended lab"
                            || $action == "replied to questionnaires"
                        ) {
                            $info  = $valuesRows[$row][5];
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action, "description" => $info]);
                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            } else {
                                if ($result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update(
                                        "participation",
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "description" => $info,
                                            "type" =>  $action,
                                            "rating" =>  0,
                                            "evaluator" => $profId
                                        ),
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "type" =>  $action
                                        )
                                    );
                                }
                            }
                        } else if ($action == "initial bonus" || $action == "presentation grade") {
                            $xp = $valuesRows[$row][4];
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action]);

                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            } else {
                                if ($result["rating"] != $xp) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update(
                                        "participation",
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "description" => "",
                                            "type" =>  $action,
                                            "rating" =>  $xp,
                                            "evaluator" => $profId
                                        ),
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "type" =>  $action
                                        )
                                    );
                                }
                            }
                        } else if ($action == "suggested presentation subject" || $action == "participated in focus groups") {
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action]);

                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            }
                        } else if ($action == "quiz grade" || $action == "lab grade") {
                            $info  = $valuesRows[$row][5];
                            $xp = $valuesRows[$row][4];
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action, "description"=> $info]);
                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            } else {
                                if ($result["rating"] != $xp || $result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update(
                                        "participation",
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "type" =>  $action,
                                            "rating" =>  $xp,
                                            "evaluator" => $profId
                                        ),
                                        array(
                                            "description" => $info,
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "type" =>  $action
                                        )
                                    );
                                }
                            }
                        } else if ($action == "popular choice award (presentation)" || $action == "golden star award") {
                            $info  = $valuesRows[$row][5];
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action]);
                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            } else {
                                if ($result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update(
                                        "participation",
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "description" => $info,
                                            "type" =>  $action,
                                            "moduleInstance" => "badges",
                                            "evaluator" => $profId
                                        ),
                                        array(
                                            "user" => $user->getId(),
                                            "course" => $this->courseId,
                                            "type" =>  $action
                                        )
                                    );
                                }
                            }
                        } else if ($action == "presentation king" || $action == "lab king" || $action == "quiz king") {
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action]);

                            if (!$result) {
                                $insertedOrUpdated = true;
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
                        } else if ($action == "hall of fame") {
                            $info  = $valuesRows[$row][5];
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type" => $action, "info"=> $info]);
                            if (!$result) {
                                $insertedOrUpdated = true;
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
                            }
                        } else if ($action == "course emperor") {
                            $result = Core::$systemDB->select("participation", ["user" => $user->getId(), "course" => $this->courseId, "type"=>$action]);
                            if (!$result) {
                                $insertedOrUpdated = true;
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
        return $insertedOrUpdated;
    }
}
