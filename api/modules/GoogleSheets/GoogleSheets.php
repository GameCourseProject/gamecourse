<?php
namespace GameCourse\Module\GoogleSheets;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GoogleHandler;
use Throwable;

/**
 * This is the Google Sheets module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class GoogleSheets extends Module
{
    const TABLE_GOOGLESHEETS_CONFIG = "googlesheets_config";
    const TABLE_GOOGLESHEETS_STATUS = "googlesheets_status";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "GoogleSheets";  // NOTE: must match the name of the class
    const NAME = "Google Sheets";
    const DESCRIPTION = "Integrates data coming from Google Sheets into the system.";
    const TYPE = ModuleType::DATA_SOURCE;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();

        // Init config & status
        Core::database()->insert(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->getCourse()->getId()]);
        Core::database()->insert(self::TABLE_GOOGLESHEETS_STATUS, ["course" => $this->getCourse()->getId()]);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        // Copy config
        $config = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->getCourse()->getId()]);
        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
            "key_" => $config["key_"],
            "clientId" => $config["clientId"],
            "projectId" => $config["projectId"],
            "authUri" => $config["authUri"],
            "tokenUri" => $config["tokenUri"],
            "authProvider" => $config["authProvider"],
            "clientSecret" => $config["clientSecret"],
            "redirectUris" => $config["redirectUris"],
            "authUrl" => $config["authUrl"],
            "accessToken" => $config["accessToken"],
            "expiresIn" => $config["expiresIn"],
            "scope" => $config["scope"],
            "tokenType" => $config["tokenType"],
            "created" => $config["created"],
            "refreshToken" => $config["refreshToken"],
            "spreadsheetId" => $config["spreadsheetId"],
            "sheetName" => $config["sheetName"]
        ], ["course" => $copyTo->getId()]);
    }

    public function disable()
    {
        $this->cleanDatabase();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getLists(): array
    {
        return [
            [
                "name" => "Status",
                "itemName" => "status",
                "headers" => [
                    ["label" => "Started importing data", "align" => "middle"],
                    ["label" => "Finished importing data", "align" => "middle"],
                    ["label" => "Now", "align" => "middle"]
                ],
                "data" => [
                    [
                        ["type" => DataType::DATETIME, "content" => ["datetime" => $this->getStartedRunning()]],
                        ["type" => DataType::DATETIME, "content" => ["datetime" => $this->getFinishedRunning()]],
                        ["type" => DataType::COLOR, "content" => ["color" => $this->isRunning() ? "#36D399" : "#EF6060", "colorLabel" => $this->isRunning() ? "Importing" : "Not importing"]]
                    ]
                ],
                "options" => [
                    "searching" => false,
                    "lengthChange" => false,
                    "paging" => false,
                    "info" => false,
                    "hasColumnFiltering" => false,
                    "hasFooters" => false,
                    "columnDefs" => [
                        ["orderable" => false, "targets" => [0, 1, 2]]
                    ]
                ]
            ]
        ];
    }

    public function getPersonalizedConfig(): ?string
    {
        return $this->id;
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getGoogleSheetsConfig(): array
    {
        $config = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], "spreadsheetId, sheetName");

        // Parse sheets and owners names
        $sheetNames = [];
        $ownerNames = [];
        if ($config["sheetName"]) {
            foreach(explode(";", $config["sheetName"]) as $name) {
                $processedName = explode(",", trim($name));
                $sheetNames[] = trim($processedName[0]);
                if (count($processedName) > 1) $ownerNames[] = trim($processedName[1]);
            }
        }

        return [
            "spreadsheetId" => $config["spreadsheetId"],
            "sheetNames" => $sheetNames,
            "ownerNames" => $ownerNames
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGoogleSheetsConfig(string $spreadsheetId, array $sheetNames, array $ownerNames)
    {
        // Check connection to Google sheet
        if (!self::canConnect($spreadsheetId, $sheetNames))
            throw new Exception("Connection to Google sheet failed.");

        // Parse sheets and owners names
        $sheetInfo = "";
        foreach ($sheetNames as $i => $sheetName) {
            if (!empty($sheetName)) {
                $owner = $ownerNames[$i];
                $sheetInfo .= "$sheetName,$owner";
                if ($i != sizeof($sheetNames) - 1) $sheetInfo .= ";";
            }
        }

        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
            "spreadsheetId" => $spreadsheetId,
            "sheetName" => $sheetInfo
        ], ["course" => $this->getCourse()->getId()]);

        $this->saveToken();
    }


    /*** ------- Credentials -------- ***/

    public function getAuthURL(): ?string
    {
        return Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], "authUrl");
    }

    public function getCredentials(): ?array
    {
        $fields = "key_, clientId, projectId, authUri, tokenUri, authProvider, clientSecret, redirectUris";
        $credentials = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], $fields);

        if (!$credentials["key_"]) return null;
        return [
            $credentials["key_"] => [
                "client_id" => $credentials["clientId"],
                "project_id" => $credentials["projectId"],
                "auth_uri" => $credentials["authUri"],
                "token_uri" => $credentials["tokenUri"],
                "auth_provider_x509_cert_url" => $credentials["authProvider"],
                "client_secret" => $credentials["clientSecret"],
                "redirect_uris" => explode(";", $credentials["redirectUris"])
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveCredentials(array $credentials)
   {
       self::validateCredentials($credentials);

       $credentialKey = key($credentials);
       $credentials = $credentials[$credentialKey];

       // Save credentials
       Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
           "key_" => $credentialKey,
           "clientId" => $credentials["client_id"],
           "projectId" => $credentials["project_id"],
           "authUri" => $credentials["auth_uri"],
           "tokenUri" => $credentials["token_uri"],
           "authProvider" => $credentials["auth_provider_x509_cert_url"],
           "clientSecret" => $credentials["client_secret"],
           "redirectUris" => implode(";", $credentials["redirect_uris"])
       ], ["course" => $this->course->getId()]);

       // Create auth URL
       $client = GoogleHandler::setCredentials($this->getCredentials(), $this->course->getId());
       Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
           "authUrl" => $client->createAuthUrl()
       ], ["course" => $this->course->getId()]);
   }

    /**
     * @throws Exception
     */
    private static function validateCredentials($credentials)
    {
        $invalid = false;

        if (!is_array($credentials) || empty($credentials))
            $invalid = true;

        $keys = ["client_id", "project_id", "auth_uri", "token_uri", "auth_provider_x509_cert_url", "client_secret", "redirect_uris"];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $credentials[key($credentials)]))
                $invalid = true;
        }

        if ($invalid)
            throw new Exception("Google credentials format is invalid.");
    }


    /*** ----------- Token ---------- ***/

    public function getToken(): ?array
    {
        $accessToken = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], "accessToken");
        if (!$accessToken) return null;

        $config = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()]);
        return [
            "access_token" => $config["accessToken"],
            "expires_in" => $config["expiresIn"],
            "scope" => $config["scope"],
            "token_type" => $config["tokenType"],
            "created" => $config["created"],
            "refresh_token" => $config["refreshToken"]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveToken(?string $authCode = null)
    {
        $responde = $this->handleToken($authCode)["access_token"];
        if (!$responde) return;

        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
            "accessToken" => $responde["access_token"],
            "expiresIn" => $responde["expires_in"],
            "scope" => $responde["scope"],
            "tokenType" => $responde["token_type"],
            "created" => $responde["created"],
            "refreshToken" => $responde["refresh_token"]
        ], ["course" => $this->course->getId()]);
    }

    /**
     * @throws Exception
     */
    private function handleToken(?string $authCode = null): array
    {
        $credentials = $this->getCredentials();
        $token = json_encode($this->getToken());
        return GoogleHandler::checkToken($credentials, $token, $authCode, $this->course->getId());
    }


    /*** ---------- Status ---------- ***/

    public function getStartedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_GOOGLESHEETS_STATUS, ["course" => $this->getCourse()->getId()], "startedRunning");
    }

    public function setStartedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_GOOGLESHEETS_STATUS, ["startedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function getFinishedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_GOOGLESHEETS_STATUS, ["course" => $this->getCourse()->getId()], "finishedRunning");
    }

    public function setFinishedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_GOOGLESHEETS_STATUS, ["finishedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function isRunning(): bool
    {
        return boolval(Core::database()->select(self::TABLE_GOOGLESHEETS_STATUS, ["course" => $this->getCourse()->getId()], "isRunning"));
    }

    public function setIsRunning(bool $status)
    {
        Core::database()->update(self::TABLE_GOOGLESHEETS_STATUS, ["isRunning" => $status], ["course" => $this->getCourse()->getId()]);
    }


    /*** ------ Importing Data ------ ***/

    private static $GoogleSheetsService;

    const COL_STUDENT_NUMBER = 0;
    const COL_STUDENT_NAME = 1;
    const COL_STUDENT_CAMPUS = 2;
    const COL_ACTION = 3;
    const COL_XP = 4;
    const COL_INFO = 5;
    
    /**
     * Checks connection to Google sheet.
     *
     * @param string $spreadsheetId
     * @param array $sheetNames
     * @return bool
     */
    private function canConnect(string $spreadsheetId, array $sheetNames): bool
    {
        try {
            $credentials = $this->getCredentials();
            if (!$credentials) return false;

            $token = json_encode($this->getToken());
            if (!$token) return false;

            $service = GoogleHandler::getGoogleSheets($credentials, $token, null, $this->course->getId());
            foreach ($sheetNames as $sheetName) {
                $service->spreadsheets_values->get($spreadsheetId, $sheetName);
            }

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Imports data from a Google sheet into the system.
     * Returns true if new data was imported, false otherwise.
     *
     * @return bool
     * @throws Exception
     */
    public function importData(): bool
    {
        if ($this->isRunning())
            throw new Exception("Already importing data from Google sheet.");

        $this->setStartedRunning(date("Y-m-d H:i:s", time()));
        $this->setIsRunning(true);

        try {
            $credentials = $this->getCredentials();
            $token = json_encode($this->getToken());
            self::$GoogleSheetsService = GoogleHandler::getGoogleSheets($credentials, $token, null, $this->course->getId());

            $newData = false;
            $config = $this->getGoogleSheetsConfig();
            foreach ($config["sheetNames"] as $i => $sheetName) {
                $prof = $this->course->getCourseUserByUsername($config["ownerNames"][$i]);
                if ($prof) {
                    $data = $this->getSheetData($config["spreadsheetId"], $sheetName);
                    if ($this->saveSheetData($data, $prof->getId())) $newData = true;
                }
            }
            return $newData;

        } finally {
            $this->setIsRunning(false);
            $this->setFinishedRunning(date("Y-m-d H:i:s", time()));
        }
    }

    /**
     * Gets Google sheet data.
     *
     * @param string $spreadsheetId
     * @param string $sheetName
     * @return array
     */
    public function getSheetData(string $spreadsheetId, string $sheetName): array
    {
        $rows = self::$GoogleSheetsService->spreadsheets_values->get($spreadsheetId, $sheetName)->getValues();
        return array_splice($rows, 1); // NOTE: 1st row is header
    }

    /**
     * Saves data from Google sheet into the system.
     *
     * @param array $data
     * @param int $profId
     * @return bool
     * @throws Exception
     */
    public function saveSheetData(array $data, int $profId): bool
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, rating, evaluator) VALUES ";
        $values = [];
        $newData = false;

        foreach ($data as $row) {
            if (!self::rowIsValid($row, [self::COL_STUDENT_NUMBER, self::COL_ACTION])) continue;

            $courseUser = $this->course->getCourseUserByStudentNumber($row[self::COL_STUDENT_NUMBER]);
            $action = $row[self::COL_ACTION];

            if ($courseUser) {
                $userId = $courseUser->getId();
                $courseId = $this->getCourse()->getId();

                switch ($action) {
                    case "initial bonus":
                    case "presentation grade":
                        if (!self::rowIsValid($row, [self::COL_XP])) break;
                        $xp = $row[self::COL_XP];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"\"",
                                "\"$action\"",
                                $xp,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if (intval($result["rating"]) != $xp) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "rating" => $xp,
                            ], ["user" => $userId, "course" => $courseId, "type" => $action]);
                            $newData = true;
                        }
                        break;

                    case "attended lecture":
                    case "attended lecture (late)":
                    case "attended lab":
                    case "replied to questionnaires":
                        if (!self::rowIsValid($row, [self::COL_INFO])) break;
                        $info  = $row[self::COL_INFO];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action, "description" => $info]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"$info\"",
                                "\"$action\"",
                                0,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if ($result["description"] != $info) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "description" => $info
                            ], ["user" => $userId, "course" => $courseId, "type" => $action]);
                            $newData = true;
                        }
                        break;

                    case "presentation king":
                    case "lab king":
                    case "quiz king":
                    case "course emperor":
                    case "suggested presentation subject":
                    case "participated in focus groups":
                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"\"",
                                "\"$action\"",
                                0,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";
                        }
                        break;

                    case "quiz grade":
                    case "lab grade":
                        if (!self::rowIsValid($row, [self::COL_XP, self::COL_INFO])) break;
                        $info  = $row[self::COL_INFO];
                        $xp = $row[self::COL_XP];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action, "description"=> $info]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"$info\"",
                                "\"$action\"",
                                $xp,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if (intval($result["rating"]) != $xp) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "rating" =>  $xp
                            ], ["user" => $userId, "course" => $courseId, "description" => $info, "type" =>  $action]);
                            $newData = true;
                        }
                        break;

                    case "popular choice award (presentation)":
                    case "golden star award":
                        if (!self::rowIsValid($row, [self::COL_INFO])) break;
                        $info  = $row[self::COL_INFO];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"$info\"",
                                "\"$action\"",
                                0,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if ($result["description"] != $info) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "description" => $info
                            ], ["user" => $userId, "course" => $courseId, "type" =>  $action]);
                            $newData = true;
                        }
                        break;

                    case "hall of fame":
                        if (!self::rowIsValid($row, [self::COL_INFO])) break;
                        $info  = $row[self::COL_INFO];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action, "description"=> $info]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"$info\"",
                                "\"$action\"",
                                0,
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
            $newData = true;
        }
        return $newData;
    }

    /**
     * Checks whether a given row parameters are valid.
     *
     * @param array $row
     * @param array $columns
     * @return bool
     */
    private static function rowIsValid(array $row, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!array_key_exists($column, $row) || empty(trim($row[$column]))) return false;

            $value = $row[$column];
            if ($column == self::COL_STUDENT_NUMBER && !ctype_digit($value)) return false;
            if ($column == self::COL_STUDENT_CAMPUS && ($value != "A" || $value != "T")) return false;
            if ($column == self::COL_XP && !ctype_digit($value)) return false;
            if ($column == self::COL_INFO && !self::columnInfoIsValid($row[self::COL_ACTION], $value)) return false;
        }
        return true;
    }

    /**
     * Checks whether a given column info is valid.
     *
     * @param string $action
     * @param $info
     * @return bool
     */
    private static function columnInfoIsValid(string $action, $info): bool
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
