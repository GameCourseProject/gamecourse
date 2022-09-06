<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\XPLevels\XPLevels;
use Utils\Utils;

/**
 * This is the Skills module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Skills extends Module
{
    const TABLE_SKILL_TREE = SkillTree::TABLE_SKILL_TREE;
    const TABLE_SKILL_TIER = Tier::TABLE_SKILL_TIER;
    const TABLE_SKILL = Skill::TABLE_SKILL;
    const TABLE_SKILL_DEPENDENCY = Skill::TABLE_SKILL_DEPENDENCY;
    const TABLE_SKILL_DEPENDENCY_COMBO = Skill::TABLE_SKILL_DEPENDENCY_COMBO;
    const TABLE_AWARD_WILDCARD = "award_wildcard";
    const TABLE_SKILL_CONFIG = 'skills_config';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Skills";  // NOTE: must match the name of the class
    const NAME = "Skills";
    const DESCRIPTION = "Gives the ability to create skill trees where students have to complete skills in order to achieve higher tiers.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];

    const DATA_FOLDER = 'skills';
    const RULE_SECTION = "Skills";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initDatabase();
        $this->createDataFolder();
        $this->initRules();

        // Init config
        Core::database()->insert(self::TABLE_SKILL_CONFIG, ["course" => $this->course->getId()]);
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
        $this->removeDataFolder();
        $this->removeRules();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getGeneralInputs(): array
    {
        return [
            ["id" => "maxExtraCredit", "label" => "Max. Extra Credit", "type" => InputType::NUMBER, "value" => $this->getMaxExtraCredit()]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGeneralInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            if ($input["id"] == "maxExtraCredit") $this->updateMaxExtraCredit($input["value"]);
        }
    }

    public function getLists(): array
    {
        $skillsTrees = SkillTree::getSkillTrees($this->course->getId());
        $lists = [[ // Skill Trees
            "listName" => "Skill Trees",
            "itemName" => "skill tree",
            "listActions" => [
                Action::NEW,
                Action::IMPORT,
                Action::EXPORT
            ],
            "listInfo" => [
                ["id" => "name", "label" => "Name", "type" => InputType::TEXT],
                ["id" => "maxReward", "label" => "Max. Reward", "type" => InputType::NUMBER]
            ],
            "items" => $skillsTrees,
            "actions" => [
                ["action" => Action::VIEW, "scope" => ActionScope::ALL],
                ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                ["action" => Action::DELETE, "scope" => ActionScope::ALL],
                ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
            ],
            Action::EDIT => [
                ["id" => "name", "label" => "Name", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                ["id" => "maxReward", "label" => "Max. Reward", "type" => InputType::NUMBER, "scope" => ActionScope::ALL]
            ],
            Action::IMPORT => [
                "extensions" => [".zip"]
            ]
        ]];

        foreach ($skillsTrees as $i => $skillTree) {
            $getListName = function (string $name) use ($skillsTrees, $skillTree, $i) {
                return (count($skillsTrees) > 1 ? (($skillTree["name"] ? $skillTree["name"] : ("Skill Tree #" . ($i + 1))) . " - ") : "") . $name;
            };

            $lists[] = [ // Tiers
                "listName" => $getListName("Tiers"),
                "itemName" => "tier",
                "parent" => $skillTree["id"],
                "listActions" => [
                    Action::NEW,
                    Action::IMPORT,
                    Action::EXPORT
                ],
                "listInfo" => [
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT],
                    ["id" => "reward", "label" => "Reward", "type" => InputType::NUMBER],
                    ["id" => "isActive", "label" => "Active", "type" => InputType::TOGGLE]
                ],
                "items" => Tier::getTiersOfSkillTree($skillTree["id"]),
                "actions" => [
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL_BUT_LAST],
                    ["action" => Action::MOVE_UP, "scope" => ActionScope::ALL_BUT_FIRST_AND_LAST],
                    ["action" => Action::MOVE_DOWN, "scope" => ActionScope::ALL_BUT_TWO_LAST],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL_BUT_LAST]
                ],
                Action::EDIT => [
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT, "scope" => ActionScope::ALL_BUT_LAST],
                    ["id" => "reward", "label" => "Reward", "type" => InputType::NUMBER, "scope" => ActionScope::ALL]
                ],
                Action::IMPORT => [
                    "extensions" => [".zip"]
                ]
            ];
        }

        return $lists;
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item)
    {
        $courseId = $this->course->getId();
        if ($listName == "Skill Trees") {   // Skill Trees
            if ($action == Action::NEW) SkillTree::addSkillTree($courseId, $item["name"] ?? null, $item["maxReward"]);
            elseif ($action == Action::EDIT) {
                $skillTree = new SkillTree($item["id"]);
                $skillTree->editSkillTree($item["name"], $item["maxReward"]);
            } elseif ($action == Action::DELETE) SkillTree::deleteSkillTree($item["id"]);

        } else if (Utils::strEndsWith($listName, "Tiers")) {    // Tiers
            if ($action == Action::NEW) Tier::addTier($item["parent"], $item["name"], $item["reward"]);
            elseif ($action == Action::EDIT || $action == Action::MOVE_UP || $action == Action::MOVE_DOWN) {
                $tier = new Tier($item["id"]);
                $position = $item["position"] + ($action == Action::EDIT ? 0 : ($action == Action::MOVE_UP ? -1 : 1));
                $tier->editTier($item["name"], $item["reward"], $position, $item["isActive"]);
            } elseif ($action == Action::DELETE) Tier::deleteTier($item["id"]);
        }
    }

    public function getPersonalizedConfig(): ?string
    {
        return $this->id;
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Rule System ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    protected function generateRuleParams(...$args): array
    {
        return Skill::generateRuleParams(...$args);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxExtraCredit(): int
    {
        return intval(Core::database()->select(self::TABLE_SKILL_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit"));
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(int $max)
    {
        $xpLevels = $this->course->getModuleById(XPLevels::ID);
        if ($xpLevels && $xpLevels->isEnabled()) {
            $generalMax = $xpLevels->getMaxExtraCredit();
            if ($max > $generalMax)
                throw new Exception("Skills max. extra credit cannot be bigger than " . $generalMax . " (general max. extra credit).");
        }

        Core::database()->update(self::TABLE_SKILL_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }


    /*** ---------- Skills ---------- ***/

    /**
     * Gets users who have completed a given skill.
     *
     * @param int $skillId
     * @return array
     * @throws Exception
     */
    public function getUsersWithSkill(int $skillId): array
    {
        $users = [];
        $skill = Skill::getSkillById($skillId);
        if ($skill) {
            $skillTreeId = $skill->getTier()->getSkillTree()->getId();
            foreach ($this->getCourse()->getStudents() as $student) {
                $completedSkills = $this->getUserSkills($student["id"], $skillTreeId);
                foreach ($completedSkills as $skill) {
                    if ($skill["id"] == $skillId) $users[] = $student;
                }
            }
        }
        return $users;
    }

    /**
     * Gets skills completed by a given user on a specific Skill Tree.
     * NOTE: only returns skills that are currently active.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @param bool|null $isExtra
     * @param bool|null $isCollab
     * @return array
     * @throws Exception
     */
    public function getUserSkills(int $userId, int $skillTreeId, bool $isExtra = null, bool $isCollab = null): array
    {
        $course = $this->getCourse();
        $awardsModule = new Awards($course);
        $userSkillAwards = $awardsModule->getUserSkillsAwards($userId, $isCollab, $isExtra);

        // Get skill info
        $skills = [];
        foreach ($userSkillAwards as $award) {
            $skill = new Skill($award["moduleInstance"]);

            // Filter by skill tree
            if ($skill->getTier()->getSkillTree()->getId() != $skillTreeId)
                continue;

            $skills[] = $skill->getData();
        }
        return $skills;
    }


    /*** -------- Wildcards --------- ***/

    /**
     * Gets total number of wildcards available to use by a given
     * user on a Skill Tree.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return int
     * @throws Exception
     */
    public function getUserTotalAvailableWildcards(int $userId, int $skillTreeId): int
    {
        $completedWildcards = $this->getUserTotalCompletedWildcards($userId, $skillTreeId);
        $usedWildcards = $this->getUserTotalUsedWildcards($userId, $skillTreeId);
        return $completedWildcards - $usedWildcards;
    }

    /**
     * Gets total number of wildcard skills completed by a given
     * user on a Skill Tree.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return int
     * @throws Exception
     */
    public function getUserTotalCompletedWildcards(int $userId, int $skillTreeId): int
    {
        $nrCompletedWildcards = 0;

        $userSkillAwards = (new Awards($this->course))->getUserSkillsAwards($userId);
        foreach ($userSkillAwards as $award) {
            // Filter by skill tree and wildcard skillls
            $skill = new Skill($award["moduleInstance"]);
            if ($skill->getTier()->getSkillTree()->getId() != $skillTreeId || !$skill->isWildcard())
                continue;

            $nrCompletedWildcards++;
        }

        return $nrCompletedWildcards;
    }

    /**
     * Gets total number of wildcards already used by a given
     * user on a Skill Tree.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return int
     * @throws Exception
     */
    public function getUserTotalUsedWildcards(int $userId, int $skillTreeId): int
    {
        $nrUsedWildcards = 0;

        $userSkillAwards = (new Awards($this->course))->getUserSkillsAwards($userId);
        foreach ($userSkillAwards as $award) {
            // Filter by skill tree
            $skill = new Skill($award["moduleInstance"]);
            if ($skill->getTier()->getSkillTree()->getId() != $skillTreeId)
                continue;

            $nrWildcardsUsed = intval(Core::database()->select(self::TABLE_AWARD_WILDCARD, [
                "user" => $userId, "course" => $this->getCourse()->getId(), "award" => $award["id"]
            ], "nrWildcardsUsed"));
            $nrUsedWildcards += $nrWildcardsUsed;
        }

        return $nrUsedWildcards;
    }

    /**
     * Checks whether a given user has at least one wildcard
     * available to use on a Skill Tree.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return bool
     * @throws Exception
     */
    public function userHasWildcardAvailable(int $userId, int $skillTreeId): bool
    {
        return $this->getUserTotalAvailableWildcards($userId, $skillTreeId) > 0;
    }
}
