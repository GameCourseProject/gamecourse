<?php
namespace GameCourse\Module\Journey;

use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Role\Role;

/**
 * This is the Journey module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Journey extends Module
{
    const TABLE_JOURNEY_PATH = JourneyPath::TABLE_JOURNEY_PATH;
    const TABLE_JOURNEY_PATH_SKILLS = JourneyPath::TABLE_JOURNEY_PATH_SKILLS;
    const TABLE_JOURNEY_PROGRESSION = JourneyPath::TABLE_JOURNEY_PROGRESSION;
    const TABLE_JOURNEY_CONFIG = 'journey_config';

    const BASE_ROLE = "Skills";
    const SKILLS_ROLES = [ self::BASE_ROLE => ["SkillTree", "Journey" ] ];

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Journey";  // NOTE: must match the name of the class
    const NAME = "Journey";
    const DESCRIPTION = "Provides a different, sequential, way of completing the Skills from the Skill Tree.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Skills::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];
    const RULE_SECTION = "Journey";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();
        $this->initRules();

        // Init config
        $skills = new Skills($this->course);
        Core::database()->insert(self::TABLE_JOURNEY_CONFIG, [
            "course" => $this->course->getId(),
            "maxXP" => $skills->getMaxXP(),
            "maxExtraCredit" => $skills->getMaxExtraCredit()
        ]);

        $this->addRolesToCourse();
        $this->initTemplates();
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        $this->cleanDatabase();
        $this->removeRules();
        $this->removeTemplates();
        $this->removeRolesFromCourse();
    }

    /**
     * Adds roles from the specific module to a course
     *
     * @param array $roles
     * @return void
     * @throws Exception
     */
    protected function addRolesToCourse()
    {
        $roles = self::SKILLS_ROLES;
        $course = $this->course;
        $courseId = $course->getId();
        $rolesNames = Role::getCourseRoles($courseId);
        $parent = array_keys($roles)[0];
        $hierarchy = $course->getRolesHierarchy();
        $studentIndex = array_search("Student", Role::DEFAULT_ROLES);
        $changes = false;

        // Add parent
        if (!in_array($parent, $rolesNames)) {
            Role::addRoleToCourse($courseId, $parent, null, null, self::ID);
            // Update hierarchy
            $hierarchy[$studentIndex]["children"][] = ["name" => $parent];
            $changes = true;
        }
        // Add children
        foreach ($roles[$parent] as $child){
            if (!in_array($child, $rolesNames)) {
                Role::addRoleToCourse($courseId, $child, null, null, self::ID);
                // Update hierarchy
                $parentIndex = array_search($parent, array_column($hierarchy[$studentIndex]["children"], "name"));
                $hierarchy[$studentIndex]["children"][$parentIndex]["children"][] = ["name" => $child];
                $changes = true;
            }
        }
        if ($changes) $course->setRolesHierarchy($hierarchy);
    }

    /**
     * Removes roles from the specific module from a course
     *
     * @param array $roles
     * @return void
     * @throws Exception
     */
    protected function removeRolesFromCourse()
    {
        $course = $this->course;
        Role::removeRoleFromCourse($course->getId(), self::BASE_ROLE);

        // Update hierarchy
        $studentIndex = array_search("Student", Role::DEFAULT_ROLES);
        $hierarchy = $course->getRolesHierarchy();

        foreach ($hierarchy[$studentIndex]["children"] as $key => $value){
            if ($value["name"] == self::BASE_ROLE){
                array_splice($hierarchy[$studentIndex]["children"], $key, 1);
                $course->setRolesHierarchy($hierarchy);
                return;
            }
        }
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
                                "id" => "minRating",
                                "value" => $this->getMinRating(),
                                "placeholder" => "Min. rating",
                                "options" => [
                                    "topLabel" => "Skills min. rating",
                                    "required" => true,
                                    "minValue" => 0
                                ],
                                "helper" => "Minimum rating for a skill to be awarded"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::TOGGLE,
                                "id" => "isRepeatable",
                                "value" => $this->getIsRepeatable(),
                                "helper" => "Allow students to complete another Journey Path after completing one.",
                                "options" => [
                                    "label" => "Allow Completion of Multiple Paths",
                                    "color" => "primary"
                                ],
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
            if ($input["id"] == "isRepeatable") $this->updateIsRepeatable($input["value"]);
        }
    }

    public function getPersonalizedConfig(): ?array
    {
        return ["position" => "after"];
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Rule System ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    protected function generateRuleParams(...$args): array
    {
        return JourneyPath::generateRuleParams(...$args);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxXP(): ?int
    {
        $maxXP = Core::database()->select(self::TABLE_JOURNEY_CONFIG, ["course" => $this->course->getId()], "maxXP");
        if (!is_null($maxXP)) $maxXP = intval($maxXP);
        return $maxXP;
    }

    /**
     * @throws Exception
     */
    public function updateMaxXP(?int $max)
    {
        Core::database()->update(self::TABLE_JOURNEY_CONFIG, ["maxXP" => $max], ["course" => $this->course->getId()]);
    }

    public function getMaxExtraCredit(): ?int
    {
        $maxExtraCredit = Core::database()->select(self::TABLE_JOURNEY_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit");
        if (!is_null($maxExtraCredit)) $maxExtraCredit = intval($maxExtraCredit);
        return $maxExtraCredit;
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(?int $max)
    {
        Core::database()->update(self::TABLE_JOURNEY_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }

    public function getMinRating(): int
    {
        return intval(Core::database()->select(self::TABLE_JOURNEY_CONFIG, ["course" => $this->course->getId()], "minRating"));
    }

    /**
     * @throws Exception
     */
    public function updateMinRating(int $minRating)
    {
        Core::database()->update(self::TABLE_JOURNEY_CONFIG, ["minRating" => $minRating], ["course" => $this->course->getId()]);
    }

    public function getIsRepeatable(): bool
    {
        return boolval(Core::database()->select(self::TABLE_JOURNEY_CONFIG, ["course" => $this->course->getId()], "isRepeatable"));
    }

    /**
     * @throws Exception
     */
    public function updateIsRepeatable(bool $isRepeatable)
    {
        Core::database()->update(self::TABLE_JOURNEY_CONFIG, ["isRepeatable" => +$isRepeatable], ["course" => $this->course->getId()]);
    }


}
