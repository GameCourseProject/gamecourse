<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Badges\Badge;
use GameCourse\Module\Badges\Badges;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class BadgesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "badges";    // NOTE: must match the name of the class
    const NAME = "Badges";
    const DESCRIPTION = "Provides access to information regarding badges.";


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

    /*** ---------- Config ---------- ***/

    /**
     * TODO: description
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxXP(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxXP = Core::dictionary()->faker()->numberBetween(20000, 22000);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $maxXP = $badgesModule->getMaxXP();
        }
        return new ValueNode($maxXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * TODO: description
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxExtraCredit(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxExtraCredit = Core::dictionary()->faker()->numberBetween(1000, 5000);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $maxExtraCredit = $badgesModule->getMaxExtraCredit();
        }
        return new ValueNode($maxExtraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** ---------- Badges ---------- ***/

    /**
     * Gets badges of course.
     *
     * @param bool|null $active
     * @param string $orderBy
     * @return ValueNode
     */
    public function getBadges(bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock badges
            $badges = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $badges = Badge::getBadges($courseId, $active);
        }
        return new ValueNode($badges, $this);
    }

    /**
     * Gets users who have earned a given badge up to a certain level.
     *
     * @param int $badgeId
     * @param int $level
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithBadge(int $badgeId, int $level): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $users = $badgesModule->getUsersWithBadge($badgeId, $level);
        }
        return new ValueNode($users, Core::dictionary()->getLibraryById(UsersLibrary::ID));
    }

    /**
     * Gets badges earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isBragging
     * @param bool|null $isCount
     * @param bool|null $isPost
     * @param bool|null $isPoint
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadges(int $userId, bool $isExtra = null, bool $isBragging = null, bool $isCount = null,
                                  bool $isPost = null, bool $isPoint = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock badges
            $badges = [];

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $badges = $badgesModule->getUserBadges($userId, $isExtra, $isBragging, $isCount, $isPost, $isPoint);
        }
        return new ValueNode($badges, $this);
    }

    /**
     * Gets user progression on a given badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeProgression(int $userId, int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $progression = Core::dictionary()->faker()->numberBetween(0, 10);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $progression = $badgesModule->getUserBadgeProgression($userId, $badgeId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets level earned by a given user on a specific badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeLevel(int $userId, int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $level = Core::dictionary()->faker()->numberBetween(1, 3);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $level = $badgesModule->getUserBadgeLevel($userId, $badgeId);
        }
        return new ValueNode($level, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
