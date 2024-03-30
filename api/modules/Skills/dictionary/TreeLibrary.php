<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Skills\SkillTree;
use GameCourse\Module\Skills\Tier;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class TreeLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    private function mockTree(int $id = null, string $name = null) : array
    {
        return [
            "id" => $id ? $id : Core::dictionary()->faker()->numberBetween(0, 100),
            "course" => 0,
            "name" => $name ? $name : Core::dictionary()->faker()->text(20),
            "maxReward" => Core::dictionary()->faker()->numberBetween(4000, 15000),
            "inView" => true
            // TODO: mock Tiers
        ];
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
            new DFunction("id",
                [["name" => "skillTree", "optional" => false, "type" => "skillTree"]],
                "Gets skill tree's id.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("name",
                [["name" => "skillTree", "optional" => false, "type" => "skillTree"]],
                "Gets skill tree's name.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("maxReward",
                [["name" => "skillTree", "optional" => false, "type" => "skillTree"]],
                "Gets skill tree's maximum reward.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("tiers",
                [["name" => "skillTree", "optional" => false, "type" => "skillTree"]],
                "Gets skill tree's tiers.",
                ReturnType::COLLECTION,
                $this
            ),
            new DFunction("getSkillTreeById",
                [["name" => "skillTreeId", "optional" => false, "type" => "int"]],
                "Gets a skill tree by its ID in the system.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getSkillTreeByName",
                [["name" => "name", "optional" => false, "type" => "string"]],
                "Gets a skill tree by its name.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getSkillTrees",
                [],
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
     * Gets skill tree's id.
     *
     * @param $skillTree
     * @return ValueNode
     * @throws Exception
     */
    public function id($skillTree): ValueNode
    {
        // NOTE: on mock data, skill tree will be mocked
        if (is_array($skillTree)) $id = $skillTree["id"];
        else $id = $skillTree->getId();
        return new ValueNode($id, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets skill tree's name.
     *
     * @param $skillTree
     * @return ValueNode
     * @throws Exception
     */
    public function name($skillTree): ValueNode
    {
        // NOTE: on mock data, skill tree will be mocked
        if (is_array($skillTree)) $name = $skillTree["name"];
        else $name = $skillTree->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

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

    /**
     * Gets skill tree's tiers.
     *
     * @param $skillTree
     * @return ValueNode
     * @throws Exception
     */
    public function tiers($skillTree): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $tiers = $skillTree["tiers"];

        } else {
            $tiers = Tier::getTiersOfSkillTree($skillTree["id"]);
        };
        return new ValueNode($tiers, Core::dictionary()->getLibraryById(TiersLibrary::ID));
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
            $skillTree = $this->mockTree($skillTreeId);

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
            $skillTree = $this->mockTree(null, $name);

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
            $skillTrees = array_map(function () {
                return $this->mockTree();
            }, range(1, Core::dictionary()->faker()->numberBetween(1, 3)));

        } else $skillTrees = SkillTree::getSkillTrees($courseId);
        return new ValueNode($skillTrees, $this);
    }
}
