<?php
namespace GameCourse\XPLevels;

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

    public function getGoal(): int
    {
        return $this->getData("goal");
    }

    public function getDescription(): ?string
    {
        return $this->getData("description");
    }

    /**
     * Gets level data from the database.
     *
     * @example getData() --> gets all level data
     * @example getData("goal") --> gets level goal
     * @example getData("goal, description") --> gets level goal & description
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
     * @param int $goal
     * @return void
     * @throws Exception
     */
    public function setGoal(int $goal)
    {
        $this->setData(["goal" => $goal]);
    }

    /**
     * @param string|null $description
     * @return void
     * @throws Exception
     */
    public function setDescription(?string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * Sets level data on the database.
     * @example setData(["goal" => 100])
     * @example setData(["goal" => 100, "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        if (key_exists("goal", $fieldValues)) {
            $previousGoal = self::getGoal();
            $newGoal = intval($fieldValues["goal"]);
            if ($previousGoal !== 0 && $newGoal <= 0)
                throw new Exception("Can't set level goal: goal needs to be a positive number.");

            if ($previousGoal === 0 && $newGoal !== 0)
                throw new Exception("Can't update goal of Level 0.");
        }

        if (count($fieldValues) != 0) Core::database()->update(self::TABLE_LEVEL, $fieldValues, ["id" => $this->id]);

        if (key_exists("goal", $fieldValues)) self::updateUsersLevel($this->getCourse()->getId());
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

    public static function getLevels(int $courseId, string $orderBy = "goal"): array
    {
        $field = "id, goal, description";
        $levels = Core::database()->selectMultiple(self::TABLE_LEVEL, ["course" => $courseId], $field, $orderBy);
        foreach ($levels as $i => &$level) {
            $level["number"] = $i;
            $level = self::parse($level);
        }
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
     * @param string|null $description
     * @return Level
     * @throws Exception
     */
    public static function addLevel(int $courseId, int $goal, ?string $description): Level
    {
        $id = Core::database()->insert(self::TABLE_LEVEL, [
            "course" => $courseId,
            "goal" => $goal,
            "description" => $description
        ]);
        self::updateUsersLevel($courseId);
        return new Level($id);
    }

    /**
     * Edits an existing level in the database.
     * Returns the edited level.
     *
     * @param int $goal
     * @param string|null $description
     * @return Level
     * @throws Exception
     */
    public function editLevel(int $goal, ?string $description): Level
    {
        $this->setData([
            "goal" => $goal,
            "description" => $description
        ]);
        return $this;
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
        if ($level->getGoal() === 0) throw new Exception("Can't delete Level 0.");
        Core::database()->delete(self::TABLE_LEVEL, ["id" => $levelId]);
        self::updateUsersLevel($level->getCourse()->getId());
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

    /**
     * Automatically updates the current level for all users.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function updateUsersLevel(int $courseId)
    {
        $course = new Course($courseId);
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
            $minXP = self::parse(null, $level[$indexes["minimum XP"]], "goal");

            $level = self::getLevelByGoal($courseId, $minXP);
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
     * @return string
     */
    public static function exportLevels(int $courseId): string
    {
        return Utils::exportToCSV(self::getLevels($courseId), function ($level) {
            return [$level["description"], $level["goal"]];
        }, self::HEADERS);
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
        $levels = self::getLevels($courseId, "goal DESC");
        foreach ($levels as $level) {
            if ($xp >= intval($level["goal"])) return new Level($level["id"]);
        }
        throw new Exception("Couldn't recalculate level: XP must be positive.");
    }

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
            if (isset($level["goal"])) $level["goal"] = intval($level["goal"]);
            return $level;

        } else {
            if ($fieldName == "id" || $fieldName == "course" || $fieldName == "goal") return intval($field);
            return $field;
        }
    }
}
