<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Badges\Badge;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class BadgeLevelsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "badgeLevels";    // NOTE: must match the name of the class
    const NAME = "Badge Levels";
    const DESCRIPTION = "Provides access to information regarding badge levels.";


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
     * Gets a given level's number.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function number($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $number = $level["number"] ?? 0;
        return new ValueNode($number, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's goal.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function goal($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $goal = $level["goal"];
        return new ValueNode($goal, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given levels's description.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function description($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $description = $level["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given level's reward.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function reward($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $reward = $level["reward"];
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's tokens.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function tokens($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $tokens = $level["tokens"];
        return new ValueNode($tokens, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given level's image URL.
     *
     * @param $level
     * @return ValueNode
     * @throws Exception
     */
    public function image($level): ValueNode
    {
        // NOTE: on mock data, badge level will be mocked
        $image = $level["image"];
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a level by its number.
     *
     * @param int $number
     * @param int $badgeId
     * @return ValueNode
     */
    public function getLevelByNumber(int $number, int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock badge level
            $level = [];

        } else {
            $badge = Badge::getBadgeById($badgeId);
            $levels = $badge->getLevels();
            $level = $levels[$number - 1];
        }
        return new ValueNode($level, $this);
    }
}
