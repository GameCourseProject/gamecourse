<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\Skill;
use GameCourse\Module\Skills\Skills;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Course\Course;

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
                $this,
            "%skill.id"
            ),
            new DFunction("name",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's name.",
                ReturnType::TEXT,
                $this,
                "%skill.name"
            ),
            new DFunction("color",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's color.",
                ReturnType::TEXT,
                $this,
                "%skill.color"
            ),
            new DFunction("dependencies",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "Gets skill's dependencies.",
                ReturnType::COLLECTION,
                $this,
                "%skill.dependencies"
            ),
            new DFunction("isCollab",
                [["name" => "skill", "optional" => false, "type" => "skill"]],
                "True if the skill is collaborative.",
                ReturnType::TEXT,
                $this,
                "%skill.isCollab"
            ),
            new DFunction("getUserSkillAttempts",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this,
            "skills.getUserSkillAttempts(%user, %skill.id)"
            ),
            new DFunction("getUserSkillCost",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets a skill's cost for a user by its ID.",
                ReturnType::OBJECT,
                $this,
                "skills.getUserSkillCost(%user, %skill.id)"
            ),
            new DFunction("isSkillAvailableForUser",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]],
                "Gets if a skill is available for a user given its ID.",
                ReturnType::BOOLEAN,
                $this,
                "skills.isSkillAvailableForUser(%user, %skill.id, %skillTree.id)"
            ),
            new DFunction("isSkillCompletedByUser",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillId", "optional" => false, "type" => "int"]],
                "Gets if a skill is completed by a user given its ID.",
                ReturnType::BOOLEAN,
                $this,
                "skills.isSkillCompletedByUser(%user, %skill.id)"
            ),
            new DFunction("getUserTotalAvailableWildcards",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]
                ],
                "Gets the number of available Wildcards of a student.",
                ReturnType::NUMBER,
                $this,
                "skills.getUserTotalAvailableWildcards(%user, %skillTree.id)"
            ),
            new DFunction("getUserSkillUsedWildcards",
                [   ["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "skillTreeId", "optional" => false, "type" => "int"]
                ],
                "Gets the number of used Wildcards by a student on a skill.",
                ReturnType::NUMBER,
                $this,
                "skills.getUserSkillUsedWildcards(%user, %skillTree.id)"
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

    /**
     * Gets skill's dependencies.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function dependencies($skill): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $dependencies = $skill["dependencies"];

        } else {
            $skill = new Skill($skill["id"]);
            $dependencies = [];
            foreach ($skill->getDependencies() as $combo) {
                $str = '';
                foreach ($combo as $index => $skill) {
                    $str .= $skill["name"] . ($index != count($combo) - 1 ? ' + ' : '');
                }
                $dependencies[] = ["name" => $str];
            }
        };
        return new ValueNode($dependencies, $this);
    }

    /**
     * Gets skill's name.
     *
     * @param $skill
     * @return ValueNode
     * @throws Exception
     */
    public function isCollab($skill): ValueNode
    {
        // NOTE: on mock data, skill will be mocked
        if (is_array($skill)) $isCollab = $skill["isCollab"];
        else $isCollab = $skill->isCollab();
        return new ValueNode($isCollab, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    
    /*** --------- General ---------- ***/

    /**
     * Gets a skill's attempts for a user by their IDs in the system.
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
    public function isSkillAvailableForUser(int $userId, int $skillId, int $skillTreeId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->boolean();

        } else $available = Skill::getSkillById($skillId)->availableForUser($userId, $skillTreeId);

        return new ValueNode($available, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Indicates if a skill is completed by a user.
     *
     * @param int $userId
     * @param int $skillId
     * @return ValueNode
     * @throws Exception
     */
    public function isSkillCompletedByUser(int $userId, int $skillId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->boolean();

        } else $available = Skill::getSkillById($skillId)->completedByUser($userId);

        return new ValueNode($available, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Gets the number of available Wildcards for a given user.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTotalAvailableWildcards(int $userId, int $skillTreeId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $available = Core::dictionary()->faker()->numberBetween(0, 2);

        } else {
            $course = new Course($courseId);
            $skillsModule = new Skills($course);
            $available = $skillsModule->getUserTotalAvailableWildcards($userId, $skillTreeId);
        }

        return new ValueNode($available, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    
    /**
     * Gets the number of available Wildcards for a given user.
     *
     * @param int $userId
     * @param int $skillTreeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillUsedWildcards(int $userId, int $skillId)
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            $used = Core::dictionary()->faker()->numberBetween(0, 2);

        } else {
            $used = Skill::getSkillById($skillId)->wildcardsUsed($userId);
        }

        return new ValueNode($used, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
