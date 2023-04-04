<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\XPLevels\Level;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class LevelLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "xpLevel";    // NOTE: must match the name of the class
    const NAME = "XP Level";
    const DESCRIPTION = "Provides access to information regarding XP levels.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    // TODO: descriptions
    public function getFunctions(): ?array
    {
        return [
            new DFunction("id",
                "Gets a given level's ID in the system.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("minXP",
                "Gets a given level's minimum XP.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("description",
                "Gets a given level's description.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("number",
                "Gets a given level's number.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getLevelById",
                "Gets a level by its ID.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getLevelByMinXP",
                "Gets a level by its minimum XP.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getLevelByXP",
                "Gets a level by its corresponding XP.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getLevelByNumber",
                "Gets a level by its number.",
                ReturnType::OBJECT,
                $this
            ),
            new DFunction("getLevels",
                "Gets levels of course. Option to order by a given parameter.",
                ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given level's ID in the system.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function id($level): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($level)) $levelId = $level["id"];
        else $levelId = $level->getId();
        return new ValueNode($levelId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's minimum XP.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function minXP($level): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($level)) $minXP = $level["minXP"];
        else $minXP = $level->getMinXP();
        return new ValueNode($minXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's description.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function description($level): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($level)) $description = $level["description"];
        else $description = $level->getDescription();
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given level's number.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function number($level): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($level)) $level = Level::getLevelById($level["id"]);
        $number = $level->getNumber();
        return new ValueNode($number, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a level by its ID.
     *
     * @param int $levelId
     * @return ValueNode
     * @throws Exception
     */
    public function getLevelById(int $levelId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock level
            $level = [];

        } else $level = Level::getLevelById($levelId);
        return new ValueNode($level, $this);
    }

    /**
     * Gets a level by its minimum XP.
     *
     * @param int $minXP
     * @return ValueNode
     * @throws Exception
     */
    public function getLevelByMinXP(int $minXP): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock level
            $level = [];

        } else $level = Level::getLevelByMinXP($courseId, $minXP);
        return new ValueNode($level, $this);
    }

    /**
     * Gets a level by its corresponding XP.
     *
     * @param int $xp
     * @return ValueNode
     * @throws Exception
     */
    public function getLevelByXP(int $xp): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock level
            $level = [];

        } else $level = Level::getLevelByXP($courseId, $xp);
        return new ValueNode($level, $this);
    }

    /**
     * Gets a level by its number.
     *
     * @param int $number
     * @return ValueNode
     * @throws Exception
     */
    public function getLevelByNumber(int $number): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock level
            $level = [];

        } else $level = Level::getLevelByNumber($courseId, $number);
        return new ValueNode($level, $this);
    }

    /**
     * Gets levels of course. Option to order by a given parameter.
     *
     * @param string $orderBy
     * @return ValueNode
     * @throws Exception
     */
    public function getLevels(string $orderBy = "minXP"): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $courseId = Core::dictionary()->getCourse()->getId();
        $this->requireCoursePermission("getCourseById", $courseId, $viewerId);

        if (Core::dictionary()->mockData()) {
            // TODO: mock levels
            $levels = [];

        } else $levels = Level::getLevels($courseId, $orderBy);
        return new ValueNode($levels, $this);
    }
}
