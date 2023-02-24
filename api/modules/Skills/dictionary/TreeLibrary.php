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
            // TODO
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
        if (is_array($skillTree)) $maxreward = $skillTree["maxReward"];
        else $maxreward = $skillTree->getMaxReward();
        return new ValueNode($maxreward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a skill tree by its ID.
     *
     * @param int $skillTreeId
     * @return ValueNode
     */
    public function getSkillTreeById(int $skillTreeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock skill tree
            $skillTree = [];

        } else {
            $skillTree = SkillTree::getSkillTreeById($skillTreeId);
        }
        return new ValueNode($skillTree, $this);
    }

    /**
     * Gets a skill tree by its name.
     *
     * @param string $name
     * @return ValueNode
     */
    public function getSkillTreeByName(string $name): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock skill tree
            $skillTree = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $skillTree = SkillTree::getSkillTreeByName($courseId, $name);
        }
        return new ValueNode($skillTree, $this);
    }

    /**
     * Gets all skill trees of course.
     *
     * @return ValueNode
     */
    public function getSkillTrees(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock skill trees
            $skillTrees = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $skillTrees = SkillTree::getSkillTrees($courseId);
        }
        return new ValueNode($skillTrees, $this);
    }
}
