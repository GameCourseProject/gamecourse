<?php
namespace GameCourse\XPLevels;

use Error;
use Event\Event;
use Event\EventType;
use GameCourse\Awards\Awards;
use GameCourse\Awards\AwardType;
use GameCourse\Badges\Badges;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Skills\Skills;
use GameCourse\Streaks\Streaks;

/**
 * This is the XP & Levels module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class XPLevels extends Module
{
    const TABLE_LEVEL = Level::TABLE_LEVEL;
    const TABLE_XP = 'user_xp';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "XPLevels";  // NOTE: must match the name of the class
    const NAME = "XP & Levels";
    const DESCRIPTION = "Enables user vocabulary to use the terms xp and points to use around the course.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => Badges::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Skills::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Streaks::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();

        // Create level zero
        $level0Id = Core::database()->insert(self::TABLE_LEVEL, [
            "course" => $this->course->getId(),
            "number" => 0,
            "goal" => 0,
            "description" => "AWOL"
        ]);

        // Init XP for all students
        $students = $this->course->getStudents(true);
        foreach ($students as $student) {
            $this->initXPForUser($student["id"], $level0Id);
        }

        $this->initEvents();
    }

    public function initEvents()
    {
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) {
            if ($courseId == $this->course->getId())
                $this->initXPForUser($studentId);
        }, self::ID);

        Event::listen(EventType::STUDENT_REMOVED_FROM_COURSE, function (int $courseId, int $studentId) {
            // NOTE: this event targets cases where the course user only changed roles and
            //       is no longer a student; there's no need for an event when a user is
            //       completely removed from course, as SQL 'ON DELETE CASCADE' will do it
            if ($courseId == $this->course->getId())
                Core::database()->delete(self::TABLE_XP, ["course" => $courseId, "user" => $studentId]);
        }, self::ID);
    }

    public function disable()
    {
        $this->cleanDatabase();
        $this->removeEvents();
    }

    protected function deleteEntries()
    {
        Core::database()->delete(self::TABLE_XP, ["course" => $this->course->getId()]);
        Core::database()->delete(self::TABLE_LEVEL, ["course" => $this->course->getId()]);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function hasListingItems(): bool
    {
        return true;
    }

    public function getListingItems(): array
    {
        return [
            "listName" => "Levels",
            "itemName" => "level",
            "listInfo" => [
                ["id" => "number", "label" => "Level", "type" => InputType::NUMBER],
                ["id" => "description", "label" => "Title", "type" => InputType::TEXT],
                ["id" => "goal", "label" => "Minimum XP", "type" => InputType::NUMBER]
            ],
            "actions" => [Action::EDIT, Action::DELETE],
            "items" => Level::getLevels($this->course->getId()),
            "edit" => [
                ["id" => "description", "label" => "Title", "type" => InputType::TEXT],
                ["id" => "goal", "label" => "Minimum XP", "type" => InputType::NUMBER]
            ]
        ];
    }

    public function saveListingItem(string $action, array $item)
    {
        $courseId = $this->course->getId();
        if ($action == Action::NEW || $action == Action::DUPLICATE) Level::addLevel($courseId, $item["goal"], $item["title"]);
        elseif ($action == Action::EDIT) {
            $level = new Level($item["id"]);
            $level->editLevel($item["goal"], $item["title"]);
        } elseif ($action == Action::DELETE) Level::deleteLevel($item["id"]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ------------ XP ------------ ***/

    /**
     * Sets 0 XP for a given user.
     * If student already has XP it will reset them.
     *
     * @param int $userId
     * @param int|null $level0Id
     * @return void
     */
    private function initXPForUser(int $userId, int $level0Id = null)
    {
        $courseId = $this->course->getId();
        if ($level0Id === null) $level0Id = Level::getLevelByNumber($this->course->getId(), 0)->getId();

        if ($this->userHasXP($userId)) // already has XP
            Core::database()->delete(self::TABLE_XP, ["course" => $courseId, "user" => $userId]);

        Core::database()->insert(self::TABLE_XP, [
            "course" => $courseId,
            "user" => $userId,
            "xp" => 0,
            "level" => $level0Id
        ]);
    }

    /**
     * Gets total XP for a given user.
     *
     * @param int $userId
     * @return int|null
     */
    public function getUserXP(int $userId): int
    {
        if (!$this->userHasXP($userId))
            throw new Error("User with ID = " . $userId . " doesn't have XP initialized.");

        return intval(Core::database()->select(self::TABLE_XP,
            ["course" => $this->course->getId(), "user" => $userId],
            "xp"
        ));
    }

    /**
     * Gets total XP for a given user of a specific type of award.
     * NOTE: types of awards in AwardType.php
     *
     * @param int $userId
     * @param string $type
     * @return int
     */
    public function getUserXPByType(int $userId, string $type): int
    {
        if ($type === AwardType::BADGE) return $this->getUserBadgesXP($userId);
        elseif ($type === AwardType::SKILL) return $this->getUserSkillsXP($userId);
        elseif ($type === AwardType::STREAK) return $this->getUserStreaksXP($userId);
        return intval(Core::database()->select(Awards::TABLE_AWARD, [
            "course" => $this->course->getId(),
            "user" => $userId,
            "type" => $type],
            "sum(reward)")
        );
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
     * @return int
     */
    public function getUserBadgesXP(int $userId, bool $extra = null): int
    {
        $this->checkDependency(Badges::ID);
        $table = Awards::TABLE_AWARD . " a LEFT JOIN " . Badges::TABLE_BADGE . " b on a.moduleInstance=b.id";
        $where = ["a.course" => $this->course->getId(), "user" => $userId, "type" => AwardType::BADGE, "isActive" => true];
        if ($extra !== null) $where["isExtra"] = $extra;
        return intval(Core::database()->select($table, $where, "sum(reward)"));
    }

    /**
     * Gets total skills XP for a given user.
     * Option for collaborative:
     *  - if null --> gets total XP for all skills
     *  - if false --> gets total XP only for skills that are not collab
     *  - if true --> gets total XP only for skills that are collab
     *
     * @param int $userId
     * @param bool|null $collab
     * @return int
     */
    public function getUserSkillsXP(int $userId, bool $collab = null): int
    {
        $this->checkDependency(Skills::ID);
        $table = Awards::TABLE_AWARD . " a LEFT JOIN " . Skills::TABLE_SKILL . " s on a.moduleInstance=s.id";
        $where = ["a.course" => $this->course->getId(), "user" => $userId, "type" => AwardType::SKILL, "isActive" => true];
        if ($collab !== null) $where["isCollab"] = $collab;
        return intval(Core::database()->select($table, $where, "sum(reward)"));
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
     * @return int
     */
    public function getUserStreaksXP(int $userId, bool $extra = null): int
    {
        $this->checkDependency(Streaks::ID);
        $table = Awards::TABLE_AWARD . " a LEFT JOIN " . Streaks::TABLE_STREAK . " s on a.moduleInstance=s.id";
        $where = ["a.course" => $this->course->getId(), "user" => $userId, "type" => AwardType::STREAK, "isActive" => true];
        if ($extra !== null) $where["isExtra"] = $extra;
        return intval(Core::database()->select($table, $where, "sum(reward)"));
    }

    /**
     * Sets total XP for a given user.
     *
     * @param int $userId
     * @param int $xp
     * @return void
     */
    public function setUserXP(int $userId, int $xp)
    {
        if (!$this->userHasXP($userId))
            throw new Error("User with ID = " . $userId . " doesn't have XP initialized.");

        $courseId = $this->course->getId();
        Core::database()->update(self::TABLE_XP, ["xp" => $xp, "level" => Level::getLevelByXP($courseId, $xp)->getId()],
            ["course" => $courseId, "user" => $userId]);
    }

    /**
     * Adds or removes XP for a given user.
     *
     * @param int $userId
     * @param int $xp
     * @return void
     */
    public function updateUserXP(int $userId, int $xp)
    {
        $newXP = $this->getUserXP($userId) + $xp;
        $this->setUserXP($userId, $newXP);
    }

    /**
     * Checks whether a given user has XP initialized.
     *
     * @param int $userId
     * @return bool
     */
    public function userHasXP(int $userId): bool
    {
        return !empty(Core::database()->select(self::TABLE_XP, ["course" => $courseId, "user" => $userId]));
    }


    /*** ---------- Levels ---------- ***/

    // NOTE: use Level model to access level methods


    /*** ---- Grade Verifications ---- ***/

    // TODO
}
