<?php
namespace GameCourse\Module\ClassCheck;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use Throwable;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the ClassCheck module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class ClassCheck extends Module
{
    const TABLE_CLASSCHECK_CONFIG = "classcheck_config";
    const TABLE_CLASSCHECK_STATUS = "classcheck_status";

    const LOGS_FOLDER = "classcheck";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "ClassCheck";  // NOTE: must match the name of the class
    const NAME = "ClassCheck";
    const DESCRIPTION = "Integrates data coming from ClassCheck into the system.";
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
        Core::database()->insert(self::TABLE_CLASSCHECK_CONFIG, ["course" => $this->getCourse()->getId()]);
        Core::database()->insert(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()]);

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
        $copiedModule = new ClassCheck($copyTo);
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
                                "id" => "tsvCode",
                                "value" => $this->getTSVCode(),
                                "placeholder" => "TSV code",
                                "options" => [
                                    "topLabel" => "TSV code"
                                ],
                                "helper" => "Classcheck TSV code URL"
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
        foreach ($inputs as $input) {
            if ($input["id"] == "tsvCode") $this->saveTSVCode($input["value"]);
            if ($input["id"] == "schedule") $this->saveSchedule($input["value"]);
        }
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getTSVCode(): ?string
    {
        return Core::database()->select(self::TABLE_CLASSCHECK_CONFIG, ["course" => $this->course->getId()], "tsvCode");
    }

    /**
     * @throws Exception
     */
    public function saveTSVCode(?string $tsvCode)
    {
        // Check connection to ClassCheck
        $this->checkConnection($tsvCode);

        Core::database()->update(self::TABLE_CLASSCHECK_CONFIG, [
            "tsvCode" => $tsvCode,
        ], ["course" => $this->getCourse()->getId()]);
    }


    public function getSchedule(): string
    {
        return Core::database()->select(self::TABLE_CLASSCHECK_CONFIG, ["course" => $this->getCourse()->getId()], "frequency");
    }

    /**
     * @throws Exception
     */
    public function saveSchedule(string $expression)
    {
        Core::database()->update(self::TABLE_CLASSCHECK_CONFIG, ["frequency" => $expression], ["course" => $this->getCourse()->getId()]);
        $this->setAutoImporting($this->isAutoImporting());
    }


    /*** ---------- Status ---------- ***/

    public function isAutoImporting(): bool
    {
        return boolval(Core::database()->select(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()], "isEnabled"));
    }

    /**
     * @throws Exception
     */
    public function setAutoImporting(bool $enable)
    {
        $courseId = $this->getCourse()->getId();
        $script = MODULES_FOLDER . "/" . self::ID . "/scripts/ImportData.php";

        if ($enable) { // enable classcheck
            $expression = $this->getSchedule();
            new CronJob($script, $expression, $courseId);

        } else { // disable classcheck
            CronJob::removeCronJob($script, $courseId);
        }
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["isEnabled" => +$enable], ["course" => $courseId]);
    }


    public function getStartedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()], "startedRunning");
    }

    public function setStartedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["startedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function getFinishedRunning(): ?string
    {
        return Core::database()->select(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()], "finishedRunning");
    }

    public function setFinishedRunning(string $datetime)
    {
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["finishedRunning" => $datetime], ["course" => $this->getCourse()->getId()]);
    }


    public function isRunning(): bool
    {
        return boolval(Core::database()->select(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()], "isRunning"));
    }

    public function setIsRunning(bool $status)
    {
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["isRunning" => +$status], ["course" => $this->getCourse()->getId()]);
    }


    /*** --------- Logging ---------- ***/

    /**
     * Gets ClassCheck logs for a given course.
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
     * Creates a new ClassCheck log on a given course.
     *
     * @param int $courseId
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function log(int $courseId, string $message, string $type = "ERROR")
    {
        $logsFile = self::getLogsFile($courseId, false);
        Utils::addLog($logsFile, $message, $type);
    }

    /**
     * Gets ClassCheck logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "classcheck_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }


    /*** ------ Importing Data ------ ***/

    /**
     * Checks connection to ClassCheck attendances.
     *
     * @param string|null $tsvCode
     * @return void
     * @throws Exception
     */
    private function checkConnection(?string $tsvCode)
    {
        if (!$tsvCode) throw new Exception("Connection to " . self::NAME . " failed: no TSV code found.");
        fopen($tsvCode, "r");
    }

    /**
     * Imports ClassCheck data into the system.
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
            $tsvCode = $this->getTSVCode();
            $newData = $this->saveAttendance($tsvCode);

            if ($newData) self::log($this->course->getId(), "Imported new data from " . self::NAME . ".", "SUCCESS");
            self::log($this->course->getId(), "Finished importing data from " . self::NAME . "...", "INFO");
            return $newData;

        } finally {
            $this->setIsRunning(false);
            $this->setFinishedRunning(date("Y-m-d H:i:s", time()));
        }
    }

    /**
     * Saves ClassCheck attendance into the system.
     *
     * @param string $tsvCode
     * @return bool
     * @throws Exception
     */
    public function saveAttendance(string $tsvCode): bool
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type) VALUES ";
        $values = [];

        $file = fopen($tsvCode, "r");
        while (!feof($file)) {
            $line = fgets($file);
            $attendance = str_getcsv($line, "\t");
            if (count($attendance) < 8) {
                // to do : file returns extra info, source should be fixed
                // if len of line is too small, do not parse
                throw new Exception("Incorrect file format.");
            }
            self::parseAttendance($attendance);

            $profUsername = $attendance[0];
            $studentUsername = $attendance[2];
            $studentName = $attendance[3];
            $action = $attendance[4];
            $attendanceType = $attendance[5];
            $classNumber = $attendance[6];
            $shift = $attendance[7];

            $prof = $this->course->getCourseUserByUsername($profUsername);
            $student = $this->course->getCourseUserByUsername($studentUsername);

            if ($student && $prof) {
                if (!$this->hasAttendance($student->getId(), $classNumber)) {
                    $params = [
                        $student->getId(),
                        $this->getCourse()->getId(),
                        "\"" . $this->id . "\"",
                        "\"$classNumber\"",
                        "\"$action\""
                    ];
                    $values[] = "(" . implode(", ", $params) . ")";
                }
            }
        }

        $newData = !empty($values);
        if ($newData) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return $newData;
    }

    /**
     * Gets a given attendance participation ID.
     * Returns null if not found.
     *
     * @param int $userId
     * @param int $classNumber
     * @return int|null
     */
    private function getAttendanceParticipationId(int $userId, int $classNumber): ?int
    {
        $id = Core::database()->select(AutoGame::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $this->getCourse()->getId(),
            "source" => $this->id,
            "description" => $classNumber
        ], "id", null, [], [], ["type" => "attended lecture%"]);
        if ($id) return $id;
        return null;
    }

    /**
     * Checks whether a given attendance is already in the system.
     *
     * @param int $userId
     * @param int $classNumber
     * @return bool
     */
    private function hasAttendance(int $userId, int $classNumber): bool
    {
        return !!$this->getAttendanceParticipationId($userId, $classNumber);
    }

    /**
     * Parses an attendance coming from ClassCheck.
     *
     * @param array $attendance
     * @return void
     */
    private static function parseAttendance(array &$attendance)
    {
        if (isset($attendance[6])) $attendance[6] = intval($attendance[6]);
    }
}
