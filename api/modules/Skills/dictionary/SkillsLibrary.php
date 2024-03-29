<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\Skill;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class SkillsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "skills";    // NOTE: must match the name of the class
    const NAME = "Skills";
    const DESCRIPTION = "Provides access to information regarding skills.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's id.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("name",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's name.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("color",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's color.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("getUserSkillAttempts",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getUserSkillCost",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this
            )
        ];
    }

    /*** --------- Getters ---------- ***/

    /**
     * Gets skill's id.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function id($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $id = $skill["id"];
        else $id = $skill->getId();
        return new ValueNode($id, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets skill's name.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function name($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $name = $skill["name"];
        else $name = $skill->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets skill's name.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function color($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $color = $skill["color"];
        else $color = $skill->getColor();
        return new ValueNode($color, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    
    /*** --------- General ---------- ***/

    /**
     * Gets a skill's cost for a user by their IDs in the system.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillAttempts(int $userId, int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $cost = Core::dictionary()->faker()->numberBetween(0, 100);

        } else $cost = Skill::getSkillById($skillId)->getSkillAttemptsOfUser($userId);
        return new ValueNode($cost, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a skill's cost for a user by their IDs in the system.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillCost(int $userId, int $skillId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $cost = Core::dictionary()->faker()->numberBetween(0, 100);

        } else $cost = Skill::getSkillById($skillId)->getSkillCostForUser($userId);
        return new ValueNode($cost, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Indicates if a skill is available for a user.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function isSkillAvailableForUser(int $userId, int $skillId)
    {
        // TODO
    }
}
