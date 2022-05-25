<?php
namespace GameCourse\XPLevels;

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

    public function getNumber(): int
    {
        return $this->getData("number");
    }

    public function getGoal(): int
    {
        return $this->getData("goal");
    }

    public function getTitle(): ?string
    {
        return $this->getData("title");
    }

    /**
     * Gets level data from the database.
     *
     * @example getData() --> gets all level data
     * @example getData("number") --> gets user name
     * @example getData("number, title") --> gets level number & title
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

    public function setNumber(int $number)
    {
        $this->setData(["number" => $number]);
    }

    public function setGoal(int $goal)
    {
        $this->setData(["goal" => $goal]);
    }

    public function setTitle(?string $title)
    {
        $this->setData(["title" => $title]);
    }

    /**
     * Sets level data on the database.
     * @example setData(["number" => 0])
     * @example setData(["number" => 0, "title" => "New title"])
     *
     * @param array $fieldValues
     * @return void
     */
    public function setData(array $fieldValues)
    {
        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_LEVEL, $fieldValues, ["id" => $this->id]);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function getLevelById(int $id): ?Level
    {
        $level = new Level($id);
        if ($level->exists()) return $level;
        else return null;
    }

    public static function getLevelByNumber(int $courseId, int $number): ?Level
    {
        $levelId = intval(Core::database()->select(self::TABLE_LEVEL, ["course" => $courseId, "number" => $number], "id"));
        if (!$levelId) return null;
        else return new Level($levelId);
    }

    public static function getLevelByGoal(int $courseId, int $goal): ?Level
    {
        $levelId = intval(Core::database()->select(self::TABLE_LEVEL, ["course" => $courseId, "goal" => $goal], "id"));
        if (!$levelId) return null;
        else return new Level($levelId);
    }

    public static function getLevelByXP(int $courseId, int $xp): ?Level
    {
        $levels = self::getLevels($courseId, "goal DESC");
        foreach ($levels as $level) {
            if ($xp >= $level["goal"])
                return new Level($level["id"]);
        }
        return null;
    }

    public static function getLevels(int $courseId, string $orderBy = null): array
    {
        $levels = Core::database()->selectMultiple(self::TABLE_LEVEL, ["course" => $courseId], "*", $orderBy ?? "number");
        foreach ($levels as &$level) { $level = self::parse($level); }
        return $levels;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Level Manipulation ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new level to the database.
     * Returns the newly created level.
     *
     * @param int $courseId
     * @param int $goal
     * @param string|null $title
     * @return Level
     */
    public static function addLevel(int $courseId, int $goal, ?string $title): Level
    {
        $id = Core::database()->insert(self::TABLE_LEVEL, [
            "number" => $goal / 1000,
            "course" => $courseId,
            "goal" => $goal,
            "title" => $title
        ]);
        return new Level($id);
    }

    /**
     * Edits an existing level in the database.
     * Returns the edited level.
     *
     * @param int $goal
     * @param string|null $title
     * @return Level
     */
    public function editLevel(int $goal, ?string $title): Level
    {
        $this->setData([
            "number" => $goal / 1000,
            "goal" => $goal,
            "title" => $title
        ]);
        return $this;
    }

    /**
     * Deletes a level from the database.
     *
     * @param int $levelId
     * @return void
     */
    public static function deleteLevel(int $levelId) {
        Core::database()->delete(self::TABLE_LEVEL, ["id" => $levelId]);
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
    public function getUserLevel(int $courseId, int $userId): Level
    {
        $levelId = intval(Core::database()->select(XPLevels::TABLE_XP, ["course" => $courseId, "user" => $userId], "level"));
        return new Level($levelId);
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
     */
    public static function importLevels(int $courseId, string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($level, $indexes) use ($courseId, $replace) {
            $title = $level[$indexes["title"]];
            $minXP = $level[$indexes["minimum XP"]];

            $level = self::getLevelByGoal($courseId, $minXP);
            if ($level) {  // level already exists
                if ($replace)  // replace
                    $level->editLevel($minXP, $title);

            } else {  // level doesn't exist
                Level::addLevel($courseId, $minXP, $title);
                return 1;
            }
            return 0;
        }, $file);
    }

    /**
     * Exports levels from a given course into a .csv file.
     *
     * @param int $courseId
     * @return string
     */
    public static function exportUsers(int $courseId): string
    {
        return Utils::exportToCSV(self::getLevels($courseId), function ($level) {
            return [$level["title"], $level["goal"]];
        }, self::HEADERS);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a level coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $level
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    private static function parse(array $level = null, $field = null, string $fieldName = null)
    {
        if ($level) {
            if (isset($level["id"])) $level["id"] = intval($level["id"]);
            if (isset($level["course"])) $level["course"] = intval($level["course"]);
            if (isset($level["number"])) $level["number"] = intval($level["number"]);
            if (isset($level["goal"])) $level["goal"] = intval($level["goal"]);
            return $level;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "number" || $fieldName == "goal")
                return intval($field);
            return $field;
        }
    }
}