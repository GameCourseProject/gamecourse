<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\XPLevels\XPLevels;

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
    const NAME = "Skill Tree";
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
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Skills($copyTo);

        // Copy config
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);

        // Copy skill trees
        $skillTrees = SkillTree::getSkillTrees($this->course->getId(), "id");
        foreach ($skillTrees as $skillTree) {
            $skillTree = new SkillTree($skillTree["id"]);
            $skillTree->copySkillTree($copyTo);
        }
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
            [
                "name" => "General",
                "contents" => [
                    [
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxExtraCredit",
                                "value" => $this->getMaxExtraCredit(),
                                "placeholder" => "Skills max. extra credit",
                                "options" => [
                                    "topLabel" => "Max. extra credit",
                                    "required" => true,
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum extra credit students can earn with skills"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "minRating",
                                "value" => $this->getMinRating(),
                                "placeholder" => "Skills min. rating",
                                "options" => [
                                    "topLabel" => "Min. rating",
                                    "required" => true,
                                    "minValue" => 0
                                ],
                                "helper" => "Minimum rating for a skill to be awarded"
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGeneralInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            if ($input["id"] == "maxExtraCredit") $this->updateMaxExtraCredit($input["value"]);
            if ($input["id"] == "minRating") $this->updateMinRating($input["value"]);
        }
    }

    public function getLists(): array
    {
        $skillsTrees = SkillTree::getSkillTrees($this->course->getId());
        return [
            [
                "name" => "Skill Trees",
                "itemName" => "skill tree",
                "topActions" => [
                    "left" => [
                        ["action" => Action::IMPORT, "icon" => "jam-download"],
                        ["action" => Action::EXPORT, "icon" => "jam-upload"]
                    ],
                    "right" => [
                        ["action" => Action::NEW, "icon" => "feather-plus-circle", "color" => "primary"]
                    ]
                ],
                "headers" => [
                    ["label" => "Name", "align" => "middle"],
                    ["label" => "Max. Reward", "align" => "middle"]
                ],
                "data" => array_map(function ($skillTree) {
                    return [
                        ["type" => DataType::TEXT, "content" => ["text" => $skillTree["name"], "classList" => "font-semibold"]],
                        ["type" => DataType::NUMBER, "content" => ["value" => $skillTree["maxReward"], "valueFormat" => "default"]]
                    ];
                }, $skillsTrees),
                "actions" => [
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
                ],
                "options" => [
                    "order" => [[0, "asc"]],
                    "columnDefs" => [
                        ["type" => "natural", "targets" => [0, 1]]
                    ]
                ],
                "items" => $skillsTrees,
                Action::NEW => [
                    "modalSize" => "md",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "name",
                                    "placeholder" => "Skill tree name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for skill tree"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "maxReward",
                                    "placeholder" => "Skill tree maximum reward",
                                    "options" => [
                                        "topLabel" => "Max. reward",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Maximum total reward that can be earned with skill tree"
                                ]
                            ]
                        ]
                    ]
                ],
                Action::EDIT => [
                    "modalSize" => "md",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "name",
                                    "placeholder" => "Skill tree name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for skill tree"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "maxReward",
                                    "placeholder" => "Skill tree maximum reward",
                                    "options" => [
                                        "topLabel" => "Max. reward",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Maximum total reward that can be earned with skill tree"
                                ]
                            ]
                        ]
                    ]
                ],
                Action::IMPORT => [
                "extensions" => [".zip"]
            ]
            ]
        ];
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
        Core::database()->update(self::TABLE_SKILL_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }

    public function getMinRating(): int
    {
        return intval(Core::database()->select(self::TABLE_SKILL_CONFIG, ["course" => $this->course->getId()], "minRating"));
    }

    /**
     * @throws Exception
     */
    public function updateMinRating(int $minRating)
    {
        Core::database()->update(self::TABLE_SKILL_CONFIG, ["minRating" => $minRating], ["course" => $this->course->getId()]);
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
                "award" => $award["id"]
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
