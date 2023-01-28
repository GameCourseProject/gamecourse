<?php
namespace GameCourse\Module\ClassCheck;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use Throwable;
use Utils\CronJob;
use Utils\Time;
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
        $logsFile = self::getLogsFile($this->getCourse()->getId());
        Utils::initLogging($logsFile);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();

        // Disable auto importing
        $this->setAutoImporting(false);

        // Remove logging info
        $logsFile = self::getLogsFile($this->getCourse()->getId());
        Utils::removeLogging($logsFile);
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
                                    "topLabel" => "TSV code",
                                    "maxLength" => 200
                                ],
                                "helper" => "Classcheck TSV code URL"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "name" => "Frequency",
                "description" => "Define how frequently data should be imported from " . self::NAME . ".",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::PERIODICITY,
                                "id" => "periodicity",
                                "value" => $this->getPeriodicity(),
                                "placeholder" => "Period of time",
                                "options" => [
                                    "filterOptions" => [Time::SECOND, Time::YEAR, Time::MONTH],
                                    "topLabel" => "Import data every...",
                                    "minNumber" => 1,
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
            if ($input["id"] == "periodicity") $this->savePeriodicity($input["value"]["number"], $input["value"]["time"]);
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
        if (!self::canConnect($tsvCode))
            throw new Exception("Connection to ClassCheck failed.");

        Core::database()->update(self::TABLE_CLASSCHECK_CONFIG, [
            "tsvCode" => $tsvCode,
        ], ["course" => $this->getCourse()->getId()]);
    }


    public function getPeriodicity(): array
    {
        $periodicity = Core::database()->select(self::TABLE_CLASSCHECK_CONFIG, ["course" => $this->getCourse()->getId()], "periodicityNumber, periodicityTime");
        return ["number" => intval($periodicity["periodicityNumber"]), "time" => $periodicity["periodicityTime"]];
    }

    public function savePeriodicity(?int $periodicityNumber, ?string $periodicityTime)
    {
        Core::database()->update(self::TABLE_CLASSCHECK_CONFIG, [
            "periodicityNumber" => $periodicityNumber,
            "periodicityTime" => $periodicityTime
        ], ["course" => $this->getCourse()->getId()]);
    }


    /*** ---------- Status ---------- ***/

    public function isAutoImporting(): bool
    {
        return boolval(Core::database()->select(self::TABLE_CLASSCHECK_STATUS, ["course" => $this->getCourse()->getId()], "isEnabled"));
    }

    public function setAutoImporting(bool $enable)
    {
        $courseId = $this->getCourse()->getId();

        if ($enable) { // enable classcheck
            $periodicity = $this->getPeriodicity();
            new CronJob(self::ID, $courseId, $periodicity["number"],  $periodicity["time"]);

        } else { // disable autogame
            CronJob::removeCronJob(self::ID, $courseId);
        }
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["isEnabled" => $enable], ["course" => $courseId]);
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
        Core::database()->update(self::TABLE_CLASSCHECK_STATUS, ["isRunning" => $status], ["course" => $this->getCourse()->getId()]);
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
        $logsFile = self::getLogsFile($courseId);
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
        $logsFile = self::getLogsFile($courseId);
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
        $filename = "classcheck_$courseId.txt";
        if ($fullPath) return self::LOGS_FOLDER . "/" . $filename;
        else return $filename;
    }


    /*** ------ Importing Data ------ ***/

    /**
     * Checks connection to ClassCheck attendances.
     *
     * @param string|null $tsvCode
     * @return bool
     */
    private static function canConnect(?string $tsvCode): bool
    {
        try {
            if (!$tsvCode) return false;
            return !!fopen($tsvCode, "r");

        } catch (Throwable $e) {
            return false;
        }
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
        if ($this->isRunning())
            throw new Exception("Already importing data from ClassCheck.");

        $this->setStartedRunning(date("Y-m-d H:i:s", time()));
        $this->setIsRunning(true);

        try {
            $tsvCode = $this->getTSVCode();
            return $this->saveAttendance($tsvCode);

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
