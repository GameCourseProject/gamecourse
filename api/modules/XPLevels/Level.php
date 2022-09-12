<?php
namespace GameCourse\Module\XPLevels;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the Level model, which implements the necessary methods
 * to interact with levels in the MySQL database.
 */
class Level
{
    const TABLE_LEVEL = 'level';

    const HEADERS = [   // headers for import/export functionality
        "title", "minimum XP"
    ];

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getCourse(): Course
    {
        return new Course($this->getData("course"));
    }

    public function getMinXP(): int
    {
        return $this->getData("minXP");
    }

    public function getDescription(): ?string
    {
        return $this->getData("description");
    }

    /**
     * Gets level data from the database.
     *
     * @example getData() --> gets all level data
     * @example getData("minXP") --> gets level minimum XP
     * @example getData("minXP, description") --> gets level minimum XP & description
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        // Get data
        $table = self::TABLE_LEVEL;
        $where = ["id" => $this->id];
        $res = Core::database()->select($table, $where, $field);
        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setMinXP(int $minXP)
    {
        $this->setData(["minXP" => $minXP]);
    }

    /**
     * @throws Exception
     */
    public function setDescription(?string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * Sets level data on the database.
     * @example setData(["minXP" => 100])
     * @example setData(["minXP" => 100, "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("minXP", $fieldValues)) {
            $previousMinXP = self::getMinXP();
            $newMinXP = intval($fieldValues["minXP"]);
            if ($previousMinXP !== 0 && $newMinXP <= 0)
                throw new Exception("Can't set level minimum XP: XP needs to be a positive number.");

            if ($previousMinXP === 0 && $newMinXP !== 0)
                throw new Exception("Can't update minimum XP of Level 0.");
        }
        if (key_exists("description", $fieldValues)) self::validateDescription($fieldValues["description"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_LEVEL, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("minXP", $fieldValues)) self::updateUsersLevel($this->getCourse()->getId());
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a level by its ID.
     * Returns null if level doesn't exist.
     *
     * @param int $id
     * @return Level|null
     */
    public static function getLevelById(int $id): ?Level
    {
        $level = new Level($id);
        if ($level->exists()) return $level;
        else return null;
    }

    /**
     * Gets a level by its minimum XP.
     * Returns null if level doesn't exist.
     *
     * @param int $courseId
     * @param int $minXP
     * @return Level|null
     */
    public static function getLevelByMinXP(int $courseId, int $minXP): ?Level
    {
        $levelId = intval(Core::database()->select(self::TABLE_LEVEL, ["course" => $courseId, "minXP" => $minXP], "id"));
        if (!$levelId) return null;
        else return new Level($levelId);
    }

    /**
     * Gets a level by its corresponding XP.
     * Returns null if level doesn't exist.
     *
     * @param int $courseId
     * @param int $xp
     * @return Level|null
     * @throws Exception
     */
    public static function getLevelByXP(int $courseId, int $xp): Level
    {
        $levels = self::getLevels($courseId, "minXP DESC");
        foreach ($levels as $level) {
            if ($xp >= $level["minXP"])
                return new Level($level["id"]);
        }

        throw new Exception("Level 0 not found.");
    }

    /**
     * Gets level 0.
     *
     * @param int $courseId
     * @return Level
     * @throws Exception
     */
    public static function getLevelZero(int $courseId): Level
    {
        $level0 = self::getLevelByMinXP($courseId, 0);
        if (!$level0) throw new Exception("Couldn't find Level 0.");
        return $level0;
    }

    /**
     * Gets all levels of course.
     * Option for ordering.
     *
     * @param int $courseId
     * @param string $orderBy
     * @return array
     */
    public static function getLevels(int $courseId, string $orderBy = "minXP"): array
    {
        $field = "id, minXP, description";
        $levels = Core::database()->selectMultiple(self::TABLE_LEVEL, ["course" => $courseId], $field, $orderBy);
        foreach ($levels as $i => &$level) {
            $level["number"] = $i;
            $level = self::parse($level);
        }
        return $levels;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Level Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new level to the database.
     * Returns the newly created level.
     *
     * @param int $courseId
     * @param int $minXP
     * @param string|null $description
     * @return Level
     * @throws Exception
     */
    public static function addLevel(int $courseId, int $minXP, ?string $description): Level
    {
        self::trim($description);
        self::validateLevel($description);
        $id = Core::database()->insert(self::TABLE_LEVEL, [
            "course" => $courseId,
            "minXP" => $minXP,
            "description" => $description
        ]);
        if (count(Level::getLevels($courseId)) != 1) self::updateUsersLevel($courseId);
        return new Level($id);
    }

    /**
     * Edits an existing level in the database.
     * Returns the edited level.
     *
     * @param int $minXP
     * @param string|null $description
     * @return Level
     * @throws Exception
     */
    public function editLevel(int $minXP, ?string $description): Level
    {
        $this->setData([
            "minXP" => $minXP,
            "description" => $description
        ]);
        return $this;
    }

    /**
     * Copies an existing level into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copyLevel(Course $copyTo)
    {
        $minXP = $this->getMinXP();
        $description = $this->getDescription();

        if ($minXP == 0) {
            $copiedLevel0 = self::getLevelZero($copyTo->getId());
            $copiedLevel0->editLevel($minXP, $description);

        } else self::addLevel($copyTo->getId(), $minXP, $description);
    }

    /**
     * Deletes a level from the database.
     *
     * @param int $levelId
     * @return void
     * @throws Exception
     */
    public static function deleteLevel(int $levelId) {
        $level = self::getLevelById($levelId);
        if ($level) {
            if ($level->getMinXP() === 0)
                throw new Exception("Can't delete Level 0.");

            $courseId = $level->getCourse()->getId();
            self::resetUsersLevel($courseId);
            Core::database()->delete(self::TABLE_LEVEL, ["id" => $levelId]);
            self::updateUsersLevel($courseId);
        }
    }

    /**
     * Checks whether level exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Users ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets the current level for a given user.
     *
     * @param int $courseId
     * @param int $userId
     * @return void
     */
    public static function getUserLevel(int $courseId, int $userId): Level
    {
        $levelId = intval(Core::database()->select(XPLevels::TABLE_XP, ["course" => $courseId, "user" => $userId], "level"));
        return new Level($levelId);
    }

    /**
     * Automatically updates the current level for all users
     * when levels changed.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function updateUsersLevel(int $courseId)
    {
        $course = Course::getCourseById($courseId);
        $xpModule = new XPLevels($course);
        $students = $course->getStudents();
        foreach ($students as $student) {
            $studentId = intval($student["id"]);
            $userXP = $xpModule->getUserXP($studentId);
            $newLevel = self::recalculateLevel($courseId, $userXP);
            Core::database()->update(XPLevels::TABLE_XP,
                ["level" => $newLevel->getId()],
                ["course" => $courseId, "user" => $studentId]
            );
        }
    }

    /**
     * Set the current level for all users as level 0.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    private static function resetUsersLevel(int $courseId)
    {
        $course = Course::getCourseById($courseId);
        $level0 = self::getLevelZero($courseId);
        $students = $course->getStudents();
        foreach ($students as $student) {
            $studentId = intval($student["id"]);
            Core::database()->update(XPLevels::TABLE_XP,
                ["level" => $level0->getId()],
                ["course" => $courseId, "user" => $studentId]
            );
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports levels into a given course from a .csv file.
     * Returns the nr. of levels imported.
     *
     * @param int $courseId
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importLevels(int $courseId, string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($level, $indexes) use ($courseId, $replace) {
            $description = $level[$indexes["title"]];
            $minXP = self::parse(null, $level[$indexes["minimum XP"]], "minXP");

            $level = self::getLevelByMinXP($courseId, $minXP);
            if ($level) {  // level already exists
                if ($replace)  // replace
                    $level->editLevel($minXP, $description);

            } else {  // level doesn't exist
                Level::addLevel($courseId, $minXP, $description);
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports levels from a given course into a .csv file.
     *
     * @param int $courseId
     * @return array
     */
    public static function exportLevels(int $courseId): array
    {
        return ["extension" => ".csv", "file" => Utils::exportToCSV(self::getLevels($courseId), function ($level) {
            return [$level["description"], $level["minXP"]];
        }, self::HEADERS)];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates level parameters.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateLevel($description)
    {
        self::validateDescription($description);
    }

    /**
     * Validates level description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (is_null($description)) return;

        if (!is_string($description) || empty($description))
            throw new Exception("Level description can't be empty.");

        if (iconv_strlen($description) > 50)
            throw new Exception("Level description is too long: maximum of 50 characters.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Recalculates which level corresponds to a given XP amount.
     *
     * @param int $courseId
     * @param int $xp
     * @return Level
     * @throws Exception
     */
    private static function recalculateLevel(int $courseId, int $xp): Level
    {
        $levels = self::getLevels($courseId, "minXP DESC");
        foreach ($levels as $level) {
            if ($xp >= intval($level["minXP"])) return new Level($level["id"]);
        }
        throw new Exception("Couldn't recalculate level: XP must be positive.");
    }

    /**
     * Parses a level coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $level
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $level = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "minXP"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $level, $field, $fieldName);
    }

    /**
     * Trims level parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["description"];
        Utils::trim($params, ...$values);
    }
}
