<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the Tag model, which implements the necessary methods
 * to interact with rule tags in the Rule System.
 */
class Tag
{
    const TABLE_RULE_TAG = "rule_tag";

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
        return Course::getCourseById($this->getData("course"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getColor(): string
    {
        return $this->getData("color");
    }

    /**
     * Gets tag data from the database.
     *
     * @example getData() --> gets all tag data
     * @example getData("name") --> gets tag name
     * @example getData("name, color") --> gets tag name & color
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_RULE_TAG;
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
        $this->setData(["name" => trim($name)]);
    }

    /**
     * @throws Exception
     */
    public function setColor(string $color)
    {
        $this->setData(["color" => $color]);
    }

    /**
     * Sets tag data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "color" => "#000000"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_RULE_TAG, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("name", $fieldValues)) {
            // Update tag name in all rules
            $rulesWithTag = Rule::getRulesWithTag($this->id);
            foreach ($rulesWithTag as $r) {
                $rule = Rule::getRuleById($r["id"]);
                $rule->setText($rule->getText());
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a tag by its ID.
     * Returns null if tag doesn't exist.
     *
     * @param int $id
     * @return Tag|null
     */
    public static function getTagById(int $id): ?Tag
    {
        $tag = new Tag($id);
        if ($tag->exists()) return $tag;
        else return null;
    }

    /**
     * Gets a tag by its name.
     * Returns null if tag doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Tag|null
     */
    public static function getTagByName(int $courseId, string $name): ?Tag
    {
        $tagId = intval(Core::database()->select(self::TABLE_RULE_TAG, ["course" => $courseId, "name" => $name], "id"));
        if (!$tagId) return null;
        else return new Tag($tagId);
    }

    /**
     * Gets tags in the Rule System for a given course.
     *
     * @param int $courseId
     * @return array
     */
    public static function getTags(int $courseId): array
    {
        $tags = Core::database()->selectMultiple(self::TABLE_RULE_TAG, ["course" => $courseId], "*", "name");
        foreach ($tags as &$tag) { $tag = self::parse($tag); }
        return $tags;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Tag Manipulation ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a tag to the Rule System.
     * Returns the newly created tag.
     *
     * @param int $courseId
     * @param string $name
     * @param string $color
     * @return Tag
     * @throws Exception
     */
    public static function addTag(int $courseId, string $name, string $color): Tag
    {
        self::trim($name);
        self::validateTag($name, $color);
        $id = Core::database()->insert(self::TABLE_RULE_TAG, [
            "course" => $courseId,
            "name" => $name,
            "color" => $color
        ]);
        return new Tag($id);
    }

    /**
     * Edits an existing tag in the Rule System.
     * Returns the edited tag.
     *
     * @param string $name
     * @param string $color
     * @return Tag
     * @throws Exception
     */
    public function editTag(string $name, string $color): Tag
    {
        $this->setData(["name" => $name, "color" => $color]);
        return $this;
    }

    /**
     * Deletes a tag from the Rule System.
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function deleteTag(int $id)
    {
        $tag = self::getTagById($id);
        if ($tag) {
            $rulesWithTag = Rule::getRulesWithTag($id);
            Core::database()->delete(self::TABLE_RULE_TAG, ["id" => $id]);
            foreach ($rulesWithTag as $r) {
                $rule = Rule::getRuleById($r["id"]);
                $rule->setText($rule->getText());
            }
        }
    }

    /**
     * Checks whether tag exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates tag parameters.
     *
     * @param $name
     * @param $color
     * @return void
     * @throws Exception
     */
    private static function validateTag($name, $color)
    {
        self::validateName($name);
        self::validateColor($color);
    }

    /**
     * Validates tag name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Tag name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Tag name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Tag name is too long: maximum of 50 characters.");
    }

    /**
     * Validates tag color.
     *
     * @throws Exception
     */
    private static function validateColor($color)
    {
        if (!is_string($color) || empty($color))
            throw new Exception("Tag color can't be null neither empty.");

        if (!Utils::isValidColor($color, "HEX"))
            throw new Exception("Tag color needs to be in HEX format.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Trims tag parameters' whitespace at start/end.
     *
     * @param string $name
     * @return void
     */
    private static function trim(string &$name)
    {
        $name = trim($name);
    }

    /**
     * Parses a tag coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $tag
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $tag = null, $field = null, string $fieldName = null)
    {
        if ($tag) {
            if (isset($tag["id"])) $tag["id"] = intval($tag["id"]);
            if (isset($tag["course"])) $tag["course"] = intval($tag["course"]);
            return $tag;

        } else {
            if ($fieldName == "id" || $fieldName == "course") return is_numeric($field) ? intval($field) : $field;
            return $field;
        }
    }
}