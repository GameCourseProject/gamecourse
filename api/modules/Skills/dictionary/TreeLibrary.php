<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\SkillTree;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class TreeLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "tree";    // NOTE: must match the name of the class
    const NAME = "Skill Tree";
    const DESCRIPTION = "Provides access to information regarding skill trees.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("maxReward",
                "Gets skill tree's maximum reward.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getSkillTreeById",
                "Gets a skill tree by its ID in the system.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getSkillTreeByName",
                "Gets a skill tree by its name.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getSkillTrees",
                "Gets all skill trees of course.",
                ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above


    /*** --------- Getters ---------- ***/

    /**
     * Gets skill tree's maximum reward.
     *
     * @param $skillTree
     * @return ValueNode
     * @throws Exception
     */
    public function maxReward($skillTree): ValueNode
    {
        // NOTE: on mock data, skill tree will be mocked
        if (is_array($skillTree)) $maxReward = $skillTree["maxReward"];
        else $maxReward = $skillTree->getMaxReward();
        return new ValueNode($maxReward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a skill tree by its ID in the system.
     *
     * @param int $skillTreeId
     * @return ValueNode
     * @throws Exception
     */
    public function getSkillTreeById(int $skillTreeId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock skill tree
            $skillTree = [];

        } else $skillTree = SkillTree::getSkillTreeById($skillTreeId);
        return new ValueNode($skillTree, $this);
    }

    /**
     * Gets a skill tree by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getSkillTreeByName(string $name): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock skill tree
            $skillTree = [];

        } else $skillTree = SkillTree::getSkillTreeByName($courseId, $name);
        return new ValueNode($skillTree, $this);
    }

    /**
     * Gets all skill trees of course.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getSkillTrees(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock skill trees
            $skillTrees = [];

        } else $skillTrees = SkillTree::getSkillTrees($courseId);
        return new ValueNode($skillTrees, $this);
    }
}
