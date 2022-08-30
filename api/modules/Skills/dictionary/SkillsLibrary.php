<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Course\Course;
use GameCourse\Module\Skills\Skills;
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
            new DFunction("hasWildcardAvailable",
                "Checks whether a given user has at least one wildcard available to use.",
                ReturnType::BOOLEAN,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Checks whether a given user has at least one wildcard
     * available to use on a Skill Tree.
     *
     * @example %user.hasWildcardAvailable(<skillTreeID>) --> true
     * @example %user.hasWildcardAvailable(<skillTreeID>) --> false
     *
     * @param array $user
     * @param int $skillTreeId
     * @param Course $course
     * @param bool $mockData
     * @return ValueNode
     *
     */
    public function hasWildcardAvailable(array $user, int $skillTreeId, Course $course, bool $mockData): ValueNode
    {
        if ($mockData) return new ValueNode(false);

        $skillsModule = new Skills($course);
        return new ValueNode($skillsModule->userHasWildcardAvailable($user["id"], $skillTreeId));
    }
}
