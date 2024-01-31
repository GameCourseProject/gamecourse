<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\AutoGame\RuleSystem\Rule;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\AutoGame\RuleSystem\Section;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Skill model, which implements the necessary methods
 * to interact with skills in the MySQL database.
 */
class Skill
{
    const TABLE_SKILL = "skill";
    const TABLE_SKILL_DEPENDENCY = "skill_dependency";
    const TABLE_SKILL_DEPENDENCY_COMBO = "skill_dependency_combo";
    const TABLE_SKILL_PROGRESSION = "skill_progression";

    const HEADERS = [   // headers for import/export functionality
        "name", "color", "page", "isCollab", "isExtra", "isActive", "position"
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

    public function getTier(): Tier
    {
        return new Tier($this->getData("tier"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getColor(): ?string
    {
        return $this->getData("color");
    }

    public function getPage(): ?string
    {
        $data = $this->getData("name, page");
        if ($data["page"]) {
            self::updatePageURLs($data["page"], $data["name"], $data["name"], "absolute");
            return $data["page"];
        }
        return null;
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function isCollab(): bool
    {
        return $this->getData("isCollab");
    }

    public function isExtra(): bool
    {
        return $this->getData("isExtra");
    }

    public function isActive(): bool
    {
        return $this->getData("isActive");
    }

    /**
     * Checks whether skill is wildcard.
     *
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->getTier()->isWildcard();
    }

    /**
     * Gets skill data from the database.
     *
     * @example getData() --> gets all skill data
     * @example getData("name") --> gets skill name
     * @example getData("name, color") --> gets skill name & color
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $table = self::TABLE_SKILL;
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
    public function setTier(int $tierId)
    {
        $this->setData(["tier" => $tierId]);
    }

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
    public function setColor(?string $color)
    {
        $this->setData(["color" => $color]);
    }

    /**
     * @throws Exception
     */
    public function setPage(?string $page)
    {
        $this->setData(["page" => $page]);
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
    public function setCollab(bool $isCollab)
    {
        $this->setData(["isCollab" => +$isCollab]);
    }

    /**
     * @throws Exception
     */
    public function setExtra(bool $isExtra)
    {
        $this->setData(["isExtra" => +$isExtra]);
    }

    /**
     * @throws Exception
     */
    public function setActive(bool $isActive)
    {
        $this->setData(["isActive" => +$isActive]);
    }

    /**
     * Sets skill data on the database.
     * @example setData(["name" => "New name"])
     * @example setData(["name" => "New name", "color" => "#ffffff"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        $course = $this->getCourse();
        $rule = $this->getRule();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("tier", $fieldValues)) {
            $newTier = new Tier($fieldValues["tier"]);
            self::validateTier($fieldValues["tier"]);
            $oldTier = $this->getTier();

            if ($newTier->getId() != $oldTier->getId()) {
                // Update skill position
                Utils::updateItemPosition($this->getPosition(), null, self::TABLE_SKILL, "position", $this->id, $oldTier->getSkills());
                unset($fieldValues["position"]);
            }
        }
        if (key_exists("name", $fieldValues)) {
            $newName = $fieldValues["name"];
            self::validateName($course->getId(), $newName, $this->id);
            $oldName = $this->getName();
        }
        if (key_exists("color", $fieldValues)) self::validateColor($fieldValues["color"]);
        if (key_exists("page", $fieldValues)) {
            self::validatePage($fieldValues["page"]);
            if (!is_null($fieldValues["page"])) {
                $oldSkillName = key_exists("name", $fieldValues) ? $oldName : $this->getName();
                $newSkillName = key_exists("name", $fieldValues) ? $newName : $oldSkillName;
                self::updatePageURLs($fieldValues["page"], $oldSkillName, $newSkillName, "relative");
            }
        }
        if (key_exists("position", $fieldValues)) {
            $newPosition = $fieldValues["position"];
            $oldPosition = $this->getPosition();
            Utils::updateItemPosition($oldPosition, $newPosition, self::TABLE_SKILL, "position", $this->id, $this->getTier()->getSkills());
        }
        if (key_exists("isActive", $fieldValues)) {
            $newStatus = $fieldValues["isActive"];
            $oldStatus = $this->isActive();
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_SKILL, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("tier", $fieldValues)) {
            if ($newTier->getId() != $oldTier->getId()) {
                // Update skill position
                $position = count($newTier->getSkills()) - 1;
                Utils::updateItemPosition(null, $position, self::TABLE_SKILL, "position", $this->id, $newTier->getSkills());

                // Update skill rule
                $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
                $isActive = key_exists("isActive", $fieldValues) ? $newStatus : $this->isActive();
                self::updateRule($course->getId(), $rule->getId(), self::findRulePosition($newTier->getId(), $position),
                    $isActive, $this->hasWildcardDependency(), $newTier->getSkillTree()->getId(), $name, array_map(function ($dependency) {
                        return array_column($dependency, "id");
                    }, $this->getDependencies())
                );
            }
        }
        if (key_exists("name", $fieldValues)) {
            if (strcmp($oldName, $newName) !== 0) {
                // Update skill folder
                rename($this->getDataFolder(true, $oldName), $this->getDataFolder(true, $newName));

                // Update skill rule name & other rules
                $rule->setName($newName);
                self::updateAllRules($this->getCourse()->getId());
            }
        }
        if (key_exists("position", $fieldValues)) {
            if ($newPosition != $oldPosition) {
                // Update skill rule position
                $name = key_exists("name", $fieldValues) ? $newName : $this->getName();
                $isActive = key_exists("isActive", $fieldValues) ? $newStatus : $this->isActive();
                self::updateRule($course->getId(), $rule->getId(), self::findRulePosition($this->getTier()->getId(), $newPosition),
                    $isActive, $this->hasWildcardDependency(), $this->getTier()->getSkillTree()->getId(), $name, array_map(function ($dependency) {
                        return array_column($dependency, "id");
                    }, $this->getDependencies())
                );
            }
        }
        if (key_exists("isActive", $fieldValues)) {
            if ($oldStatus != $newStatus) {
                if (!$newStatus) {
                    // Remove as dependency
                    $this->removeAsDependency();

                    // If no wildcard skills active, remove wildcard dependencies
                    if ($this->isWildcard()) {
                        $skillTreeId = $this->getTier()->getSkillTree()->getId();
                        $wildcardTier = Tier::getWildcard($skillTreeId);
                        if (empty($wildcardTier->getSkills(true)))
                            self::removeAllWildcardDependencies($skillTreeId);
                    }
                }

                // Update rule status
                $rule->setActive($newStatus);
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a skill by its ID.
     * Returns null if skill doesn't exist.
     *
     * @param int $id
     * @return Skill|null
     */
    public static function getSkillById(int $id): ?Skill
    {
        $skill = new Skill($id);
        if ($skill->exists()) return $skill;
        else return null;
    }

    /**
     * Gets a skill by its name.
     * Returns null if skill doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Skill|null
     */
    public static function getSkillByName(int $courseId, string $name): ?Skill
    {
        $skillId = intval(Core::database()->select(self::TABLE_SKILL, ["course" => $courseId, "name" => $name], "id"));
        if (!$skillId) return null;
        else return new Skill($skillId);
    }

    /**
     * Gets a skill by its position.
     * Returns null if skill doesn't exist.
     *
     * @param int $tierId
     * @param int $position
     * @return Skill|null
     */
    public static function getSkillByPosition(int $tierId, int $position): ?Skill
    {
        $skillId = intval(Core::database()->select(self::TABLE_SKILL, ["tier" => $tierId, "position" => $position], "id"));
        if (!$skillId) return null;
        else return new Skill($skillId);
    }

    /**
     * Gets a skill by its rule.
     * Returns null if skill doesn't exist.
     *
     * @param int $ruleId
     * @return Skill|null
     */
    public static function getSkillByRule(int $ruleId): ?Skill
    {
        $skillId = intval(Core::database()->select(self::TABLE_SKILL, ["rule" => $ruleId], "id"));
        if (!$skillId) return null;
        else return new Skill($skillId);
    }

    /**
     * Gets all skills of course.
     * Option for 'collab', 'extra', 'active' and ordering.
     *
     * @param int $courseId
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @param string $orderBy
     * @return array
     * @throws Exception
     */
    public static function getSkills(int $courseId, bool $active = null, bool $extra = null, bool $collab = null,
                                     string $orderBy = "name"): array
    {
        $where = ["course" => $courseId];
        if ($active !== null) $where["isActive"] = $active;
        if ($extra !== null) $where["isExtra"] = $extra;
        if ($collab !== null) $where["isCollab"] = $collab;
        $skills = Core::database()->selectMultiple(self::TABLE_SKILL, $where, "*", $orderBy);
        foreach ($skills as &$skillInfo) {
            $skill = self::getSkillById($skillInfo["id"]);
            $skillInfo["page"] = $skill->getPage();
            $skillInfo["dependencies"] = $skill->getDependencies();
            $skillInfo = self::parse($skillInfo);
        }
        return $skills;
    }

    /**
     * Gets all skills of a skill tree.
     * Option for 'collab', 'extra', 'active' and ordering.
     *
     * @param int $skillTreeId
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @param string $orderBy
     * @return array
     * @throws Exception
     */
    public static function getSkillsOfSkillTree(int $skillTreeId, bool $active = null, bool $extra = null,
                                                bool $collab = null, string $orderBy = "t.position, s.position"): array
    {
        $where = ["st.id" => $skillTreeId];
        if ($active !== null) $where["s.isActive"] = $active;
        if ($extra !== null) $where["s.isExtra"] = $extra;
        if ($collab !== null) $where["s.isCollab"] = $collab;
        $skills = Core::database()->selectMultiple(self::TABLE_SKILL . " s LEFT JOIN " . Tier::TABLE_SKILL_TIER .
            " t on s.tier=t.id LEFT JOIN " . SkillTree::TABLE_SKILL_TREE . " st on t.skillTree=st.id", $where, "s.*", $orderBy);
        foreach ($skills as &$skillInfo) {
            $skill = self::getSkillById($skillInfo["id"]);
            $skillInfo["page"] = $skill->getPage();
            $skillInfo["dependencies"] = $skill->getDependencies();
            $skillInfo = self::parse($skillInfo);
        }
        return $skills;
    }

    /**
     * Gets all skills of a tier.
     * Option for 'collab', 'extra', 'active' and ordering.
     *
     * @param int $tierId
     * @param bool|null $active
     * @param bool|null $extra
     * @param bool|null $collab
     * @param string $orderBy
     * @return array
     * @throws Exception
     */
    public static function getSkillsOfTier(int $tierId, bool $active = null, bool $extra = null, bool $collab = null,
                                           string $orderBy = "s.position"): array
    {
        $where = ["t.id" => $tierId];
        if ($active !== null) $where["s.isActive"] = $active;
        if ($extra !== null) $where["s.isExtra"] = $extra;
        if ($collab !== null) $where["s.isCollab"] = $collab;
        $skills = Core::database()->selectMultiple(self::TABLE_SKILL . " s LEFT JOIN " . Tier::TABLE_SKILL_TIER . " t on s.tier=t.id",
            $where, "s.*", $orderBy);
        foreach ($skills as &$skillInfo) {
            $skill = self::getSkillById($skillInfo["id"]);
            $skillInfo["page"] = $skill->getPage();
            $skillInfo["dependencies"] = $skill->getDependencies();
            $skillInfo = self::parse($skillInfo);
        }
        return $skills;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Skill Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a new skill to the database.
     * Returns the newly created skill.
     *
     * @param int $tierId
     * @param string $name
     * @param string|null $color
     * @param string|null $page
     * @param bool $isCollab
     * @param bool $isExtra
     * @param array $dependencies
     * @return Skill
     * @throws Exception
     */
    public static function addSkill(int $tierId, string $name, ?string $color, ?string $page, bool $isCollab,
                                    bool $isExtra, array $dependencies): Skill
    {
        self::trim($name, $color, $page);
        $tier = Tier::getTierById($tierId);
        $courseId = $tier->getCourse()->getId();
        self::validateSkill($courseId, $tierId, $name, $color, $page, $isCollab, $isExtra, $dependencies);
        $positionInTier = count($tier->getSkills());

        // Create skill rule
        $hasWildcardDependency = self::hasWildcardInDependencies($dependencies);
        $rule = self::addRule($courseId, $tierId, $positionInTier, $hasWildcardDependency, $name, $dependencies);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_SKILL, [
            "course" => $courseId,
            "tier" => $tierId,
            "name" => $name,
            "color" => $color,
            "isCollab" => +$isCollab,
            "isExtra" => +$isExtra,
            "position" => $positionInTier,
            "rule" => $rule->getId()
        ]);
        $skill = new Skill($id);

        // Set skill page & dependencies
        $skill->setPage($page);
        $skill->setDependencies($dependencies);

        // Create skill data folder
        self::createDataFolder($courseId, $name);

        return $skill;
    }

    /**
     * Edits an existing skill in the database.
     * Returns the edited skill.
     *
     * @param int $tierId
     * @param string $name
     * @param string|null $color
     * @param string|null $page
     * @param bool $isCollab
     * @param bool $isExtra
     * @param bool $isActive
     * @param int $position
     * @param array $dependencies
     * @return Skill
     * @throws Exception
     */
    public function editSkill(int $tierId, string $name, ?string $color, ?string $page, bool $isCollab, bool $isExtra,
                              bool $isActive, int $position, array $dependencies): Skill
    {
        $oldTier = $this->getTier();
        $this->setData([
            "tier" => $tierId,
            "name" => $name,
            "color" => $color,
            "page" => $page,
            "isCollab" => +$isCollab,
            "isExtra" => +$isExtra,
            "isActive" => +$isActive,
            "position" => $position
        ]);

        // If tier changed, remove invalid dependencies
        if ($tierId != $oldTier->getId()) {
            $newTier = new Tier($tierId);
            if ($newTier->getPosition() == 0 || $newTier->isWildcard()) $dependencies = [];
            else $dependencies = array_filter($dependencies, function ($combo) use ($newTier) {
                foreach ($combo as $skillId) {
                    $skillTier = (new Skill($skillId))->getTier();
                    if (!$skillTier->isWildcard() && $skillTier->getPosition() >= $newTier->getPosition()) return false;
                }
                return true;
            });
        }

        $this->setDependencies($dependencies);
        return $this;
    }

    /**
     * Copies an existing skill into another given tier.
     *
     * @param Tier $copyTo
     * @return void
     * @throws Exception
     */
    public function copySkill(Tier $copyTo): Skill
    {
        $skillInfo = $this->getData();

        // Copy dependencies
        $courseIdCopyTo = $copyTo->getCourse()->getId();
        $dependencies = array_map(function ($combo) use ($courseIdCopyTo) {
            foreach ($combo as &$skill) {
                if ($skill["name"] == Tier::WILDCARD) $skill = 0;
                else {
                    $s = Skill::getSkillByName($courseIdCopyTo, $skill["name"]);
                    if (!$s) throw new Exception("Skill '" . $skill["name"] . "' not found in course with ID = " . $courseIdCopyTo);
                    $skill = $s->getId();
                };
            }
            return $combo;
        }, $this->getDependencies());

        // Copy skill
        $copiedSkill = self::addSkill($copyTo->getId(), $skillInfo["name"], $skillInfo["color"], $skillInfo["page"],
            $skillInfo["isCollab"], $skillInfo["isExtra"], $dependencies);
        $copiedSkill->setActive($skillInfo["isActive"]);

        // Copy data folder
        Utils::copyDirectory($this->getDataFolder() . "/", $copiedSkill->getDataFolder() . "/");

        // Copy rule
        $this->getRule()->mirrorRule($copiedSkill->getRule());

        return $copiedSkill;
    }

    /**
     * Deletes a skill from the database.
     *
     * @param int $skillId
     * @return void
     * @throws Exception
     */
    public static function deleteSkill(int $skillId) {
        $skill = self::getSkillById($skillId);
        if ($skill) {
            $courseId = $skill->getCourse()->getId();

            if ($skill->isWildcard()) {
                // Disable wildcard skill
                // NOTE: needed to remove wildcard dependencies
                $skill->setActive(false);
            }

            // Remove skill data folder
            self::removeDataFolder($courseId, $skill->getName());

            // Update position
            $position = $skill->getPosition();
            Utils::updateItemPosition($position, null, self::TABLE_SKILL, "position", $skillId, $skill->getTier()->getSkills());

            // Remove as dependency of other skills
            $skill->removeAsDependency();

            // Remove skill rule
            self::removeRule($courseId, $skill->getRule()->getId());

            // Delete skill from database
            Core::database()->delete(self::TABLE_SKILL, ["id" => $skillId]);
        }
    }

    public static function setIsWildcard(int $skillId, bool $status){
        $table = self::TABLE_SKILL_DEPENDENCY_COMBO . " sdc JOIN " . self::TABLE_SKILL_DEPENDENCY .
            " sd on sdc.dependency=sd.id JOIN " . self::TABLE_SKILL . " s on sd.skill=s.id";
        $where = ["s.id" => $skillId];
        Core::database()->update($table, ["sdc.wildcard" => $status], $where);
    }

    /**
     * Checks whether skill exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Dependencies ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill dependencies.
     *
     * @return array
     * @throws Exception
     */
    public function getDependencies(): array
    {
        $skillTreeId = $this->getTier()->getSkillTree()->getId();
        $wildcardTier = Tier::getWildcard($skillTreeId);

        $dependencies = [];

        $dependencyIds = array_column(Core::database()->selectMultiple(self::TABLE_SKILL_DEPENDENCY, ["skill" => $this->id], "id", "id"), "id");
        foreach ($dependencyIds as $dependencyId) {
            $hasWildcard = false;
            $combo = array_map(function ($skillId) use (&$hasWildcard, $wildcardTier) {
                if ($skillId == 0) { // wildcard
                    $hasWildcard = true;
                    $skill = ["id" => 0, "tier" => $wildcardTier->getId(), "name" => Tier::WILDCARD];
                } else $skill = self::getSkillById($skillId)->getData();
                return self::parse($skill);
            }, self::getDependencyCombo($dependencyId));

            // Force wildcard in dependency to come last
            if ($hasWildcard) {
                for ($i = 0; $i < count($combo); $i++) {
                    $skill = $combo[$i];
                    if ($skill["name"] == Tier::WILDCARD) {
                        array_splice($combo, $i, 1);
                        $combo[] = $skill;
                    }
                }
            }

            $dependencies[$dependencyId] = $combo;
        }

        return $dependencies;
    }

    /**
     * Gets skill dependants.
     *
     * @return array
     * @throws Exception
     */
    public function getDependants(): array
    {
        $dependants = [];

        $dependantsIds = array_map(function ($dependencyId) {
            return Core::database()->select(self::TABLE_SKILL_DEPENDENCY, ["id" => $dependencyId], "skill");
        }, array_column(Core::database()->selectMultiple(self::TABLE_SKILL_DEPENDENCY_COMBO, ["skill" => $this->id], "dependency"), "dependency"));
        foreach ($dependantsIds as $dependantId) {
            $dependant = self::getSkillById($dependantId);
            $dependants[] = self::parse($dependant->getData());
        }

        return $dependants;
    }

    /**
     * Sets skill dependencies.
     *
     * @param array $dependencies
     * @return void
     * @throws Exception
     */
    public function setDependencies(array $dependencies)
    {
        self::validateDependencies($this->getTier(), $dependencies);

        // Remove all skill dependencies
        foreach ($this->getDependencies() as $dependencyId => $combo) {
            $this->removeDependency($dependencyId);
        }

        // Add new dependencies
        foreach ($dependencies as $combo) {
            $this->addDependency($combo);
        }
    }

    /**
     * Adds a new dependency to skill.
     *
     * @param array $combo
     * @return void
     * @throws Exception
     */
    public function addDependency(array $combo)
    {
        $dependencies = array_map(function ($dependency) {
            return array_column($dependency, "id");
        }, $this->getDependencies());
        $dependencies[] = $combo;
        self::validateDependencies($this->getTier(), $dependencies);

        if (!$this->hasDependency(null, $combo)) {
            $dependencyId = Core::database()->insert(self::TABLE_SKILL_DEPENDENCY, ["skill" => $this->id]);
            foreach ($combo as $skillId) {
                $where = ["dependency" => $dependencyId];
                if ($skillId == 0) // wildcard
                    $where["wildcard"] = true;
                else $where["skill"] = $skillId;
                Core::database()->insert(self::TABLE_SKILL_DEPENDENCY_COMBO, $where);
            }

            // Update skill rule
            $rule = $this->getRule();
            self::updateRule($this->getCourse()->getId(), $rule->getId(), $rule->getPosition(), $rule->isActive(),
                $this->hasWildcardDependency(), $this->getTier()->getSkillTree()->getId(), $this->getName(),
                array_map(function ($dependency) {
                    return array_column($dependency, "id");
                }, $this->getDependencies())
            );
        }
    }

    /**
     * Removes dependency from skill.
     *
     * @param int $dependencyId
     * @return void
     * @throws Exception
     */
    public function removeDependency(int $dependencyId)
    {
        Core::database()->delete(self::TABLE_SKILL_DEPENDENCY, ["id" => $dependencyId, "skill" => $this->id]);

        // Update skill rule
        $rule = $this->getRule();
        self::updateRule($this->getCourse()->getId(), $rule->getId(), $rule->getPosition(), $rule->isActive(),
            $this->hasWildcardDependency(), $this->getTier()->getSkillTree()->getId(), $this->getName(),
            array_map(function ($dependency) {
                return array_column($dependency, "id");
            }, $this->getDependencies())
        );
    }

    /**
     * Removes skill as a dependency of other skills.
     *
     * @return void
     * @throws Exception
     */
    public function removeAsDependency()
    {
        $dependants = $this->getDependants();
        foreach ($dependants as $dependant) {
            $dependant = self::getSkillById($dependant["id"]);
            $dependencyIds = array_column(Core::database()->selectMultiple(self::TABLE_SKILL_DEPENDENCY, ["skill" => $dependant->getId()]), "id");
            foreach ($dependencyIds as $dependencyId) {
                $combo = self::getDependencyCombo($dependencyId);
                if (in_array($this->id, $combo))
                    $dependant->removeDependency($dependencyId);
            }
        }
    }

    /**
     * Removes all wildcard dependencies from skill.
     *
     * @return void
     * @throws Exception
     */
    public function removeWildcardDependencies()
    {
        $dependencies = $this->getDependencies();
        foreach ($dependencies as $dependencyId => $combo) {
            foreach ($combo as $skill) {
                if ($skill["id"] == 0) { // wildcard dependency
                    $this->removeDependency($dependencyId);
                    break;
                }
            }
        }
    }

    /**
     * Checks whether skill has a given dependency.
     *
     * @param int|null $dependencyId
     * @param array|null $combo
     * @return bool
     * @throws Exception
     */
    public function hasDependency(int $dependencyId = null, array $combo = null): bool
    {
        if ($dependencyId === null && $combo === null)
            throw new Exception("Need either dependency ID or dependency combo IDs to check whether a skill has a given dependency.");

        if ($dependencyId)
            return !empty(Core::database()->select(self::TABLE_SKILL_DEPENDENCY, ["id" => $dependencyId, "skill" => $this->id]));

        if (empty($combo))
            throw new Exception("Combo is empty: can't check whether skill has dependency.");

        $dependencyIds = array_column(Core::database()->selectMultiple(self::TABLE_SKILL_DEPENDENCY, ["skill" => $this->id], "id"), "id");
        foreach ($dependencyIds as $dependencyId) {
            $depCombo = self::getDependencyCombo($dependencyId);
            $hasCombo = true;
            foreach ($depCombo as $skillId) {
                if (!in_array($skillId, $combo)) {
                    $hasCombo = false;
                    break;
                }
            }
            if ($hasCombo) return true;
        }
        return false;
    }

    /**
     * Checks whether skill has at least one wildcard dependency.
     *
     * @return bool
     * @throws Exception
     */
    public function hasWildcardDependency(): bool
    {
        $dependencies = array_map(function ($dependency) {
            return array_column($dependency, "id");
        }, $this->getDependencies());
        return self::hasWildcardInDependencies($dependencies);
    }

    /**
     * Gets a dependency combo of a given dependency ID.
     *
     * @param int $dependencyId
     * @return array
     */
    private static function getDependencyCombo(int $dependencyId): array
    {
        $comboInfo = Core::database()->selectMultiple(self::TABLE_SKILL_DEPENDENCY_COMBO . " s JOIN " .
            self::TABLE_SKILL_DEPENDENCY . " d on d.id=s.dependency", ["d.id" => $dependencyId], "s.skill, s.wildcard");

        $combo = [];
        foreach ($comboInfo as $info) {
            $skillId = $info["wildcard"] ? 0 : intval($info["skill"]);
            $combo[] = $skillId;
        }
        return $combo;
    }

    /**
     * Removes all wildcard dependencies in the Skill Tree.
     *
     * @param int $skillTreeId
     * @return void
     * @throws Exception
     */
    private static function removeAllWildcardDependencies(int $skillTreeId)
    {
        $skills = Skill::getSkillsOfSkillTree($skillTreeId);
        foreach ($skills as $skill) {
            $skill = new Skill($skill["id"]);
            $skill->removeWildcardDependencies();
        }
    }

    /**
     * Checks whether there's at least one wildcard dependency
     * in an array of dependencies.
     *
     * @example hasWildcardInDependencies([[1, 2], [1, 0], [2, 0]]) --> true
     * @example hasWildcardInDependencies([[1, 2]]) --> false
     *
     * @param array $dependencies
     * @return bool
     */
    private static function hasWildcardInDependencies(array $dependencies): bool
    {
        foreach ($dependencies as $combo) {
            foreach ($combo as $skillId) {
                if ($skillId == 0) return true;
            }
        }
        return false;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Cost ----------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill cost in tokens for a given user.
     *
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public function getSkillCostForUser(int $userId): int
    {
        $course = $this->getCourse();
        $skillsModule = new Skills($course);
        $skillsModule->checkDependency(VirtualCurrency::ID);

        // Get tier cost info
        $tier = $this->getTier();
        $tierInfo = $tier->getData("costType, cost, increment, minRating");

        // Get skill cost for user
        if ($tierInfo["costType"] === "fixed") return $tierInfo["cost"];

        $name = $this->getName();
        $nrAttempts = count(array_filter(AutoGame::getParticipations($course->getId(), $userId, "graded post"),
            function ($item) use ($name, $tierInfo) {
                return $item["description"] === "Skill Tree, Re: $name" && $item["rating"] >= $tierInfo["minRating"];
        }));

        if ($tierInfo["costType"] === "exponential") return $tierInfo["cost"] + ($nrAttempts > 0 ? $tierInfo["increment"] * (2 ** $nrAttempts) : 0);

        return $tierInfo["cost"] + $tierInfo["increment"] * $nrAttempts;
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Rules ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill rule.
     *
     * @return Rule
     */
    public function getRule(): Rule
    {
        return Rule::getRuleById($this->getData("rule"));
    }

    /**
     * Adds a new skill rule to the Rule System.
     * Returns the newly created rule.
     *
     * @param int $courseId
     * @param int $tierId
     * @param int $positionInTier
     * @param bool $hasWildcardDependency
     * @param string $skillName
     * @param array $dependencies
     * @return Rule
     * @throws Exception
     */
    public static function addRule(int $courseId, int $tierId, int $positionInTier, bool $hasWildcardDependency,
                                    string $skillName, array $dependencies): Rule
    {
        // Find rule position
        $position = self::findRulePosition($tierId, $positionInTier);

        // Add rule to skills section
        $skillTreeId = (new Tier($tierId))->getSkillTree()->getId();
        $skillsModule = new Skills(new Course($courseId));
        return $skillsModule->addRuleOfItem($position, $hasWildcardDependency, $skillTreeId, $skillName, $dependencies);
    }

    /**
     * Updates all skill rules in the Rule System.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    private static function updateAllRules(int $courseId)
    {
        $skillRules = Section::getSectionByName($courseId, Skills::RULE_SECTION)->getRules();
        foreach ($skillRules as $r) {
            $skill = self::getSkillByRule($r["id"]);
            self::updateRule($courseId, $r["id"], $r["position"], $r["isActive"], $skill->hasWildcardDependency(),
                $skill->getTier()->getSkillTree()->getId(), $skill->getName(), array_map(function ($dependency) {
                    return array_column($dependency, "id");
                }, $skill->getDependencies())
            );
        }
    }

    /**
     * Updates skill rule in the Rule System.
     *
     * @param int $courseId
     * @param int $ruleId
     * @param int $position
     * @param bool $isActive
     * @param bool $hasWildcardDependency
     * @param int $skillTreeId
     * @param string $skillName
     * @param array $dependencies
     * @return void
     * @throws Exception
     */
    private static function updateRule(int $courseId, int $ruleId, int $position, bool $isActive, bool $hasWildcardDependency,
                                       int $skillTreeId, string $skillName, array $dependencies)
    {
        $skillsModule = new Skills(new Course($courseId));
        $skillsModule->updateRuleOfItem($ruleId, $position, $isActive, $hasWildcardDependency, $skillTreeId, $skillName, $dependencies);
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
        $skillsModule = new Skills(new Course($courseId));
        $skillsModule->deleteRuleOfItem($ruleId);
    }

    /**
     * Generates skill rule parameters.
     *
     * @param bool $isWildcard
     * @param int $skillTreeId
     * @param string $skillName
     * @param array $dependencies
     * @return array
     */
    public static function generateRuleParams(bool $isWildcard, int $skillTreeId, string $skillName, array $dependencies): array
    {
        // Generate rule clauses
        $when = file_get_contents(__DIR__ . "/rules/" . ($isWildcard ? "wildcard" : "basic") . "/when_template.txt");
        $then = file_get_contents(__DIR__ . "/rules/" . ($isWildcard ? "wildcard" : "basic") . "/then_template.txt");

        // Fill-in skill name
        $when = str_replace("<skill-name>", $skillName, $when);
        $then = str_replace("<skill-name>", $skillName, $then);

        if ($isWildcard) {
            // Fill-in skill tree ID
            $when = str_replace("<skill-tree-ID>", $skillTreeId, $when);

            // Fill-in wildcard tier name
            $when = str_replace("<wildcard-tier-name>", "\"" . Tier::WILDCARD . "\"", $when);
        }

        // Fill-in skill dependencies
        if (empty($dependencies)) {
            $when = preg_replace("/<skill-dependencies>(\r*\n)*/", "", $when);
            $then = str_replace(", <dependencies>", "", $then);

        } else {
            $dependenciesTxt = "";
            $conditions = [];
            $skillBasedConditions = [];
            $comboNr = 1;

            // Generate dependencies text
            foreach ($dependencies as $dependency) {
                $combo = [];
                foreach ($dependency as $skillId) {
                    $combo[] = $skillId == 0 ? "wildcard" : "skill_completed(target, \"" . (new Skill($skillId))->getName() . "\")";
                }
                $dependenciesTxt .= "combo" . $comboNr . " = " . implode(" and ", $combo) . "\n";
                $conditions[] = "combo" . $comboNr;
                if (!in_array("wildcard", $combo)) $skillBasedConditions[] = "combo" . $comboNr;
                $comboNr++;
            }
            $dependenciesTxt .= "dependencies = " . implode(" or ", $conditions);

            // Fill-in dependencies
            $parts = explode("<skill-dependencies>", $when);
            array_splice($parts, 1, 0, $dependenciesTxt);
            $when = implode("", $parts);
            $then = str_replace("<dependencies>", "dependencies", $then);

            if ($isWildcard) {
                // Fill-in skill based
                $skillBased = !empty($skillBasedConditions) ? implode(" or ", $skillBasedConditions) : "False";
                $when = str_replace("<skill-based>", $skillBased, $when);
            }
        }

        return ["name" => $skillName, "when" => $when, "then" => $then];
    }

    /**
     * Finds skill rule position based on skill position in a given tier.
     *
     * @param int $tierId
     * @param int $positionInTier
     * @return int
     * @throws Exception
     */
    private static function findRulePosition(int $tierId, int $positionInTier): int
    {
        $tier = new Tier($tierId);
        $skillTreeId = $tier->getSkillTree()->getId();

        if ($tier->isWildcard()) return $positionInTier; // wildcard skills come first

        $position = 0;
        $tiers = Tier::getTiersOfSkillTree($skillTreeId);
        foreach ($tiers as $t) {
            if ($t["id"] == $tierId) {
                $position += $positionInTier + count(Tier::getWildcard($skillTreeId)->getSkills());
                break;
            }
            $position += count(self::getSkillsOfTier($t["id"]));
        }
        return $position;
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Skill Data -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets skill data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @param string|null $skillName
     * @return string
     */
    public function getDataFolder(bool $fullPath = true, string $skillName = null): string
    {
        if (!$skillName) $skillName = $this->getName();
        $skillsModule = new Skills($this->getCourse());
        return $skillsModule->getDataFolder($fullPath) . "/" . Utils::strip($skillName, "_");
    }

    /**
     * Gets skill data folder contents.
     *
     * @return array
     * @throws Exception
     */
    public function getDataFolderContents(): array
    {
        return Utils::getDirectoryContents($this->getDataFolder());

    }

    /**
     * Creates a data folder for a given skill. If folder exists, it
     * will delete its contents.
     *
     * @param int $courseId
     * @param string $skillName
     * @return string
     * @throws Exception
     */
    public static function createDataFolder(int $courseId, string $skillName): string
    {
        $dataFolder = self::getSkillByName($courseId, $skillName)->getDataFolder();
        if (file_exists($dataFolder)) self::removeDataFolder($courseId, $skillName);
        mkdir($dataFolder, 0777, true);
        return $dataFolder;
    }

    /**
     * Deletes a given skills's data folder.
     *
     * @param int $courseId
     * @param string $skillName
     * @return void
     * @throws Exception
     */
    public static function removeDataFolder(int $courseId, string $skillName)
    {
        $dataFolder = self::getSkillByName($courseId, $skillName)->getDataFolder();
        if (file_exists($dataFolder)) Utils::deleteDirectory($dataFolder);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @param int $courseId
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importSkills(int $courseId, string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/skills.zip";
        Utils::uploadFile($tempFolder, $contents, "skills.zip");
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "skills.zip");

        $file = file_get_contents($tempFolder ."/skills.csv");
        $ruleFile = file_get_contents($tempFolder ."/skillsRules.csv");
        $nrSkillsImported = Utils::importFromCSV(self::HEADERS, function($skill, $indexes) use ($courseId, $replace, $ruleFile) {
            $name = Utils::nullify($skill[$indexes["name"]]);
            $color = Utils::nullify($skill[$indexes["color"]]);
            $page = Utils::nullify($skill[$indexes["page"]]);
            $isCollab = self::parse(null, Utils::nullify($skill[$indexes["isCollab"]]), "isCollab");
            $isExtra = self::parse(null, Utils::nullify($skill[$indexes["isExtra"]]), "isExtra");
            $isActive = self::parse(null, Utils::nullify($skill[$indexes["isActive"]]), "isActive");
            $position = self::parse(null, Utils::nullify($skill[$indexes["position"]]), "position");

            $skill = self::getSkillByName($courseId, $name);
            if ($skill) { // skill already exists
                if ($replace){ // replace
                    $tierId = $skill->getTier()->getId();
                    $dependencies = $skill->getDependencies();
                    // both tierId and dependencies are not something to be edited through import
                    $skill->editSkill($tierId, $name, $color, $page, $isCollab, $isExtra, $isActive, $position, $dependencies);

                    if ($ruleFile){
                        Rule::importRules($courseId, Section::getSectionIdByModule($courseId, Skills::ID), $ruleFile, $replace);
                    }

                }
            } else { // skill doesn't exist
                self::addSkill();  // FIXME which tier should it be added to?

                if ($ruleFile){
                    Rule::importRules($courseId, Section::getSectionIdByModule($courseId, Skills::ID), $ruleFile, $replace);
                }
                return 1;
            }
            return 0;
        }, $file);

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrSkillsImported;
    }

    /**
     * @param int $courseId
     * @param bool $replace
     * @param string $skillsFile
     * @param string $skillDependenciesFile
     * @param string $skillsRulesFile
     * @param string|null $skillsAndTiersFile
     * @param SkillTree|null $skillTree
     * @return int
     * @throws Exception
     */
    public static function importSkillsActions(int $courseId, bool $replace, string $skillsFile, string $skillDependenciesFile,
                                               string $skillsRulesFile, string $skillsAndTiersFile = null,
                                               SkillTree $skillTree = null): int {

        // Prepares relation between tiers and skills
        if (isset($skillsAndTiersFile)){
            $skillsAndTiers = [];
            Utils::importFromCSV(["tier", "skill"], function ($element, $indexes) use (&$skillsAndTiers) {
                $tier = Utils::nullify($element[$indexes["tier"]]);
                $skills = Utils::nullify($element[$indexes["skill"]]);

                $skillsArray = explode(", ", $skills);
                array_push($skillsAndTiers, ["tier" => $tier, "skill" => $skillsArray]);
            }, $skillsAndTiersFile);
        }

        // Prepare relation between skills and rules
        $skillsRules = [];
        Rule::importRulesActions($courseId, $replace, $skillsRulesFile, $skillsRules);

        // Prepare relation between skills and dependencies
        $skillDependencies = [];
        Utils::importFromCSV(["name", "dependencies", "isWildcard"], function($skillDependency, $indexes) use (&$skillDependencies) {
            $name = $skillDependency[$indexes["name"]];
            $dependencies = $skillDependency[$indexes["dependencies"]];
            $isWildcard = $skillDependency[$indexes["isWildcard"]];

            $dependenciesArray = explode(", ", $dependencies);
            array_push($skillDependencies, ["name" => $name, "dependencies" => $dependenciesArray, "isWildcard" => $isWildcard]);

        }, $skillDependenciesFile);

        // Import skills
        $mySkills = [];
        $nrSkillsImported = Utils::importFromCSV(Skill::HEADERS, function ($skill, $indexes) use ($courseId, $replace,
            $skillsAndTiers, $skillTree, $skillsRules, &$mySkills) {
            $name = Utils::nullify($skill[$indexes["name"]]);
            $color = Utils::nullify($skill[$indexes["color"]]);
            $page = Utils::nullify(self::parseToExportAndImport($skill[$indexes["page"]], "import"));
            $isCollab = self::parse(null, $skill[$indexes["isCollab"]], "isCollab");
            $isExtra = self::parse(null, $skill[$indexes["isExtra"]], "isExtra");
            $isActive = self::parse(null, $skill[$indexes["isActive"]], "isActive");
            $position = self::parse(null, $skill[$indexes["position"]], "position");

            // only works with importation of skillTree
            if (isset($skillTree)){
                $skillTreeId = $skillTree->getId();
            } else {
                // TODO -- where to store skills? (in which skillTree)
                // COMPLETE AND UNCOMMENT
                // $skillTreeId = ...
                // $skillTree = new SkillTree($skillTreeId);
            }
            $skills = Skill::getSkillsOfSkillTree($skillTreeId);

            $flagFound = false;
            // See if there's a skill inside the skillTree
            foreach ($skills as $skillElement) {
                if ($skillElement["name"] === $name){ // skill already exists
                    $skill = Skill::getSkillById($skillElement["id"]);
                    $flagFound = true;
                    if ($replace){ // replace

                        if ($skillsAndTiers) {
                            // Only used when importing entire skillTree
                            $skillsTiersIndex = null;
                            foreach ($skillsAndTiers as $index => $element) {
                                if (isset($element["skill"]) && is_array($element["skill"]) && in_array($name, $element["skill"])) {
                                    $skillsTiersIndex = $index;
                                    break;
                                }
                            }
                            $tierName = $skillsAndTiers[$skillsTiersIndex]["tier"];

                        } else {
                            // TODO -- where to store skills? (in which tier)
                            // $tierName = ...
                        }

                        $tierId = Tier::getTierByName($skillTree->getId(), $tierName)->getId();

                        // Dependencies will be dealt later, after all skills have been imported, to avoid non-existing skill errors
                        $skill->editSkill($tierId, $name, $color, $page, $isCollab, $isExtra, $isActive, $position, []);
                        array_push($mySkills, $skill->getId());

                        // edit its rule with the new information
                        $ruleIndex = array_search($name, array_column($skillsRules, 'name'));

                        $rule = $skillsRules[$ruleIndex]["rule"];
                        $ruleToEdit = Rule::getRuleByName($courseId, $skill->getName());
                        $ruleToEdit->editRule($rule["name"], $rule["description"],
                            $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                            Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"),
                            []
                        );
                    }
                    break;
                }
            }

            if (!$flagFound) { // skill doesn't exist

                // We need to see if there's already a rule with the skill's name in the system before importing new skill
                $ruleIndex = array_search($name, array_column($skillsRules, 'name'));
                $rule = $skillsRules[$ruleIndex]["rule"];

                $sectionId = Section::getSectionIdByModule($courseId, Skills::ID);
                $sectionRulesNames = array_map(function ($sectionRule) {return $sectionRule["name"];}, Rule::getRulesOfSection($sectionId));
                // rule already exists in system
                if (in_array($rule["name"], $sectionRulesNames)){
                    // remove existing rule first
                    $ruleToDelete = Rule::getRuleByName($courseId, $rule["name"]);
                    Rule::deleteRule($ruleToDelete->getId());
                }

                if (isset($skillsAndTiers)){
                    $skillsTiersIndex = null;
                    foreach ($skillsAndTiers as $index => $element) {
                        if (isset($element["skill"]) && is_array($element["skill"]) && in_array($name, $element["skill"])) {
                            $skillsTiersIndex = $index;
                            break;
                        }
                    }
                    $tierName = $skillsAndTiers[$skillsTiersIndex]["tier"];

                } else {
                    // TODO -- where to store skills? (in which tier)
                }

                $tierId = Tier::getTierByName($skillTree->getId(), $tierName)->getId();
                // Add new skill (will add rule with template text)
                // Dependencies will be dealt later, after all skills have been imported, to avoid non-existing skill errors
                $skill = Skill::addSkill($tierId, $name, $color, $page, $isCollab, $isExtra, []);
                array_push($mySkills, $skill->getId());

                // Edit rule with imported information
                $ruleToEdit = Rule::getRuleByName($courseId, $rule["name"]);
                $ruleToEdit->editRule($skill->getName(), $rule["description"],
                    $rule["whenClause"], $rule["thenClause"], intval($rule["position"]),
                    Rule::parse(null, Utils::nullify($rule["isActive"]), "isActive"), []
                );
                return 1;
            }
            return 0;
        }, $skillsFile);

        // FIXME -- To be tested - dont delete
        /*foreach ($mySkills as $skillElement){
            $mySkill = new Skill($skillElement);

            $skillDependenciesArray = [];
            $index = null;

            $matchingDependencies = array_filter($skillDependencies, function ($element) use ($mySkill) {
                return isset($element["dependencies"])
                    && is_array($element["dependencies"])
                    && $element["name"] == $mySkill->getName();
            });

            if (!empty($matchingDependencies)) {
                $element = reset($matchingDependencies); // Get the first matching element
                $index = key($matchingDependencies); // Get the index of the first matching element

                $skillDependenciesArray = array_map(function ($dependency) use ($courseId) {
                    return Skill::getSkillByName($courseId, $dependency)->getData();
                }, $element["dependencies"]);
            }
        }*/

        //$mySkill->setDependencies($skillDependenciesArray);
        //$mySkill->setIsWildcard($mySkill->getId(), Skill::parse(null, $skillDependencies[$index]["isWildcard"], "isWildcard"));

        return $nrSkillsImported;
    }

    /**
     * Exports skills to a .zip file.
     *
     * @param int $courseId
     * @param array $skillIds
     * @return array
     * @throws Exception
     */
    public static function exportSkills(int $courseId, array $skillIds): array
    {
        $course = new Course($courseId);

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store badges' info
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/" . Utils::strip($course->getShort() ?? $course->getName(), '_') . "-skills.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        // Add skills .csv file
        $skillsToExport = array_values(array_filter(self::getSkills($courseId), function ($skill) use ($skillIds) {
            return in_array($skill["id"], $skillIds); }));
        self::exportSkillsActions($courseId, $skillsToExport, $zip);

        $zip->close();
        return ["extension" => ".zip", "path" => str_replace(ROOT_PATH, API_URL . "/", $zipPath)];
    }

    /**
     * Function where the exporting actually takes place.
     * Also exports dependencies between skills.
     * SkillTree.php uses this function to export relation between skills and tiers.
     *
     * @param int $courseId
     * @param array $skills
     * @param ZipArchive $zip
     * @param array|null $skillsAndTiers
     * @return void
     * @throws Exception
     */
    public static function exportSkillsActions(int $courseId, array $skills, ZipArchive &$zip, array &$skillsAndTiers = null) {
        $skillDependencies = [];
        // Exports skills and prepares relation between skills and its dependencies
        // (also prepares relation between skills and tiers when exporting entire skillTree)
        $zip->addFromString("skills.csv", Utils::exportToCSV($skills, function ($skill) use (&$skillDependencies, &$skillsAndTiers) {
            if (is_array($skillsAndTiers)){
                $tierName = Tier::getTierById($skill["tier"])->getName();
                $tierSkillIndex = array_search($tierName, array_column($skillsAndTiers, 'tier'));

                if ($skillsAndTiers[$tierSkillIndex]["skill"] == null ){
                    $skillsAndTiers[$tierSkillIndex]["skill"] = $skill["name"];

                } else {
                    $skillsAndTiers[$tierSkillIndex]["skill"] .=  ", " . $skill["name"];
                }
            }

            $skillElement =  Skill::getSkillById($skill["id"]);
            $skillDependencies[] = ["name" => $skillElement->getName(), "dependencies" => null, "isWildcard" =>
                Skill::parse(null, $skillElement->isWildcard(), "isWildcard")];

            $dependencyNames = [];
            $dependencies =  $skillElement->getDependencies();
            foreach ($dependencies as $dependencyPair){
                foreach ($dependencyPair as $dependency){
                    $dependencyNames[] = $dependency["name"];
                }
            }

            if (!empty($dependencyNames)) {
                $lastIndex = count($skillDependencies) - 1;
                $skillDependencies[$lastIndex]["dependencies"] = implode(", ", $dependencyNames);
            }

            if (($skill["page"]) !== null) $page = self::parseToExportAndImport($skill["page"], "export");
            else $page = "";

            return [$skill["name"], $skill["color"], $page, +$skill["isCollab"], +$skill["isExtra"], +$skill["isActive"], $skill["position"]];
        }, self::HEADERS));

        // Exports dependencies
        $zip->addFromString("skillDependencies.csv", Utils::exportToCSV($skillDependencies, function ($skillDependency) {
            return [$skillDependency["name"], $skillDependency["dependencies"], +$skillDependency["isWildcard"]];
        }, ["name", "dependencies", "isWildcard"]));

        // Exports skills' rules
        $skillsSection = RuleSystem::getSectionIdByModule($courseId, Skills::ID);
        $skillRules = Rule::getRulesOfSection($skillsSection);
        $zip->addFromString("skillsRules.csv", Rule::exportRulesActions($skillRules));

    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates skill parameters.
     *
     * @param int $courseId
     * @param int $tierId
     * @param $name
     * @param $color
     * @param $page
     * @param $isCollab
     * @param $isExtra
     * @param $dependencies
     * @return void
     * @throws Exception
     */
    private static function validateSkill(int $courseId, int $tierId, $name, $color, $page, $isCollab, $isExtra, $dependencies)
    {
        self::validateTier($tierId);
        self::validateName($courseId, $name);
        self::validateColor($color);
        self::validatePage($page);
        self::validateDependencies(new Tier($tierId), $dependencies);
        if (!is_bool($isCollab)) throw new Exception("'isCollab' must be either true or false.");
        if (!is_bool($isExtra)) throw new Exception("'isExtra' must be either true or false.");
    }

    /**
     * Validates skill tier.
     *
     * @param $tierId
     * @return void
     * @throws Exception
     */
    private static function validateTier(int $tierId)
    {
        $tier = Tier::getTierById($tierId);
        if (!$tier) throw new Exception("Tier  with ID = " . $tierId . " doesn't exist.");
    }

    /**
     * Validates skill name.
     *
     * @param int $courseId
     * @param $name
     * @param int|null $skillId
     * @return void
     * @throws Exception
     */
    private static function validateName(int $courseId, $name, int $skillId = null)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Skill name can't be null neither empty.");

        preg_match("/[^\w()&\s-]/u", $name, $matches);
        if (count($matches) != 0)
            throw new Exception("Skill name '" . $name . "' is not allowed. Allowed characters: alphanumeric, '_', '(', ')', '-', '&'");

        if (iconv_strlen($name) > 50)
            throw new Exception("Skill name is too long: maximum of 50 characters.");

        $whereNot = [];
        if ($skillId) $whereNot[] = ["id", $skillId];
        $skillNames = array_column(Core::database()->selectMultiple(self::TABLE_SKILL, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $skillNames))
            throw new Exception("Duplicate skill name: '$name'");
    }

    /**
     * Validates skill color.
     *
     * @throws Exception
     */
    private static function validateColor($color)
    {
        if (is_null($color)) return;

        if (!Utils::isValidColor($color, "HEX"))
            throw new Exception("Skill color needs to be in HEX format.");
    }

    /**
     * Validates skill page.
     *
     * @throws Exception
     */
    private static function validatePage($page)
    {
        if (is_null($page)) return;

        if (!is_string($page) || empty(trim($page)))
            throw new Exception("Skill page can't be empty.");
    }

    /**
     * Validates skill dependencies.
     *
     * @param Tier $tier
     * @param array $dependencies
     * @return void
     *
     * @throws Exception
     */
    private static function validateDependencies(Tier $tier, array $dependencies)
    {
        if ($tier->isWildcard() && !empty($dependencies))
            throw new Exception("A wilcard skill can't have dependencies.");

        if ($tier->getPosition() == 0 && !empty($dependencies))
            throw new Exception("The first tier can't have dependencies.");

        foreach ($dependencies as $combo) {
            $invalidFormat = false;
            if (!is_array($combo)) $invalidFormat = true;

            if (!$invalidFormat) {
                foreach ($combo as $skillId) {
                    if (!is_numeric($skillId)) {
                        $invalidFormat = true;
                        break;
                    }
                }
            }

            if ($invalidFormat)
                throw new Exception("Invalid combo format. Format must be: [<skillID>, <skillID>, ...]");
        }

        $wildcardTier = Tier::getWildcard($tier->getSkillTree()->getId());
        if (self::hasWildcardInDependencies($dependencies) && empty($wildcardTier->getSkills(true)))
            throw new Exception("Can't add wildcard dependency on skill: there's no wildcard skills active.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function parseToExportAndImport(string $text, string $mode): string {
        if ($mode == "export") {
            $res = str_replace(["\"", "\'", '"'], '\quote\\', $text);
            return str_replace("\n", '\newline\\', $res);
        } else if ($mode == "import"){
            $res = str_replace('\quote\\', "\"", $text);
            return str_replace('\newline\\', "\n", $res);
        } else return $text;
    }

    /**
     * Updates all page URLs:
     *  - swaps skill URLs from absolute to relative or vice-versa
     *  - updates skill URLs folder name
     *
     * @param string $page
     * @param string $oldName
     * @param string $newName
     * @param string $swapTo
     * @return void
     */
    private function updatePageURLs(string &$page, string $oldName, string $newName, string $swapTo) {
        // Swap page URLs
        $courseId = $this->getCourse()->getId();
        $page = preg_replace_callback("/src=[\"'](.*?)[\"']/", function ($matches) use ($courseId, $swapTo) {
            return "src=\"" . Course::transformURL($matches[1], $swapTo, $courseId) . "\"";
        }, $page);

        // Update skill name on path
        $courseDataFolder = API_URL . "/" . $this->getCourse()->getDataFolder(false);
        $oldPath = $courseDataFolder . "/" . $this->getDataFolder(false, $oldName);
        $newPath = $courseDataFolder . "/" . $this->getDataFolder(false, $newName);
        $page = str_replace($oldPath, $newPath, $page);
    }

    /**
     * Parses a skill coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $skill
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $skill = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "tier", "position", "rule"];
        $boolValues = ["isCollab", "isExtra", "isActive", "isWildcard"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $skill, $field, $fieldName);
    }

    /**
     * Trims skill parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "color", "page"];
        Utils::trim($params, ...$values);
    }
}
