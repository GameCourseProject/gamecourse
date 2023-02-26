<?php
namespace GameCourse\Module\Moodle;

use Database\Database;
use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\User\User;
use PDO;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the Moodle module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Moodle extends Module
{
    const TABLE_MOODLE_CONFIG = "moodle_config";
    const TABLE_MOODLE_STATUS = "moodle_status";

    const LOGS_FOLDER = "moodle";

    const DEFAULT_DB_SERVER = "db.rnl.tecnico.ulisboa.pt";
    const DEFAULT_DB_USER = "pcm_moodle";
    const DEFAULT_DB_NAME = "pcm_moodle";
    const DEFAULT_DB_PORT = 3306;
    const DEFAULT_TABLES_PREFIX = "mdl_";
    const DEFAULT_MOODLE_URL = "https://pcm.rnl.tecnico.ulisboa.pt/moodle";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Moodle";  // NOTE: must match the name of the class
    const NAME = "Moodle";
    const DESCRIPTION = "Integrates data coming from Moodle into the system.";
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
        Core::database()->insert(self::TABLE_MOODLE_CONFIG, [
            "course" => $this->getCourse()->getId(),
            "dbServer" => self::DEFAULT_DB_SERVER,
            "dbUser" => self::DEFAULT_DB_USER,
            "dbName" => self::DEFAULT_DB_NAME,
            "dbPort" => self::DEFAULT_DB_PORT,
            "tablesPrefix" => self::DEFAULT_TABLES_PREFIX,
            "moodleURL" => self::DEFAULT_MOODLE_URL
        ]);
        Core::database()->insert(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()]);

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
        $copiedModule = new Moodle($copyTo);
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
        $config = $this->getMoodleConfig();
        return [
            [
                "name" => "General",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/2",
                                "type" => InputType::URL,
                                "id" => "moodleURL",
                                "value" => $config["moodleURL"],
                                "placeholder" => "Moodle URL",
                                "options" => [
                                    "topLabel" => "Moodle URL",
                                    "required" => true,
                                    "maxLength" => 100
                                ],
                                "helper" => "URL of Moodle to get data from"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/2",
                                "type" => InputType::NUMBER,
                                "id" => "moodleCourse",
                                "value" => $config["moodleCourse"],
                                "placeholder" => "Moodle course ID",
                                "options" => [
                                    "topLabel" => "Moodle course ID",
                                    "required" => true,
                                ],
                                "helper" => "Moodle ID of course to get data from"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "name" => "Database",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TEXT,
                                "id" => "dbServer",
                                "value" => $config["dbServer"],
                                "placeholder" => "Database server",
                                "options" => [
                                    "topLabel" => "Server",
                                    "required" => true,
                                    "maxLength" => 100
                                ],
                                "helper" => "Moodle database server"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "dbPort",
                                "value" => $config["dbPort"],
                                "placeholder" => "Database port",
                                "options" => [
                                    "topLabel" => "Port",
                                    "required" => true,
                                ],
                                "helper" => "Moodle database port"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TEXT,
                                "id" => "dbName",
                                "value" => $config["dbName"],
                                "placeholder" => "Database name",
                                "options" => [
                                    "topLabel" => "Name",
                                    "required" => true,
                                    "maxLength" => 50
                                ],
                                "helper" => "Moodle database name"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TEXT,
                                "id" => "dbUser",
                                "value" => $config["dbUser"],
                                "placeholder" => "Database user",
                                "options" => [
                                    "topLabel" => "User",
                                    "required" => true,
                                    "maxLength" => 25
                                ],
                                "helper" => "Moodle database user"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TEXT,
                                "id" => "dbPass",
                                "value" => $config["dbPass"],
                                "placeholder" => "Database password",
                                "options" => [
                                    "topLabel" => "Password",
                                    "required" => true,
                                    "maxLength" => 50
                                ],
                                "helper" => "Moodle database password"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TEXT,
                                "id" => "tablesPrefix",
                                "value" => $config["tablesPrefix"],
                                "placeholder" => "Database tables prefix",
                                "options" => [
                                    "topLabel" => "Tables prefix",
                                    "required" => true,
                                    "maxLength" => 25
                                ],
                                "helper" => "Moodle database tables prefix"
                            ]
                        ]
                    ]
                ]
            ],
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
        $inpts = [];
        foreach ($inputs as $input) {
            $inpts[$input["id"]] = $input["value"];
        }
        $inputs = $inpts;

        if (isset($inputs["moodleURL"]) || isset($inputs["moodleCourse"])) {
            Core::database()->update(self::TABLE_MOODLE_CONFIG, [
                "moodleURL" => $inputs["moodleURL"],
                "moodleCourse" => $inputs["moodleCourse"],
            ], ["course" => $this->getCourse()->getId()]);
        }

        if (isset($inputs["dbServer"]) || isset($inputs["dbUser"]) || isset($inputs["dbPass"]) || isset($inputs["dbName"]) || isset($inputs["dbPort"]) || isset($inputs["tablesPrefix"]))
            $this->saveMoodleConfig($inputs["dbServer"], $inputs["dbUser"], $inputs["dbPass"], $inputs["dbName"], $inputs["dbPort"], $inputs["tablesPrefix"]);

        if ($input["id"] == "schedule") $this->saveSchedule($input["value"]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMoodleConfig(): array
    {
        $config = Core::database()->select(self::TABLE_MOODLE_CONFIG, ["course" => $this->course->getId()]);

        // Parse config
        unset($config["course"]);
        if (!is_null($config["dbPort"])) $config["dbPort"] = intval($config["dbPort"]);
        if (!is_null($config["moodleCourse"])) $config["moodleCourse"] = intval($config["moodleCourse"]);

        return $config;
    }

    /**
     * @throws Exception
     */
    public function saveMoodleConfig(string $dbServer, string $dbUser, ?string $dbPass, string $dbName, int $dbPort, string $tablesPrefix)
    {
        // Check connection to Moodle database
        $this->checkConnection($dbServer, $dbUser, $dbPass, $dbName, $dbPort);

        Core::database()->update(self::TABLE_MOODLE_CONFIG, [
            "dbServer" => $dbServer,
            "dbUser" => $dbUser,
            "dbPass" => $dbPass,
            "dbName" => $dbName,
            "dbPort" => $dbPort,
            "tablesPrefix" => $tablesPrefix,
        ], ["course" => $this->getCourse()->getId()]);
    }


    public function getSchedule(): string
    {
        return Core::database()->select(self::TABLE_MOODLE_CONFIG, ["course" => $this->getCourse()->getId()], "frequency");
    }

    /**
     * @throws Exception
     */
    public function saveSchedule(string $expression)
    {
        Core::database()->update(self::TABLE_MOODLE_CONFIG, ["frequency" => $expression,], ["course" => $this->getCourse()->getId()]);
        $this->setAutoImporting($this->isAutoImporting());
    }


    /*** ---------- Status ---------- ***/

    public function isAutoImporting(): bool
    {
        return boolval(Core::database()->select(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()], "isEnabled"));
    }

    /**
     * @throws Exception
     */
    public function setAutoImporting(bool $enable)
    {
        $courseId = $this->getCourse()->getId();
        $script = MODULES_FOLDER . "/" . self::ID . "/scripts/ImportData.php";

        if ($enable) { // enable moodle
            $expression = $this->getSchedule();
            new CronJob($script, $expression, $courseId);

        } else { // disable moodle
            CronJob::removeCronJob($script, $courseId);
        }
        Core::database()->update(self::TABLE_MOODLE_STATUS, ["isEnabled" => +$enable], ["course" => $courseId]);
    }


    public function getStartedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()], "startedRunning");
    }

    public function setStartedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_MOODLE_STATUS, ["startedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function getFinishedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()], "finishedRunning");
    }

    public function setFinishedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_MOODLE_STATUS, ["finishedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function isRunning(): bool
    {
        return boolval(Core::database()->select(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()], "isRunning"));
    }

    public function setIsRunning(bool $status)
    {
        Core::database()->update(self::TABLE_MOODLE_STATUS, ["isRunning" => +$status], ["course" => $this->getCourse()->getId()]);
    }


    public function getCheckpoint(): ?string
    {
        return Core::database()->select(self::TABLE_MOODLE_STATUS, ["course" => $this->getCourse()->getId()], "checkpoint");
    }

    public function setCheckpoint(string $datetime)
    {
        Core::database()->update(self::TABLE_MOODLE_STATUS, ["checkpoint" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    /*** --------- Logging ---------- ***/

    /**
     * Gets Moodle logs for a given course.
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
     * Creates a new Moodle log on a given course.
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
     * Gets Moodle logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "moodle_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }


    /*** ------ Importing Data ------ ***/

    private static $MoodleDatabase;
    private static $prefix;     // Moodle tables prefix
    private static $courseId;   // Moodle course ID
    private static $checkpoint; // Last run checkpoint

    /**
     * Get an instance of the Moodle database.
     *
     * @return Database
     * @throws Exception
     */
    public function database(): Database
    {
        if (!self::$MoodleDatabase) {
            $config = $this->getMoodleConfig();
            if (!$config["dbServer"] || !$config["dbUser"] || !$config["dbPass"] || !$config["dbName"] || !$config["dbPort"])
                throw new Exception("Can't connect to Moodle database: no connection info found.");
            self::$MoodleDatabase = new Database($config["dbServer"], $config["dbUser"], $config["dbPass"], $config["dbName"], $config["dbPort"]);
            self::$checkpoint = strtotime($this->getCheckpoint());

            if (is_null($config["tablesPrefix"]))
                throw new Exception("There's no Moodle tables prefix currently set.");
            self::$prefix = $config["tablesPrefix"];

            if (is_null($config["moodleCourse"]))
                throw new Exception("There's no Moodle course ID currently set.");
            self::$courseId = $config["moodleCourse"];
        }
        return self::$MoodleDatabase;
    }

    /**
     * Checks connection to Moodle database.
     *
     * @param string $dbServer
     * @param string $dbUser
     * @param string|null $dbPass
     * @param string $dbName
     * @param int $dbPort
     * @return void
     * @throws Exception
     */
    private static function checkConnection(string $dbServer, string $dbUser, ?string $dbPass, string $dbName, int $dbPort)
    {
        if (!$dbPass) throw new Exception("Connection to Moodle failed: no database password set.");
        new Database($dbServer, $dbUser, $dbPass, $dbName, $dbPort);
    }

    /**
     * Imports Moodle data into the system.
     * Returns true if new data was imported, false otherwise.
     *
     * @return bool
     * @throws Exception
     */
    public function importData(): bool
    {
        if ($this->isRunning()) {
            self::log($this->course->getId(), "Already importing data from " . self::NAME . ".", "WARNING");
            return false;
        }

        $this->setStartedRunning(date("Y-m-d H:i:s", time()));
        $this->setIsRunning(true);
        self::log($this->course->getId(), "Importing data from " . self::NAME . "...", "INFO");

        try {
            $timestamps = []; // Timestamps of last record imported on each item

            // Initialize Moodle database
            $this->database();

            $import = ["Logs", "ForumGrades", "Peergrades", "QuizGrades", "AssignmentGrades"];
            foreach ($import as $item) {
                $timestamp = $this->{"import".$item}();
                if ($timestamp) $timestamps[] = $timestamp;
            }

            $newData = !empty($timestamps);
            if ($newData) {
                $this->setCheckpoint(date("Y-m-d H:i:s", max($timestamps)));
                self::log($this->course->getId(), "Imported new data from " . self::NAME . ".", "SUCCESS");
            }

            self::log($this->course->getId(), "Finished importing data from " . self::NAME . "...", "INFO");
            return $newData;

        } finally {
            $this->setIsRunning(false);
            $this->setFinishedRunning(date("Y-m-d H:i:s", time()));
        }
    }


    // Assignment Grades

    /**
     * Imports Moodle assignment grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @return int|null
     * @throws Exception
     */
    public function importAssignmentGrades(): ?int
    {
        $assignmentGrades = $this->getAssignmentGrades();
        return $this->saveAssignmentGrades($assignmentGrades);
    }

    /**
     * Gets new Moodle assignment grades.
     *
     * @return array
     * @throws Exception
     */
    public function getAssignmentGrades(): array
    {
        $fields = "a.id as assignmentId, a.name as assignmentName, u.username, g.grade, ug.username as grader, 
                   s.timemodified as submissionTimestamp, g.timemodified as gradeTimestamp";
        $table = self::$prefix . "assign_grades g JOIN " . self::$prefix . "assign a on g.assignment=a.id JOIN " .
            self::$prefix . "assign_submission s on s.assignment=a.id and s.userid=g.userid JOIN " .
            self::$prefix . "user u on g.userid=u.id JOIN " . self::$prefix . "user ug on g.grader=ug.id JOIN " .
            self::$prefix . "course c on a.course=c.id";
        $where = ["a.course" => self::$courseId];
        $whereCompare = [["g.grade", ">", -1]];
        if (self::$checkpoint) $whereCompare[] = ["g.timemodified", ">", self::$checkpoint];
        $orderBy = "g.timemodified";
        return $this->database()->selectMultiple($table, $where, $fields, $orderBy, [], $whereCompare);
    }

    /**
     * Saves Moodle assignment grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @param array $assignmentGrades
     * @return int|null
     * @throws Exception
     */
    public function saveAssignmentGrades(array $assignmentGrades): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, post, date, rating, evaluator) VALUES ";
        $values = [];
        $lastRecordTimestamp = null;

        foreach ($assignmentGrades as $assignmentGrade) {
            self::parseAssignmentGrade($assignmentGrade);
            $courseUser = $this->course->getCourseUserByUsername($assignmentGrade["username"]);
            if ($courseUser) {
                $grader = $this->course->getCourseUserByUsername($assignmentGrade["grader"]);
                if ($grader) {
                    if (!$this->hasAssignmentGrade($courseUser->getId(), $assignmentGrade["assignmentId"], $grader->getId())) { // new assignment grade
                        $params = [
                            $courseUser->getId(),
                            $this->getCourse()->getId(),
                            "\"" . $this->id . "\"",
                            "\"" . $assignmentGrade["assignmentName"] . "\"",
                            "\"assignment grade\"",
                            "\"mod/assign/view.php?id=" . $assignmentGrade["assignmentId"] . "\"",
                            "\"" . date("Y-m-d H:i:s", $assignmentGrade["submissionTimestamp"]) . "\"",
                            $assignmentGrade["grade"],
                            $grader->getId()
                        ];
                        $values[] = "(" . implode(", ", $params) . ")";

                    } else { // already has assignment grade
                        Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                            "description" => $assignmentGrade["assignmentName"],
                            "date" => date("Y-m-d H:i:s", $assignmentGrade["submissionTimestamp"]),
                            "rating" => $assignmentGrade["grade"],
                            "evaluator" => $grader->getId()
                        ], ["id" => $this->getAssignmentGradeParticipationId($courseUser->getId(), $assignmentGrade["assignmentId"], $grader->getId())]);
                    }
                    $lastRecordTimestamp = max($assignmentGrade["gradeTimestamp"], $lastRecordTimestamp);

                } else self::log($this->course->getId(), "(While importing assignment grades) No user with username '" . $assignmentGrade["grader"] . "' enrolled in the course.", "WARNING");

            } else self::log($this->course->getId(), "(While importing assignment grades) No user with username '" . $assignmentGrade["username"] . "' enrolled in the course.", "WARNING");
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $lastRecordTimestamp;
    }

    /**
     * Gets a given assignment grade participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param int $assignmentId
     * @param int $graderId
     * @return int|null
     */
    private function getAssignmentGradeParticipationId(int $userId, int $assignmentId, int $graderId): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "type" => "assignment grade",
            "evaluator" => $graderId
        ], "id", null, [], [], ["post" => "%assign%id=$assignmentId"]);
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given assignment grade is already in the system.
     *
     * @param int $userId
     * @param int $assignmentId
     * @param int $graderId
     * @return bool
     */
    private function hasAssignmentGrade(int $userId, int $assignmentId, int $graderId): bool
    {
        return !!$this->getAssignmentGradeParticipationId($userId, $assignmentId, $graderId);
    }

    /**
     * Parses an assignment grade coming from Moodle database.
     *
     * @param array $assignmentGrade
     * @return void
     */
    private static function parseAssignmentGrade(array &$assignmentGrade)
    {
        if (isset($assignmentGrade["assignmentId"])) $assignmentGrade["assignmentId"] = intval($assignmentGrade["assignmentId"]);
        if (isset($assignmentGrade["assignmentName"])) $assignmentGrade["assignmentName"] = addslashes($assignmentGrade["assignmentName"]);
        if (isset($assignmentGrade["grade"])) $assignmentGrade["grade"] = intval(round($assignmentGrade["grade"]));
        if (isset($assignmentGrade["timestamp"])) $assignmentGrade["timestamp"] = intval($assignmentGrade["timestamp"]);
    }


    // Forum Grades

    /**
     * Imports Moodle forum grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @return int|null
     * @throws Exception
     */
    public function importForumGrades(): ?int
    {
        $forumGrades = $this->getForumGrades();
        $t1 = $this->saveForumGrades($forumGrades);

        $peergradedForumGrades = $this->getPeergradedForumGrades();
        $t2 = $this->saveForumGrades($peergradedForumGrades, true);

        return max($t1, $t2);
    }

    /**
     * Gets new Moodle forum grades.
     *
     * @return array
     * @throws Exception
     */
    public function getForumGrades(): array
    {
        $fields = "f.name as forumName, fd.id as discussionId, fp.subject, g.itemId as gradeId, u.username, g.rating as grade, 
                   ug.username as grader, fp.modified as submissionTimestamp, g.timemodified as gradeTimestamp";
        $table = self::$prefix . "forum f JOIN " . self::$prefix . "forum_discussions fd on fd.forum=f.id JOIN " .
            self::$prefix . "forum_posts fp on fp.discussion=fd.id JOIN " . self::$prefix . "rating g on g.itemId=fp.id JOIN " .
            self::$prefix . "user u on fp.userid=u.id JOIN " . self::$prefix . "user ug on g.userid=ug.id JOIN " .
            self::$prefix . "course c on f.course=c.id";
        $where = ["f.course" => self::$courseId];
        $whereCompare = [["g.rating", ">", -1]];
        if (self::$checkpoint) $whereCompare[] = ["g.timemodified", ">", self::$checkpoint];
        $orderBy = "g.timemodified";
        return $this->database()->selectMultiple($table, $where, $fields, $orderBy, [], $whereCompare);
    }

    /**
     * Gets new Moodle peergraded forum grades.
     *
     * @return array
     * @throws Exception
     */
    public function getPeergradedForumGrades(): array
    {
        $fields = "f.name as forumName, fd.id as discussionId, fp.subject, g.itemId as gradeId, u.username, g.rating as grade, 
                   ug.username as grader, fp.modified as submissionTimestamp, g.timemodified as gradeTimestamp";
        $table = self::$prefix . "peerforum f JOIN " . self::$prefix . "peerforum_discussions fd on fd.peerforum=f.id JOIN " .
            self::$prefix . "peerforum_posts fp on fp.discussion=fd.id JOIN " . self::$prefix . "rating g on g.itemId=fp.id JOIN " .
            self::$prefix . "user u on fp.userid=u.id JOIN " . self::$prefix . "user ug on g.userid=ug.id JOIN " .
            self::$prefix . "course c on f.course=c.id";
        $where = ["f.course" => self::$courseId];
        $whereCompare = [["g.rating", ">", -1]];
        if (self::$checkpoint) $whereCompare[] = ["g.timemodified", ">", self::$checkpoint];
        $orderBy = "g.timemodified";
        return $this->database()->selectMultiple($table, $where, $fields, $orderBy, [], $whereCompare);
    }

    /**
     * Saves Moodle forum grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @param array $forumGrades
     * @param bool $peerForum
     * @return int|null
     * @throws Exception
     */
    public function saveForumGrades(array $forumGrades, bool $peerForum = false): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, post, date, rating, evaluator) VALUES ";
        $values = [];
        $lastRecordTimestamp = null;

        foreach ($forumGrades as $forumGrade) {
            self::parseForumGrade($forumGrade);
            $courseUser = $this->course->getCourseUserByUsername($forumGrade["username"]);
            if ($courseUser) {
                $grader = $this->course->getCourseUserByUsername($forumGrade["grader"]);
                if ($grader) {
                    if (!$this->hasForumGrade($courseUser->getId(), $forumGrade["discussionId"], $forumGrade["gradeId"], $grader->getId())) { // new grade
                        $params = [
                            $courseUser->getId(),
                            $this->getCourse()->getId(),
                            "\"" . $this->id . "\"",
                            "\"" . $forumGrade["forumName"] . ", Re: " . $forumGrade["subject"] . "\"",
                            "\"graded post\"",
                            "\"mod/" . ($peerForum ? "peerforum" : "forum") . "/discuss.php?d=" . $forumGrade["discussionId"] . "#p" . $forumGrade["gradeId"] . "\"",
                            "\"" . date("Y-m-d H:i:s", $forumGrade["submissionTimestamp"]) . "\"",
                            $forumGrade["grade"],
                            $grader->getId()
                        ];
                        $values[] = "(" . implode(", ", $params) . ")";

                    } else { // already has grade
                        Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                            "description" => $forumGrade["forumName"] . ", Re: " . $forumGrade["subject"],
                            "date" => date("Y-m-d H:i:s", $forumGrade["submissionTimestamp"]),
                            "rating" => $forumGrade["grade"],
                            "evaluator" => $grader->getId()
                        ], ["id" => $this->getForumGradeParticipationId($courseUser->getId(), $forumGrade["discussionId"], $forumGrade["gradeId"], $grader->getId())]);
                    }
                    $lastRecordTimestamp = max($forumGrade["gradeTimestamp"], $lastRecordTimestamp);

                } else self::log($this->course->getId(), "(While importing forum grades) No user with username '" . $forumGrade["grader"] . "' enrolled in the course.", "WARNING");

            } else self::log($this->course->getId(), "(While importing forum grades) No user with username '" . $forumGrade["username"] . "' enrolled in the course.", "WARNING");
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $lastRecordTimestamp;
    }

    /**
     * Gets a given forum grade participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param int $discussionId
     * @param int $postId
     * @param int $graderId
     * @return int|null
     */
    private function getForumGradeParticipationId(int $userId, int $discussionId, int $postId, int $graderId): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "type" => "graded post",
            "evaluator" => $graderId,
        ], "id", null, [], [], ["post" => "%forum%d=$discussionId#p$postId"]);
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given forum grade is already in the system.
     *
     * @param int $userId
     * @param int $discussionId
     * @param int $postId
     * @param int $graderId
     * @return bool
     */
    private function hasForumGrade(int $userId, int $discussionId, int $postId, int $graderId): bool
    {
        return !!$this->getForumGradeParticipationId($userId, $discussionId, $postId, $graderId);
    }

    /**
     * Parses a forum grade coming from Moodle database.
     *
     * @param array $forumGrade
     * @return void
     */
    private static function parseForumGrade(array &$forumGrade)
    {
        if (isset($forumGrade["forumName"])) $forumGrade["forumName"] = addslashes($forumGrade["forumName"]);
        if (isset($forumGrade["discussionId"])) $forumGrade["discussionId"] = intval($forumGrade["discussionId"]);
        if (isset($forumGrade["subject"])) $forumGrade["subject"] = addslashes($forumGrade["subject"]);
        if (isset($forumGrade["gradeId"])) $forumGrade["gradeId"] = intval($forumGrade["gradeId"]);
        if (isset($forumGrade["grade"])) $forumGrade["grade"] = intval(round($forumGrade["grade"]));
        if (isset($forumGrade["timestamp"])) $forumGrade["timestamp"] = intval($forumGrade["timestamp"]);
    }


    // Logs

    /**
     * Imports Moodle logs into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @return int|null
     * @throws Exception
     */
    public function importLogs(): ?int
    {
        $logs = $this->getLogs();
        return $this->saveLogs($logs);
    }

    /**
     * Gets new Moodle logs.
     *
     * @return array
     * @throws Exception
     */
    public function getLogs(): array
    {
        // Query fields
        $query = "SELECT ";
        $query .= "l.id, l.timecreated, u.username, l.action, l.target, l.other, l.component, l.contextinstanceid as cmid, l.objectid, l.objecttable";

        // Tables to consult + joins
        $tables = " FROM ";
        $tables .= self::$prefix . "user u JOIN " . self::$prefix . "logstore_standard_log l on l.userid=u.id JOIN " .
            self::$prefix . "course c on l.courseid=c.id";

        // Where clause fields
        $where = " WHERE (";
        $where .= "(component = 'mod_questionnaire' and (action = 'submitted' or action = 'viewed' or action = 'resumed'))"; // submitted, viewed or resumed questionaire
        $where .= " or (component = 'mod_assign' and (action = 'submitted' or action = 'updated' or action = 'viewed'))"; // assign
        $where .= " or ((component = 'mod_forum' or component = 'mod_peerforum') and (action = 'searched' or target = 'subscribers' or target = 'user_report' or target = 'course_module' or target = 'course_module_instance_list') )"; // resource view
        $where .= " or component = 'mod_resource'"; // resource view
        $where .= " or component = 'mod_quiz'"; // quiz
        $where .= " or component = 'mod_chat'"; // chat
        $where .= " or component = 'mod_url'"; // url
        $where .= " or component = 'mod_page'"; // page
        $where .= " or target = 'role'"; // role
        $where .= " or (target = 'recent_activity' and action = 'viewed')"; // view recent activity
        $where .= " or (target = 'course' and action = 'viewed')"; // course view
        $where .= " or target = 'user_enrolment'"; // enrolment
        $where .= " or (action = 'viewed' and (target = 'user_list' or target = 'user_profile'))"; // user view and user view all
        $where .= " or (objecttable = 'forum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted'))"; // forum discussion created, viewed, or deleted
        $where .= " or (objecttable = 'tag_instance' and (action = 'added' or action = 'removed'))"; // tag added or removed
        $where .= " or (objecttable = 'forum_posts' and (action = 'uploaded' or action = 'updated' or action = 'deleted' or action = 'created')) "; // forum created, deleted, uploaded or updated
        $where .= " or (objecttable = 'forum_subscriptions' and (action = 'created' or action = 'deleted')) "; // forum subscribed or unsubscribed
        $where .= " or (objecttable = 'peerforum_discussions' and (action = 'created' or action = 'viewed' or action = 'deleted'))"; // peerforum created, viewed, or deleted
        $where .= " or (objecttable = 'peerforum_subscriptions' and (action = 'created' or action = 'deleted')) "; // peerforum subscribed or unsubscribed
        $where .= " or (objecttable = 'peerforum_posts' and (action = 'uploaded' or action = 'deleted' or action = 'created')))"; // peerforum

        // Others
        $where .= " AND l.courseid=" . self::$courseId;
        if (self::$checkpoint) $where .= " AND l.timecreated > '" . self::$checkpoint . "'";

        // Order by
        $orderBy = " ORDER BY l.timecreated;";

        $sql = $query . $tables . $where . $orderBy;
        return $this->database()->executeQuery($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Saves Moodle logs into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @param array $logs
     * @return int|null
     * @throws Exception
     */
    public function saveLogs(array $logs): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, post, date) VALUES ";
        $values = [];
        $lastRecordTimestamp = null;

        foreach ($logs as $log) {
            $this->parseLog($log);
            if ($log["description"] && $log["action"] != "updated") {
                $courseUser = $this->course->getCourseUserByUsername($log["username"]);
                if ($courseUser) {
                    if (!$this->hasLog($courseUser->getId(), $log["action"], $log["description"], $log["url"])) { // new log
                        $params = [
                            $courseUser->getId(),
                            $this->getCourse()->getId(),
                            "\"" . $this->id . "\"",
                            "\"" . $log["description"] . "\"",
                            "\"" . $log["action"] . "\"",
                            "\"" . $log["url"] . "\"",
                            "\"" . date("Y-m-d H:i:s", $log['timestamp']) . "\""
                        ];
                        $values[] = "(" . implode(", ", $params) . ")";
                    }
                    $lastRecordTimestamp = max($log["timestamp"], $lastRecordTimestamp);

                } else if ($log["username"] !== "admin") {
                    // Ignore admins that are not enrolled in course
                    $user = User::getUserByUsername($log["username"]);
                    if ($user && !$user->isAdmin())
                        self::log($this->course->getId(), "(While importing logs) No user with username '" . $log["username"] . "' enrolled in the course.", "WARNING");
                    else if (!$user)
                        self::log($this->course->getId(), "(While importing logs) No user with username '" . $log["username"] . "' on GameCourse.", "WARNING");
                }
            }
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $lastRecordTimestamp;
    }

    /**
     * Gets a given log participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param string $type
     * @param string|null $description
     * @param string|null $post
     * @return int|null
     */
    private function getLogParticipationId(int $userId, string $type, ?string $description, ?string $post): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "description" => $description,
            "type" => $type,
            "post" => $post
        ], "id");
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given log is already in the system.
     *
     * @param int $userId
     * @param string $type
     * @param string|null $description
     * @param string|null $post
     * @return bool
     */
    private function hasLog(int $userId, string $type, ?string $description, ?string $post): bool
    {
        return !!$this->getLogParticipationId($userId, $type, $description, $post);
    }

    /**
     * Parses a log coming from Moodle database.
     *
     * @param array $log
     * @return void
     * @throws Exception
     */
    private function parseLog(array &$log)
    {
        $action = $log["action"] ?? null;
        $other = json_decode($log["other"]) ?? null;

        $url = null;
        $description = null;

        // Parse component
        switch ($log["component"]) {
            case "mod_quiz":
                $url = 'mod/quiz/view.php?id=' . $log['cmid'];
                $action = "quiz " . $log['action'];

                if ($log["target"] == "report") {
                    $action = "quiz report";
                    $info = $other->quizid;

                } else if ($log["target"] == "attempt_preview") {
                    $action = "quiz preview";
                    $info = $other->quizid;

                } else if ($log["target"] == "attempt_summary") {
                    $action = "quiz view summary";
                    $info = $other->quizid;
                } else if ($log["target"] == "edit_page" || ($log["target"] == "attempt" && $log["action"] != "started")) {
                    $info = $other->quizid;

                } else { //course_module
                    $info = $log['objectid'];
                }

                $description = $this->database()->select(self::$prefix . "quiz", ["id" => $info], "name");
                break;

            case "mod_chat":
                $url = 'mod/chat/view.php?id=' . $log['cmid'];
                $action = "chat " . $log['action'];
                $info = $log['objectid'];
                break;

            case "mod_questionnaire":
                $url = 'mod/questionnaire/view.php?id=' . $log['cmid'];
                $info = $log["action"] == "submitted" ? $other->questionnaireid : $log['objectid'];
                $action = "questionnaire " . $log['action'];
                $description = $this->database()->select(self::$prefix . "questionnaire", ["id" => $info], "name");
                break;

            case "mod_page":
                $url = 'mod/page/view.php?id=' . $log['cmid'];
                $action = "page " . $log['action'];
                $info = $log['objectid'];
                $description = $this->database()->select(self::$prefix . "page", ["id" => $info], "name");
                break;

            case "mod_assign":
                $url = 'mod/assign/view.php?id=' . $log['cmid'];
                $action = "assignment " . $log['action'];

                if ($log["target"] == "course_module") {
                    $description = $this->database()->select(self::$prefix . "assign a JOIN " . self::$prefix .
                        "logstore_standard_log l on a.id=l.objectid", [
                            "component" => "mod_assign",
                            "objectid" => $log["objectid"]
                    ], "name");

                } else if ($log["target"] == "submission_form" || $log["target"] == "grading_table" || $log["target"] == "grading_form"
                    || $log["target"] == "remove_submission_form" || $log["target"] == "submission_confirmation_form") {

                    $description = $this->database()->select(self::$prefix . "assign", ["id" => $other->assignid], "name");
                }
                break;

            case "mod_resource":
                $url = 'mod/resource/view.php?id=' . $log['cmid'];
                $action = "resource " . $log['action'];
                $description = $this->database()->select(self::$prefix . "resource r JOIN " . self::$prefix . "logstore_standard_log l on r.id=l.objectid", ["component" => "mod_resource", "objectid" => $log['objectid']], "name");
                break;

            case "mod_url":
                $url = 'mod/url/view.php?id=' . $log['cmid'];
                $action = "url " . $log['action'];
                $description = $this->database()->select(self::$prefix . "url u JOIN " . self::$prefix . "logstore_standard_log l on u.id=l.objectid", ["component" => "mod_url", "objectid" => $log['objectid']], "name");
                break;

            case "mod_forum":
                if ($log['objecttable'] == "forum_subscriptions") {
                    $url = 'mod/forum/view.php?id=' . $other->forumid;
                    $info = $other->forumid;
                    if ($log['action'] == "created") $action = "subscribe forum";
                    else if ($log['action'] == "deleted") $action = "unsubscribe forum";
                    $description = $this->database()->select(self::$prefix . "forum", ["id" => $info], "name");

                } else if ($log['objecttable'] == 'forum_discussions') {
                    if ($log['action'] == 'created') {
                        $action = "forum add discussion";
                        $url = "mod/forum/discuss.php?d=" . $log['objectid'];
                        $info = $log['objectid'];

                    } else if ($log['action'] == 'viewed') {
                        $action = "forum view discussion";
                        $url = "mod/forum/discuss.php?d=" . $log['cmid'];
                        $info = $log['cmid'];

                    } else if ($log['action'] == 'deleted') {
                        $action = "forum delete discussion";
                        $url = "mod/forum/view.php?id=" . $log['cmid'];
                        $info = $log['cmid'];
                    }

                    $description = $this->database()->select(self::$prefix . "forum_discussions", ["id" => $info], "name");

                } else if ($log['objecttable'] == 'forum') {
                    if ($log['action'] == 'viewed') $action = "forum view forum";

                } else if ($log['objecttable'] == 'forum_posts') {
                    if ($log['action'] == 'created') {
                        $action = "forum add post";
                        $url = "mod/forum/discuss.php?d=" . $other->discussionid . "&parent=" . $log['objectid'];

                    } else if ($log['action'] == 'uploaded') {
                        $action = "forum upload post";
                        $url = "mod/forum/discuss.php?d=" . $other->discussionid . "&parent=" . $log['objectid'];

                    } else if ($log['action'] == 'deleted') {
                        $action = "forum delete post";
                        $url = "mod/forum/discuss.php?d=" . $other->discussionid;

                    } else if ($log['action'] == 'updated') {
                        $action = "forum update post";
                        $url = "mod/forum/discuss.php?d=" . $other->discussionid . "#p" . $log['objectid'] . "&parent=" . $log['objectid'];
                    }

                    $description = $this->database()->select(self::$prefix . "forum_posts", ["id" => $log["objectid"]], "subject");
                }

                if ($log['target'] == "course") {
                    if ($log['action'] = "searched") $action = "forum search";

                } else if ($log['target'] == "subscribers") {
                    if ($log['action'] = "viewed") $action = "forum view subscribers";

                } else if ($log['target'] == "course_module_instance_list") {
                    if ($log['action'] = "viewed") $action = "forum view forums";

                } else if ($log['target'] == "user_report"){
                    if ($log['action'] = "viewed") $action = "forum user report";
                }
                break;

            case "mod_peerforum":
                if ($log['objecttable'] == 'peerforum_posts') {
                    if ($log['action'] == 'created') {
                        $action = "peerforum add post";
                        $url = "mod/peerforum/discuss.php?d=" . $other->discussionid . "&parent=" . $log['objectid'];

                    } else if ($log['action'] == 'uploaded') {
                        $action = "peerforum upload post";
                        $url = "mod/peerforum/discuss.php?d=" . $other->discussionid . "&parent=" . $log['objectid'];

                    } else if ($log['action'] == 'deleted') {
                        $action = "peerforum delete post";
                        $url = "mod/peerforum/discuss.php?d=" . $other->discussionid;

                    } else if ($log['action'] == 'updated') {
                        $action = "peerforum update post";
                        $url = "mod/peerforum/discuss.php?d=" . $other->discussionid . "#p" . $log['objectid'] . "&parent=" . $log['objectid'];
                    }
                    $description = $this->database()->select(self::$prefix . "peerforum_posts", ["id" => $log["objectid"]], "subject");

                } else if ($log['objecttable'] == 'peerforum_discussions') {
                    if ($log['action'] == 'created') {
                        $action = "peerforum add discussion";
                        $url = "mod/peerforum/discuss.php?d=" . $log['objectid'];
                        $info = $log['objectid'];

                    } else if ($log['action'] == 'viewed') {
                        $action = "peerforum view discussion";
                        $url = "discuss.php?d=" . $log['cmid'];
                        $info = $log['cmid'];

                    } else if ($log['action'] == 'deleted') {
                        $action = "peerforum delete discussion";
                        $url = "view.php?id=" . $log['cmid'];
                        $info = $log['cmid'];
                    }
                    $description = $this->database()->select(self::$prefix . "peerforum_discussions", ["id" => $info], "name");

                } else if ($log['objecttable'] == "peerforum_subscriptions") {
                    if ($log['action'] == "created") {
                        $action = "subscribe peerforum";
                        $url = "view.php?id=" . $other->peerforumid;
                        $info = $other->peerforumid;

                    } else if ($log['action'] == "deleted") {
                        $action = "unsubscribe peerforum";
                        $url = "view.php?id=" . $other->peerforumid;
                        $info = $other->peerforumid;
                    }

                    $description = $this->database()->select(self::$prefix . "peerforum", ["id" => $info], "name");

                } else if ($log['objecttable'] == 'peerforum') {
                    if ($log['action'] == 'viewed') $action = "peerforum view peerforum";
                }

                if ($log['target'] == "course") {
                    if ($log['action'] = "searched") $action = "peerforum search";

                } else if ($log['target'] == "subscribers") {
                    if ($log['action'] = "viewed") $action = "peerforum view subscribers";

                } else if ($log['target'] == "course_module_instance_list") {
                    if ($log['action'] = "viewed") $action = "peerforum view peerforums";

                } else if ($log['target'] == "user_report") {
                    if ($log['action'] = "viewed") $action = "peerforum user report";
                }
                break;

            default:
                break;
        }

        // Parse target
        switch ($log["target"]) {
            case "role";
                $url = "admin/roles/assign.php?contextid=" . $log['cmid'] . "&roleid=" . $log['objectid'];
                $action = "role " . $log['action'];
                $description = $this->database()->select(self::$prefix . "role r JOIN " . self::$prefix . "logstore_standard_log l on r.id=l.objectid", ["target" => "role", "l.id" => $log["id"]], "shortname");
                break;

            case "user_list";
                $url = "user/view.php?id=" . $log['objectid'] . "&course=" . self::$courseId;
                $action = "user view all";
                break;

            case "user_profile";
                $url = "user/index.php?id=" . self::$courseId;
                $action = "user view";
                break;

            case "recent_activity";
                $action = "course view recent";
                break;

            case "course";
                $action = "course view";
                break;

            case "tag";
                if ($log['action'] == "added") $action = "tag add";
                if ($log['action'] == "removed") $action = "tag remove";

                $info = $other->tagid;
                $description = $this->database()->select(self::$prefix . "tag t JOIN " . self::$prefix . "logstore_standard_log l on t.id=" . $info, ["target" => "tag", "l.id" => $log["id"]], "name");
                break;

            case "user_enrolment";
                $description = $log["target"];
                $url = "../enrol/users.php?id=" . $log['cmid'];
                if ($log['action'] == 'created') $action = "course enrol user";
                else if ($log['action'] == 'deleted') $action = "course unenrol user";
                break;

            default:
                break;
        }

        $log = [
            "timestamp" => $log['timecreated'],
            "username" => $log["username"],
            "description" => addslashes($description),
            "action" => $action,
            "url" => $url
        ];
    }


    // Peergrades

    /**
     * Imports Moodle peergrades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @return int|null
     * @throws Exception
     */
    public function importPeergrades(): ?int
    {
        $peergrades = $this->getPeergrades();
        return $this->savePeergrades($peergrades);
    }

    /**
     * Gets new Moodle peergrades.
     *
     * @return array
     * @throws Exception
     */
    public function getPeergrades(): array
    {
        $fields = "f.name as forumName, fd.id as discussionId, fp.subject, g.itemId as peergradeId, u.username, g.peergrade as grade, 
                   ug.username as grader, g.timemodified as timestamp";
        $table = self::$prefix . "peerforum f JOIN " . self::$prefix . "peerforum_discussions fd on fd.peerforum=f.id JOIN " .
            self::$prefix . "peerforum_posts fp on fp.discussion=fd.id JOIN " . self::$prefix . "peerforum_peergrade g on g.itemId=fp.id JOIN " .
            self::$prefix . "user u on fp.userid=u.id JOIN " . self::$prefix . "user ug on g.userid=ug.id JOIN " .
            self::$prefix . "course c on f.course=c.id";
        $where = ["f.course" => self::$courseId];
        $whereCompare = [["g.peergrade", ">", -1]];
        if (self::$checkpoint) $whereCompare[] = ["g.timemodified", ">", self::$checkpoint];
        $orderBy = "g.timemodified";
        return $this->database()->selectMultiple($table, $where, $fields, $orderBy, [], $whereCompare);
    }

    /**
     * Saves Moodle peergrades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @param array $peergrades
     * @return int|null
     * @throws Exception
     */
    public function savePeergrades(array $peergrades): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, post, date, rating, evaluator) VALUES ";
        $values = [];
        $lastRecordTimestamp = null;

        foreach ($peergrades as $peergrade) {
            self::parsePeergrade($peergrade);
            $courseUser = $this->course->getCourseUserByUsername($peergrade["username"]);
            if ($courseUser) {
                $grader = $this->course->getCourseUserByUsername($peergrade["grader"]);
                if ($grader) {
                    if (!$this->hasPeergrade($courseUser->getId(), $peergrade["discussionId"], $peergrade["peergradeId"], $grader->getId())) { // new peergrade
                        $params = [
                            $courseUser->getId(),
                            $this->getCourse()->getId(),
                            "\"" . $this->id . "\"",
                            "\"" . $peergrade["forumName"] . ", Re: " . $peergrade["subject"] . "\"",
                            "\"peergraded post\"",
                            "\"mod/peerforum/discuss.php?d=" . $peergrade["discussionId"] . "#p" . $peergrade["peergradeId"] . "\"",
                            "\"" . date("Y-m-d H:i:s", $peergrade["timestamp"]) . "\"",
                            $peergrade["grade"],
                            $grader->getId()
                        ];
                        $values[] = "(" . implode(", ", $params) . ")";

                    } else { // already has peergrade
                        Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                            "description" => $peergrade["forumName"] . ", Re: " . $peergrade["subject"],
                            "date" => date("Y-m-d H:i:s", $peergrade["timestamp"]),
                            "rating" => $peergrade["grade"],
                            "evaluator" => $grader->getId()
                        ], ["id" => $this->getPeergradeParticipationId($courseUser->getId(), $peergrade["discussionId"], $peergrade["peergradeId"], $grader->getId())]);
                    }
                    $lastRecordTimestamp = max($peergrade["timestamp"], $lastRecordTimestamp);

                } else self::log($this->course->getId(), "(While importing peergrades) No user with username '" . $peergrade["grader"] . "' enrolled in the course.", "WARNING");

            } else self::log($this->course->getId(), "(While importing peergrades) No user with username '" . $peergrade["username"] . "' enrolled in the course.", "WARNING");
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $lastRecordTimestamp;
    }

    /**
     * Gets a given peergrade participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param int $discussionId
     * @param int $peergradeId
     * @param int $graderId
     * @return int|null
     */
    private function getPeergradeParticipationId(int $userId, int $discussionId, int $peergradeId, int $graderId): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "type" => "peergraded post",
            "evaluator" => $graderId,
        ], "id", null, [], [], ["post" => "%peerforum%d=$discussionId#p$peergradeId"]);
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given peergrade is already in the system.
     *
     * @param int $userId
     * @param int $discussionId
     * @param int $peergradeId
     * @param int $graderId
     * @return bool
     */
    private function hasPeergrade(int $userId, int $discussionId, int $peergradeId, int $graderId): bool
    {
        return !!$this->getPeergradeParticipationId($userId, $discussionId, $peergradeId, $graderId);
    }

    /**
     * Parses a peergrade coming from Moodle database.
     *
     * @param array $peergrade
     * @return void
     */
    private static function parsePeergrade(array &$peergrade)
    {
        if (isset($peergrade["forumName"])) $peergrade["forumName"] = addslashes($peergrade["forumName"]);
        if (isset($peergrade["discussionId"])) $peergrade["discussionId"] = intval($peergrade["discussionId"]);
        if (isset($peergrade["subject"])) $peergrade["subject"] = addslashes($peergrade["subject"]);
        if (isset($peergrade["peergradeId"])) $peergrade["peergradeId"] = intval($peergrade["peergradeId"]);
        if (isset($peergrade["grade"])) $peergrade["grade"] = intval(round($peergrade["grade"]));
        if (isset($peergrade["timestamp"])) $peergrade["timestamp"] = intval($peergrade["timestamp"]);
    }


    // Quiz Grades

    /**
     * Imports Moodle quiz grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @return int|null
     * @throws Exception
     */
    public function importQuizGrades(): ?int
    {
        $quizGrades = $this->getQuizGrades();
        return $this->saveQuizGrades($quizGrades);
    }

    /**
     * Gets new Moodle quiz grades.
     *
     * @return array
     * @throws Exception
     */
    public function getQuizGrades(): array
    {
        $fields = "q.id as quizzId, q.name as quizName, u.username, g.grade, g.timemodified as timestamp";
        $table = self::$prefix . "quiz_grades g JOIN " . self::$prefix . "quiz q on g.quiz=q.id JOIN " .
            self::$prefix . "user u on g.userid=u.id JOIN " . self::$prefix . "course c on q.course=c.id";
        $where = ["q.course" => self::$courseId];
        $whereCompare = [["g.grade", ">", -1]];
        if (!is_null(self::$checkpoint)) $whereCompare[] = ["g.timemodified", ">", self::$checkpoint];
        $orderBy = "g.timemodified";
        return $this->database()->selectMultiple($table, $where, $fields, $orderBy, [], $whereCompare);
    }

    /**
     * Saves Moodle quiz grades into the system.
     * Returns last record timestamp if new data was imported,
     * null otherwise.
     *
     * @param array $quizGrades
     * @return int|null
     * @throws Exception
     */
    public function saveQuizGrades(array $quizGrades): ?int
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type, post, date, rating) VALUES ";
        $values = [];
        $lastRecordTimestamp = null;

        foreach ($quizGrades as $quizGrade) {
            self::parseQuizGrade($quizGrade);
            $courseUser = $this->course->getCourseUserByUsername($quizGrade["username"]);
            if ($courseUser) {
                if (!$this->hasQuizGrade($courseUser->getId(), $quizGrade["quizzId"])) { // new quiz grade
                    $params = [
                        $courseUser->getId(),
                        $this->getCourse()->getId(),
                        "\"" . $this->id . "\"",
                        "\"" . $quizGrade["quizName"] . "\"",
                        "\"quiz grade\"",
                        "\"mod/quiz/view.php?id=" . $quizGrade["quizzId"] . "\"",
                        "\"" . date("Y-m-d H:i:s", $quizGrade["timestamp"]) . "\"",
                        $quizGrade["grade"]
                    ];
                    $values[] = "(" . implode(", ", $params) . ")";

                } else { // already has quiz grade
                    Core::database()->update(AutoGame::TABLE_PARTICIPATION, [
                        "description" => $quizGrade["quizName"],
                        "date" => date("Y-m-d H:i:s", $quizGrade["timestamp"]),
                        "rating" => $quizGrade["grade"]
                    ], ["id" => $this->getQuizGradeParticipationId($courseUser->getId(), $quizGrade["quizzId"])]);
                }
                $lastRecordTimestamp = max($quizGrade["timestamp"], $lastRecordTimestamp);

            } else self::log($this->course->getId(), "(While importing quiz grades) No user with username '" . $quizGrade["username"] . "' enrolled in the course.", "WARNING");
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $lastRecordTimestamp;
    }

    /**
     * Gets a given quiz grade participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param int $quizId
     * @return int|null
     */
    private function getQuizGradeParticipationId(int $userId, int $quizId): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "type" => "quiz grade",
        ], "id", null, [], [], ["post" => "%quiz%id=$quizId"]);
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given quiz grade is already in the system.
     *
     * @param int $userId
     * @param int $quizId
     * @return bool
     */
    private function hasQuizGrade(int $userId, int $quizId): bool
    {
        return !!$this->getQuizGradeParticipationId($userId, $quizId);
    }

    /**
     * Parses a quiz grade coming from Moodle database.
     *
     * @param array $quizGrade
     * @return void
     */
    private static function parseQuizGrade(array &$quizGrade)
    {
        if (isset($quizGrade["quizzId"])) $quizGrade["quizzId"] = intval($quizGrade["quizzId"]);
        if (isset($quizGrade["quizName"])) $quizGrade["quizName"] = addslashes($quizGrade["quizName"]);
        if (isset($quizGrade["grade"])) $quizGrade["grade"] = intval(round($quizGrade["grade"]));
        if (isset($quizGrade["timestamp"])) $quizGrade["timestamp"] = intval($quizGrade["timestamp"]);
    }
}
