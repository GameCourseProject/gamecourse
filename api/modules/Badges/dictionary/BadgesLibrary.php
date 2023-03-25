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

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given badge's ID in the system.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function id($badge): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($badge)) $badgeId = $badge["id"];
        else $badgeId = $badge->getId();
        return new ValueNode($badgeId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given badge's name.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function name($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $name = $badge["name"];
        else $name = $badge->getName();
        return new ValueNode($name, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given badge's description.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function description($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $description = $badge["description"];
        else $description = $badge->getDescription();
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given badge's image.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function image($badge): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($badge)) $image = $badge["image"];
        else $image = $badge->getImage();
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets badge levels.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function levels($badge): ValueNode
    {
        // NOTE: on mock data, badge will be mocked
        if (is_array($badge)) $badge = Badge::getBadgeById($badge["id"]);
        $levels = $badge->getLevels();
        return new ValueNode($levels, Core::dictionary()->getLibraryById(BadgeLevelsLibrary::ID));
    }

    /**
     * Checks whether a given badge is extra credit.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isExtra($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isExtra = $badge["isExtra"];
        else $isExtra = $badge->isExtra();
        return new ValueNode($isExtra, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is bragging.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isBragging($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isBragging = $badge["isBragging"];
        else $isBragging = $badge->isBragging();
        return new ValueNode($isBragging, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is extra credit.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isCount($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isCount = $badge["isCount"];
        else $isCount= $badge->isCount();
        return new ValueNode($isCount, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is extra credit.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isPost($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isPost = $badge["isPost"];
        else $isPost = $badge->isPost();
        return new ValueNode($isPost, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Checks whether a given badge is extra credit.
     *
     * @param $badge
     * @return ValueNode
     * @throws Exception
     */
    public function isPoint($badge): ValueNode
    {
        // NOTE: on mock data, level will be mocked
        if (is_array($badge)) $isPoint= $badge["isPoint"];
        else $isPoint = $badge->isPoint();
        return new ValueNode($isPoint, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }


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

    /**
     * TODO: description
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getBlankImage(): ValueNode
    {
        $badgesModule = new Badges(Core::dictionary()->getCourse());
        $image = $badgesModule->getBlankImage();
        return new ValueNode($image, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a badge by its ID.
     *
     * @param int $badgeId
     * @return ValueNode
     */
    public function getBadgeById(int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock badge
            $badge = [];

        } else {
            $badge = Badge::getBadgeById($badgeId);
        }
        return new ValueNode($badge, $this);
    }

    /**
     * Gets a badge by its name.
     *
     * @param string $name
     * @return ValueNode
     */
    public function getBadgeByName(string $name): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock badge
            $badge = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $badge = Badge::getBadgeByName($courseId, $name);
        }
        return new ValueNode($badge, $this);
    }

    /**
     * Gets badges of course.
     *
     * @param bool|null $active
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
     * Gets user progression information on a given badge,
     * e.g. description and links to posts.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeProgressionInfo(int $userId, int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock progression
            $progression = [];

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $progression = $badgesModule->getUserBadgeProgressionInfo($userId, $badgeId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(BadgeProgressionLibrary::ID));
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
            $level = Core::dictionary()->faker()->numberBetween(0, 3);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $level = $badgesModule->getUserBadgeLevel($userId, $badgeId);
        }

        return new ValueNode($level, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets the next level user can earn on a specific badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgeNextLevel(int $userId, int $badgeId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $userLevel = Core::dictionary()->faker()->numberBetween(0, 3);
            $nrLevels = Core::dictionary()->faker()->numberBetween(1, 3);

        } else {
            $badgesModule = new Badges(Core::dictionary()->getCourse());
            $userLevel = $badgesModule->getUserBadgeLevel($userId, $badgeId);
            $nrLevels = Badge::getBadgeById($badgeId)->getNrLevels();
        }
        $nextLevel = $userLevel < $nrLevels ? $userLevel + 1 : null;
        return new ValueNode($nextLevel, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
