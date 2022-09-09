<?php
namespace GameCourse\Module\Badges;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\XPLevels\XPLevels;
use Utils\Cache;
use Utils\Utils;

/**
 * This is the Badges module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Badges extends Module
{
    const TABLE_BADGE = Badge::TABLE_BADGE;
    const TABLE_BADGE_LEVEL = Badge::TABLE_BADGE_LEVEL;
    const TABLE_BADGE_PROGRESSION = Badge::TABLE_BADGE_PROGRESSION;
    const TABLE_BADGE_CONFIG = 'badges_config';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Badges";  // NOTE: must match the name of the class
    const NAME = "Badges";
    const DESCRIPTION = "Enables badges as a type of award to be given to students under certain conditions.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];

    const DATA_FOLDER = 'badges';
    const RULE_SECTION = "Badges";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initDatabase();
        $this->createDataFolder();
        $this->initRules();

        // Init config
        Core::database()->insert(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()]);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Badges($copyTo);

        // Copy config
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);

        // Copy badges
        $badges = Badge::getBadges($this->course->getId(), null, "id");
        foreach ($badges as $badge) {
            $badge = new Badge($badge["id"]);
            $badge->copyBadge($copyTo);
        }
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
        $this->removeDataFolder();
        $this->removeRules();
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
            ["id" => "maxExtraCredit", "label" => "Max. Extra Credit", "type" => InputType::NUMBER, "value" => $this->getMaxExtraCredit()],
            ["id" => "extraOverlay", "label" => "Overlay for extra", "type" => InputType::IMAGE, "value" => null],
            ["id" => "braggingOverlay", "label" => "Overlay for bragging", "type" => InputType::IMAGE, "value" => null],
            ["id" => "lvl2Overlay", "label" => "Overlay for level 2", "type" => InputType::IMAGE, "value" => null],
            ["id" => "lvl3Overlay", "label" => "Overlay for level 3", "type" => InputType::IMAGE, "value" => null],
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
                "listName" => "Badges",
                "itemName" => "badge",
                "listActions" => [
                    Action::NEW,
                    Action::IMPORT,
                    Action::EXPORT
                ],
                "listInfo" => [
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT],
                    ["id" => "description", "label" => "Description", "type" => InputType::TEXT],
                    ["id" => "image", "label" => "Image", "type" => InputType::IMAGE],
                    ["id" => "nrLevels", "label" => "# Levels", "type" => InputType::NUMBER],
                    ["id" => "isBragging", "label" => "Bragging", "type" => InputType::TOGGLE],
                    ["id" => "isExtra", "label" => "Extra Credit", "type" => InputType::TOGGLE],
                    ["id" => "isActive", "label" => "Active", "type" => InputType::TOGGLE]
                ],
                "items" => Badge::getBadges($this->course->getId()),
                "actions" => [
                    ["action" => Action::DUPLICATE, "scope" => ActionScope::ALL],
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
                ],
                Action::EDIT => [ // NOTE: limit of 3 levels
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "description", "label" => "Description", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "desc1", "label" => "Level 1", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "reward1", "label" => "Reward 1 (XP)", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "desc2", "label" => "Level 2", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "reward2", "label" => "Reward 2 (XP)", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "desc3", "label" => "Level 3", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "reward3", "label" => "Reward 3 (XP)", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "isCount", "label" => "is Count", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "isPost", "label" => "is Post", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "isPoint", "label" => "is Point", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "goal1", "label" => "Goal level 1", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "goal2", "label" => "Goal level 2", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "goal3", "label" => "Goal level 3", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "image", "label" => "Image", "type" => InputType::IMAGE, "scope" => ActionScope::ALL]
                ],
                Action::IMPORT => [
                    "extensions" => [".zip"]
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
        if ($listName == "Badges") {
            if (!isset($item["reward1"]))
                throw new Exception("Badges must have the first level.");

            if (isset($item["reward3"]) && !isset($item["reward2"]))
                throw new Exception("Badge levels must be in ascending order.");

            if ($action == Action::NEW || $action == Action::DUPLICATE || $action == Action::EDIT) {
                // Format levels
                $levels = [];
                $i = 0;
                while ($i++ <= 3) {
                    if (isset($item["reward" . $i])) $levels[] = [
                        "description" => $item["desc" . $i],
                        "goal" => $item["goal" . $i],
                        "reward" => $item["reward" . $i]
                    ];
                }

                if ($action == Action::NEW || $action == Action::DUPLICATE) {
                    // Format name
                    $name = $item["name"];
                    if ($action == Action::DUPLICATE) $name .= " (Copy)";

                    $badge = Badge::addBadge($courseId, $name, $item["description"], $item["isExtra"] ?? false,
                        $item["isBragging"] ?? false, $item["isCount"] ?? false, $item["isPost"] ?? false,
                        $item["isPoint"] ?? false, $levels);

                    if ($action == Action::DUPLICATE)
                        Utils::copyDirectory(Badge::getBadgeByName($courseId, $item["name"])->getDataFolder() . "/", $badge->getDataFolder() . "/");

                } else {
                    $badge = Badge::getBadgeById($item["id"]);
                    $badge->editBadge($item["name"], $item["description"], $item["isExtra"] ?? false,
                        $item["isBragging"] ?? false, $item["isCount"] ?? false, $item["isPost"] ?? false,
                        $item["isPoint"] ?? false, $item["isActive"] ?? false, $levels);
                }

                if (isset($item["image"]) && !Utils::strStartsWith($item["image"], API_URL))
                    $badge->setImage($item["image"]);

            } elseif ($action == Action::DELETE) Badge::deleteBadge($item["id"]);
        }
    }

    /**
     * @throws Exception
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        if ($listName == "Badges") return Badge::importBadges($this->course->getId(), $file, $replace);
        return null;
    }

    /**
     * @throws Exception
     */
    public function exportListingItems(string $listName, int $itemId = null): ?array
    {
        if ($listName == "Badges") return Badge::exportBadges($this->course->getId());
        return null;
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Rule System ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    protected function generateRuleParams(...$args): array
    {
        return Badge::generateRuleParams(...$args);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxExtraCredit(): int
    {
        return intval(Core::database()->select(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit"));
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(int $max)
    {
        $xpLevels = $this->course->getModuleById(XPLevels::ID);
        if ($xpLevels && $xpLevels->isEnabled()) {
            $generalMax = $xpLevels->getMaxExtraCredit();
            if ($max > $generalMax)
                throw new Exception("Badges max. extra credit cannot be bigger than " . $generalMax . " (general max. extra credit).");
        }

        Core::database()->update(self::TABLE_BADGE_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }


    /*** ---------- Badges ---------- ***/

    /**
     * Gets users who have earned a given badge up to a certain level.
     *
     * @param int $badgeId
     * @param int $level
     * @return array
     * @throws Exception
     */
    public function getUsersWithBadge(int $badgeId, int $level): array
    {
        $users = [];
        foreach ($this->getCourse()->getStudents() as $student) {
            $badgeLevel = $this->getUserBadgeLevel($student["id"], $badgeId);
            if ($badgeLevel >= $level) $users[] = $student;
        }
        return $users;
    }

    /**
     * Gets badges earned by a given user.
     * NOTE: only returns badges that are currently active.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isBragging
     * @param bool|null $isCount
     * @param bool|null $isPost
     * @param bool|null $isPoint
     * @return array
     * @throws Exception
     */
    public function getUserBadges(int $userId, bool $isExtra = null, bool $isBragging = null, bool $isCount = null,
                                  bool $isPost = null, bool $isPoint = null): array
    {
        $course = $this->getCourse();
        $awardsModule = new Awards($course);
        $userBadgeAwards = $awardsModule->getUserBadgesAwards($userId, $isExtra, $isBragging, $isCount, $isPost, $isPoint);

        // Group by badge ID
        $awards = [];
        foreach ($userBadgeAwards as $award) {
            $awards[$award["moduleInstance"]][] = $award;
        }
        $userBadgeAwards = $awards;

        // Get badge info & user level on it
        $badges = [];
        foreach ($userBadgeAwards as $badgeId => $awards) {
            $badge = (new Badge($badgeId))->getData();
            $badge["level"] = count($awards);
            $badges[] = $badge;
        }
        return $badges;
    }

    /**
     * Gets user progression on a given badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return int
     */
    public function getUserBadgeProgression(int $userId, int $badgeId): int
    {
        $courseId = $this->getCourse()->getId();

        $cacheId = "badge_progression_b" . $badgeId . "_u" . $userId;
        $cacheValue = Cache::get($courseId, $cacheId);

        if (AutoGame::isRunning($courseId) && !is_null($cacheValue)) {
            // NOTE: get value from cache while AutoGame is running
            //       since progression table is not stable
            return $cacheValue;

        } else {
            // TODO
        }
    }

    /**
     * Gets level earned by a given user on a specific badge.
     *
     * @param int $userId
     * @param int $badgeId
     * @return int
     * @throws Exception
     */
    public function getUserBadgeLevel(int $userId, int $badgeId): int
    {
        $userBadges = $this->getUserBadges($userId);
        foreach ($userBadges as $badge) {
            if ($badge["id"] == $badgeId) return $badge["level"];
        }
        return 0;
    }
}
