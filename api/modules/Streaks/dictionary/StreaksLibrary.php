<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Streaks\Streak;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class StreaksLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "streaks";    // NOTE: must match the name of the class
    const NAME = "Streaks";
    const DESCRIPTION = "Provides access to information regarding streaks.";


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
    public function isEnabled(): ValueNode
    {
        $course = Core::dictionary()->getCourse();
        $isEnabled = $course->isModuleEnabled(Streaks::ID);
        return new ValueNode($isEnabled, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

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
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $maxXP = $streaksModule->getMaxXP();
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
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $maxExtraCredit = $streaksModule->getMaxExtraCredit();
        }
        return new ValueNode($maxExtraCredit, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** ---------- Streaks ---------- ***/

    /**
     * Gets streaks of course.
     *
     * @param bool|null $active
     * @return ValueNode
     */
    public function getStreaks(bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock streaks
            $streaks = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $streaks = Streak::getStreaks($courseId, $active);
        }
        return new ValueNode($streaks, $this);
    }

    /**
     * Gets users who have earned a given streak at least once.
     *
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUsersWithStreak(int $streakId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock users
            $users = [];

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $users = $streaksModule->getUsersWithStreak($streakId);
        }
        return new ValueNode($users, Core::dictionary()->getLibraryById(UsersLibrary::ID));
    }

    /**
     * Gets streaks earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isRepeatable
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaks(int $userId, bool $isExtra = null, bool $isRepeatable = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock streaks
            $streaks = [];

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $streaks = $streaksModule->getUserStreaks($userId, $isExtra, $isRepeatable);
        }
        return new ValueNode($streaks, $this);
    }

    /**
     * Gets user progression on a given streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakProgression(int $userId, int $streakId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $progression = Core::dictionary()->faker()->numberBetween(0, 10);

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $progression = $streaksModule->getUserStreakProgression($userId, $streakId);
        }
        return new ValueNode($progression, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets how many times a given user has completed a specific streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakCompletions(int $userId, int $streakId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $completions = Core::dictionary()->faker()->numberBetween(0, 5);

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $completions = $streaksModule->getUserStreakCompletions($userId, $streakId);
        }
        return new ValueNode($completions, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets streak deadline for a given user.
     *
     * @param int $userId
     * @param int $streakId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreakDeadline(int $userId, int $streakId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $deadline = Core::dictionary()->faker()->dateTimeBetween("now", "+1 week")->format("Y-m-d H:i:s");

        } else {
            $streaksModule = new Streaks(Core::dictionary()->getCourse());
            $deadline = $streaksModule->getUserStreakDeadline($userId, $streakId);
        }
        return new ValueNode($deadline, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
