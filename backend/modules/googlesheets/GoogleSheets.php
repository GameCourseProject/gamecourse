<?php

namespace Modules\GoogleSheets;

use GameCourse\GoogleHandler;
use GameCourse\Core;
use GameCourse\User;

class GoogleSheets
{
    private $courseId;
    private $spreadsheetId;
    private $sheetName;

    const COL_STUDENT_NUMBER = 0;
    const COL_STUDENT_NAME = 1;
    const COL_STUDENT_CAMPUS = 2;
    const COL_ACTION = 3;
    const COL_XP = 4;
    const COL_INFO = 5;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }

    public static function checkConnection($courseId){
        $gs = new GoogleSheets($courseId);
        $credentials = $gs->getCredentialsFromDB();
        $token = $gs->getTokenFromDB();
        $service = GoogleHandler::getGoogleSheets($credentials, $token, null, $gs->courseId);
        $gs->getDBConfigValues();
        $names = explode(";", $gs->sheetName);

        foreach ($names as $name) {
            $currentName = explode(",", $name);
            $service->spreadsheets_values->get($gs->spreadsheetId, $currentName[0]);
        }
        return true;
    }

    public function getCredentialsFromDB()
    {
        $credentialDB = Core::$systemDB->select(GoogleSheetsModule::TABLE_CONFIG, ["course" => $this->courseId], "*");

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
        $accessExists = Core::$systemDB->select(GoogleSheetsModule::TABLE_CONFIG, ["course" => $this->courseId], "accessToken");
        if ($accessExists) {
            $credentialDB = Core::$systemDB->select(GoogleSheetsModule::TABLE_CONFIG, ["course" => $this->courseId], "*");

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
        Core::$systemDB->update(GoogleSheetsModule::TABLE_CONFIG, ["authUrl" => $cli->createAuthUrl()], ["course" => $this->courseId]);
    }


    public function handleToken($code = null)
    {
        $credentials = $this->getCredentialsFromDB();
        $token = $this->getTokenFromDB();
        return GoogleHandler::checkToken($credentials, $token, $code, $this->courseId);
    }

    public function saveTokenToDB($code = null)
    {
        $response = $this->handleToken($code);
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
            Core::$systemDB->update(GoogleSheetsModule::TABLE_CONFIG, $arrayToDB, ["course" => $this->courseId]);
        }
    }

    public function getDBConfigValues()
    {
        $googleSheetsVarsDB = Core::$systemDB->select(GoogleSheetsModule::TABLE_CONFIG, ["course" => $this->courseId], "*");
        $this->spreadsheetId = $googleSheetsVarsDB["spreadsheetId"];
        $this->sheetName = $googleSheetsVarsDB["sheetName"];
    }

    public function readGoogleSheets()
    {
        $credentials = $this->getCredentialsFromDB();
        $token = $this->getTokenFromDB();
        $service = GoogleHandler::getGoogleSheets($credentials, $token, null, $this->courseId);
        $this->getDBConfigValues();

        $sql = "insert into participation (user, course, description, type, rating, evaluator) values ";
        $values = "";
        $insertedOrUpdated = false;

        $names = explode(";", $this->sheetName);
        foreach ($names as $name) {
            $processedName = explode(",", $name);
            $sheetName = $processedName[0];
            $profUsername = $processedName[1];
            $responseRows = $service->spreadsheets_values->get($this->spreadsheetId, $sheetName);

            list($newValues, $iOrU) = $this->writeToDB($profUsername, $responseRows->getValues());
            $values .= $newValues;
            $insertedOrUpdated = $iOrU;
        }

        if ($values) {
            $values = rtrim($values, ",");
            $sql .= $values;
            Core::$systemDB->executeQuery($sql);
        }

        return $insertedOrUpdated;
    }

    /**
     * Gets values to be inserted into the database in the form
     * of an SQL value string, or updates values that were already
     * in the database.
     *
     * Returns values to be inserted and whether or not there's new
     * data or data to be updated.
     *
     * @param $profUsername
     * @param $valuesRows
     *
     * @return array
     */
    public function writeToDB($profUsername, $valuesRows): array
    {
        $courseId = $this->courseId;
        $profId = User::getUserByUsername($profUsername)->getId();
        $courseUserProf = Core::$systemDB->select("course_user", ["id" => $profId, "course" => $courseId]);
        $values = "";
        $insertedOrUpdated = false;

        if ($courseUserProf) {
            for ($row = 1; $row < sizeof($valuesRows); $row++) {
                if (!$this->rowIsValid($valuesRows[$row], [self::COL_STUDENT_NUMBER, self::COL_ACTION])) continue;
                $user = User::getUserByStudentNumber($valuesRows[$row][self::COL_STUDENT_NUMBER]);
                $action = $valuesRows[$row][self::COL_ACTION];

                if ($user) {
                    $userId = $user->getId();
                    $courseUser = Core::$systemDB->select("course_user", ["id" => $userId, "course" => $courseId]);

                    if ($courseUser) {
                        switch ($action) {
                            case "initial bonus":
                            case "presentation grade":
                                if (!$this->rowIsValid($valuesRows[$row], [self::COL_XP])) break;
                                $xp = $valuesRows[$row][self::COL_XP];
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'','" . $action . "', '" . $xp . "','" . $profId . "'),";

                                } else if ($result["rating"] != $xp) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update("participation",
                                        array("description" => "", "rating" => $xp, "evaluator" => $profId),
                                        array("user" => $userId, "course" => $courseId, "type" => $action)
                                    );
                                }
                                break;

                            case "attended lecture":
                            case "attended lecture (late)":
                            case "attended lab":
                            case "replied to questionnaires":
                                if (!$this->rowIsValid($valuesRows[$row], [self::COL_INFO])) break;
                                $info  = $valuesRows[$row][self::COL_INFO];
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action, "description" => $info]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'" . $info . "','" . $action . "', '0','" . $profId . "'),";

                                } else if ($result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update("participation",
                                        array("description" => $info, "rating" => 0, "evaluator" => $profId),
                                        array("user" => $userId, "course" => $this->courseId, "type" => $action)
                                    );
                                }
                                break;

                            case "presentation king":
                            case "lab king":
                            case "quiz king":
                            case "course emperor":
                            case "suggested presentation subject":
                            case "participated in focus groups":
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'','" . $action . "', '0','" . $profId . "'),";
                                }
                                break;

                            case "quiz grade":
                            case "lab grade":
                                if (!$this->rowIsValid($valuesRows[$row], [self::COL_XP, self::COL_INFO])) break;
                                $info  = $valuesRows[$row][self::COL_INFO];
                                $xp = $valuesRows[$row][self::COL_XP];
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action, "description"=> $info]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'" . $info . "','" . $action . "', '" . $xp . "','" . $profId . "'),";

                                } else if ($result["rating"] != $xp || $result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update("participation",
                                        array("rating" =>  $xp, "evaluator" => $profId),
                                        array("user" => $userId, "course" => $courseId, "description" => $info, "type" =>  $action)
                                    ); // FIXME: if description has changed it won't update
                                }
                                break;

                            case "popular choice award (presentation)":
                            case "golden star award":
                                if (!$this->rowIsValid($valuesRows[$row], [self::COL_INFO])) break;
                                $info  = $valuesRows[$row][self::COL_INFO];
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'" . $info . "','" . $action . "', '0','" . $profId . "'),";

                                } else if ($result["description"] != $info) {
                                    $insertedOrUpdated = true;
                                    Core::$systemDB->update("participation",
                                        array("description" => $info, "evaluator" => $profId),
                                        array("user" => $userId, "course" => $courseId, "type" =>  $action)
                                    );
                                }
                                break;

                            case "hall of fame":
                                if (!$this->rowIsValid($valuesRows[$row], [self::COL_INFO])) break;
                                $info  = $valuesRows[$row][self::COL_INFO];
                                $result = Core::$systemDB->select("participation", ["user" => $userId, "course" => $courseId, "type" => $action, "description"=> $info]);
                                if (!$result) {
                                    $insertedOrUpdated = true;
                                    $values .= "(" . $userId . "," . $courseId . ",'" . $info . "','" . $action . "', '0','" . $profId . "'),";
                                }
                                break;

                            default:
                                break;
                        }
                    }
                }
            }
        }

        return array($values, $insertedOrUpdated);
    }

    private function rowIsValid(array $row, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!array_key_exists($column, $row) || $row[$column] == "") return false;

            $value = $row[$column];
            if ($column == self::COL_STUDENT_NUMBER && !ctype_digit($value)) return false;
            if ($column == self::COL_STUDENT_CAMPUS && ($value != "A" || $value != "T")) return false;
            if ($column == self::COL_XP && !ctype_digit($value)) return false;
            if ($column == self::COL_INFO && !$this->columnInfoIsValid($row[self::COL_ACTION], $value)) return false;
        }
        return true;
    }

    private function columnInfoIsValid(string $action, $info): bool
    {
        switch ($action) {
            case "attended lab":
            case "attended lecture":
            case "attended lecture (late)":
            case "golden star award":
            case "great video":
            case "guild master":
            case "guild warrior":
            case "lab grade":
            case "popular choice award (presentation)":
            case "quiz grade":
            case "replied to questionnaires":
                return ctype_digit($info);

            default:
                return true;
        }
    }
}
