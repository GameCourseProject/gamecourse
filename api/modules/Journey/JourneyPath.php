<?php
namespace GameCourse\Module\Journey;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Journey Path model, which implements the necessary methods
 * to interact with paths in the MySQL database.
 */
class JourneyPath
{
    const TABLE_JOURNEY_PATH = 'journey_path';
    const TABLE_JOURNEY_PATH_SKILLS = 'journey_path_skills';

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

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getColor(): string
    {
        return $this->getData("color");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets journey path data from the database.
     *
     * @param string $field
     * @return array|int|string|boolean|null
     * @example getData("name, color") --> gets path name & color
     *
     * @example getData() --> gets all path data
     * @example getData("name") --> gets path name
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_JOURNEY_PATH;
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
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setColor(string $color)
    {
        $this->setData(["color" => $color]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets path data on the database.
     * @param array $fieldValues
     * @return void
     * @throws Exception
     * @example setData(["name" => "New name", "color" => "#000000"])
     *
     * @example setData(["name" => "New name"])
     */
    public function setData(array $fieldValues)
    {
        $courseId = $this->getCourse()->getId();
        /* $rule = $this->getRule(); */

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($courseId, $newName, $this->id);
            $oldName = $this->getName();
        }
        if (key_exists("color", $fieldValues)) {
            $newColor = $fieldValues["color"];
            self::validateColor($newColor);
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_JOURNEY_PATH, $fieldValues, ["id" => $this->id]);

        /*if (key_exists("isActive", $fieldValues)) {
            if ($oldStatus != $newStatus) {
                // Update rule status
                $rule->setActive($newStatus);
            }
        }
        if (key_exists("name", $fieldValues)) {
            // Update path rule
            $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
            self::updateRule($rule->getId(), $name, $description, $isPoint, $this->getLevels());
        }*/
        // TODO: Commented stuff when rules are ready
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates path parameters.
     *
     * @param int $courseId
     * @param $name
     * @param $color
     * @return void
     * @throws Exception
     */
    private static function validatePath(int $courseId, $name, $color)
    {
        self::validateName($courseId, $name);
        self::validateColor($color);
        // TODO: complete
    }

    /**
     * Validates path name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $pathId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $pathId = null)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Path name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Path name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Path name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($pathId) $whereNot[] = ["id", $pathId];
        $pathNames = array_column(Core::database()->selectMultiple(self::TABLE_JOURNEY_PATH, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $pathNames))
            throw new Exception("Duplicate path name: '$name'");
    }

    /**
     * Validates path color.
     *
     * @param $color
     * @return void
     * @throws Exception
     */
    private static function validateColor($color)
    {
        if (is_null($color)) return;

        if (!Utils::isValidColor($color, "HEX"))
            throw new Exception("Path color needs to be in HEX format.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a path by its ID.
     * Returns null if path doesn't exist.
     *
     * @param int $id
     * @return JourneyPath|null
     */
    public static function getJourneyPathById(int $id): ?JourneyPath
    {
        $path = new JourneyPath($id);
        if ($path->exists()) return $path;
        else return null;
    }

    /**
     * Gets a path by its name.
     * Returns null if path doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return JourneyPath|null
     */
    public static function getJourneyPathByName(int $courseId, string $name): ?JourneyPath
    {
        $pathId = intval(Core::database()->select(self::TABLE_JOURNEY_PATH,
            ["course" => $courseId, "name" => $name], "id"));
        if (!$pathId) return null;
        else return new JourneyPath($pathId);
    }

    /**
     * Gets all paths of course.
     * Option for 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param string $orderBy
     * @return array
     */
    public static function getJourneyPaths(int $courseId, bool $active = null, string $orderBy = "name"): array
    {
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        $paths = Core::database()->selectMultiple(self::TABLE_JOURNEY_PATH, $where, "*", $orderBy);
        foreach ($paths as &$pathInfo) {
            $pathInfo = self::parse($pathInfo);
        }
        return $paths;
    }

    /**
     * Gets all skills of path.
     * Option for 'collab', 'extra', 'active' and ordering.
     *
     * @param int $pathId
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @param string $orderBy
     * @return array
     * @throws Exception
     */
    public function getSkills(bool $active = null, bool $extra = null, bool $collab = null,
                                           string $orderBy = "s.position"): array
    {
        $where = ["p.path" => $this->id];
        if ($active !== null) $where["s.isActive"] = $active;
        if ($extra !== null) $where["s.isExtra"] = $extra;
        if ($collab !== null) $where["s.isCollab"] = $collab;
        $skills = Core::database()->selectMultiple(Skills::TABLE_SKILL . " s LEFT JOIN " . self::TABLE_JOURNEY_PATH_SKILLS . " p on s.id=p.skill",
            $where, "s.*", $orderBy);
        foreach ($skills as &$skillInfo) {
            $skill = Skill::getSkillById($skillInfo["id"]);
            $skillInfo["page"] = $skill->getPage();
            $skillInfo["dependencies"] = $skill->getDependencies();
            $skillInfo = Skill::parse($skillInfo);
        }
        return $skills;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Path Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new path to the database.
     * Returns the newly created path.
     *
     * @param int $courseId
     * @param string $name
     * @param string $color
     * @return JourneyPath
     * @throws Exception
     */
    public static function addJourneyPath(int $courseId, string $name, string $color): JourneyPath
    {
        self::trim($name);
        self::validateName($courseId, $name);
        $id = Core::database()->insert(self::TABLE_JOURNEY_PATH, [
            "course" => $courseId,
            "name" => $name,
            "color" => $color
        ]);
        $path = new JourneyPath($id);

        return $path;
    }

    /**
     * Edits an existing path in the database.
     * Returns the edited path.
     *
     * @param string $name
     * @param string $color
     * @return JourneyPath
     * @throws Exception
     */
    public function editJourneyPath(string $name, string $color): JourneyPath
    {
        $this->setData([
            "name" => $name,
            "color" => $color
        ]);
        return $this;
    }

    /**
     * Deletes an existing path in the database.
     *
     * @return JourneyPath
     * @throws Exception
     */
    public static function deleteJourneyPath(int $pathId) {
        $path = self::getJourneyPathById($pathId);
        if ($path) {
            // $courseId = $path->getCourse()->getId();

            // TODO: Remove skill rule
            // self::removeRule($courseId, $path->getRule()->getId());

            // Delete skill from database
            Core::database()->delete(self::TABLE_JOURNEY_PATH, ["id" => $pathId]);
        }
    }

    /**
     * Checks whether journey path exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a journey path coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $path
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $path = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $path, $field, $fieldName);
    }

    /**
     * Trims path parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "color"];
        Utils::trim($params, ...$values);
    }
}
