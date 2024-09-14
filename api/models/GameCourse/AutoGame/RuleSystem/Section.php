<?php
namespace GameCourse\AutoGame\RuleSystem;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use Utils\Utils;

/**
 * This is the Section model, which implements the necessary methods
 * to interact with rule sections in the Rule System.
 */
class Section
{
    const TABLE_RULE_SECTION = "rule_section";

    const MISCELLANEOUS_SECTION = "Miscellaneous";
    const GRAVEYARD_SECTION = "Graveyard";
    const RULE_DIVIDER = "#########";

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

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function getModule(): ?Module
    {
        $moduleId = $this->getData("module");
        if ($moduleId) return $this->getCourse()->getModuleById($moduleId);
        return null;
    }

    public function getFile(bool $fullPath = true, string $name = null, int $priority = null): string
    {
        $courseId = $this->getCourse()->getId();
        if (is_null($name)) $name = $this->getName();
        if (is_null($priority)) $priority = $this->getPosition() + 1;
        return RuleSystem::getDataFolder($courseId, $fullPath) . "/" . $priority . "-" . Utils::strip($name, "_") . ".txt";
    }

    public function isActive(): bool {
        return $this->getData("isActive");
    }

    /**
     * Gets section data from the database.
     *
     * @example getData() --> gets all section data
     * @example getData("name") --> gets section name
     * @example getData("name, position") --> gets section name & position
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_RULE_SECTION;
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
    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    /**
     * @throws Exception
     */
    public function setModule(?Module $module)
    {
        $this->setData(["module" => $module ? $module->getId() : null]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive) {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets section data on the database.
     *
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "position" => 1])
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
        if (key_exists("name", $fieldValues)) {
            $newName = trim($fieldValues["name"]);
            self::validateName($courseId, $newName, $this->id);
            $oldName = $this->getName();
        }
        if (key_exists("position", $fieldValues)) {
            $newPosition = $fieldValues["position"];
            $oldPosition = $this->getPosition();
            Utils::updateItemPosition($oldPosition, $newPosition, self::TABLE_RULE_SECTION, "position",
                $this->id, self::getSections($this->getCourse()->getId()), function ($sectionId, $oldPosition, $newPosition) {
                    $section = new Section($sectionId);
                    $name = $section->getName();
                    rename($section->getFile(true, $name, $oldPosition + 1), $section->getFile(true, $name, $newPosition + 1));
                });
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_RULE_SECTION, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("name", $fieldValues)) {
            // Update section file name if name has changed
            if (strcmp($oldName, $newName) !== 0) {
                $position = key_exists("position", $fieldValues) ? $oldPosition : $this->getPosition();
                rename($this->getFile(true, $oldName, ($position ?? -1) + 1), $this->getFile(true, $newName, ($position ?? -1) + 1));
            }
        }
        if (key_exists("position", $fieldValues)) {
            // Update section file name if position has changed
            if ($newPosition !== $oldPosition) {
                $name = $newName ?? $this->getName();
                rename($this->getFile(true, $name, ($oldPosition ?? -1) + 1), $this->getFile(true, $name, ($newPosition ?? -1) + 1));
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a section by its ID.
     * Returns null if section doesn't exist.
     *
     * @param int $id
     * @return Section|null
     */
    public static function getSectionById(int $id): ?Section
    {
        $section = new Section($id);
        if ($section->exists()) return $section;
        else return null;
    }

    public static function getSectionIdByModule(int $courseId, string $moduleId): int {
        $table = self::TABLE_RULE_SECTION;
        $where = ["course" => $courseId, "module" => $moduleId];
        return intval(Core::database()->select($table, $where, "id"));
    }

    /**
     * Gets a section by its name.
     * Returns null if section doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Section|null
     */
    public static function getSectionByName(int $courseId, string $name): ?Section
    {
        $sectionId = intval(Core::database()->select(self::TABLE_RULE_SECTION, ["course" => $courseId, "name" => $name], "id"));
        if (!$sectionId) return null;
        else return new Section($sectionId);
    }

    /**
     * Gets a section by its position.
     * Returns null if section doesn't exist.
     *
     * @param int $courseId
     * @param int $position
     * @return Section|null
     */
    public static function getSectionByPosition(int $courseId, int $position): ?Section
    {
        $sectionId = intval(Core::database()->select(self::TABLE_RULE_SECTION, ["course" => $courseId, "position" => $position], "id"));
        if (!$sectionId) return null;
        else return new Section($sectionId);
    }

    /**
     * Gets sections in the Rule System for a given course.
     *
     * @param int $courseId
     * @return array
     */
    public static function getSections(int $courseId): array
    {
        $sections = Core::database()->selectMultiple(self::TABLE_RULE_SECTION, ["course" => $courseId], "*", "position");
        foreach ($sections as &$section) { $section = self::parse($section); }
        return $sections;
    }

    /**
     * Gets id of section "Graveyard" (aka section where orphan rules are)
     *
     * @param int $courseId
     * @return int
     */
    public static function getGraveyardSectionId(int $courseId): int {
        return Core::database()->select(self::TABLE_RULE_SECTION, ["course" => $courseId, "name" => self::GRAVEYARD_SECTION], "id");
    }

    public static function getRulesInGraveyard(int $courseId, int $graveyardSectionId): array {
        return Core::database()->selectMultiple(Rule::TABLE_RULE, ["course" => $courseId, "section" => $graveyardSectionId]);
    }

    /*** ---------------------------------------------------- ***/
    /*** --------------- Section Manipulation --------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a section to the Rule System.
     * Returns the newly created section.
     *
     * @param int $courseId
     * @param string $name
     * @param int|null $position
     * @param string|null $moduleId
     * @param bool $isActive
     * @return Section
     * @throws Exception
     */
    public static function addSection(int $courseId, string $name, int $position = null, string $moduleId = null, bool $isActive = true): Section
    {
        self::trim($name);
        self::validateSection($courseId, $name);

        // Insert in database
        if (is_null($position)) $position = 0;
        $id = Core::database()->insert(self::TABLE_RULE_SECTION, [
            "course" => $courseId,
            "name" => $name,
            "module" => $moduleId,
            "isActive" => +$isActive
        ]);
        Utils::updateItemPosition(null, $position, self::TABLE_RULE_SECTION, "position", $id,
            self::getSections($courseId), function ($sectionId, $oldPosition, $newPosition) {
                $section = new Section($sectionId);
                $name = $section->getName();
                rename($section->getFile(true, $name, $oldPosition + 1), $section->getFile(true, $name, $newPosition + 1));
            });

        $section = new Section($id);

        // Create section file
        file_put_contents($section->getFile(true, $name, $position + 1), "");

        return $section;
    }

    /**
     * Edits an existing section in the Rule System.
     * Returns the edited section.
     *
     * @param string $name
     * @param int $position
     * @param bool $isActive
     * @return Section
     * @throws Exception
     */
    public function editSection(string $name, int $position, bool $isActive): Section
    {
        $this->setData([
            "name" => $name,
            "position" => $position,
            "isActive" => +$isActive
        ]);
        return $this;
    }

    /**
     * Copies an existing section into another given course.
     *
     * @param Course $copyTo
     * @return void
     * @throws Exception
     */
    public function copySection(Course $copyTo): Section
    {
        // Copy section
        $copiedSection = self::addSection($copyTo->getId(), $this->getName(), $this->getPosition(),
            $this->getModule() ? $this->getModule()->getId() : null, $this->isActive());

        // Copy rules
        foreach ($this->getRules() as $rule) {
            $rule = new Rule($rule["id"]);
            $rule->copyRule($copiedSection);
        }

        return $copiedSection;
    }

    /**
     * Deletes a section from the Rule System.
     *
     * @param int $sectionId
     * @return void
     * @throws Exception
     */
    public static function deleteSection(int $sectionId)
    {
        $section = self::getSectionById($sectionId);
        if ($section) {
            // Remove section file
            unlink($section->getFile());

            // Update position
            $position = $section->getPosition();
            Utils::updateItemPosition($position, null, self::TABLE_RULE_SECTION, "position", $sectionId,
                self::getSections($section->getCourse()->getId()), function ($sectionId, $oldPosition, $newPosition) {
                    $section = new Section($sectionId);
                    $name = $section->getName();
                    rename($section->getFile(true, $name, $oldPosition + 1), $section->getFile(true, $name, $newPosition + 1));
                });

            // Delete section from database
            Core::database()->delete(self::TABLE_RULE_SECTION, ["id" => $sectionId]);
        }
    }

    /**
     * Checks whether section exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }

    /**
     * Makes rules orphan ("Graveyard" section means parent module has been disabled and rules are inactive)
     *
     * @param int $courseId
     * @param string $moduleId
     * @return void
     * @throws Exception
     */
    public static function moveRulesToGraveyard(int $courseId, string $moduleId) {
        $graveyardSectionId = self::getGraveyardSectionId($courseId);

        $table = Rule::TABLE_RULE . " r JOIN " . self::TABLE_RULE_SECTION .
            " s on r.section=s.id WHERE s.module=\"$moduleId\" and r.course=\"$courseId\" and s.course=\"$courseId\"";

        $rules = array_map(function($rule) { return intval($rule["id"]); }, Core::database()->selectMultiple($table, [], "r.id"));
        $nrRules = count(self::getRulesInGraveyard($courseId, $graveyardSectionId));

        foreach ($rules as $rule) {
            Core::database()->update(Rule::TABLE_RULE, ["section" => $graveyardSectionId, "position" => $nrRules], ["id" => $rule]);
            $nrRules = $nrRules + 1;
        }
    }

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets section's roles.
     * @return array
     */
    public function getRoles(): array
    {
        return Role::getSectionRoles($this->id);
    }

    /**
     * Replaces section's roles in the database.
     *
     * @param array $rolesNames
     * @return void
     * @throws Exception
     */
    public function setRoles(array $rolesNames)
    {
        Role::setSectionRoles($this->id, $this->getCourse()->getId(), $rolesNames);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Rules ----------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets section rules, ordered by priority.
     *
     * @param bool|null $active
     * @return array
     * @throws Exception
     */
    public function getRules(bool $active = null): array
    {
        return Rule::getRulesOfSection($this->id, $active);
    }

    /**
     * Adds a rule to section on a specific position.
     * If no position is given, it will add at the beginning.
     *
     * @param string $name
     * @param string|null $description
     * @param string $when
     * @param string $then
     * @param int|null $position
     * @param bool $isActive
     * @param array $tags
     * @return Rule
     * @throws Exception
     */
    public function addRule(string $name, ?string $description, string $when, string $then, int $position = null,
                            bool $isActive = true, array $tags = []): Rule
    {
        $courseId = $this->getCourse()->getId();
        return Rule::addRule($courseId, $this->id, $name, $description, $when, $then, $position ?? 0, $isActive, $tags);
    }

    /**
     * Removes rule from section.
     *
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    public function removeRule(int $ruleId)
    {
        Rule::deleteRule($ruleId);
    }

    /**
     * Adds rule text to section file in a given position.
     *
     * @param string $text
     * @param int $position
     * @return void
     */
    public function addRuleText(string $text, int $position)
    {
        // Get rules text
        $sectionFile = $this->getFile();
        $rulesText = $this->splitRules(file_get_contents($sectionFile));

        // Add new rule text & update section file
        array_splice($rulesText, $position, 0, $text);
        file_put_contents($sectionFile, $this->joinRules($rulesText));
    }

    /**
     * Updates rule text in section file.
     *
     * @param string $text
     * @param int $position
     * @return void
     */
    public function updateRuleText(string $text, int $position)
    {
        // Get rules text
        $sectionFile = $this->getFile();
        $rulesText = $this->splitRules(file_get_contents($sectionFile));

        // Update rule text & update section file
        $rulesText[$position] = $text;
        file_put_contents($sectionFile, $this->joinRules($rulesText));
    }

    /**
     * Removes rule text from section file.
     *
     * @param int $position
     * @return void
     */
    public function removeRuleText(int $position)
    {
        // Get rules text
        $sectionFile = $this->getFile();
        $rulesText = $this->splitRules(file_get_contents($sectionFile));

        // Remove rule text & update section file
        array_splice($rulesText, $position, 1);
        file_put_contents($sectionFile, $this->joinRules($rulesText));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates section parameters.
     *
     * @param int $courseId
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateSection(int $courseId, $name)
    {
        self::validateName($courseId, $name);
    }

    /**
     * Validates section name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $sectionId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $sectionId = null)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Section name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Section name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Section name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($sectionId) $whereNot[] = ["id", $sectionId];
        $sectionNames = array_column(Core::database()->selectMultiple(self::TABLE_RULE_SECTION, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $sectionNames))
            throw new Exception("Duplicate section name: '$name'");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Splits rules text by divider.
     *
     * @param string $text
     * @return array
     */
    private function splitRules(string $text): array
    {
        $rulesText = array_map(function ($rule) { return trim($rule); }, explode(self::RULE_DIVIDER, $text));
        if (count($rulesText) == 1 && empty($rulesText[0])) return [];
        return $rulesText;
    }

    /**
     * Joins rules text with divider.
     *
     * @param array $rulesText
     * @return string
     */
    private function joinRules(array $rulesText): string
    {
        return implode("\n\n" . self::RULE_DIVIDER . "\n\n", $rulesText);
    }

    /**
     * Parses a section coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $section
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $section = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "position"];
        $boolValues = ["isActive"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $section, $field, $fieldName);
    }

    /**
     * Trims section parameters' whitespace at start/end.
     *
     * @return void
     */
    private static function trim(mixed &...$values)
    {
        $params = ["name"];
        Utils::trim($params, ...$values);
    }
}
