<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Tier;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class TiersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "tiers";    // NOTE: must match the name of the class
    const NAME = "Tiers";
    const DESCRIPTION = "Provides access to information regarding tiers.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockTier(int $id = null, string $name = null) : array
    {
        return [
            "id" => $id ?: Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => $name ?: Core::dictionary()->faker()->text(5),
            "reward" => Core::dictionary()->faker()->numberBetween(200, 2000),
            "isActive" => Core::dictionary()->faker()->boolean(),
            "skills" => array_map(function () {
                return $this->mockSkill();
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 7)))
        ];
    }

    private function mockSkill() : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "name" => Core::dictionary()->faker()->text(20),
            "color" => Core::dictionary()->faker()->hexColor(),
            "isCollab" => Core::dictionary()->faker()->boolean(),
            "isExtra" => Core::dictionary()->faker()->boolean(),
            "isActive" => Core::dictionary()->faker()->boolean(),
            "dependencies" => array_map(function () {
                return ["name" => Core::dictionary()->faker()->text(20)];
            }, range(1, Core::dictionary()->faker()->numberBetween(0, 3)))
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's id.",
                ReturnType::TEXT,
                $this,
                "%tier.id"
            ),
            new DFunction("name",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's name.",
                ReturnType::TEXT,
                $this,
                "%tier.name"
            ),
            new DFunction("reward",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's reward.",
                ReturnType::NUMBER,
                $this,
                "%tier.reward"
            ),
            new DFunction("skills",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Gets tier's skills.",
                ReturnType::SKILLS_COLLECTION,
                $this,
                "%tier.skills"
            ),
            new DFunction("isActive",
                [["name" => "tier", "optional" => false, "type" => "tier"]],
                "Checks whether a given tier is active.",
                ReturnType::BOOLEAN,
                $this,
                "%tier.isActive"
            ),
            new DFunction("getTierById",
                [["name" => "tierId", "optional" => false, "type" => "int"]],
                "Gets a skill tier by its ID in the system.",
                ReturnType::OBJECT,
                $this,
                "tiers.getTierById(1)"
            ),
            new DFunction("getTierByName",
                [["name" => "name", "optional" => false, "type" => "string"],
                 ["name" => "skillTreeId", "optional" => false, "type" => "int"]],
                "Gets a skill tier by its name. Requires the id of the skill tree it belongs to.",
                ReturnType::OBJECT,
                $this,
                "tiers.getTierByName(%skillTree.id, 'Wildcard')"
            ),
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets tier's id.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function id($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["id"];
        else $name = $tier->getId();
        return new ValueNode($name, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets tier's name.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function name($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["name"];
        else $name = $tier->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets tier's name.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function reward($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["reward"];
        else $name = $tier->getReward();
        return new ValueNode($name, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets tier's skills.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function skills($tier): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $skills = $tier["skills"];

        } else {
            $skills = Skill::getSkillsOfTier($tier["id"]);
        };
        return new ValueNode($skills, Core::dictionary()->getLibraryById(SkillsLibrary::ID));
    }

    /**
     * Checks whether a given tier is active.
     *
     * @param $tier
     * @return ValueNode
     * @throws Exception
     */
    public function isActive($tier): ValueNode
    {
        // NOTE: on mock data, tier will be mocked
        if (is_array($tier)) $name = $tier["isActive"];
        else $name = $tier->isActive();
        return new ValueNode($name, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /*** --------- General ---------- ***/

    /**
     * Gets a skill tree by its ID in the system.
     *
     * @param int $tierId
     * @return ValueNode
     * @throws Exception
     */
    public function getTierById(int $tierId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $tier = $this->mockTier($tierId);

        } else $tier = Tier::getTierById($tierId);
        return new ValueNode($tier, $this);
    }

    /**
     * Gets a skill tree by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getTierByName(int $skillTreeId, string $name): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $tier = $this->mockTier(null, $name);

        } else $tier = Tier::getTierByName($skillTreeId, $name);
        return new ValueNode($tier, $this);
    }
}
