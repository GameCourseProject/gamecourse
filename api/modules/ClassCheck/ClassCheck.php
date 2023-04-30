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
    public static function log(int $courseId, string $message, string $type)
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

    const COL_EVALUATOR_USERNAME = 0;
    const COL_STUDENT_USERNAME = 2;
    const COL_STUDENT_NAME = 3;
    const COL_ACTION = 4;
    const COL_ATTENDANCE_TYPE = 5;
    const COL_LECTURE_NR = 6;
    const COL_SHIFT = 7;

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
        if (!fopen($tsvCode, "r")) throw new Exception("Connection to " . self::NAME . " failed: couldn't open TSV code file.");
    }

    /**
     * Imports ClassCheck data into the system.
     * Returns which targets had new data imported.
     *
     * @return array
     * @throws Exception
     */
    public function importData(): array
    {
        if ($this->isRunning()) {
            self::log($this->course->getId(), "Already importing data from " . self::NAME . ".", "WARNING");
            return [];
        }

        $this->setStartedRunning(date("Y-m-d H:i:s", time()));
        $this->setIsRunning(true);
        self::log($this->course->getId(), "Importing data from " . self::NAME . "...", "INFO");

        try {
            $tsvCode = $this->getTSVCode();
            $targets = $this->saveAttendance($tsvCode);

            if (!empty($targets))
                self::log($this->course->getId(), "Imported new data from " . self::NAME . ".", "SUCCESS");

            self::log($this->course->getId(), "Finished importing data from " . self::NAME . "...", "INFO");
            return $targets;

        } finally {
            $this->setIsRunning(false);
            $this->setFinishedRunning(date("Y-m-d H:i:s", time()));
        }
    }

    /**
     * Saves ClassCheck attendance into the system.
     *
     * @param string $tsvCode
     * @return array
     * @throws Exception
     */
    public function saveAttendance(string $tsvCode): array
    {
        // NOTE: it's better performance-wise to do only one big insert
        //       as opposed to multiple small inserts
        $sql = "INSERT INTO " . AutoGame::TABLE_PARTICIPATION . " (user, course, source, description, type) VALUES ";
        $values = [];

        $targets = []; // Which users to run AutoGame for, based on new attendance imported

        $lineNr = 1;
        $file = fopen($tsvCode, "r");
        while (!feof($file)) {
            $line = fgets($file);
            $attendance = str_getcsv($line, "\t");
            if (!self::attendanceIsValid($attendance)) {
                self::log($this->course->getId(), "Line #$lineNr is in an invalid format.", "WARNING");
                $lineNr++;
                continue;
            }
            self::parseAttendance($attendance);

            $profUsername = $attendance[self::COL_EVALUATOR_USERNAME];
            $studentUsername = $attendance[self::COL_STUDENT_USERNAME];
            $action = $attendance[self::COL_ACTION];
            $lectureNr = $attendance[self::COL_LECTURE_NR];

            $prof = $this->course->getCourseUserByUsername($profUsername);
            if ($prof) {
                $student = $this->course->getCourseUserByUsername($studentUsername);
                if ($student) {
                    if (!$this->hasAttendance($student->getId(), $lectureNr)) {
                        $params = [
                            $student->getId(),
                            $this->getCourse()->getId(),
                            "\"" . $this->id . "\"",
                            "\"$lectureNr\"",
                            "\"$action\""
                        ];
                        $values[] = "(" . implode(", ", $params) . ")";
                        $targets[] = $student->getId();
                    }

                } else self::log($this->course->getId(), "No student with username '$studentUsername' enrolled in the course.", "WARNING");

            } else self::log($this->course->getId(), "No teacher with username '$profUsername' enrolled in the course.", "WARNING");

            $lineNr++;
        }

        if (!empty($values)) {
            $sql .= implode(", ", $values);
            Core::database()->executeQuery($sql);
        }
        return array_unique($targets);
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
        if (isset($attendance[self::COL_LECTURE_NR])) $attendance[self::COL_LECTURE_NR] = intval($attendance[self::COL_LECTURE_NR]);
    }

    /**
     * Checks whether a given attendance is valid.
     *
     * @param array $attendance
     * @return bool
     */
    private static function attendanceIsValid(array $attendance): bool
    {
        if (count($attendance) !== 8) return false;
        if (!in_array($attendance[self::COL_ACTION], ["attended lecture", "attended lecture (late)"])) return false;
        if (!ctype_digit($attendance[self::COL_LECTURE_NR])) return false;
        return true;
    }
}
