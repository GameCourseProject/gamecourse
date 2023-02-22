<?php
namespace GameCourse\Module\Streaks;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use Utils\Cache;
use Utils\Time;

/**
 * This is the Streaks module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Streaks extends Module
{
    const TABLE_STREAK = Streak::TABLE_STREAK;
    const TABLE_STREAK_PROGRESSION = Streak::TABLE_STREAK_PROGRESSION;
    const TABLE_STREAK_DEADLINE = Streak::TABLE_STREAK_DEADLINE;
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
        $this->initRules();

        // Init config
        Core::database()->insert(self::TABLE_STREAK_CONFIG, ["course" => $this->course->getId()]);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Streaks($copyTo);

        // Copy config
        $maxXP = $this->getMaxXP();
        $copiedModule->updateMaxXP($maxXP);
        $maxExtraCredit = $this->getMaxExtraCredit();
        $copiedModule->updateMaxExtraCredit($maxExtraCredit);

        // Copy streaks
        $streaks = Streak::getStreaks($this->course->getId(), null, "id");
        foreach ($streaks as $streak) {
            $streak = new Streak($streak["id"]);
            $streak->copyStreak($copyTo);
        }
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
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
                                    "topLabel" => "Streaks max. XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum XP each student can earn with streaks"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxExtraCredit",
                                "value" => $this->getMaxExtraCredit(),
                                "placeholder" => "Max. extra credit",
                                "options" => [
                                    "topLabel" => "Streaks max. extra credit XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum extra credit XP each student can earn with streaks"
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

    /**
     * @throws Exception
     */
    public function getLists(): array
    {
        $streaks = array_map(function ($streak) {
            $streak["isPeriodic"] = !is_null($streak["periodicityTime"]);
            $streak["periodicity"] = ["number" => $streak["periodicityNumber"], "time" => $streak["periodicityTime"]];
            return $streak;
        }, Streak::getStreaks($this->course->getId()));

        $lists = [
            [
                "name" => "Streaks",
                "itemName" => "streak",
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
                    ["label" => "Streak", "align" => "left"],
                    ["label" => "Reward (XP)", "align" => "middle"],
                    ["label" => "Repeats", "align" => "middle"],
                    ["label" => "Extra", "align" => "middle"],
                    ["label" => "Active", "align" => "middle"]
                ],
                "data" => array_map(function ($streak) {
                    return [
                        ["type" => DataType::AVATAR, "content" => ["avatarSrc" => $streak["image"], "avatarTitle" => $streak["name"], "avatarSubtitle" => $streak["description"]]],
                        ["type" => DataType::NUMBER, "content" => ["value" => $streak["reward"], "valueFormat" => "default"]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isRepeatable", "toggleValue" => $streak["isRepeatable"]]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isExtra", "toggleValue" => $streak["isExtra"]]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isActive", "toggleValue" => $streak["isActive"]]]
                    ];
                }, $streaks),
                "actions" => [
                    ["action" => Action::VIEW_RULE, "scope" => ActionScope::ALL],
                    ["action" => Action::DUPLICATE, "scope" => ActionScope::ALL],
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL],
                    ["action" => Action::DELETE, "scope" => ActionScope::ALL],
                    ["action" => Action::EXPORT, "scope" => ActionScope::ALL]
                ],
                "options" => [
                    "order" => [[0, "asc"]],
                    "columnDefs" => [
                        ["type" => "natural", "targets" => [0, 1]],
                        ["orderable" => false, "targets" => [2, 3, 4]]
                    ]
                ],
                "items" => $streaks,
                Action::NEW => [
                    "modalSize" => "lg",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "name",
                                    "placeholder" => "Streak name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Streak name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Streak description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "required" => true,
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Streak description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description of how to earn the streak"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::COLOR,
                                    "id" => "color",
                                    "placeholder" => "Streak color",
                                    "options" => [
                                        "topLabel" => "Color"
                                    ],
                                    "helper" => "Color for streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "reward",
                                    "placeholder" => "Streak reward (XP)",
                                    "options" => [
                                        "topLabel" => "Reward (XP)",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when earning the streak"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-10",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "goal",
                                    "placeholder" => "Number of steps",
                                    "options" => [
                                        "topLabel" => "Steps",
                                        "required" => true,
                                        "minValue" => 1
                                    ],
                                    "helper" => "Number of steps to earn the streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/4",
                                    "type" => InputType::TOGGLE,
                                    "id" => "isRepeatable",
                                    "options" => [
                                        "classList" => "sm:mt-12",
                                        "label" => "Repeatable",
                                    ],
                                    "helper" => "Whether the streak can be earned multiple times"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/4",
                                    "type" => InputType::TOGGLE,
                                    "id" => "isPeriodic",
                                    "options" => [
                                        "color" => "accent",
                                        "classList" => "sm:mt-12",
                                        "label" => "Periodic",
                                    ],
                                    "helper" => "Whether the streak is earned by doing actions periodically instead of consecutively"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "visibleWhen" => ["isPeriodic" => true],
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::PERIODICITY,
                                    "id" => "periodicity",
                                    "placeholder" => "Period of time",
                                    "options" => [
                                        "filterOptions" => [Time::SECOND, Time::YEAR],
                                        "color" => "accent",
                                        "topLabel" => "Period",
                                        "minNumber" => 1,
                                        "required" => true,
                                    ],
                                    "helper" => "Period of time available to earn (absolute period) or progress (relative period) in the streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::SELECT,
                                    "id" => "periodicityType",
                                    "placeholder" => "Type of period",
                                    "options" => [
                                        "color" => "accent",
                                        "options" => [
                                            ["value" => "absolute", "text" => "Absolute"],
                                            ["value" => "relative", "text" => "Relative"]
                                        ],
                                        "search" => false,
                                        "topLabel" => "Period type",
                                        "required" => true
                                    ],
                                    "helper" => "Whether period is 'absolute' (doing actions every period of time) or 'relative' (referring to period of time between each action)"
                                ],
                                [
                                    "contentType" => "item",
                                    "disabledWhen" => ["periodicityType" => "relative"],
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "periodicityGoal",
                                    "placeholder" => "Goal for period",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Period goal",
                                        "minValue" => 1,
                                        "required" => true
                                    ],
                                    "helper" => "Number of actions to be performed on each period"
                                ],
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "full",
                                    "type" => "dynamic-text",
                                    "id" => "example",
                                    "options" => [
                                        "type" => "conditional",
                                        "value" => [
                                            "consecutive (goal = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => false],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something consecutively one time.</i>"
                                            ],
                                            "consecutive (goal > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => false],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something consecutively {{goal}} times.</i>"
                                            ],

                                            "periodic absolute (goal = 1 and periodicityGoal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal = 1 and periodicityGoal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],
                                            "periodic absolute (goal = 1 and periodicityGoal > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-error'>Period goal can't be bigger than the number of steps.</b>"
                                            ],

                                            "periodic absolute (goal = period goal and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => "{{goal}}"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal = period goal and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => "{{goal}}"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],

                                            "periodic absolute (goal > 1 and periodicityGoal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal > 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times, {{periodicityGoal}} every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal > 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times, {{periodicityGoal}} every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],

                                            "periodic relative (goal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time with no more than a {{periodicity.time}} in between.</i>"
                                            ],
                                            "periodic relative (goal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time with no more than {{periodicity.number}} {{periodicity.time}}s in between.</i>"
                                            ],
                                            "periodic relative (goal > 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times with no more than a {{periodicity.time}} in between.</i>"
                                            ],
                                            "periodic relative (goal > 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times with no more than {{periodicity.number}} {{periodicity.time}}s in between.</i>"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                Action::EDIT => [
                    "modalSize" => "lg",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "name",
                                    "placeholder" => "Streak name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Streak name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Streak description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "required" => true,
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Streak description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description of how to earn the streak"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::COLOR,
                                    "id" => "color",
                                    "placeholder" => "Streak color",
                                    "options" => [
                                        "topLabel" => "Color"
                                    ],
                                    "helper" => "Color for streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "reward",
                                    "placeholder" => "Streak reward (XP)",
                                    "options" => [
                                        "topLabel" => "Reward (XP)",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when earning the streak"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-10",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "goal",
                                    "placeholder" => "Number of steps",
                                    "options" => [
                                        "topLabel" => "Steps",
                                        "required" => true,
                                        "minValue" => 1
                                    ],
                                    "helper" => "Number of steps to earn the streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/4",
                                    "type" => InputType::TOGGLE,
                                    "id" => "isRepeatable",
                                    "options" => [
                                        "classList" => "sm:mt-12",
                                        "label" => "Repeatable",
                                    ],
                                    "helper" => "Whether the streak can be earned multiple times"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/4",
                                    "type" => InputType::TOGGLE,
                                    "id" => "isPeriodic",
                                    "options" => [
                                        "color" => "accent",
                                        "classList" => "sm:mt-12",
                                        "label" => "Periodic",
                                    ],
                                    "helper" => "Whether the streak is earned by doing actions periodically instead of consecutively"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "visibleWhen" => ["isPeriodic" => true],
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::PERIODICITY,
                                    "id" => "periodicity",
                                    "placeholder" => "Period of time",
                                    "options" => [
                                        "filterOptions" => [Time::SECOND, Time::YEAR],
                                        "color" => "accent",
                                        "topLabel" => "Period",
                                        "minNumber" => 1,
                                        "required" => true,
                                    ],
                                    "helper" => "Period of time available to earn (absolute period) or progress (relative period) in the streak"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::SELECT,
                                    "id" => "periodicityType",
                                    "placeholder" => "Type of period",
                                    "options" => [
                                        "color" => "accent",
                                        "options" => [
                                            ["value" => "absolute", "text" => "Absolute"],
                                            ["value" => "relative", "text" => "Relative"]
                                        ],
                                        "search" => false,
                                        "topLabel" => "Period type",
                                        "required" => true
                                    ],
                                    "helper" => "Whether period is 'absolute' (doing actions every period of time) or 'relative' (referring to period of time between each action)"
                                ],
                                [
                                    "contentType" => "item",
                                    "disabledWhen" => ["periodicityType" => "relative"],
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "periodicityGoal",
                                    "placeholder" => "Goal for period",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Period goal",
                                        "minValue" => 1,
                                        "required" => true
                                    ],
                                    "helper" => "Number of actions to be performed on each period"
                                ],
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "full",
                                    "type" => "dynamic-text",
                                    "id" => "example",
                                    "options" => [
                                        "type" => "conditional",
                                        "value" => [
                                            "consecutive (goal = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => false],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something consecutively one time.</i>"
                                            ],
                                            "consecutive (goal > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => false],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something consecutively {{goal}} times.</i>"
                                            ],

                                            "periodic absolute (goal = 1 and periodicityGoal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal = 1 and periodicityGoal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],
                                            "periodic absolute (goal = 1 and periodicityGoal > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-error'>Period goal can't be bigger than the number of steps.</b>"
                                            ],

                                            "periodic absolute (goal = period goal and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => "{{goal}}"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal = period goal and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => "{{goal}}"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],

                                            "periodic absolute (goal > 1 and periodicityGoal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => 1],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal > 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times, {{periodicityGoal}} every {{periodicity.time}}.</i>"
                                            ],
                                            "periodic absolute (goal > 1 and periodicityGoal > 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "absolute", "periodicityGoal" => true],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times, {{periodicityGoal}} every {{periodicity.number}} {{periodicity.time}}s.</i>"
                                            ],

                                            "periodic relative (goal = 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time with no more than a {{periodicity.time}} in between.</i>"
                                            ],
                                            "periodic relative (goal = 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => 1, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something one time with no more than {{periodicity.number}} {{periodicity.time}}s in between.</i>"
                                            ],
                                            "periodic relative (goal > 1 and periodicityNumber = 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => 1, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times with no more than a {{periodicity.time}} in between.</i>"
                                            ],
                                            "periodic relative (goal > 1 and periodicityNumber > 1)" => [
                                                "when" => ["goal" => true, "isPeriodic" => true, "periodicity.number" => true, "periodicity.time" => true, "periodicityType" => "relative"],
                                                "show" => "<b class='text-info'>Example for these settings:</b><br> <i>Doing something {{goal}} times with no more than {{periodicity.number}} {{periodicity.time}}s in between.</i>"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                Action::IMPORT => [
                    "extensions" => [".csv", ".txt"],
                    "csvHeaders" => Streak::HEADERS,
                    "csvRows" => [
                        ["Sage", "Get three consecutive maximum grades in quizzes", "#00A2FF", "3", "", "", "", "", "50", "100", "1", "0", "1"],
                        ["Constant Gardener", "Do five skills with no more than five days between them", "#36987B", "5", "1", "5", "day", "relative", "150", "100", "0", "1", "1"],
                        ["...", "...", "...", "...", "...", "...", "...", "...", "...", "...", "...", "...", "..."]
                    ]
                ]
            ]
        ];

        // Add tokens option as reward, if virtual currency enabled
        $VCModule = new VirtualCurrency($this->course);
        if ($VCModule->exists() && $VCModule->isEnabled()) {
            $VCName = $VCModule->getVCName();

            // Add VC reward to table
            array_splice($lists[0]["headers"], 2, 0, [
                ["label" => "Reward (" . $VCName . ")", "align" => "middle"],
            ]);
            array_splice($lists[0]["options"]["columnDefs"][0]["targets"], 2, 0, 2);
            $lists[0]["options"]["columnDefs"][1]["targets"] = array_map(function ($target) {
                return $target + 1;
            }, $lists[0]["options"]["columnDefs"][1]["targets"]);
            $lists[0]["data"] = array_map(function (&$row, $index) use ($streaks) {
                array_splice($row, 2, 0, [
                    ["type" => DataType::NUMBER, "content" => ["value" => $streaks[$index]["tokens"], "valueFormat" => "default"]],
                ]);
                return $row;
            }, $lists[0]["data"], array_keys($lists[0]["data"]));

            // Add option when creating streak
            array_splice($lists[0][Action::NEW]["contents"][1]["contents"], 2, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/3",
                    "type" => InputType::NUMBER,
                    "id" => "tokens",
                    "placeholder" => "Streak reward",
                    "options" => [
                        "topLabel" => "Reward (" . $VCName . ")",
                        "minValue" => 0
                    ],
                    "helper" => $VCName . " reward to be given when earning the streak"
                ]
            ]);
            $lists[0][Action::NEW]["contents"][1]["contents"] = array_map(function ($item) {
                $item["width"] = "1/3";
                return $item;
            }, $lists[0][Action::NEW]["contents"][1]["contents"]);

            // Add option when editing streak
            array_splice($lists[0][Action::EDIT]["contents"][1]["contents"], 2, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/3",
                    "type" => InputType::NUMBER,
                    "id" => "tokens",
                    "placeholder" => "Streak reward",
                    "options" => [
                        "topLabel" => "Reward (" . $VCName . ")",
                        "minValue" => 0
                    ],
                    "helper" => $VCName . " reward to be given when earning the streak"
                ]
            ]);
            $lists[0][Action::EDIT]["contents"][1]["contents"] = array_map(function ($item) {
                $item["width"] = "1/3";
                return $item;
            }, $lists[0][Action::EDIT]["contents"][1]["contents"]);
        }

        return $lists;
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item): ?string
    {
        $courseId = $this->course->getId();
        if ($listName == "Streaks") {
            if ($action == Action::NEW || $action == Action::DUPLICATE || $action == Action::EDIT) {
                // Get periodicity
                if ($item["isPeriodic"]) {
                    $item["periodicityNumber"] = $item["periodicity"]["number"];
                    $item["periodicityTime"] = $item["periodicity"]["time"];
                    if ($item["periodicityType"] === "relative") $item["periodicityGoal"] = 1;
                    if ($item["periodicityGoal"] > $item["goal"])
                        throw new Exception("Period goal needs to be smaller or equal than the number of steps.");

                } else {
                    $params = ["periodicityGoal", "periodicityNumber", "periodicityTime", "periodicityType"];
                    foreach ($params as $param) { $item[$param] = null; }
                }

                // Make verifications
                if ($item["isPeriodic"]) {
                    if (!isset($item["periodicityNumber"]) || !isset($item["periodicityTime"]) || !isset($item["periodicityType"]))
                        throw new Exception("Periodic streaks must have a period set and its type.");

                    if ($item["periodicityType"] === "absolute" && !isset($item["periodicityGoal"]))
                        throw new Exception("Periodic streaks with an absolute period type must have a period goal set.");
                }

                // Format name
                $name = $item["name"];
                if ($action == Action::DUPLICATE) $name .= " (Copy)";

                if ($action == Action::NEW || $action == Action::DUPLICATE) {
                    Streak::addStreak($courseId, $name, $item["description"], $item["color"] ?? null, $item["goal"],
                        $item["periodicityGoal"] ?? null, $item["periodicityNumber"] ?? null,
                        $item["periodicityTime"] ?? null, $item["periodicityType"] ?? null, $item["reward"],
                        $item["tokens"] ?? 0, $item["isExtra"] ?? false, $item["isRepeatable"] ?? false);

                } else {
                    $streak = Streak::getStreakById($item["id"]);
                    $streak->editStreak($item["name"], $item["description"], $item["color"] ?? null, $item["goal"],
                        $item["periodicityGoal"] ?? null, $item["periodicityNumber"] ?? null,
                        $item["periodicityTime"] ?? null, $item["periodicityType"] ?? null,
                        $item["reward"], $item["tokens"] ?? 0, $item["isExtra"] ?? false, $item["isRepeatable"] ?? false,
                        $item["isActive"] ?? false);
                }

            } elseif ($action == Action::DELETE) Streak::deleteStreak($item["id"]);
        }

        return null;
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
    public function exportListingItems(string $listName, array $items): ?array
    {
        if ($listName == "Streaks") return Streak::exportStreaks($this->course->getId(), $items);
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

    public function getMaxXP(): ?int
    {
        $maxXP = Core::database()->select(self::TABLE_STREAK_CONFIG, ["course" => $this->course->getId()], "maxXP");
        if (!is_null($maxXP)) $maxXP = intval($maxXP);
        return $maxXP;
    }

    /**
     * @throws Exception
     */
    public function updateMaxXP(?int $max)
    {
        Core::database()->update(self::TABLE_STREAK_CONFIG, ["maxXP" => $max], ["course" => $this->course->getId()]);
    }

    public function getMaxExtraCredit(): ?int
    {
        $maxExtraCredit = Core::database()->select(self::TABLE_STREAK_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit");
        if (!is_null($maxExtraCredit)) $maxExtraCredit = intval($maxExtraCredit);
        return $maxExtraCredit;
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(?int $max)
    {
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
            $student["nrCompletions"] = $streakNrCompletions;
            if ($streakNrCompletions > 0) $users[] = $student;
        }
        return $users;
    }

    /**
     * Gets streaks earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isRepeatable
     * @return array
     * @throws Exception
     */
    public function getUserStreaks(int $userId, bool $isExtra = null, bool $isRepeatable = null): array
    {
        $course = $this->getCourse();
        $awardsModule = new Awards($course);
        $userStreakAwards = $awardsModule->getUserStreaksAwards($userId, $isRepeatable, $isExtra);

        // Group by streak ID
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

        $cacheId = "streak_progression_u" . $userId . "_s" . $streakId;
        $cacheValue = Cache::get($courseId, $cacheId);

        if (AutoGame::isRunning($courseId) && !is_null($cacheValue)) {
            // NOTE: get value from cache while AutoGame is running
            //       since progression table is not stable
            return $cacheValue;

        } else {
            $progression = Core::database()->select(self::TABLE_STREAK_PROGRESSION,
                ["user" => $userId, "streak" => $streakId], "COUNT(*)");

            // Store in cache
            $cacheValue = $progression;
            Cache::store($courseId, $cacheId, $cacheValue);

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

    /**
     * Gets streak deadline for a given user.
     *
     * @param int $userId
     * @param int $streakdId
     * @return string|null
     */
    public function getUserStreakDeadline(int $userId, int $streakdId): ?string
    {
        $streak = Streak::getStreakById($streakdId);
        return $streak->getDeadline($userId);
    }
}
