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
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\NotificationSystem\Notification;
use GameCourse\Views\Dictionary\ReturnType;

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

    const NOTIFICATIONS_DESCRIPTION = "Sends a motivating message whenever a user has only 10% of the Level's XP missing to level up.";
    const NOTIFICATIONS_FORMAT = "You are so close to reaching Level %levelNumber - %levelDescription! Only %XPLeft XP to go 🚀";
    const NOTIFICATIONS_VARIABLES = "levelNumber,levelDescription,XPLeft";


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
        $this->initProviders();

        // Add notifications metadata
        $response = Core::database()->select(Notification::TABLE_NOTIFICATION_DESCRIPTIONS, ["module" => $this->getId()]);
        if (!$response) {
            Core::database()->insert(Notification::TABLE_NOTIFICATION_DESCRIPTIONS, [
                "module" => $this->getId(),
                "description" => self::NOTIFICATIONS_DESCRIPTION,
                "variables" => self::NOTIFICATIONS_VARIABLES
            ]);
        }
        $this->initNotifications();
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

    public function providers(): array
    {
        $XPEvolution =  [
            "name" => "XPEvolution",
            "description" => "Provides total XP of a given user over time. Time options: 'day', 'week', 'month'. Option to compare evolution with other users.",
            "returnType" => ReturnType::COLLECTION,
            "function" => "\$XPEvolution = [[\"name\" => \"You\", \"data\" => []]];

        if (Core::dictionary()->mockData()) {
            for (\$i = 0; \$i <= 10; \$i++) {
                \$XPEvolution[0][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(\$i * 500, (\$i + 1) * 500)];
            }
            if (!empty(\$compareWith)) {
                \$XPEvolution[] = [\"name\" => \$compareWithLabel, \"data\" => []];
                for (\$i = 0; \$i <= 10; \$i++) {
                    \$XPEvolution[1][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(\$i * 500, (\$i + 1) * 500)];
                }
            }

        } else {
            \$course = Core::dictionary()->getCourse();
            if (!\$course) \$this->throwError(\"XPEvolution\", \"no course found\");

            \$courseDates = \$course->getData(\"startDate, endDate\");
            if (!\$courseDates[\"startDate\"]) \$this->throwError(\"XPEvolution\", \"course doesn't have a start date\");
            if (!\$courseDates[\"endDate\"]) \$this->throwError(\"XPEvolution\", \"course doesn't have an end date\");

            \$XPModule = new \GameCourse\Module\XPLevels\XPLevels(\$course);
            \$userXP = \$XPModule->getUserXP(\$userId);

            // Get time passed
            \$baseline = \$courseDates[\"startDate\"];
            \$now = date(\"Y-m-d H:i:s\", time());
            \$timePassed = Time::timeBetween(\$baseline, Time::earliest(\$now, \$courseDates[\"endDate\"]), \$time);

            // Get data from cache if exists and is updated
            \$cacheId = \"xp_evolution_u\$userId\";
            \$cacheValue = Cache::get(\$course->getId(), \$cacheId);

            // Calculate user evolution
            if (!is_null(\$cacheValue) && !empty(\$cacheValue[0][\"data\"]) && end(\$cacheValue[0][\"data\"])[\"x\"] === \$timePassed) {
                \$XPEvolution[0][\"data\"] = \$cacheValue[0][\"data\"];

            } else {
                // Get user awards
                \$awardsModule = new \GameCourse\Module\Awards\Awards(\$course);
                \$awards = array_filter(\$awardsModule->getUserAwards(\$userId), function (\$award) {
                    return \$award[\"type\"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                });

                \$totalXP = 0;
                \$t = 0;

                // Calculate XP over time
                while (\$t <= \$timePassed) {
                    \$awardsOfTime = array_filter(\$awards, function (\$award) use (\$baseline, \$t, \$time) {
                        return Time::timeBetween(\$baseline, \$award[\"date\"], \$time) == \$t;
                    });
                    foreach (\$awardsOfTime as \$award) { \$totalXP += \$award[\"reward\"]; }
                    \$XPEvolution[0][\"data\"][] = [\"x\" => \$t, \"y\" => \$totalXP];
                    \$t++;
                }
            }

            // Calculate others' avg. evolution
            if (!empty(\$compareWith)) {
                \$userIds = array_values(array_filter(
                    array_map(function (\$user) { if (is_array(\$user)) return \$user[\"id\"]; return \$user->getId(); }, \$compareWith),
                    function (\$uId) use (\$userId) { return \$uId !== \$userId; } // NOTE: ignore user to compare with
                ));
                \$nrUsers = count(\$userIds);

                if (\$nrUsers !== 0) {
                    \$XPEvolution[] = [\"name\" => \$compareWithLabel, \"data\" => []];
                    \$totalXP = intval(Core::database()->executeQuery(\"SELECT SUM(xp) FROM \" . \GameCourse\Module\XPLevels\XPLevels::TABLE_XP .
                        \" WHERE course = \" . Core::dictionary()->getCourse()->getId() . \" AND user IN (\" . implode(\", \", \$userIds) . \");\")->fetch()[0]);

                    if (!is_null(\$cacheValue) && !empty(\$cacheValue[1][\"data\"]) && \$totalXP === end(\$cacheValue[1][\"data\"])[\"y\"]) {
                        \$XPEvolution[1][\"data\"] = \$cacheValue[1][\"data\"];

                    } else {
                        foreach (\$userIds as \$i => \$uId) {
                            // Get user awards
                            \$awardsModule = new \GameCourse\Module\Awards\Awards(\$course);
                            \$awards = array_filter(\$awardsModule->getUserAwards(\$uId), function (\$award) {
                                return \$award[\"type\"] !== \GameCourse\Module\Awards\AwardType::TOKENS;
                            });

                            \$totalUserXP = 0;
                            \$t = 0;

                            // Calculate XP over time
                            while (\$t <= \$timePassed) {
                                \$awardsOfTime = array_filter(\$awards, function (\$award) use (\$baseline, \$t, \$time) {
                                    return Time::timeBetween(\$baseline, \$award[\"date\"], \$time) == \$t;
                                });
                                foreach (\$awardsOfTime as \$award) { \$totalUserXP += \$award[\"reward\"]; }
                                if (\$i == 0) \$XPEvolution[1][\"data\"][\$t] = [\"x\" => \$t, \"y\" => round(\$totalUserXP / \$nrUsers)];
                                else \$XPEvolution[1][\"data\"][\$t][\"y\"] += round(\$totalUserXP / \$nrUsers);
                                \$t++;
                            }
                        }
                    }
                }
            }

            // Store in cache
            \$cacheValue = \$XPEvolution;
            Cache::store(\$course->getId(), \$cacheId, \$cacheValue);
        }

        return new ValueNode(\$XPEvolution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));",
            "args" => ["int \$userId", "string \$time", "array \$compareWith = []", "string \$compareWithLabel = \"Others\""]
        ];

        $XPDistribution = [
            "name" => "XPDistribution",
            "description" => "Provides a distribution of the total XP of given users. Option for interval to group XP, max. XP and whether to show an average of each interval group.",
            "returnType" => ReturnType::COLLECTION,
            "function" => "\$XPDistribution = [[\"name\" => \"XP Distribution\", \"type\" => \"column\", \"data\" => []]];
        if (\$showAverage) \$XPDistribution[] = [\"name\" => \"Average\", \"type\" => \"line\", \"data\" => []];
        if (\$interval > \$max) \$interval = 1;

        if (Core::dictionary()->mockData()) {
            if (is_null(\$max)) \$max = 20000;
            for (\$i = (\$interval === 1 ? 0 : \$interval); \$i <= \$max; \$i += \$interval) {
                \$XPDistribution[0][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(0, 50)];
                if (\$showAverage) \$XPDistribution[1][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(0, 50)];
            }

        } else {
            \$course = Core::dictionary()->getCourse();
            if (!\$course) \$this->throwError(\"XPDistribution\", \"no course found\");

            \$userIds = array_map(function (\$user) { if (is_array(\$user)) return \$user[\"id\"]; return \$user->getId(); }, \$users);
            \$nrUsers = count(\$userIds);

            if (\$nrUsers !== 0) {
                // Get each user XP
                \$XPByUser = [];
                foreach (\$userIds as \$userId) {
                    \$XPModule = new \GameCourse\Module\XPLevels\XPLevels(\$course);
                    \$XPByUser[] = \$XPModule->getUserXP(\$userId);
                }

                // Initialize data
                if (is_null(\$max)) \$max = ceil(max(\$XPByUser) / \$interval) * \$interval;
                for (\$i = (\$interval === 1 ? 0 : \$interval); \$i <= \$max; \$i += \$interval) {
                    \$XPDistribution[0][\"data\"][] = [\"x\" => \$i, \"y\" => 0];
                    if (\$showAverage) \$XPDistribution[1][\"data\"][] = [\"x\" => \$i, \"y\" => 0];
                }

                // Process data
                foreach (\$XPByUser as \$userXP) {
                    \$i = \$interval === 1 ? \$userXP : (\$userXP === \$interval ? floor(\$userXP / \$interval) - 1 : floor(\$userXP / \$interval));
                    \$XPDistribution[0][\"data\"][\$i][\"y\"] += 1;
                    if (\$showAverage) {
                        if (\$XPDistribution[1][\"data\"][\$i][\"y\"] === 0) \$XPDistribution[1][\"data\"][\$i][\"y\"] = round(\$userXP / \$nrUsers);
                        else \$XPDistribution[1][\"data\"][\$i][\"y\"] += round(\$userXP / \$nrUsers);
                    }
                }
            }
        }

        return new ValueNode(\$XPDistribution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));",
            "args" => ["array \$users", "int \$interval = 1", "int \$max = null", "bool \$showAverage = false"]
        ];

        $XPOverview = [
            "name" => "XPOverview",
            "description" => "Provides an XP overview for a given user. Award types must follow the format: 'type: label'. Option to compare overview with other users.",
            "returnType" => ReturnType::COLLECTION,
            "function" => "\$XPOverview = [[\"name\" => \"You\", \"data\" => []]];

        // Parse award types
        \$awardTypesParsed = array_map(\"trim\", explode(\",\", \$awardTypes));
        \$awardTypes = [];
        foreach (\$awardTypesParsed as \$awardType) {
            \$awardTypeParsed = array_map(\"trim\", explode(\":\", \$awardType));
            \$awardTypes[\$awardTypeParsed[0]] = \$awardTypeParsed[1];
        }

        if (Core::dictionary()->mockData()) {
            foreach(\$awardTypes as \$type => \$label) {
                \$XPOverview[0][\"data\"][] = [\"x\" => \$label, \"y\" => Core::dictionary()->faker()->numberBetween(0, 3000)];
            }
            if (!empty(\$compareWith)) {
                \$XPOverview[] = [\"name\" => \$compareWithLabel, \"data\" => []];
                foreach(\$awardTypes as \$type => \$label) {
                    \$XPOverview[1][\"data\"][] = [\"x\" => \$label, \"y\" => Core::dictionary()->faker()->numberBetween(0, 3000)];
                }
            }

        } else {
            \$course = Core::dictionary()->getCourse();
            if (!\$course) \$this->throwError(\"XPOverview\", \"no course found\");

            // Get user awards
            \$awardsModule = new \GameCourse\Module\Awards\Awards(\$course);
            \$awards = \$awardsModule->getUserAwards(\$userId);

            // Calculate XP for each award type
            foreach(\$awardTypes as \$type => \$label) {
                \$totalXP = array_sum(array_column(array_filter(\$awards, function (\$award) use (\$type) {
                    return \$award[\"type\"] === \$type;
                }), \"reward\"));
                \$XPOverview[0][\"data\"][] = [\"x\" => \$label, \"y\" => \$totalXP];
            }

            // Calculate others' avg. overview
            if (!empty(\$compareWith)) {
                \$userIds = array_values(array_filter(
                    array_map(function (\$user) { if (is_array(\$user)) return \$user[\"id\"]; return \$user->getId(); }, \$compareWith),
                    function (\$uId) use (\$userId) { return \$uId !== \$userId; } // NOTE: ignore user to compare with
                ));
                \$nrUsers = count(\$userIds);

                if (\$nrUsers !== 0) {
                    \$XPOverview[] = [\"name\" => \$compareWithLabel, \"data\" => []];

                    foreach (\$userIds as \$i => \$uId) {
                        // Get user awards
                        \$awards = \$awardsModule->getUserAwards(\$uId);

                        // Calculate XP for each award type
                        \$t = 0;
                        foreach(\$awardTypes as \$type => \$label) {
                            \$totalUserXP = array_sum(array_column(array_filter(\$awards, function (\$award) use (\$type) {
                                return \$award[\"type\"] === \$type;
                            }), \"reward\"));
                            if (\$i == 0) \$XPOverview[1][\"data\"][\$t] = [\"x\" => \$label, \"y\" => round(\$totalUserXP / \$nrUsers)];
                            else \$XPOverview[1][\"data\"][\$t][\"y\"] += round(\$totalUserXP / \$nrUsers);
                            \$t++;
                        }
                    }
                }
            }
        }

        return new ValueNode(\$XPOverview, Core::dictionary()->getLibraryById(CollectionLibrary::ID));",
            "args" => ["int \$userId", "string \$awardTypes = \"\"", "array \$compareWith = []", "string \$compareWithLabel = \"Others\""]
        ];

        return [$XPEvolution, $XPDistribution, $XPOverview];
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new XPLevels($copyTo);

        // Copy config
        $maxXP = $this->getMaxXP();
        $copiedModule->updateMaxXP($maxXP);
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);

        // Copy levels
        $levels = Level::getLevels($this->course->getId());
        foreach ($levels as $level) {
            $level = new Level($level["id"]);
            $level->copyLevel($copyTo);
        }
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
        $this->removeEvents();
        $this->removeProviders();
        $this->removeNotifications();
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
                        "contentType" => "container",
                        "classList" => "flex flex-wrap items-center",
                        "contents" => [
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxXP",
                                "value" => $this->getMaxXP(),
                                "placeholder" => "Max. XP",
                                "options" => [
                                    "topLabel" => "Total max. XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum XP each student can earn in total"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxExtraCredit",
                                "value" => $this->getMaxExtraCredit(),
                                "placeholder" => "Max. extra credit",
                                "options" => [
                                    "topLabel" => "Total max. extra credit XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum extra credit XP each student can earn in total"
                            ]
                        ]
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
            if ($input["id"] == "maxXP") $this->updateMaxXP($input["value"]);
            if ($input["id"] == "maxExtraCredit") $this->updateMaxExtraCredit($input["value"]);
        }
    }

    public function getLists(): array
    {
        $levels = Level::getLevels($this->course->getId());
        return [
            [
                "name" => "Levels",
                "description" => "Create multiple descriptive levels dividing the range of total XP earned by students, 
                                encouraging them to climb to highest ones. There needs to be at least one level setup.",
                "itemName" => "level",
                "topActions" => [
                    "left" => [
                        ["action" => Action::IMPORT, "icon" => "jam-download"],
                        ["action" => Action::EXPORT, "icon" => "jam-upload"]
                    ],
                    "right" => [
                        ["action" => Action::NEW, "icon" => "feather-plus-circle", "color" => "primary"]
                    ]
                ],
                "headers" => [
                    ["label" => "Level", "align" => "middle"],
                    ["label" => "Description", "align" => "middle"],
                    ["label" => "Minimim XP", "align" => "middle"]
                ],
                "data" => array_map(function ($level) {
                    return [
                        ["type" => DataType::NUMBER, "content" => ["value" => $level["number"], "valueFormat" => "none"]],
                        ["type" => DataType::TEXT, "content" => ["text" => $level["description"]]],
                        ["type" => DataType::NUMBER, "content" => ["value" => $level["minXP"], "valueFormat" => "default"]]
                    ];
                }, $levels),
                "actions" => [
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL_BUT_FIRST],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
                ],
                "options" => [
                    "order" => [[0, "asc"]],
                    "columnDefs" => [
                        ["type" => "natural", "targets" => [0, 1, 2]]
                    ]
                ],
                "items" => $levels,
                Action::NEW => [
                    "modalSize" => "md",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Level description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Description for level"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "minXP",
                                    "placeholder" => "Level minimum XP",
                                    "options" => [
                                        "topLabel" => "Minimum XP",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Minimum XP to be in the level"
                                ]
                            ]
                        ]
                    ]
                ],
                Action::EDIT => [
                    "modalSize" => "md",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "description",
                                    "placeholder" => "Level description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Description for level"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL_BUT_FIRST,
                                    "id" => "minXP",
                                    "placeholder" => "Level minimum XP",
                                    "options" => [
                                        "topLabel" => "Minimum XP",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Minimum XP to be in the level"
                                ]
                            ]
                        ]
                    ]
                ],
                Action::IMPORT => [
                    "extensions" => [".csv", ".txt"],
                    "csvHeaders" => Level::HEADERS,
                    "csvRows" => [
                        ["AWOL", "0"],
                        ["Level 1", "1000"],
                        ["...", "..."]
                    ]
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item): ?string
    {
        $courseId = $this->course->getId();
        if ($listName == "Levels") {
            if ($action == Action::NEW) Level::addLevel($courseId, $item["minXP"], $item["description"]);
            elseif ($action == Action::EDIT) {
                $level = Level::getLevelById($item["id"]);
                $level->editLevel($item["minXP"], $item["description"]);
            } elseif ($action == Action::DELETE) Level::deleteLevel($item["id"]);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function importListingItems(string $listName, string $file, bool $replace = true): ?int
    {
        if ($listName == "Levels") return Level::importLevels($this->course->getId(), $file, $replace);
        return null;
    }

    public function exportListingItems(string $listName, array $items): ?array
    {
        if ($listName == "Levels") return Level::exportLevels($this->course->getId(), $items);
        return null;
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxXP(): ?int
    {
        $maxXP = Core::database()->select(self::TABLE_XP_CONFIG, ["course" => $this->course->getId()], "maxXP");
        if (!is_null($maxXP)) $maxXP = intval($maxXP);
        return $maxXP;
    }

    /**
     * @throws Exception
     */
    public function updateMaxXP(?int $max)
    {
        Core::database()->update(self::TABLE_XP_CONFIG, ["maxXP" => $max], ["course" => $this->course->getId()]);
    }

    public function getMaxExtraCredit(): ?int
    {
        $maxExtraCredit = Core::database()->select(self::TABLE_XP_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit");
        if (!is_null($maxExtraCredit)) $maxExtraCredit = intval($maxExtraCredit);
        return $maxExtraCredit;
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(?int $max)
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
     * Gets total extra credit XP for a given user.
     *
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public function getUserExtraCreditXP(int $userId): int
    {
        if (!$this->userHasXP($userId))
            throw new Exception("User with ID = " . $userId . " doesn't have XP initialized.");

        $totalExtraCredit = 0;

        // Get badges extra credit
        $badgesModule = new Badges($this->course);
        if ($badgesModule->isEnabled()) $totalExtraCredit += $this->getUserBadgesXP($userId, true);

        // Get skills extra credit
        $skillsModule = new Skills($this->course);
        if ($skillsModule->isEnabled()) $totalExtraCredit += $this->getUserSkillsXP($userId, true);

        // Get streaks extra credit
        $streaksModule = new Streaks($this->course);
        if ($streaksModule->isEnabled()) $totalExtraCredit += $this->getUserStreaksXP($userId, true);

        return $totalExtraCredit;
    }

    /**
     * Gets total XP for a given user of a specific type of award.
     * NOTE: types of awards in AwardType.php
     *
     * @param int $userId
     * @param string $type
     * @param int|null $instance
     * @return int
     * @throws Exception
     */
    public function getUserXPByType(int $userId, string $type, ?int $instance = null): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserTotalRewardByType($userId, $type, $instance);
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
     *  - if false --> gets total XP only for skills that are not extra
     *  - if true --> gets total XP only for skills that are extra
     *
     * @param int $userId
     * @param bool|null $extra
     * @return int
     * @throws Exception
     */
    public function getUserSkillsXP(int $userId, bool $extra = null): int
    {
        $awardsModule = new Awards($this->course);
        return $awardsModule->getUserSkillsTotalReward($userId, null, $extra);
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
        $newXP = min($this->getMaxXP() ?? PHP_INT_MAX, $xp);
        Core::database()->update(self::TABLE_XP, ["xp" => $newXP, "level" => Level::getLevelByXP($courseId, $xp)->getId()],
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
        return !empty(Core::database()->select(self::TABLE_XP, ["course" => $this->course->getId(), "user" => $userId]));
    }


    /*** ---- Grade Verifications ---- ***/

    // TODO: refactor and improve (check old gamecourse 21/22)

    /**
     * Returns notifications to be sent to a student.
     *
     * @param int $userId
     * @throws Exception
     */
    public function getNotification($userId): ?string
    {
        $totalXP = $this->getUserXP($userId);
        $levels = Level::getLevels($this->course->getId());

        foreach($levels as $level) {
            if ($level["minXP"] > 0 && $totalXP / $level["minXP"] < 1 && $totalXP / $level["minXP"] >= 0.9) {
                $params["levelNumber"] = $level["number"];
                $params["levelDescription"] = $level["description"] ;
                $params["XPLeft"] = $level["minXP"] - $totalXP;
                $format = Core::database()->select(Notification::TABLE_NOTIFICATION_CONFIG, ["course" => $this->course->getId(), "module" => $this->getId()])["format"];
                return Notification::getFinalNotificationText($this->course->getId(), $userId, $format, $params);
            }
        }
        return null;
    }
}
