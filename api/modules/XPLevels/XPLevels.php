<?php
namespace GameCourse\Module\XPLevels;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Streaks\Streaks;

/**
 * This is the XP & Levels module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class XPLevels extends Module
{
    const TABLE_LEVEL = Level::TABLE_LEVEL;
    const TABLE_XP = "user_xp";
    const TABLE_XP_CONFIG = "xp_config";

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
    const DESCRIPTION = "Enables Experience Points (XP) to be given to students as a reward, distributed between different levels.";
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

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initDatabase();

        // Init config
        Core::database()->insert(self::TABLE_XP_CONFIG, ["course" => $this->course->getId()]);

        // Create level zero
        $level0Id = Level::addLevel($this->course->getId(), 0, "AWOL")->getId();

        // Init XP for all students
        $students = $this->course->getStudents();
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

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new XPLevels($copyTo);

        // Copy config
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);

        // Copy levels
        $levels = Level::getLevels($this->course->getId());
        foreach ($levels as $level) {
            $level = new Level($level["id"]);
            $level->copyLevel($copyTo);
        }
    }

    public function disable()
    {
        $this->cleanDatabase();
        $this->removeEvents();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getGeneralInputs(): array
    {
        return [
            [
                "name" => "General",
                "contents" => [
                    [
                        "contentType" => "item",
                        "width" => "1/3",
                        "type" => InputType::NUMBER,
                        "id" => "maxExtraCredit",
                        "value" => $this->getMaxExtraCredit(),
                        "placeholder" => "Max. extra credit",
                        "options" => [
                            "topLabel" => "Total max. extra credit",
                            "required" => true,
                            "minValue" => 0
                        ],
                        "helper" => "Maximum extra credit students can earn in total"
                    ]
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveGeneralInputs(array $inputs)
    {
        foreach ($inputs as $input) {
            if ($input["id"] == "maxExtraCredit") $this->updateMaxExtraCredit($input["value"]);
        }
    }

    public function getLists(): array
    {
        return [
            [
                "listName" => "Levels",
                "itemName" => "level",
                "listActions" => [
                    Action::NEW,
                    Action::IMPORT,
                    Action::EXPORT
                ],
                "listInfo" => [
                    ["id" => "number", "label" => "Level", "type" => InputType::NUMBER],
                    ["id" => "description", "label" => "Title", "type" => InputType::TEXT],
                    ["id" => "minXP", "label" => "Minimum XP", "type" => InputType::NUMBER]
                ],
                "items" => Level::getLevels($this->course->getId()),
                "actions" => [
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL_BUT_FIRST]
                ],
                Action::EDIT => [
                    ["id" => "description", "label" => "Title", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "minXP", "label" => "Minimum XP", "type" => InputType::NUMBER, "scope" => ActionScope::ALL_BUT_FIRST]
                ],
                Action::IMPORT => [
                    "extensions" => [".csv", ".txt"]
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item)
    {
        $courseId = $this->course->getId();
        if ($listName == "Levels") {
            if ($action == Action::NEW) Level::addLevel($courseId, $item["minXP"], $item["description"]);
            elseif ($action == Action::EDIT) {
                $level = Level::getLevelById($item["id"]);
                $level->editLevel($item["minXP"], $item["description"]);
            } elseif ($action == Action::DELETE) Level::deleteLevel($item["id"]);
        }
    }

    /**
     * @throws Exception
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        if ($listName == "Levels") return Level::importLevels($this->course->getId(), $file, $replace);
        return null;
    }

    public function exportListingItems(string $listName, int $itemId = null): ?array
    {
        if ($listName == "Levels") return Level::exportLevels($this->course->getId());
        return null;
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxExtraCredit(): int
    {
        return intval(Core::database()->select(self::TABLE_XP_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit"));
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(int $max)
    {
        Core::database()->update(self::TABLE_XP_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }


    /*** ------------ XP ------------ ***/

    /**
     * Sets 0 XP for a given user.
     * If student already has XP it will reset them.
     *
     * @param int $userId
     * @param int|null $level0Id
     * @return void
     * @throws Exception
     */
    private function initXPForUser(int $userId, int $level0Id = null)
    {
        $courseId = $this->course->getId();
        if ($level0Id === null) $level0Id = Level::getLevelZero($this->course->getId())->getId();

        if ($this->userHasXP($userId)) // already has XP
            Core::database()->update(self::TABLE_XP, [
                "xp" => 0,
                "level" => $level0Id
            ], ["course" => $courseId, "user" => $userId]);

        else
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
     * @return int
     * @throws Exception
     */
    public function getUserXP(int $userId): int
    {
        if (!$this->userHasXP($userId))
            throw new Exception("User with ID = " . $userId . " doesn't have XP initialized.");

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
     * @throws Exception
     */
    public function getUserXPByType(int $userId, string $type): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserTotalRewardByType($userId, $type);
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
     * @throws Exception
     */
    public function getUserBadgesXP(int $userId, bool $extra = null): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserBadgesTotalReward($userId, $extra);
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
     * @throws Exception
     */
    public function getUserSkillsXP(int $userId, bool $collab = null): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserSkillsTotalReward($userId, $collab);
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
     * @throws Exception
     */
    public function getUserStreaksXP(int $userId, bool $extra = null): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserStreaksTotalReward($userId, $extra);
    }

    /**
     * Sets total XP for a given user.
     *
     * @param int $userId
     * @param int $xp
     * @return void
     * @throws Exception
     */
    public function setUserXP(int $userId, int $xp)
    {
        if (!$this->userHasXP($userId))
            throw new Exception("User with ID = " . $userId . " doesn't have XP initialized.");

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
     * @throws Exception
     */
    public function updateUserXP(int $userId, int $xp)
    {
        $newXP = max($this->getUserXP($userId) + $xp, 0);
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
        return !empty(Core::database()->select(self::TABLE_XP, ["course" => $this->course->getId(), "user" => $userId]));
    }


    /*** ---- Grade Verifications ---- ***/

    // TODO: refactor and improve (check old gamecourse 21/22)
}
