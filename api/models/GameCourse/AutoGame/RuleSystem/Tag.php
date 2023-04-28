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
        $courseId = $this->getCourse()->getId();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($courseId, $fieldValues["name"], $this->id);
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

    /**
     * Gets tags for a given rule.
     *
     * @param int $ruleId
     * @return array
     */
    public static function getRuleTags(int $ruleId): array
    {
        $table = Rule::TABLE_RULE_TAGS . " rt JOIN " . self::TABLE_RULE_TAG . " t on rt.tag=t.id";
        $where = ["rt.rule" => $ruleId];
        $tags = Core::database()->selectMultiple($table, $where, "t.*", "t.name");
        foreach ($tags as &$tag) { $tag = self::parse($tag); }
        return $tags;
    }

    public function getRules(): array{
        return Rule::getRulesWithTag($this->getId());
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
        self::trim($name, $color);
        self::validateTag($courseId, $name, $color);
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
     * Copies an existing tag into another given course.
     *
     * @param Course $copyTo
     * @return Tag
     * @throws Exception
     */
    public function copyTag(Course $copyTo): Tag
    {
        if ($this->getCourse()->getId() == $copyTo->getId()) // tag already exists
            return $this;

        $tagName = $this->getName();
        if (!RuleSystem::hasTag($copyTo->getId(), $tagName))
            $tag = RuleSystem::addTag($copyTo->getId(), $tagName, $this->getColor());
        else $tag = Tag::getTagByName($copyTo->getId(), $tagName);
        return $tag;
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

    public function updateRules(array $rules) {
        // remove rules that got removed
        $oldRules = $this->getRules();
        foreach ($oldRules as $oldRule) {
            $exists = !empty(array_filter($rules, function ($rule) use ($oldRule) {
                return $rule->getId() && $rule->getId() == $oldRule["id"];
            }));

            if (!$exists) {
                $rule = Rule::getRuleById($oldRule["id"]);
                $rule->removeTag($this->getId());
            };
        }


        // Update rules
        foreach ($rules as $rule){
            if (!$rule->hasTag($this->getId()))
                $rule->addTag($this->getId());
        }

    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates tag parameters.
     *
     * @param int $courseId
     * @param $name
     * @param $color
     * @return void
     * @throws Exception
     */
    private static function validateTag(int $courseId, $name, $color)
    {
        self::validateName($courseId, $name);
        self::validateColor($color);
    }

    /**
     * Validates tag name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $tagId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $tagId = null)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Tag name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Tag name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Tag name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($tagId) $whereNot[] = ["id", $tagId];
        $tagNames = array_column(Core::database()->selectMultiple(self::TABLE_RULE_TAG, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $tagNames))
            throw new Exception("Duplicate tag name: '$name'");
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
     * Parses a tag coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $tag
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $tag = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course"];

        return Utils::parse(["int" => $intValues], $tag, $field, $fieldName);
    }

    /**
     * Trims tag parameters' whitespace at start/end.
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