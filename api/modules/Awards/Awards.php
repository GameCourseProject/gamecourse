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

/**
 * This is the Awards module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Awards extends Module
{
    const TABLE_AWARD = 'award';
    const TABLE_AWARD_PARTICIPATION = 'award_participation';
    const TABLE_AWARD_TEST = 'award_test'; // FIXME: keep it?

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
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
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

    public function disable()
    {
        $this->cleanDatabase();
    }

    protected function deleteEntries()
    {
        Core::database()->delete(self::TABLE_AWARD, ["course" => $this->course->getId()]);
        Core::database()->delete(self::TABLE_AWARD_TEST, ["course" => $this->course->getId()]);
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
     *
     * @param int $userId
     * @param bool|null $extra
     * @return array
     * @throws Exception
     */
    public function getUserBadgesAwards(int $userId, bool $extra = null): array
    {
        $this->checkDependency(Badges::ID);
        $table = self::TABLE_AWARD . " a LEFT JOIN " . Badges::TABLE_BADGE . " b on a.moduleInstance=b.id";
        $where = ["a.course" => $this->course->getId(), "a.user" => $userId, "a.type" => AwardType::BADGE, "b.isActive" => true];
        if ($extra !== null) $where["b.isExtra"] = $extra;
        return Core::database()->selectMultiple($table, $where, "a.*");
    }

    /**
     * Gets skills awards for a given user.
     * Option for collaborative:
     *  - if null --> gets awards for all skills
     *  - if false --> gets awards only for skills that are not collaborative
     *  - if true --> gets awards only for skills that are collaborative
     * Option for extra:
     *  - if null --> gets awards for all skills
     *  - if false --> gets awards only for skills that are not extra credit
     *  - if true --> gets awards only for skills that are extra credit
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


    /*** ---------- Rewards ---------- ***/

    /**
     * Gets total reward for a given user.
     *
     * @param int $userId
     * @return int
     */
    public function getUserTotalReward(int $userId): int
    {
       return array_sum(array_column($this->getUserAwards($userId), "reward"));
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
     *
     * @param int $userId
     * @param bool|null $extra
     * @return int
     * @throws Exception
     */
    public function getUserBadgesTotalReward(int $userId, bool $extra = null): int
    {
        $this->checkDependency(Badges::ID);
        return array_sum(array_column($this->getUserBadgesAwards($userId, $extra), "reward"));
    }

    /**
     * Gets total skills reward for a given user.
     * Option for collaborative:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not collaborative
     *  - if true --> gets total reward only for skills that are collaborative
     * Option for extra:
     *  - if null --> gets total reward for all skills
     *  - if false --> gets total reward only for skills that are not extra credit
     *  - if true --> gets total reward only for skills that are extra credit
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
}
