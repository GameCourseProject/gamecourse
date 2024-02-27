<?php
namespace GameCourse\Module\Skills;

use Exception;
use GameCourse\AutoGame\AutoGame;
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
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\NotificationSystem\Notification;

/**
 * This is the Skills module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Skills extends Module
{
    const TABLE_SKILL_TREE = SkillTree::TABLE_SKILL_TREE;
    const TABLE_SKILL_TIER = Tier::TABLE_SKILL_TIER;
    const TABLE_SKILL_TIER_COST = Tier::TABLE_SKILL_TIER_COST;
    const TABLE_SKILL = Skill::TABLE_SKILL;
    const TABLE_SKILL_DEPENDENCY = Skill::TABLE_SKILL_DEPENDENCY;
    const TABLE_SKILL_DEPENDENCY_COMBO = Skill::TABLE_SKILL_DEPENDENCY_COMBO;
    const TABLE_SKILL_PROGRESSION = Skill::TABLE_SKILL_PROGRESSION;
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
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];

    const DATA_FOLDER = 'skills';
    const RULE_SECTION = "Skills";

    const NOTIFICATIONS_DESCRIPTION = "If there's a skill that will unlock 2 or more other skills, suggests it to the user.";
    const NOTIFICATIONS_FORMAT = "Completing the skill %bestSkill will open the door to %numberOfSkillsItWillUnlock more skills 👀 Ready for the challenge?";
    const NOTIFICATIONS_VARIABLES = "bestSkill,numberOfSkillsItWillUnlock";


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

        // Add notifications metadata
        $response = Core::database()->select(Notification::TABLE_NOTIFICATION_DESCRIPTIONS, ["module" => $this->getId()]);
        if (!$response) {
            Core::database()->insert(Notification::TABLE_NOTIFICATION_DESCRIPTIONS, [
                "module" => $this->getId(),
                "description" => self::NOTIFICATIONS_DESCRIPTION,
                "variables" => self::NOTIFICATIONS_VARIABLES
            ]);
        }
        $this->initNotifications();
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Skills($copyTo);

        // Copy config
        $maxXP = $this->getMaxXP();
        $copiedModule->updateMaxXP($maxXP);
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);
        $minRating = $this->getMinRating();
        $copiedModule->updateMinRating($minRating);

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
        $this->removeNotifications();
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
                                "id" => "maxXP",
                                "value" => $this->getMaxXP(),
                                "placeholder" => "Max. XP",
                                "options" => [
                                    "topLabel" => "Skills max. XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum XP each student can earn with skills"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxExtraCredit",
                                "value" => $this->getMaxExtraCredit(),
                                "placeholder" => "Max. extra credit",
                                "options" => [
                                    "topLabel" => "Skills max. extra credit XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum extra credit XP each student can earn with skills"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "minRating",
                                "value" => $this->getMinRating(),
                                "placeholder" => "Min. rating",
                                "options" => [
                                    "topLabel" => "Skills min. rating",
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
            if ($input["id"] == "maxXP") $this->updateMaxXP($input["value"]);
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
                    ["label" => "Max. Reward (XP)", "align" => "middle"]
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
                                        "topLabel" => "Max. reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Maximum XP that can be earned with skill tree"
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
                                        "topLabel" => "Max. reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Maximum XP that can be earned with skill tree"
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
    public function saveListingItem(string $listName, string $action, array $item): ?string
    {
        $courseId = $this->course->getId();
        if ($listName == "Skill Trees") {   // Skill Trees
            if ($action == Action::NEW) SkillTree::addSkillTree($courseId, $item["name"] ?? null, $item["maxReward"] ?? null);
            elseif ($action == Action::EDIT) {
                $skillTree = new SkillTree($item["id"]);
                $skillTree->editSkillTree($item["name"] ?? null, $item["maxReward"] ?? null);
            } elseif ($action == Action::DELETE) SkillTree::deleteSkillTree($item["id"]);
        }

        return null;
    }

    public function getPersonalizedConfig(): ?array
    {
        return ["position" => "after"];
    }

    /**
     * @throws Exception
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        if ($listName == "Skill Trees") return SkillTree::importSkillTrees($this->course->getId(), $file, $replace);
        else if ($listName == "Skills") return Skill::importSkills($this->course->getId(), $file, $replace);
        else if ($listName == "Tiers") return Tier::importTiers($this->course->getId(), $file, $replace);
        return null;
    }

    /**
     * @throws Exception
     */
    public function exportListingItems(string $listName, array $items): ?array
    {
        if ($listName == "Skill Trees") return SkillTree::exportSkillTrees($this->course->getId(), $items);
        else if ($listName == "Skills") return Skill::exportSkills($this->course->getId(), $items);
        else if ($listName == "Tiers") return Tier::exportTiers($this->course->getId(), $items);
        return null;
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

    public function getMaxXP(): ?int
    {
        $maxXP = Core::database()->select(self::TABLE_SKILL_CONFIG, ["course" => $this->course->getId()], "maxXP");
        if (!is_null($maxXP)) $maxXP = intval($maxXP);
        return $maxXP;
    }

    /**
     * @throws Exception
     */
    public function updateMaxXP(?int $max)
    {
        Core::database()->update(self::TABLE_SKILL_CONFIG, ["maxXP" => $max], ["course" => $this->course->getId()]);
    }

    public function getMaxExtraCredit(): ?int
    {
        $maxExtraCredit = Core::database()->select(self::TABLE_SKILL_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit");
        if (!is_null($maxExtraCredit)) $maxExtraCredit = intval($maxExtraCredit);
        return $maxExtraCredit;
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(?int $max)
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
                    if ($skill["id"] == $skillId) {
                        $users[] = $student;
                        break;
                    }
                }
            }
        }
        return $users;
    }

    /**
     * Gets skills completed by a given user on a specific Skill Tree.
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

            $skillData = $skill->getData();
            $skillData["attempts"] = count(array_filter(AutoGame::getParticipations($course->getId(), $userId, "graded post"),
                function ($item) use ($skill) {
                    $name = $skill->getName();
                    return $item["description"] === "Skill Tree, Re: $name";
                })); // FIXME: create function to get attempts

            $VCModule = new VirtualCurrency($course);
            if ($VCModule->isEnabled()) $skillData["cost"] = $skill->getSkillCostForUser($userId);

            $skills[] = $skillData;
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


    /*** -------- Skill Trees --------- ***/

    /**
     *
     * Sets a specific skillTree status (either inView or not) given its id
     *
     * @param int $skillTreeId
     * @param bool $status
     * @return void
     * @throws Exception
     */
    public static function setSkillTreeInView(int $skillTreeId, bool $status){
        $skillTree = new SkillTree($skillTreeId);
        $skillTree->setInView($status);
    }

    /*** ------------------------------- ***/


    /**
     * Returns notifications to be sent to a user.
     *
     * @param int $userId
     * @throws Exception
     */
    public function getNotification($userId): ?string
    {
        $skillsTrees = SkillTree::getSkillTrees($this->course->getId());
        $maxCount = 1; // Only recommend a skill if it unlocks more than 1
        $bestSkill = null;

        foreach ($skillsTrees as $tree) {
            $completedSkills = $this->getUserSkills($userId, $tree["id"]);
            
            if (count($completedSkills) > 0) {
                $allTreeSkills = (new SkillTree($tree["id"]))->getSkills(true);
                
                // Find the skill that will unlock the most skills together with the already obtained ones
                foreach ($allTreeSkills as $testSkill) {
                    if (!$this->in_array_by_id($testSkill["id"], $completedSkills)) {
    
                        $count = 0;
                        foreach ($allTreeSkills as $unlockSkill) {
    
                            if ($unlockSkill["id"] != $testSkill["id"] // skill to unlock is different from skill suggested
                                && !$this->in_array_by_id($unlockSkill["id"], $completedSkills) // not completed yet
                                && count($unlockSkill["dependencies"]) > 0) { // has dependencies
    
                                foreach ($unlockSkill["dependencies"] as $dependencySet) {
                                    // the test skill is in the dependency set
                                    if ($this->in_array_by_id($testSkill["id"], $dependencySet)) {
                                        // all skills of the set are fulfilled (either test or completed)
                                        $fulfilledDependencies = array_filter($dependencySet, 
                                            function ($dependencySkill) use ($completedSkills, $testSkill) {
                                                return $this->in_array_by_id($dependencySkill["id"], $completedSkills) || $dependencySkill["id"] == $testSkill["id"];
                                            }
                                        );
                                        if (count($fulfilledDependencies) == count($dependencySet)) {
                                            $count += 1;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ($count > $maxCount) {
                            $maxCount = $count;
                            $bestSkill = $testSkill;
                        }
                    }
                }
            }
        }

        if ($bestSkill) {
            $params["bestSkill"] = $bestSkill["name"];
            $params["numberOfSkillsItWillUnlock"] = $maxCount;
            $format = Core::database()->select(Notification::TABLE_NOTIFICATION_CONFIG, ["course" => $this->course->getId(), "module" => $this->getId()])["format"];
            return Notification::getFinalNotificationText($this->course->getId(), $userId, $format, $params);
        }
        return null;
    }
    // Auxiliary function
    function in_array_by_id($id, $skills){
        foreach ($skills as $skill) {
            if ($skill["id"] == $id)
                return true;
        }
        return false;
    }
}
