<?php
namespace GameCourse\Module\Streaks;

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
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use Utils\Cache;
use Utils\Utils;

/**
 * This is the Streaks module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Streaks extends Module
{
    const TABLE_STREAK = Streak::TABLE_STREAK;
    const TABLE_STREAK_PROGRESSION = Streak::TABLE_STREAK_PROGRESSION;
    const TABLE_STREAK_PARTICIPATIONS = Streak::TABLE_STREAK_PARTICIPATIONS;
    const TABLE_STREAK_CONFIG = 'streaks_config';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Streaks";  // NOTE: must match the name of the class
    const NAME = "Streaks";
    const DESCRIPTION = "Enables streaks as a type of award to be given to students under certain conditions.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Awards::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];

    const DATA_FOLDER = 'streaks';
    const RULE_SECTION = "Streaks";


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
        Core::database()->insert(self::TABLE_STREAK_CONFIG, ["course" => $this->course->getId()]);
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

    /**
     * @throws Exception
     */
    public function getLists(): array
    {
        $lists = [
            [
                "listName" => "Streaks",
                "itemName" => "streak",
                "importExtensions" => [".zip"],
                "listInfo" => [
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT],
                    ["id" => "description", "label" => "Description", "type" => InputType::TEXT],
                    ["id" => "color", "label" => "Color", "type" => InputType::COLOR],
                    ["id" => "count", "label" => "Count", "type" => InputType::NUMBER],
                    ["id" => "reward", "label" => "XP", "type" => InputType::NUMBER],
                    ["id" => "isExtra", "label" => "Extra Credit", "type" => InputType::TOGGLE],
                    ["id" => "isActive", "label" => "Active", "type" => InputType::TOGGLE]
                ],
                "items" => Streak::getStreaks($this->course->getId()),
                "actions" => [
                    ["action" => Action::DUPLICATE, "scope" => ActionScope::ALL],
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
                ],
                Action::EDIT => [
                    ["id" => "name", "label" => "Name", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "description", "label" => "Description", "type" => InputType::TEXT, "scope" => ActionScope::ALL],
                    ["id" => "color", "label" => "Color", "type" => InputType::COLOR, "scope" => ActionScope::ALL],
                    ["id" => "count", "label" => "Count", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "reward", "label" => "Reward (XP)", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "isRepeatable", "label" => "is Repeatable", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "isPeriodic", "label" => "is Periodic", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "isCount", "label" => "is Count", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "isAtMost", "label" => "is At Most", "type" => InputType::TOGGLE, "scope" => ActionScope::ALL],
                    ["id" => "periodicity", "label" => "Periodicity", "type" => InputType::NUMBER, "scope" => ActionScope::ALL],
                    ["id" => "periodicityTime", "label" => "Periodicity Time", "type" => InputType::SELECT, "scope" => ActionScope::ALL, "options" => [
                        "items" => [
                            ["id" => "days", "labelParam" => "Days"]
                        ]
                    ]],
                ],
            ]
        ];

        // Tokens info
        $virtualCurrencyModule = $this->course->getModuleById(VirtualCurrency::ID);
        if ($virtualCurrencyModule && $virtualCurrencyModule->isEnabled()) {
            $VCName = $virtualCurrencyModule->getName();
            array_splice($lists[0]["listInfo"], 5, 0, [
                ["id" => "tokens", "label" => $VCName, "type" => InputType::NUMBER]
            ]);
            array_splice($lists[0][Action::EDIT], 5, 0, [
                ["id" => "tokens", "label" => "Reward ($VCName)", "type" => InputType::NUMBER, "scope" => ActionScope::ALL]
            ]);
        }

        return $lists;
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item)
    {
        $courseId = $this->course->getId();
        if ($listName == "Streaks") {
            $item["isPeriodic"] = $item["isPeriodic"] ?? false;
            if ($item["isPeriodic"] && (!isset($item["periodicity"]) || !isset($item["periodicityTime"])))
                throw new Exception("Periodic streaks must have a periodicity and periodicity time.");

            if ($action == Action::NEW || $action == Action::DUPLICATE) {
                // Format name
                $name = $item["name"];
                if ($action == Action::DUPLICATE) $name .= " (Copy)";

                $streak = Streak::addStreak($courseId, $name, $item["description"], $item["color"] ?? null, $item["count"],
                    $item["periodicity"] ?? null, $item["periodicityTime"] ?? null, $item["reward"],
                    $item["tokens"] ?? null, $item["isRepeatable"] ?? false, $item["isCount"] ?? false,
                    $item["isPeriodic"] ?? false, $item["isAtMost"] ?? false, $item["isExtra"] ?? false);

                if ($action == Action::DUPLICATE)
                    Utils::copyDirectory(Streak::getStreakByName($courseId, $item["name"])->getDataFolder() . "/", $streak->getDataFolder() . "/");

            } elseif ($action == Action::EDIT) {
                $streak = Streak::getStreakById($item["id"]);
                $streak->editStreak($item["name"], $item["description"], $item["color"] ?? null, $item["count"],
                    $item["periodicity"] ?? null, $item["periodicityTime"] ?? null, $item["reward"],
                    $item["tokens"] ?? null, $item["isRepeatable"] ?? false, $item["isCount"] ?? false,
                    $item["isPeriodic"] ?? false, $item["isAtMost"] ?? false, $item["isExtra"] ?? false, $item["isActive"] ?? false);

            } elseif ($action == Action::DELETE) Streak::deleteStreak($item["id"]);
        }
    }

    /**
     * @throws Exception
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        if ($listName == "Streaks") return Streak::importStreaks($this->course->getId(), $file, $replace);
        return null;
    }

    /**
     * @throws Exception
     */
    public function exportListingItems(string $listName, int $itemId = null): ?array
    {
        if ($listName == "Streaks") return Streak::exportStreaks($this->course->getId());
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
        return Streak::generateRuleParams(...$args);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxExtraCredit(): int
    {
        return intval(Core::database()->select(self::TABLE_STREAK_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit"));
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
                throw new Exception("Streaks max. extra credit cannot be bigger than " . $generalMax . " (general max. extra credit).");
        }

        Core::database()->update(self::TABLE_STREAK_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }


    /*** --------- Streaks ---------- ***/

    /**
     * Gets users who have earned a given streak at least once,
     * as well as how many times they have earned it.
     *
     * @param int $streakId
     * @return array
     * @throws Exception
     */
    public function getUsersWithStreak(int $streakId): array
    {
        $users = [];
        foreach ($this->getCourse()->getStudents() as $student) {
            $streakNrCompletions = $this->getUserStreakCompletions($student["id"], $streakId);
            if ($streakNrCompletions > 0) $users[] = $student;
        }
        return $users;
    }

    /**
     * Gets streaks earned by a given user.
     * NOTE: only returns streaks that are currently active.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @return array
     * @throws Exception
     */
    public function getUserStreaks(int $userId, bool $isExtra = null): array
    {
        $course = $this->getCourse();
        $awardsModule = new Awards($course);
        $userStreakAwards = $awardsModule->getUserStreaksAwards($userId, $isExtra);

        // Group by badge ID
        $awards = [];
        foreach ($userStreakAwards as $award) {
            $awards[$award["moduleInstance"]][] = $award;
        }
        $userStreakAwards = $awards;

        // Get streak info & user nr. completions on it
        $streaks = [];
        foreach ($userStreakAwards as $streakId => $awards) {
            $streak = (new Streak($streakId))->getData();
            $streak["nrCompletions"] = count($awards);
            $streaks[] = $streak;
        }
        return $streaks;
    }

    /**
     * Gets user progression on a given streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return int
     */
    public function getUserStreakProgression(int $userId, int $streakId): int
    {
        $courseId = $this->getCourse()->getId();

        $cacheId = "streak_progression_s" . $streakId . "_u" . $userId;
        $cacheValue = Cache::get($courseId, $cacheId);

        if (AutoGame::isRunning($courseId) && !is_null($cacheValue)) {
            // NOTE: get value from cache while AutoGame is running
            //       since progression table is not stable
            return $cacheValue;

        } else {
            $total = intval(Core::database()->select(self::TABLE_STREAK_PROGRESSION, [
                "course" => $courseId,
                "user" => $userId,
                "streak" => $streakId
            ], "COUNT(*)"));
            $progression = $total % (new Streak($streakId))->getCount();
            Cache::store($courseId, $cacheId, $progression);
            return $progression;
        }
    }

    /**
     * Gets how many times a given user has completed a specific streak.
     *
     * @param int $userId
     * @param int $streakId
     * @return int
     * @throws Exception
     */
    public function getUserStreakCompletions(int $userId, int $streakId): int
    {
        $userStreaks = $this->getUserStreaks($userId);
        foreach ($userStreaks as $streak) {
            if ($streak["id"] == $streakId) return $streak["nrCompletions"];
        }
        return 0;
    }
}
