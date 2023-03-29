<?php
namespace GameCourse\Module\GoogleSheets;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GoogleHandler;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the Google Sheets module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class GoogleSheets extends Module
{
    const TABLE_GOOGLESHEETS_CONFIG = "googlesheets_config";
    const TABLE_GOOGLESHEETS_STATUS = "googlesheets_status";

    const LOGS_FOLDER = "googlesheets";

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

        // Setup logging
        $logsFile = self::getLogsFile($this->getCourse()->getId(), false);
        Utils::initLogging($logsFile);

        $this->initEvents();
    }

    public function initEvents()
    {
        Event::listen(EventType::COURSE_DISABLED, function (int $courseId) {
            if ($courseId == $this->course->getId())
                $this->setAutoImporting(false);
        }, self::ID);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new GoogleSheets($copyTo);
        $copiedModule->saveSchedule($this->getSchedule());
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        // Disable auto importing
        $this->setAutoImporting(false);

        // Remove logging info
        $logsFile = self::getLogsFile($this->getCourse()->getId(), false);
        Utils::removeLogging($logsFile);

        $this->cleanDatabase();
        $this->removeEvents();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getGeneralInputs(): array
    {
        return [
            [
                "name" => "Schedule",
                "description" => "Define how frequently data should be imported from " . self::NAME . ".",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/2",
                                "type" => InputType::SCHEDULE,
                                "id" => "schedule",
                                "value" => $this->getSchedule(),
                                "options" => [
                                    "required" => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGeneralInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            if ($input["id"] == "schedule") $this->saveSchedule($input["value"]);
        }
    }

    public function getPersonalizedConfig(): ?array
    {
        return ["position" => "before"];
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getGoogleSheetsConfig(): array
    {
        $config = Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], "spreadsheetId, sheetsInfo");

        // Parse sheets and owners names
        $sheetNames = [];
        $ownerNames = [];
        if ($config["sheetsInfo"]) {
            foreach(explode(";", $config["sheetsInfo"]) as $name) {
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
        $this->checkConnection($spreadsheetId, $sheetNames);

        // Parse sheets and owners names
        $sheetsInfo = "";
        foreach ($sheetNames as $i => $sheetName) {
            if (!empty($sheetName)) {
                $owner = $ownerNames[$i];
                $sheetsInfo .= "$sheetName,$owner";
                if ($i != sizeof($sheetNames) - 1) $sheetsInfo .= ";";
            }
        }

        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
            "spreadsheetId" => $spreadsheetId,
            "sheetsInfo" => $sheetsInfo
        ], ["course" => $this->getCourse()->getId()]);
    }


    public function getSchedule(): string
    {
        return Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->getCourse()->getId()], "frequency");
    }

    /**
     * @throws Exception
     */
    public function saveSchedule(string $expression)
    {
        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, ["frequency" => $expression,], ["course" => $this->getCourse()->getId()]);
        $this->setAutoImporting($this->isAutoImporting());
    }


    /*** ------- Credentials -------- ***/

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
    public function saveCredentials(array $credentials): string
    {
       self::validateCredentials($credentials);

       // Save credentials
       $credentialKey = key($credentials);
       Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
           "key_" => $credentialKey,
           "clientId" => $credentials[$credentialKey]["client_id"],
           "projectId" => $credentials[$credentialKey]["project_id"],
           "authUri" => $credentials[$credentialKey]["auth_uri"],
           "tokenUri" => $credentials[$credentialKey]["token_uri"],
           "authProvider" => $credentials[$credentialKey]["auth_provider_x509_cert_url"],
           "clientSecret" => $credentials[$credentialKey]["client_secret"],
           "redirectUris" => implode(";", $credentials[$credentialKey]["redirect_uris"])
       ], ["course" => $this->course->getId()]);

       // Create auth URL
       try {
           $client = GoogleHandler::getGoogleSheetsClient($this->course->getId(), $credentials);
           $authURL = $client->createAuthUrl();

       } catch (\Google\Exception $exception) {
           throw new Exception("Couldn't create auth URL: " . $exception->getMessage());
       }
       return $authURL;
   }

    /**
     * @throws Exception
     */
    private static function validateCredentials($credentials)
    {
        $invalid = false;

        if (!is_array($credentials) || empty($credentials)) {
            $invalid = true;

        } else {
            $keys = ["client_id", "project_id", "auth_uri", "token_uri", "auth_provider_x509_cert_url", "client_secret", "redirect_uris"];
            foreach ($keys as $key) {
                if (!array_key_exists($key, $credentials[key($credentials)]))
                    $invalid = true;
            }
        }

        if ($invalid) throw new Exception("Google credentials format is invalid.");
    }


    /*** ----------- Access Token ---------- ***/

    /**
     * @throws \Google\Exception
     */
    public function getAccessToken(): ?array
    {
        $accessToken = json_decode(Core::database()->select(self::TABLE_GOOGLESHEETS_CONFIG, ["course" => $this->course->getId()], "accessToken"), true);
        if (!$accessToken) return null;

        // Refresh access token if expired
        $credentials = $this->getCredentials();
        $client = GoogleHandler::getGoogleSheetsClient($this->course->getId(), $credentials, $accessToken);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if (!$refreshToken) { // needs to request authorization again
                $accessToken = null;

            } else { // simply refresh
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $accessToken = $client->getAccessToken();
            }
            Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, ["accessToken" => json_encode($accessToken)], ["course" => $this->course->getId()]);
        }
        return $accessToken;
    }

    /**
     * @throws Exception
     */
    public function createAccessToken(string $authCode)
    {
        $credentials = $this->getCredentials();
        $client = GoogleHandler::getGoogleSheetsClient($this->course->getId(), $credentials);
        $accessToken = GoogleHandler::createAccessToken($client, $authCode);

        if (!$accessToken) throw new Exception("Couldn't create access token.");
        Core::database()->update(self::TABLE_GOOGLESHEETS_CONFIG, [
            "accessToken" => json_encode($accessToken)
        ], ["course" => $this->course->getId()]);
    }


    /*** ---------- Status ---------- ***/

    public function isAutoImporting(): bool
    {
        return boolval(Core::database()->select(self::TABLE_GOOGLESHEETS_STATUS, ["course" => $this->getCourse()->getId()], "isEnabled"));
    }

    /**
     * @throws Exception
     */
    public function setAutoImporting(bool $enable)
    {
        $courseId = $this->getCourse()->getId();
        $script = MODULES_FOLDER . "/" . self::ID . "/scripts/ImportData.php";

        if ($enable) { // enable googlesheets
            $expression = $this->getSchedule();
            new CronJob($script, $expression, $courseId);

        } else { // disable googlesheets
            CronJob::removeCronJob($script, $courseId);
        }
        Core::database()->update(self::TABLE_GOOGLESHEETS_STATUS, ["isEnabled" => +$enable], ["course" => $courseId]);
    }


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
        Core::database()->update(self::TABLE_GOOGLESHEETS_STATUS, ["isRunning" => +$status], ["course" => $this->getCourse()->getId()]);
    }


    /*** --------- Logging ---------- ***/

    /**
     * Gets GoogleSheets logs for a given course.
     *
     * @param int $courseId
     * @return string
     */
    public static function getRunningLogs(int $courseId): string
    {
        $logsFile = self::getLogsFile($courseId, false);
        return Utils::getLogs($logsFile);
    }

    /**
     * Creates a new GoogleSheets log on a given course.
     *
     * @param int $courseId
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function log(int $courseId, string $message, string $type)
    {
        $logsFile = self::getLogsFile($courseId, false);
        Utils::addLog($logsFile, $message, $type);
    }

    /**
     * Gets GoogleSheets logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "googlesheets_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }


    /*** ------ Importing Data ------ ***/

    private static $GSService;

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
     * @return void
     * @throws Exception
     */
    private function checkConnection(string $spreadsheetId, array $sheetNames)
    {
        $credentials = $this->getCredentials();
        if (!$credentials) throw new Exception("Connection to Google sheet failed: no credentials found.");

        $accessToken = $this->getAccessToken();
        if (!$accessToken) throw new Exception("Connection to Google sheet failed: no access token found.");

        $client = GoogleHandler::getGoogleSheetsClient($this->course->getId(), $credentials, $accessToken);
        $service = GoogleHandler::getGoogleSheetsService($client);
        foreach ($sheetNames as $sheetName) {
            $service->spreadsheets_values->get($spreadsheetId, $sheetName);
        }
    }

    /**
     * Imports data from a Google sheet into the system.
     * Returns the datetime of the oldest record imported
     * if new data was imported, null otherwise.
     *
     * @return string|null
     * @throws Exception
     */
    public function importData(): ?string
    {
        if ($this->isRunning()) {
            self::log($this->course->getId(), "Already importing data from " . self::NAME . ".", "WARNING");
            return false;
        }

        $this->setStartedRunning(date("Y-m-d H:i:s", time()));
        $this->setIsRunning(true);
        self::log($this->course->getId(), "Importing data from " . self::NAME . "...", "INFO");

        try {
            // NOTE: AutoGame will run for targets with new data after checkpoint
            $AutoGameCheckpoint = null; // Timestamp of oldest record imported

            $credentials = $this->getCredentials();
            $accessToken = $this->getAccessToken();
            $client = GoogleHandler::getGoogleSheetsClient($this->course->getId(), $credentials, $accessToken);
            self::$GSService = GoogleHandler::getGoogleSheetsService($client);

            $config = $this->getGoogleSheetsConfig();

            // Import or update data in each sheet
            $GSData = [];
            foreach ($config["sheetNames"] as $i => $sheetName) {
                $prof = $this->course->getCourseUserByUsername($config["ownerNames"][$i]);
                if ($prof) {
                    $data = $this->getSheetData($config["spreadsheetId"], $sheetName);
                    $GSData[$sheetName] = $data;
                    $checkpoint = $this->saveSheetData($sheetName, $data, $prof->getId());
                    if ($checkpoint) {
                        $AutoGameCheckpoint = min($AutoGameCheckpoint, $checkpoint) ?? $checkpoint;
                        self::log($this->course->getId(), "Imported new data from sheet '" . $sheetName . "'.", "SUCCESS");
                    }
                } else self::log($this->course->getId(), "No teacher with username '" . $config["ownerNames"][$i] . "' enrolled in the course.", "WARNING");
            }

            // Remove deleted data from the database
            $this->removeDeletedData($GSData, $config["sheetNames"], $config["ownerNames"]);

            self::log($this->course->getId(), "Finished importing data from " . self::NAME . "...", "INFO");
            return $AutoGameCheckpoint ? date("Y-m-d H:i:s", $AutoGameCheckpoint) : null;

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
        $rows = self::$GSService->spreadsheets_values->get($spreadsheetId, $sheetName)->getValues();
        return array_splice($rows, 1); // NOTE: 1st row is header
    }

    /**
     * Saves data from Google sheet into the system.
     *
     * @param string $sheetName
     * @param array $data
     * @param int $profId
     * @return int|null
     */
    public function saveSheetData(string $sheetName, array $data, int $profId): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, rating, evaluator) VALUES ";
        $values = [];

        $oldestRecordTimestamp = null; // Timestamp of the oldest record imported

        foreach ($data as $i => $row) {
            if (!self::rowIsValid($row, [self::COL_STUDENT_NUMBER, self::COL_ACTION])) continue;

            $courseUser = $this->course->getCourseUserByStudentNumber($row[self::COL_STUDENT_NUMBER]);
            $action = $row[self::COL_ACTION];

            if ($courseUser) {
                $userId = $courseUser->getId();
                $courseId = $this->getCourse()->getId();

                switch ($action) {
                    case "initial bonus":
                    case "initial tokens":
                    case "presentation grade":
                        if (!self::rowIsValid($row, [self::COL_XP])) {
                            self::log($this->course->getId(), "Row #" . ($i + 2) . " on sheet '" . $sheetName . "' is in an invalid format.", "WARNING");
                            break;
                        }
                        $xp = $row[self::COL_XP];

                        $result = Core::database()->select(AutoGame::TABLE_PARTICIPATION, ["user" => $userId, "course" => $courseId, "type" => $action]);
                        if (!$result) { // new data
                            $params = [
                                $userId,
                                $courseId,
                                "\"" . $this->id . "\"",
                                "\"\"",
                                "\"$action\"",
                                intval(round($xp)),
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if (intval($result["rating"]) != $xp) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "rating" => intval(round($xp)),
                            ], ["user" => $userId, "course" => $courseId, "type" => $action]);
                            $recordTimeStamp = strtotime($result["date"]);
                            $oldestRecordTimestamp = min($oldestRecordTimestamp, $recordTimeStamp) ?? $recordTimeStamp;
                        }
                        break;

                    case "attended lecture":
                    case "attended lecture (late)":
                    case "attended lab":
                    case "guild master":
                    case "guild warrior":
                    case "replied to questionnaires":
                        if (!self::rowIsValid($row, [self::COL_INFO])) {
                            self::log($this->course->getId(), "Row #" . ($i + 2) . " on sheet '" . $sheetName . "' is in an invalid format.", "WARNING");
                            break;
                        }
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
                            $recordTimeStamp = strtotime($result["date"]);
                            $oldestRecordTimestamp = min($oldestRecordTimestamp, $recordTimeStamp) ?? $recordTimeStamp;
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
                        if (!self::rowIsValid($row, [self::COL_XP, self::COL_INFO])) {
                            self::log($this->course->getId(), "Row #" . ($i + 2) . " on sheet '" . $sheetName . "' is in an invalid format.", "WARNING");
                            break;
                        }
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
                                intval(round($xp)),
                                $profId
                            ];
                            $values[] = "(" . implode(", ", $params) . ")";

                        } else if (intval($result["rating"]) != $xp) { // update data
                            Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                                "rating" =>  intval(round($xp))
                            ], ["user" => $userId, "course" => $courseId, "description" => $info, "type" =>  $action]);
                            $recordTimeStamp = strtotime($result["date"]);
                            $oldestRecordTimestamp = min($oldestRecordTimestamp, $recordTimeStamp) ?? $recordTimeStamp;
                        }
                        break;

                    case "popular choice award (presentation)":
                    case "golden star award":
                    case "great video":
                        if (!self::rowIsValid($row, [self::COL_INFO])) {
                            self::log($this->course->getId(), "Row #" . ($i + 2) . " on sheet '" . $sheetName . "' is in an invalid format.", "WARNING");
                            break;
                        }
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
                            $recordTimeStamp = strtotime($result["date"]);
                            $oldestRecordTimestamp = min($oldestRecordTimestamp, $recordTimeStamp) ?? $recordTimeStamp;
                        }
                        break;

                    case "hall of fame":
                        if (!self::rowIsValid($row, [self::COL_INFO])) {
                            self::log($this->course->getId(), "Row #" . ($i + 2) . " on sheet '" . $sheetName . "' is in an invalid format.", "WARNING");
                            break;
                        }
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
            } else self::log($this->course->getId(), "No user with student nr. '" . $row[self::COL_STUDENT_NUMBER] . "' enrolled in the course.", "WARNING");
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
            $recordsTimestamp = time();
            $oldestRecordTimestamp = min($oldestRecordTimestamp, $recordsTimestamp) ?? $recordsTimestamp;
        }
        return $oldestRecordTimestamp;
    }

    /**
     * Removes data from the database that has since been deleted
     * from the GoogleSheet.
     *
     * @param array $sheetData
     * @param array $sheetNames
     * @param array $ownerNames
     * @return void
     */
    private function removeDeletedData(array $sheetData, array $sheetNames, array $ownerNames)
    {
        $courseId = $this->getCourse()->getId();

        // Get all GoogleSheet participations on the database
        $GSParticipations = AutoGame::getParticipations($courseId, null, null, null, null,
            null, null, $this->id);

        // Go over each participation and remove it if not found on the sheet
        $dataRemoved = [];
        foreach ($GSParticipations as $p) {
            $profUsername = $this->course->getCourseUserById($p["evaluator"])->getUsername();
            $studentNumber = $this->course->getCourseUserById($p["user"])->getStudentNumber();

            $sheetName = null;
            foreach ($sheetNames as $i => $sName) {
                if ($ownerNames[$i] == $profUsername) {
                    $sheetName = $sName;
                    break;
                }
            }

            switch ($p["type"]) {
                case "initial bonus":
                case "initial tokens":
                case "presentation grade":
                    if (!self::foundInSheet($sheetData[$sheetName], $studentNumber, $p["type"], $p["rating"])) {
                        AutoGame::removeParticipation($p["id"]);
                        $dataRemoved[$sheetName] = isset($dataRemoved[$sheetName]) ? $dataRemoved[$sheetName] + 1 : 1;
                    }
                    break;

                case "attended lecture":
                case "attended lecture (late)":
                case "attended lab":
                case "guild master":
                case "guild warrior":
                case "replied to questionnaires":
                case "popular choice award (presentation)":
                case "golden star award":
                case "great video":
                    if (!self::foundInSheet($sheetData[$sheetName], $studentNumber, $p["type"], null, $p["description"])) {
                        AutoGame::removeParticipation($p["id"]);
                        $dataRemoved[$sheetName] = isset($dataRemoved[$sheetName]) ? $dataRemoved[$sheetName] + 1 : 1;
                    }
                    break;

                case "presentation king":
                case "lab king":
                case "quiz king":
                case "course emperor":
                case "suggested presentation subject":
                case "participated in focus groups":
                case "hall of fame":
                    if (!self::foundInSheet($sheetData[$sheetName], $studentNumber, $p["type"])) {
                        AutoGame::removeParticipation($p["id"]);
                        $dataRemoved[$sheetName] = isset($dataRemoved[$sheetName]) ? $dataRemoved[$sheetName] + 1 : 1;
                    }
                    break;

                case "quiz grade":
                case "lab grade":
                    if (!self::foundInSheet($sheetData[$sheetName], $studentNumber, $p["type"], $p["rating"], $p["description"])) {
                        AutoGame::removeParticipation($p["id"]);
                        $dataRemoved[$sheetName] = isset($dataRemoved[$sheetName]) ? $dataRemoved[$sheetName] + 1 : 1;
                    }
                    break;

                default:
                    break;
            }
        }

        foreach ($dataRemoved as $sheetName => $nrRemoved) {
            self::log($this->course->getId(), "Removed $nrRemoved line" . ($nrRemoved != 1 ? "s" : "") . " from sheet '" . $sheetName . "'.", "WARNING");
        }
    }

    /**
     * Checks whether a given piece of data exists on a sheet.
     *
     * @param array $sheetData
     * @param int $studentNumber
     * @param string $action
     * @param int|null $xp
     * @param string|null $info
     * @return bool
     */
    private function foundInSheet(array $sheetData, int $studentNumber, string $action, ?int $xp = null, ?string $info = null): bool
    {
        foreach ($sheetData as $row) {
            if (intval($row[self::COL_STUDENT_NUMBER]) == $studentNumber && $row[self::COL_ACTION] == $action &&
                (is_null($xp) || intval($row[self::COL_XP]) == $xp) && (is_null($info) || $row[self::COL_INFO] == $info))
                return true;
        }
        return false;
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
            if (!array_key_exists($column, $row) || strlen(trim($row[$column])) === 0) return false;

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
