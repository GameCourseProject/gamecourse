<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\Utils;

/**
 * This is the Rule model, which implements the necessary methods
 * to interact with autogame rules in the Rule System.
 */
class Rule
{
    const TABLE_RULE = "rule";
    const TABLE_RULE_TAGS = "rule_tags";

    const HEADERS = [   // headers for import/export functionality
        "name", "isActive"
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
        return Course::getCourseById($this->getData("course"));
    }

    public function getSection(): Section
    {
        return Section::getSectionById($this->getData("section"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getDescription(): ?string
    {
        return $this->getData("description");
    }

    public function getWhen(): string
    {
        return $this->getData("whenClause");
    }

    public function getThen(): string
    {
        return $this->getData("thenClause");
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function getText(): string
    {
        $data = $this->getData();
        $tags = $this->getTags();
        return self::generateText($data["name"], $data["description"], $data["whenClause"], $data["thenClause"], $data["isActive"], $tags);
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Gets rule data from the database.
     *
     * @example getData() --> gets all rule data
     * @example getData("name") --> gets rule name
     * @example getData("name, description") --> gets rule name & description
     *
     * @param string $field
     * @return array|int|string|bool|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_RULE;
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
    public function setDescription(?string $description)
    {
        $this->setData(["description" => $description]);
    }

    /**
     * @throws Exception
     */
    public function setWhen(string $when)
    {
        $this->setData(["whenClause" => $when]);
    }

    /**
     * @throws Exception
     */
    public function setThen(string $then)
    {
        $this->setData(["thenClause" => $then]);
    }

    /**
     * @throws Exception
     */
    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    public function setText(string $text)
    {
        $section = $this->getSection();
        $section->updateRuleText(trim($text), $this->getPosition());
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets rule data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "description" => "New description"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        $section = $this->getSection();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("whenClause", $fieldValues)) self::validateWhen($fieldValues["whenClause"]);
        if (key_exists("thenClause", $fieldValues)) self::validateWhen($fieldValues["thenClause"]);
        if (key_exists("position", $fieldValues)) {
            $newPosition = $fieldValues["position"];
            $oldPosition = $this->getPosition();
            Utils::updateItemPosition($oldPosition, $newPosition, self::TABLE_RULE, "position", $this->id, $section->getRules());
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_RULE, $fieldValues, ["id" => $this->id]);

        // Additional actions
        $position = $this->getPosition();
        $section->removeRuleText(key_exists("position", $fieldValues) ? $oldPosition : $position);
        $section->addRuleText($this->getText(), $position);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a rule by its ID.
     * Returns null if rule doesn't exist.
     *
     * @param int $id
     * @return Rule|null
     */
    public static function getRuleById(int $id): ?Rule
    {
        $rule = new Rule($id);
        if ($rule->exists()) return $rule;
        else return null;
    }

    /**
     * Gets a rule by its name.
     * Returns null if rule doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Rule|null
     * @throws Exception
     */
    public static function getRuleByName(int $courseId, string $name): ?Rule
    {
        $ruleId = intval(Core::database()->select(self::TABLE_RULE, ["course" => $courseId, "name" => $name], "id"));
        if (!$ruleId) return null;
        else return new Rule($ruleId);
    }

    /**
     * Gets a rule by its position.
     * Returns null if rule doesn't exist.
     *
     * @param int $sectionId
     * @param int $position
     * @return Rule|null
     */
    public static function getRuleByPosition(int $sectionId, int $position): ?Rule
    {
        $ruleId = intval(Core::database()->select(self::TABLE_RULE, ["section" => $sectionId, "position" => $position], "id"));
        if (!$ruleId) return null;
        else return new Rule($ruleId);
    }

    /**
     * Gets rules in the Rule System for a given course, ordered by priority.
     * Option for 'active'.
     *
     * @param int $courseId
     * @param bool|null $active
     * @return array
     */
    public static function getRules(int $courseId, bool $active = null): array
    {
        $table = self::TABLE_RULE . " r JOIN " . Section::TABLE_RULE_SECTION . " s on r.section=s.id";
        $where = ["r.course" => $courseId];
        if ($active !== null) $where["r.isActive"] = $active;
        $rules = Core::database()->selectMultiple($table, $where, "r.*, s.position as sectionPos", "sectionPos, position");
        foreach ($rules as &$rule) {
            $rule = self::parse($rule);
            unset($rule["sectionPos"]);
        }
        return $rules;
    }

    /**
     * Gets all rules of a given section, ordered by priority.
     * Option for 'active'.
     *
     * @param int $sectionId
     * @param bool|null $active
     * @return array
     */
    public static function getRulesOfSection(int $sectionId, bool $active = null): array
    {
        $where = ["section" => $sectionId];
        if ($active !== null) $where["isActive"] = $active;
        $rules = Core::database()->selectMultiple(self::TABLE_RULE, $where, "*", "position");
        foreach ($rules as &$rule) { $rule = self::parse($rule); }
        return $rules;
    }

    /**
     * Gets all rules with a given tag, ordered by priority.
     * Option for 'active'.
     *
     * @param int $tagId
     * @param bool|null $active
     * @return array
     */
    public static function getRulesWithTag(int $tagId, bool $active = null): array
    {
        $table = self::TABLE_RULE . " r JOIN " . Section::TABLE_RULE_SECTION . " s on r.section=s.id JOIN " . self::TABLE_RULE_TAGS . " rt on rt.rule=r.id";
        $where = ["rt.tag" => $tagId];
        if ($active !== null) $where["r.isActive"] = $active;
        $rules = Core::database()->selectMultiple($table, $where, "r.*, s.position as sectionPos", "sectionPos, position");
        foreach ($rules as &$rule) {
            $rule = self::parse($rule);
            unset($rule["sectionPos"]);
        }
        return $rules;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Rule Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param int $sectionId
     * @param string $name
     * @param string|null $description
     * @param string $when
     * @param string $then
     * @param int $position
     * @param bool $isActive
     * @param array $tags
     * @return Rule
     * @throws Exception
     */
    public static function addRule(int $courseId, int $sectionId, string $name, ?string $description, string $when,
                                   string $then, int $position, bool $isActive = true, array $tags = []): Rule
    {
        self::trim($name, $description, $when, $then);
        self::validateRule($name, $when, $then, $isActive);
        $section = new Section($sectionId);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_RULE, [
            "course" => $courseId,
            "section" => $sectionId,
            "name" => $name,
            "description" => $description,
            "whenClause" => $when,
            "thenClause" => $then,
            "isActive" => +$isActive
        ]);
        Utils::updateItemPosition(null, $position, self::TABLE_RULE, "position", $id, $section->getRules());
        $rule = new Rule($id);

        // Add rule text to section file
        $ruleText = self::generateText($name, $description, $when, $then, $isActive, $tags);
        $section->addRuleText($ruleText, $position);

        // Add tags
        foreach ($tags as $tag) {
            $rule->addTag($tag["id"]);
        }

        return $rule;
    }

    /**
     * Edits an existing rule in the Rule System.
     * Returns the edited rule.
     *
     * @param string $name
     * @param string|null $description
     * @param string $when
     * @param string $then
     * @param int $position
     * @param bool $isActive
     * @param array $tags
     * @return Rule
     * @throws Exception
     */
    public function editRule(string $name, ?string $description, string $when, string $then, int $position,
                             bool $isActive = true, array $tags = []): Rule
    {
        $this->setData([
            "name" => $name,
            "description" => $description,
            "whenClause" => $when,
            "thenClause" => $then,
            "isActive" => +$isActive,
            "position" => $position
        ]);
        $this->updateTags($tags);
        return $this;
    }

    /**
     * Copies an existing rule into another given section.
     *
     * @param Section $section
     * @return void
     * @throws Exception
     */
    public function copyRule(Section $section)
    {
        // Copy tags
        $tags = [];
        $copyTo = $section->getCourse();
        foreach ($this->getTags() as $tag) {
            $tag = new Tag($tag["id"]);
            $copiedTag = $tag->copyTag($copyTo);
            $tags[] = $copiedTag->getData();
        }

        // Copy rule
        $ruleInfo = $this->getData();
        self::addRule($section->getCourse()->getId(), $section->getId(), $ruleInfo["name"], $ruleInfo["description"],
            $ruleInfo["whenClause"], $ruleInfo["thenClause"], $ruleInfo["position"], $ruleInfo["isActive"], $tags);
    }

    /**
     * Edits an existing rule to be the same as another rule.
     *
     * @param Rule $mirrorTo
     * @return void
     * @throws Exception
     */
    public function mirrorRule(Rule $mirrorTo)
    {
        // Mirror tags
        $tags = [];
        $mirrorToCourse = $mirrorTo->getCourse();
        foreach ($this->getTags() as $tag) {
            $tag = new Tag($tag["id"]);
            $copiedTag = $tag->copyTag($mirrorToCourse);
            $tags[] = $copiedTag->getData();
        }

        // Mirror rule
        $ruleInfo = $this->getData();
        $mirrorTo->editRule($ruleInfo["name"], $ruleInfo["description"], $ruleInfo["whenClause"], $ruleInfo["thenClause"],
            $mirrorTo->getPosition(), $ruleInfo["isActive"], $tags);
    }

    /**
     * Deletes a rule from the Rule System.
     *
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    public static function deleteRule(int $ruleId)
    {
        $rule = self::getRuleById($ruleId);
        if ($rule) {
            $section = $rule->getSection();

            // Update position
            $position = $rule->getPosition();
            Utils::updateItemPosition($position, null, self::TABLE_RULE, "position", $ruleId, $section->getRules());

            // Remove rule text from section file
            $section->removeRuleText($position);

            // Delete rule from database
            Core::database()->delete(self::TABLE_RULE, ["id" => $ruleId]);
        }
    }

    /**
     * Checks whether rule exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Tags ----------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets rule's tags.
     *
     * @return array
     */
    public function getTags(): array
    {
        return Tag::getRuleTags($this->id);
    }

    /**
     * Adds a given tag to rule.
     *
     * @param int $tagId
     * @return void
     */
    public function addTag(int $tagId)
    {
        if (!$this->hasTag($tagId)) {
            Core::database()->insert(self::TABLE_RULE_TAGS, ["rule" => $this->id, "tag" => $tagId]);
            $this->setText($this->getText());
        }
    }

    /**
     * Removes a given tag from rule.
     *
     * @param int $tagId
     * @return void
     */
    public function removeTag(int $tagId)
    {
        if ($this->hasTag($tagId)) {
            Core::database()->delete(self::TABLE_RULE_TAGS, ["rule" => $this->id, "tag" => $tagId]);
            $this->setText($this->getText());
        }
    }

    /**
     * Checks whether rule has a given tag.
     *
     * @param int $tagId
     * @return bool
     */
    public function hasTag(int $tagId): bool
    {
        return !empty(Core::database()->select(self::TABLE_RULE_TAGS, ["rule" => $this->id, "tag" => $tagId]));
    }

    /**
     * Updates rule's tags in the database, without fully
     * replacing them.
     *
     * @param array $tags
     * @return void
     */
    private function updateTags(array $tags)
    {
        // Remove tags that got removed
        $oldTags = $this->getTags();
        foreach ($oldTags as $oldTag) {
            $exists = !empty(array_filter($tags, function ($tag) use ($oldTag) {
                return $tag["id"] && $tag["id"] == $oldTag["id"];
            }));
            if (!$exists) $this->removeTag($oldTag["id"]);
        }

        // Update tags
        foreach ($tags as $tag) {
            if (!$this->hasTag($tag["id"]))
                $this->addTag($tag["id"]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates rule parameters.
     *
     * @param $name
     * @param $when
     * @param $then
     * @param $isActive
     * @return void
     * @throws Exception
     */
    private static function validateRule($name, $when, $then, $isActive)
    {
        self::validateName($name);
        self::validateWhen($when);
        self::validateThen($then);

        if (!is_bool($isActive)) throw new Exception("'isActive' must be either true or false.");
    }

    /**
     * Validates rule name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Rule name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Rule name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Rule name is too long: maximum of 50 characters.");
    }

    /**
     * Validates rule when.
     *
     * @param $when
     * @return void
     * @throws Exception
     */
    private static function validateWhen($when)
    {
        if (!is_string($when) || empty(trim($when)))
            throw new Exception("Rule when clause can't be null neither empty.");

        // TODO: validate format (python code? libraries/functions exist? etc)
    }

    /**
     * Validates rule then.
     *
     * @param $then
     * @return void
     * @throws Exception
     */
    private static function validateThen($then)
    {
        if (!is_string($then) || empty(trim($then)))
            throw new Exception("Rule then clause can't be null neither empty.");

        // TODO: validate format (python code? libraries/functions exist? etc)
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Generates the rule text.
     *
     * @param string $name
     * @param string|null $description
     * @param string $when
     * @param string $then
     * @param bool $isActive
     * @param array $tags
     * @return string
     */
    public static function generateText(string $name, ?string $description, string $when, string $then, bool $isActive,
                                         array $tags = []): string
    {
        // Create from template
        $rule = file_get_contents(__DIR__ . "/rule_template.txt");

        // Fill-in rule name
        $rule = str_replace("<name>", $name, $rule);

        // Fill-in rule status
        $rule = $isActive ?
            preg_replace("/<status>\r*\n/", "", $rule) :
            str_replace("<status>", "INACTIVE", $rule);

        // Fill-in rule tags
        $rule = str_replace("<tags>", implode(", ", array_column($tags, "name")), $rule);

        // Fill-in rule description
        if ($description) {
            $lines = preg_split("/\r*\n/", $description);
            $rule = str_replace("<description>", implode("\n", array_map(function ($line, $index) {
                return "#" . ($index == 0 ? " " : "") . "$line";
            }, $lines, array_keys($lines))), $rule);

        } else $rule = preg_replace("/<description>\r*\n/", "", $rule);

        // Fill-in when clause
        $lines = preg_split("/\r*\n/", $when);
        $rule = str_replace("<when>", implode("\n", array_map(function ($line, $index) {
            return ($index != 0 ? "\t\t" : "") . "$line";
            }, $lines, array_keys($lines))), $rule);

        // Fill-in then clause
        $lines = preg_split("/\r*\n/", $then);
        $rule = str_replace("<then>", implode("\n", array_map(function ($line, $index) {
            return ($index != 0 ? "\t\t" : "") . "$line";
        }, $lines, array_keys($lines))), $rule);

        return $rule;
    }

    /**
     * Parses a rule coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $rule
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $rule = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "section", "position"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $rule, $field, $fieldName);
    }

    /**
     * Trims rule parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "description", "whenClause", "thenClause"];
        Utils::trim($params, ...$values);
    }
}
