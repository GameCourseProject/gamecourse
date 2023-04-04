<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class XPLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "xp";    // NOTE: must match the name of the class
    const NAME = "XP";
    const DESCRIPTION = "Provides access to information regarding XP.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    // TODO: descriptions
    public function getFunctions(): ?array
    {
        return [
            new DFunction("getMaxXP",
                "Gets maximum XP each student can earn in total.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getMaxExtraCredit",
                "Gets maximum extra credit each student can earn in total.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserXP",
                "Gets total XP for a given user.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserExtraCreditXP",
                "Gets total extra credit XP for a given user.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserXPByType",
                "Gets total XP for a given user of a specific type of award.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserBadgesXP",
                "Gets total badges XP for a given user. Some options available.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserSkillsXP",
                "Gets total skills XP for a given user. Some options available.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("getUserStreaksXP",
                "Gets total streaks XP for a given user. Some options available.",
                ReturnType::NUMBER,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** ---------- Config ---------- ***/

    /**
     * Gets maximum XP each student can earn in total.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxXP(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxXP = Core::dictionary()->faker()->numberBetween(20000, 22000);

        } else {
            $XPModule = new XPLevels(Core::dictionary()->getCourse());
            $maxXP = $XPModule->getMaxXP();
        }
        return new ValueNode($maxXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets maximum extra credit each student can earn in total.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getMaxExtraCredit(): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $maxExtraCredit = Core::dictionary()->faker()->numberBetween(1000, 5000);

        } else {
            $XPModule = new XPLevels(Core::dictionary()->getCourse());
            $maxExtraCredit = $XPModule->getMaxExtraCredit();
        }
        return new ValueNode($maxExtraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets total XP for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserXP(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userXP = Core::dictionary()->faker()->numberBetween(0, 20000);

        } else {
            $XPModule = new XPLevels($course);
            $userXP = $XPModule->getUserXP($userId);
        }
        return new ValueNode($userXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total extra credit XP for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserExtraCreditXP(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $extraCredit = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $XPModule = new XPLevels($course);
            $extraCredit = $XPModule->getUserExtraCreditXP($userId);
        }
        return new ValueNode($extraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total XP for a given user of a specific type of award.
     * NOTE: types of awards in AwardType.php
     *
     * @param int $userId
     * @param string $type
     * @param int|null $instance
     * @return ValueNode
     * @throws Exception
     */
    public function getUserXPByType(int $userId, string $type, ?int $instance = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userXPByType = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $XPModule = new XPLevels($course);
            $userXPByType = $XPModule->getUserXPByType($userId, $type, $instance);
        }
        return new ValueNode($userXPByType, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total badges XP for a given user.
     * Option for extra credit:
     *  - if null --> gets total XP for all badges
     *  - if false --> gets total XP only for badges that are not extra
     *  - if true --> gets total XP only for badges that are extra
     *
     * @param int $userId
     * @param bool|null $extra
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgesXP(int $userId, bool $extra = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userBadgesXP = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $XPModule = new XPLevels($course);
            $userBadgesXP = $XPModule->getUserBadgesXP($userId, $extra);
        }
        return new ValueNode($userBadgesXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total skills XP for a given user.
     * Option for collaborative:
     *  - if null --> gets total XP for all skills
     *  - if false --> gets total XP only for skills that are not collab
     *  - if true --> gets total XP only for skills that are collab
     *
     * @param int $userId
     * @param bool|null $extra
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillsXP(int $userId, bool $extra = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userSkillsXP = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $XPModule = new XPLevels($course);
            $userSkillsXP = $XPModule->getUserSkillsXP($userId, $extra);
        }
        return new ValueNode($userSkillsXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total streaks XP for a given user.
     * Option for extra credit:
     *  - if null --> gets total XP for all streaks
     *  - if false --> gets total XP only for streaks that are not extra
     *  - if true --> gets total XP only for streaks that are extra
     *
     * @param int $userId
     * @param bool|null $extra
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaksXP(int $userId, bool $extra = null): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userStreaksXP = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $XPModule = new XPLevels($course);
            $userStreaksXP = $XPModule->getUserStreaksXP($userId, $extra);
        }
        return new ValueNode($userStreaksXP, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
