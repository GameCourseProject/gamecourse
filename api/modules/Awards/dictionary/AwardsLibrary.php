<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\Awards\Awards;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class AwardsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "awards";    // NOTE: must match the name of the class
    const NAME = "Awards";
    const DESCRIPTION = "Provides access to information regarding awards.";


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
     * Gets a given award's ID in the system.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function id($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $awardId = $award["id"];
        return new ValueNode($awardId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's description.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function description($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $description = $award["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's type.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function type($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $type = $award["type"];
        return new ValueNode($type, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's module instance.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function instance($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $instance = $award["moduleInstance"];
        return new ValueNode($instance, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's reward.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function reward($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $reward = $award["reward"];
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given award's date.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function date($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $date = $award["date"];
        return new ValueNode($date, Core::dictionary()->getLibraryById(TimeLibrary::ID));
    }

    /**
     * Gets a given award's icon.
     *
     * @param $award
     * @return ValueNode
     * @throws Exception
     */
    public function icon($award): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $icon = $awardsModule->getIconOfType($award["type"]);
        return new ValueNode($icon, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given award's image URL.
     *
     * @param $award
     * @param string $style
     * @param string $extension
     * @return ValueNode
     * @throws Exception
     */
    public function image($award, string $style = "outline" | "solid", string $extension = "jpg" | "svg"): ValueNode
    {
        // NOTE: on mock data, award will be mocked
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $image = $awardsModule->getImageOfType($award["type"], $style, $extension);
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


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
        $isEnabled = $course->isModuleEnabled(Awards::ID);
        return new ValueNode($isEnabled, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * TODO: description
     *
     * @param string $type
     * @return ValueNode
     * @throws Exception
     */
    public function getIconOfType(string $type): ValueNode
    {
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $icon = $awardsModule->getIconOfType($type);
        return new ValueNode($icon, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * TODO: description
     *
     * @param string $type
     * @param string $style
     * @param string $extension
     * @return ValueNode
     * @throws Exception
     */
    public function getImageOfType(string $type, string $style = "outline" | "solid", string $extension = "jpg" | "svg"): ValueNode
    {
        $awardsModule = new Awards(Core::dictionary()->getCourse());
        $image = $awardsModule->getImageOfType($type, $style, $extension);
        return new ValueNode($image, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** ---------- Awards ---------- ***/

    /**
     * Gets awards for a given user.
     *
     * @param int $userId
     * @return ValueNode
     */
    public function getUserAwards(int $userId): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock awards
            $awards = [];

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $awards = $awardsModule->getUserAwards($userId);
        }
        return new ValueNode($awards, $this);
    }

    /**
     * Gets awards for a given user of a specific type of award.
     *
     * @param int $userId
     * @param string $type
     * @return ValueNode
     * @throws Exception
     */
    public function getUserAwardsByType(int $userId, string $type): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock awards
            $awards = [];

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $awards = $awardsModule->getUserAwardsByType($userId, $type);
        }
        return new ValueNode($awards, $this);
    }

    /**
     * Gets badges awards for a given user.
     * Option for extra credit:
     *  - if null --> gets awards for all badges
     *  - if false --> gets awards only for badges that are not extra credit
     *  - if true --> gets awards only for badges that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $extra
     * @param bool|null $bragging
     * @param bool|null $count
     * @param bool|null $post
     * @param bool|null $point
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgesAwards(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                        bool $post = null, bool $point = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock awards
            $awards = [];

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $awards = $awardsModule->getUserBadgesAwards($userId, $extra, $bragging, $count, $post, $point, $active);
        }
        return new ValueNode($awards, $this);
    }

    /**
     * Gets total skills reward for a given user.
     * Option for collaborative:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not collaborative
     *  - if true --> gets total reward only for skills that are collaborative
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $repeatable
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillsAwards(int $userId, bool $collab = null, bool $extra = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock awards
            $awards = [];

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $awards = $awardsModule->getUserSkillsAwards($userId, $collab, $extra, $active);
        }
        return new ValueNode($awards, $this);
    }

    /**
     * Gets streaks awards for a given user.
     * Option for extra credit:
     *  - if null --> gets awards for all streaks
     *  - if false --> gets awards only for streaks that are not extra credit
     *  - if true --> gets awards only for streaks that are extra credit
     *
     * @param int $userId
     * @param bool|null $repeatable
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaksAwards(int $userId, bool $repeatable = null, bool $extra = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock awards
            $awards = [];

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $awards = $awardsModule->getUserStreaksAwards($userId, $repeatable, $extra, $active);
        }
        return new ValueNode($awards, $this);
    }


    /*** ---------- Rewards ---------- ***/

    /**
     * Gets total reward for a given user of a specific type of award.
     *
     * @param int $userId
     * @param string $type
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTotalRewardByType(int $userId, string $type): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $reward = $awardsModule->getUserTotalRewardByType($userId, $type);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total badges reward for a given user.
     * Option for extra credit:
     *  - if null --> gets total reward for all badges
     *  - if false --> gets total reward only for badges that are not extra credit
     *  - if true --> gets total reward only for badges that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $extra
     * @param bool|null $bragging
     * @param bool|null $count
     * @param bool|null $post
     * @param bool|null $point
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserBadgesTotalReward(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                             bool $post = null, bool $point = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $reward = $awardsModule->getUserBadgesTotalReward($userId, $extra, $bragging, $count, $post, $point, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total skills reward for a given user.
     * Option for collaborative:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not collaborative
     *  - if true --> gets total reward only for skills that are collaborative
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $collab
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSkillsTotalReward(int $userId, bool $collab = null, bool $extra = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $reward = $awardsModule->getUserSkillsTotalReward($userId, $collab, $extra, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets total streaks reward for a given user.
     * Option for extra credit:
     *  - if null --> gets total reward for all streaks
     *  - if false --> gets total reward only for streaks that are not extra credit
     *  - if true --> gets total reward only for streaks that are extra credit
     *
     * @param int $userId
     * @param bool|null $repeatable
     * @param bool|null $extra
     * @param bool|null $active
     * @return ValueNode
     * @throws Exception
     */
    public function getUserStreaksTotalReward(int $userId, bool $repeatable = null, bool $extra = null, bool $active = null): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            $reward = Core::dictionary()->faker()->numberBetween(0, 3000);

        } else {
            $awardsModule = new Awards(Core::dictionary()->getCourse());
            $reward = $awardsModule->getUserStreaksTotalReward($userId, $repeatable, $active, $active);
        }
        return new ValueNode($reward, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
