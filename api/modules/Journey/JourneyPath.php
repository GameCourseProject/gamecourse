<?php
namespace GameCourse\Module\Journey;

use Exception;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Skills\Tier;
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
    const TABLE_JOURNEY_PROGRESSION = 'journey_progression';

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

    /**
     * Gets path rules.
     *
     * @return Rule
     */
    public function getRules(): array
    {
        $rules = [];
        foreach ($this->getSkills() as $skill) {
            $where = ["skill" => $skill["id"], "path" => $this->getId()];
            $ruleId = Core::database()->select(self::TABLE_JOURNEY_PATH_SKILLS, $where, "rule");
            $rules[] = Rule::getRuleById($ruleId);
        }
        return $rules;
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
            if ($oldStatus != $newStatus) {
                // Update rule status
                foreach ($this->getRules() as $rule) {
                    $rule->setActive($newStatus);
                }
            }
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_JOURNEY_PATH, $fieldValues, ["id" => $this->id]);
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
     * Option to include or not the reward value.
     *
     * @param int $pathId
     * @param bool|null $withReward
     * @return array
     * @throws Exception
     */
    public function getSkills(bool $withReward = false): array
    {
        $where = ["p.path" => $this->id];
        $skills = Core::database()->selectMultiple(self::TABLE_JOURNEY_PATH_SKILLS . " p LEFT JOIN " . Skills::TABLE_SKILL . " s on s.id=p.skill",
            $where, "s.*, p.reward", "p.position");
        foreach ($skills as &$skillInfo) {
            $skill = Skill::getSkillById($skillInfo["id"]);
            $skillInfo["page"] = $skill->getPage();
            $skillInfo["dependencies"] = $skill->getDependencies();
            $skillInfo = Skill::parse($skillInfo);
            if ($withReward) {
                if (!isset($skillInfo["reward"])) $skillInfo["reward"] = $skill->getTier()->getReward();
            }
        }
        return $skills;
    }

    /**
     * Gets total XP of path.
     * Option to include or not the reward value.
     *
     * @param int $pathId
     * @param bool|null $withReward
     * @return array
     * @throws Exception
     */
    public function getTotalXP(): int
    {
        $total = 0;
        $where = ["path" => $this->id];
        $skills = Core::database()->selectMultiple(self::TABLE_JOURNEY_PATH_SKILLS, $where, "skill");
        foreach ($skills as $skillInfo) {
            $skill = Skill::getSkillById($skillInfo["skill"]);
            $total += $skill->getTier()->getReward();
        }
        return $total;
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
     * @param array $skills
     * @return JourneyPath
     * @throws Exception
     */
    public static function addJourneyPath(int $courseId, string $name, string $color, array $skills): JourneyPath
    {
        self::trim($name);
        self::validateName($courseId, $name);
        $id = Core::database()->insert(self::TABLE_JOURNEY_PATH, [
            "course" => $courseId,
            "name" => $name,
            "color" => $color
        ]);
        $path = new JourneyPath($id);
        $path->setSkills($skills);
        return $path;
    }

    /**
     * Edits an existing path in the database.
     * Returns the edited path.
     *
     * @param string $name
     * @param string $color
     * @param bool $isActive
     * @param array $skills
     * @return JourneyPath
     * @throws Exception
     */
    public function editJourneyPath(string $name, string $color, bool $isActive, ?array $skills): JourneyPath
    {
        if (isset($skills)) $this->setSkills($skills);
        $this->setData([
            "name" => $name,
            "color" => $color,
            "isActive" => +$isActive
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
            $courseId = $path->getCourse()->getId();

            // Remove skill rules
            foreach ($path->getRules() as $rule) {
                self::removeRule($courseId, $rule->getId());
            }

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
    /*** ----------------------- Skills --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Sets path skills.
     *
     * @param array $skills
     * @return void
     * @throws Exception
     */
    public function setSkills(array $skills)
    {
        // Remove all skills
        foreach ($this->getSkills() as $skill) {
            $this->removeSkill($skill["id"]);
        }

        // Add new skills
        foreach ($skills as $index=>$skill) {
            $this->addSkill($index, $skill["id"], $skill["reward"]);
        }
    }

    /**
     * Adds a new skill to the path.
     *
     * @param int $position
     * @param int $skillId
     * @return void
     * @throws Exception
     */
    public function addSkill(int $position, int $skillId, ?int $reward)
    {
        $pathId = $this->getId();

        $skill = new Skill($skillId);
        $dependencies = Core::database()->selectMultiple(self::TABLE_JOURNEY_PATH_SKILLS, ["path" => $pathId], "skill", null, [], [["position", "<", $position]]);
        $rule = self::addRule($this->getCourse()->getId(), $pathId, $position, $skill->getName(), array_column($dependencies, "skill"));

        $data = ["skill" => $skillId, "path" => $pathId, "position" => $position, "rule" => $rule->getId()];
        if (isset($reward) && $reward != $skill->getReward()) $data["reward"] = $reward;

        Core::database()->insert(self::TABLE_JOURNEY_PATH_SKILLS, $data);
    }

    /**
     * Removes a skill from the path.
     *
     * @param int $skillId
     * @return void
     * @throws Exception
     */
    public function removeSkill(int $skillId)
    {
        $where = ["skill" => $skillId, "path" => $this->getId()];

        $ruleId = Core::database()->select(self::TABLE_JOURNEY_PATH_SKILLS, $where, "rule");
        self::removeRule($this->getCourse()->getId(), $ruleId);

        Core::database()->delete(self::TABLE_JOURNEY_PATH_SKILLS, $where);
    }

    /**
     * Returns bool indicating if path is complete by user or not
     *
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function isCompletedByUser(int $userId): bool
    {
        foreach($this->getSkills() as $skillInfo) {
            $skill = new Skill($skillInfo["id"]);
            if (!$skill->completedByUser($userId)) return false;
        }
        return true;
    }

    /**
     * Returns bool indicating if path is in progress by user or not
     *
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function isInProgressByUser(int $userId): bool
    {
        $res = false;
        $skills = $this->getSkills();

        foreach($skills as $index=>$skillInfo) {
            $skill = new Skill($skillInfo["id"]);
            if ($skill->completedByUser($userId)) {
                if ($index == count($skills) - 1) $res = false; // isn't in progress anymore, it was complete!
                else $res = true;
            }
        }
        return $res;
    }

    /**
     * Returns bool indicating if skill is available for user or not
     *
     * @param int $courseId
     * @param int $userId
     * @param int $pathId
     * @param int $skillId
     * @return bool
     * @throws Exception
     */
    public static function isSkillAvailableForUser(int $courseId, int $userId, int $pathId, int $skillId): bool
    {
        $path = self::getJourneyPathById($pathId);
        $skills = $path->getSkills();

        if ($path->isCompletedByUser($userId)) return true;
        else if ($path->isInProgressByUser($userId)) {
            $position = array_search($skillId, array_map(function ($s) {
                return $s["id"];
            }, $skills));
            if (isset($position)) {
                if ($position == 0) return true;
                $prevSkill = new Skill($skills[$position - 1]["id"]);
                return $prevSkill->completedByUser($userId);
            } else return false;
        }
        else {
            if ($skills[0]["id"] != $skillId) return false;

            $allPaths = self::getJourneyPaths($courseId);
            $pathsInProgress = array_filter($allPaths, function($e) use($userId) {
                $el = new JourneyPath($e["id"]);
                return $el->isInProgressByUser($userId) == true;
            });
            if (count($pathsInProgress) > 0) return false;

            $pathsCompleted = array_filter($allPaths, function($e) use($userId) {
                $el = new JourneyPath($e["id"]);
                return $el->isCompletedByUser($userId) == true;
            });
            $journeyModule = new Journey(new Course($courseId));
            if (count($pathsCompleted) > 0) return $journeyModule->getIsRepeatable();
            return true;
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Rules ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new skill rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param int $pathId
     * @param int $positionInPath
     * @param string $skillName
     * @param array $dependencies
     * @return Rule
     * @throws Exception
     */
    public static function addRule(int $courseId, int $pathId, int $positionInPath,
                                   string $skillName, array $dependencies): Rule
    {
        // Find rule position
        $position = self::findRulePosition($pathId, $positionInPath);

        // Add rule to skills section
        $journeyModule = new Journey(new Course($courseId));
        return $journeyModule->addRuleOfItem($position, $skillName, $dependencies);
    }

    /**
     * Deletes skill rule from the Rule System.
     *
     * @param int $courseId
     * @param int $ruleId
     * @return void
     * @throws Exception
     */
    private static function removeRule(int $courseId, int $ruleId)
    {
        $journeyModule = new Journey(new Course($courseId));
        $journeyModule->deleteRuleOfItem($ruleId);
    }

    /**
     * Generates skill rule parameters.
     *
     * @param string $skillName
     * @param array $dependencies
     * @return array
     */
    public static function generateRuleParams(string $skillName, array $dependencies): array
    {
        // Generate rule clauses
        $when = file_get_contents(__DIR__ . "/rules/when_template.txt");
        $then = file_get_contents(__DIR__ . "/rules/then_template.txt");

        // Fill-in skill name
        $when = str_replace("<skill-name>", $skillName, $when);
        $then = str_replace("<skill-name>", $skillName, $then);

        // Fill-in skill dependencies
        if (empty($dependencies)) {
            $when = preg_replace("/<skill-dependencies>(\r*\n)*/", "", $when);
            $then = str_replace(", <dependencies>", "", $then);

        } else {
            $dependenciesTxt = "";
            $conditions = [];

            // Generate dependencies text
            foreach ($dependencies as $skillId) {
                $conditions[] = "skill_completed(target, \"" . (new Skill($skillId))->getName() . "\")";
            }
            $dependenciesTxt .= "dependencies = " . implode(" and ", $conditions);

            // Fill-in dependencies
            $parts = explode("<skill-dependencies>", $when);
            array_splice($parts, 1, 0, $dependenciesTxt);
            $when = implode("", $parts);
            $then = str_replace("<dependencies>", "dependencies", $then);
        }

        return ["name" => $skillName . " (Journey)", "when" => $when, "then" => $then];
    }

    /**
     * Finds skill rule position based on skill position in a given path.
     *
     * @param int $tierId
     * @param int $positionInTier
     * @return int
     * @throws Exception
     */
    private static function findRulePosition(int $pathId, int $positionInPath): int
    {
        $paths = JourneyPath::getJourneyPaths((new JourneyPath($pathId))->getCourse()->getId());

        $position = 0;
        foreach ($paths as $p) {
            if ($p["id"] == $pathId) {
                $position += $positionInPath;
                break;
            }
            $path = new JourneyPath($p["id"]);
            $position += count($path->getSkills());
        }
        return $position;
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
