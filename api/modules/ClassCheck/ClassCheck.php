<?php
namespace GameCourse\Module\ClassCheck;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use Throwable;

/**
 * This is the ClassCheck module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class ClassCheck extends Module
{
    const TABLE_CLASSCHECK_CONFIG = "classcheck_config";
    const TABLE_CLASSCHECK_STATUS = "classcheck_status";

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
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new ClassCheck($copyTo);

        // Copy config
        $tsvCode = $this->getTSVCode();
        $copiedModule->saveTSVCode($tsvCode);
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
        }
    }

    public function getLists(): array
    {
        return [
            [
                "listName" => "Status",
                "itemName" => "status",
                "listInfo" => [
                    ["id" => "startedRunning", "label" => "Started Running", "type" => InputType::DATETIME],
                    ["id" => "finishedRunning", "label" => "Finished Running", "type" => InputType::DATETIME],
                    ["id" => "isRunning", "label" => "Is Running", "type" => InputType::COLOR, "options" => ["showLabel" => false]]
                ],
                "items" => [
                    [
                        "startedRunning" => $this->getStartedRunning(),
                        "finishedRunning" => $this->getFinishedRunning(),
                        "isRunning" => $this->isRunning() ? "green" : "tomato"
                    ]
                ]
            ]
        ];
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


    /*** ---------- Status ---------- ***/

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
