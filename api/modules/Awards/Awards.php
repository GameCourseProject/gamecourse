<?php
namespace GameCourse\Module\Awards;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;

/**
 * This is the Awards module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Awards extends Module
{
    const TABLE_AWARD = 'award';
    const TABLE_AWARD_PARTICIPATION = 'award_participation';
    const TABLE_AWARD_TEST = 'award_test';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Awards";  // NOTE: must match the name of the class
    const NAME = "Awards";
    const DESCRIPTION = "Enables awards to be given to students under certain conditions.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Badges::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Skills::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Streaks::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        $this->cleanDatabase();
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Awards ---------- ***/

    /**
     * Gets awards for a given user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserAwards(int $userId): array
    {
        return Core::database()->selectMultiple(self::TABLE_AWARD, [
            "course" => $this->course->getId(),
            "user" => $userId
        ]);
    }

    /**
     * Gets awards for a given user of a specific type of award.
     * NOTE: types of awards in AwardType.php
     *
     * @param int $userId
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function getUserAwardsByType(int $userId, string $type): array
    {
        if ($type === AwardType::BADGE) return $this->getUserBadgesAwards($userId);
        elseif ($type === AwardType::SKILL) return $this->getUserSkillsAwards($userId);
        elseif ($type === AwardType::STREAK) return $this->getUserStreaksAwards($userId);
        return Core::database()->selectMultiple(self::TABLE_AWARD, [
            "course" => $this->course->getId(),
            "user" => $userId,
            "type" => $type
        ]);
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
     * @return array
     * @throws Exception
     */
    public function getUserBadgesAwards(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                        bool $post = null, bool $point = null): array
    {
        $this->checkDependency(Badges::ID);
        $table = self::TABLE_AWARD . " a LEFT JOIN " . Badges::TABLE_BADGE . " b on a.moduleInstance=b.id";
        $where = ["a.course" => $this->course->getId(), "a.user" => $userId, "a.type" => AwardType::BADGE, "b.isActive" => true];
        if ($extra !== null) $where["b.isExtra"] = $extra;
        if ($bragging !== null) $where["b.isBragging"] = $bragging;
        if ($count !== null) $where["b.isCount"] = $count;
        if ($post !== null) $where["b.isPost"] = $post;
        if ($point !== null) $where["b.isPoint"] = $point;
        return Core::database()->selectMultiple($table, $where, "a.*");
    }

    /**
     * Gets skills awards for a given user.
     * Option for extra:
     *  - if null --> gets awards for all skills
     *  - if false --> gets awards only for skills that are not extra credit
     *  - if true --> gets awards only for skills that are extra credit
     * (same for other options)
     *
     * @param int $userId
     * @param bool|null $collab
     * @param bool|null $extra
     * @return array
     * @throws Exception
     */
    public function getUserSkillsAwards(int $userId, bool $collab = null, bool $extra = null): array
    {
        $this->checkDependency(Skills::ID);
        $table = self::TABLE_AWARD . " a LEFT JOIN " . Skills::TABLE_SKILL . " s on a.moduleInstance=s.id";
        $where = ["a.course" => $this->course->getId(), "a.user" => $userId, "a.type" => AwardType::SKILL, "s.isActive" => true];
        if ($collab !== null) $where["s.isCollab"] = $collab;
        if ($extra !== null) $where["s.isExtra"] = $extra;
        return Core::database()->selectMultiple($table, $where, "a.*");
    }

    /**
     * Gets streaks awards for a given user.
     * Option for extra credit:
     *  - if null --> gets awards for all streaks
     *  - if false --> gets awards only for streaks that are not extra credit
     *  - if true --> gets awards only for streaks that are extra credit
     *
     * @param int $userId
     * @param bool|null $extra
     * @return array
     * @throws Exception
     */
    public function getUserStreaksAwards(int $userId, bool $extra = null): array
    {
        $this->checkDependency(Streaks::ID);
        $table = self::TABLE_AWARD . " a LEFT JOIN " . Streaks::TABLE_STREAK . " s on a.moduleInstance=s.id";
        $where = ["a.course" => $this->course->getId(), "a.user" => $userId, "a.type" => AwardType::STREAK, "s.isActive" => true];
        if ($extra !== null) $where["s.isExtra"] = $extra;
        return Core::database()->selectMultiple($table, $where, "a.*");
    }

    /**
     * Gives an award to a given user.
     *
     * @param int $userId
     * @param string $description
     * @param string $type
     * @param int|null $moduleInstance
     * @param int $reward
     * @return void
     * @throws Exception
     */
    public function giveAward(int $userId, string $description, string $type, int $moduleInstance = null, int $reward = 0)
    {
        self::validateAward($description, $type, $reward);
        Core::database()->insert(self::TABLE_AWARD, [
            "user" => $userId,
            "course" => $this->course->getId(),
            "description" => $description,
            "type" => $type,
            "moduleInstance" => $moduleInstance,
            "reward" => $reward
        ]);
    }


    /*** ---------- Rewards ---------- ***/

    /**
     * Gets total reward for a given user, by type of reward.
     *
     * @param int $userId
     * @return array
     */
    public function getUserTotalReward(int $userId): array
    {
        $totalReward = [];

        // Get total XP reward
        try {
            $this->checkDependency(XPLevels::ID);
            $totalReward["XP"] = array_sum(array_column(Core::database()->selectMultiple(self::TABLE_AWARD, [
                "course" => $this->course->getId(),
                "user" => $userId,
            ], "*", null, [["type", AwardType::TOKEN]]), "reward"));

        } catch (Exception $e) {}

        // Get total tokens reward
        try {
            $this->checkDependency(VirtualCurrency::ID);
            $totalReward["tokens"] = $this->getUserTotalRewardByType($userId, AwardType::TOKEN);

        } catch (Exception $e) {}

        return $totalReward;
    }

    /**
     * Gets total reward for a given user of a specific type of award.
     * NOTE: types of awards in AwardType.php
     *
     * @param int $userId
     * @param string $type
     * @return int
     * @throws Exception
     */
    public function getUserTotalRewardByType(int $userId, string $type): int
    {
        return array_sum(array_column($this->getUserAwardsByType($userId, $type), "reward"));
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
     * @return int
     * @throws Exception
     */
    public function getUserBadgesTotalReward(int $userId, bool $extra = null, bool $bragging = null, bool $count = null,
                                             bool $post = null, bool $point = null): int
    {
        $this->checkDependency(Badges::ID);
        return array_sum(array_column($this->getUserBadgesAwards($userId, $extra, $bragging, $count, $post, $point), "reward"));
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
     * @return int
     * @throws Exception
     */
    public function getUserSkillsTotalReward(int $userId, bool $collab = null, bool $extra = null): int
    {
        $this->checkDependency(Skills::ID);
        return array_sum(array_column($this->getUserSkillsAwards($userId, $collab, $extra), "reward"));
    }

    /**
     * Gets total streaks reward for a given user.
     * Option for extra credit:
     *  - if null --> gets total reward for all streaks
     *  - if false --> gets total reward only for streaks that are not extra credit
     *  - if true --> gets total reward only for streaks that are extra credit
     *
     * @param int $userId
     * @param bool|null $extra
     * @return int
     * @throws Exception
     */
    public function getUserStreaksTotalReward(int $userId, bool $extra = null): int
    {
        $this->checkDependency(Streaks::ID);
        return array_sum(array_column($this->getUserStreaksAwards($userId, $extra), "reward"));
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Validations ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Validates award parameters.
     *
     * @param $description
     * @param $type
     * @param $reward
     * @return void
     * @throws Exception
     */
    private static function validateAward($description, $type, $reward)
    {
        self::validateDescription($description);
        self::validateReward($reward);

        if (!AwardType::exists($type))
            throw new Exception("Award type '$type' doesn't exist in the system.");
    }

    /**
     * Validates award description.
     *
     * @param $description
     * @return void
     * @throws Exception
     */
    private static function validateDescription($description)
    {
        if (!is_string($description) || empty(trim($description)))
            throw new Exception("Award description can't be null neither empty.");

        if (iconv_strlen($description) > 100)
            throw new Exception("Award description is too long: maximum of 100 characters.");
    }

    /**
     * Validates award reward.
     *
     * @param $reward
     * @return void
     * @throws Exception
     */
    private static function validateReward($reward)
    {
        if (!is_numeric($reward) || !is_int($reward))
            throw new Exception("Award reward must be an integer.");
    }
}
