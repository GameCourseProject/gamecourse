<?php
namespace GameCourse\Module\Badges;

use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Awards\AwardType;
use GameCourse\Module\Config\Action;
use GameCourse\Module\Config\ActionScope;
use GameCourse\Module\Config\DataType;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Moodle\Moodle;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\Views\Dictionary\ReturnType;
use Utils\Cache;
use Utils\Utils;
use GameCourse\NotificationSystem\Notification;

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
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/'];

    const DATA_FOLDER = 'badges';
    const RULE_SECTION = "Badges";

    // FIXME -> Change later (profiling_adaptation_role_connection should not be hardcoded)
    // NOTE: profiling_adaptation_role_connection not really used at the moment
    // Structure is: [ Game_element => [ "VersionA" => [ description, profiling_adaptation_role_connection ] ] ]
    const ADAPTATION_BADGES = [ "Badges" => [
        "B001" => ["Badges displayed in alphabetic order", [ "Regular", "Achiever" ]],
        "B002" => ["Badges displayed with achieved first", [ "Halfhearted", "Underachiever" ] ] ] ];

    const NOTIFICATIONS_DESCRIPTION = "Lets the user know when he's close to leveling up a Badge.";
    const NOTIFICATIONS_FORMAT = "You are %numberOfEventsLeft events away from achieving the %badgeName badge 🎖️ %badgeDescription - %nextLevelDescription";
    const NOTIFICATIONS_VARIABLES = "numberOfEventsLeft,badgeName,badgeDescription,nextLevelDescription";

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
        $this->initTemplates();
        $this->initRules();
        $this->initProviders();

        // Init config
        Core::database()->insert(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()]);

        // Add adaptation roles
        // FIXME: Debug only
         $this->addAdaptationRolesToCourse(self::ADAPTATION_BADGES);
        // initEvents(); // FIXME: Debug only
         GameElement::addGameElement($this->course->getId(), self::ID);

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

    public function providers(): array
    {
        $badgeDistribution =  [
            "name" => "badgeDistribution",
            "description" => "Provides a distribution of the total number of badges of given users. Option for interval to group badges, max. number of badges and whether to show an average of each interval group.",
            "returnType" => ReturnType::COLLECTION,
            "function" => "\$badgeDistribution = [[\"name\" => \"Badge Distribution\", \"type\" => \"column\", \"data\" => []]];
        if (\$showAverage) \$badgeDistribution[] = [\"name\" => \"Average\", \"type\" => \"line\", \"data\" => []];
        if (\$interval > \$max) \$interval = 1;

        if (Core::dictionary()->mockData()) {
            if (is_null(\$max)) \$max = 60;
            for (\$i = (\$interval === 1 ? 0 : \$interval); \$i <= \$max; \$i += \$interval) {
                \$badgeDistribution[0][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(0, 50)];
                if (\$showAverage) \$badgeDistribution[1][\"data\"][] = [\"x\" => \$i, \"y\" => Core::dictionary()->faker()->numberBetween(0, 50)];
            }

        } else {
            \$course = Core::dictionary()->getCourse();
            if (!\$course) \$this->throwError(\"badgeDistribution\", \"no course found\");

            \$userIds = array_map(function (\$user) { if (is_array(\$user)) return \$user[\"id\"]; return \$user->getId(); }, \$users);
            \$nrUsers = count(\$userIds);

            if (\$nrUsers !== 0) {
                // Get each user #badges
                \$badgesByUser = [];
                foreach (\$userIds as \$userId) {
                    \$badgesModules = new \GameCourse\Module\Badges\Badges(\$course);
                    \$badgesByUser[] = count(\$badgesModules->getUserBadges(\$userId));
                }

                // Initialize data
                if (is_null(\$max)) \$max = ceil(max(\$badgesByUser) / \$interval) * \$interval;
                for (\$i = (\$interval === 1 ? 0 : \$interval); \$i <= \$max; \$i += \$interval) {
                    \$badgeDistribution[0][\"data\"][] = [\"x\" => \$i, \"y\" => 0];
                    if (\$showAverage) \$badgeDistribution[1][\"data\"][] = [\"x\" => \$i, \"y\" => 0];
                }

                // Process data
                foreach (\$badgesByUser as \$userBadges) {
                    \$i = \$interval === 1 ? \$userBadges : (\$userBadges === \$interval ? floor(\$userBadges / \$interval) - 1 : floor(\$userBadges / \$interval));
                    \$badgeDistribution[0][\"data\"][\$i][\"y\"] += 1;
                    if (\$showAverage) {
                        if (\$badgeDistribution[1][\"data\"][\$i][\"y\"] === 0) \$badgeDistribution[1][\"data\"][\$i][\"y\"] = round(\$userBadges / \$nrUsers);
                        else \$badgeDistribution[1][\"data\"][\$i][\"y\"] += round(\$userBadges / \$nrUsers);
                    }
                }
            }
        }

        return new ValueNode(\$badgeDistribution, Core::dictionary()->getLibraryById(CollectionLibrary::ID));",
            "args" => ["array \$users", "int \$interval = 1", "int \$max = null", "bool \$showAverage = false"]
        ];

        return [$badgeDistribution];
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new Badges($copyTo);

        // Copy config
        $maxXP = $this->getMaxXP();
        $copiedModule->updateMaxXP($maxXP);
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
        $this->removeAdaptationRolesFromCourse(self::ADAPTATION_BADGES);
        GameElement::removeGameElement($this->course->getId(), self::ID);
        $this->cleanDatabase();
        $this->removeDataFolder();
        $this->removeTemplates();
        $this->removeRules();
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
                                    "topLabel" => "Badges max. XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum XP each student can earn with badges"
                            ],
                            [
                                "contentType" => "item",
                                "width" => "1/3",
                                "type" => InputType::NUMBER,
                                "id" => "maxExtraCredit",
                                "value" => $this->getMaxExtraCredit(),
                                "placeholder" => "Max. extra credit",
                                "options" => [
                                    "topLabel" => "Badges max. extra credit XP",
                                    "minValue" => 0
                                ],
                                "helper" => "Maximum extra credit XP each student can earn with badges"
                            ]
                        ]
                    ]
                ]
            ],
            // TODO: badge overlays
        ];

//        return [
//            ["id" => "maxExtraCredit", "label" => "Max. Extra Credit", "type" => InputType::NUMBER, "value" => $this->getMaxExtraCredit()],
//            ["id" => "extraOverlay", "label" => "Overlay for extra", "type" => InputType::IMAGE, "value" => null],
//            ["id" => "braggingOverlay", "label" => "Overlay for bragging", "type" => InputType::IMAGE, "value" => null],
//            ["id" => "lvl2Overlay", "label" => "Overlay for level 2", "type" => InputType::IMAGE, "value" => null],
//            ["id" => "lvl3Overlay", "label" => "Overlay for level 3", "type" => InputType::IMAGE, "value" => null],
//        ];
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
        $badges = Badge::getBadges($this->course->getId());
        foreach ($badges as &$badge) {
            $isCount = $badge["isCount"];
            $isPoint = $badge["isPoint"];
            $badge["isCount"] = $isCount ? "isCount" : "isPoint";
            $badge["isPoint"] = $isPoint ? "isPoint" : "isCount";
        }

        $lists = [
            [
                "name" => "Badges",
                "itemName" => "badge",
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
                    ["label" => "Badge", "align" => "left"],
                    ["label" => "# Levels", "align" => "middle"],
                    ["label" => "Bragging", "align" => "middle"],
                    ["label" => "Extra", "align" => "middle"],
                    ["label" => "Active", "align" => "middle"]
                ],
                "data" => array_map(function ($badge) {
                    return [
                        ["type" => DataType::AVATAR, "content" => ["avatarSrc" => $badge["image"], "avatarTitle" => $badge["name"], "avatarSubtitle" => $badge["description"]]],
                        ["type" => DataType::NUMBER, "content" => ["value" => $badge["nrLevels"], "valueFormat" => "none"]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isBragging", "toggleValue" => $badge["isBragging"]]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isExtra", "toggleValue" => $badge["isExtra"]]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isActive", "toggleValue" => $badge["isActive"]]]
                    ];
                }, $badges),
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
                "items" => $badges,
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
                                    "placeholder" => "Badge name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Badge name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for badge"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Badge description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "required" => true,
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Badge description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description of how to earn the badge"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "full",
                                    "type" => InputType::FILE,
                                    "id" => "image",
                                    "options" => [
                                        "accept" => [".svg", ".png", ".jpg", ".jpeg"],
                                        "size" => "xs",
                                        "color" => "primary",
                                        "label" => "Image"
                                    ],
                                    "helper" => "Image to reperesent badge"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::RADIO,
                                    "id" => "isCount",
                                    "options" => [
                                        "group" => "badge-type",
                                        "optionValue" => "isCount",
                                        "label" => "Based on counts",
                                        "required" => true
                                    ],
                                    "helper" => "Whether badge is earned by counting ocurrences of some type"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::RADIO,
                                    "id" => "isPoint",
                                    "options" => [
                                        "group" => "badge-type",
                                        "optionValue" => "isPoint",
                                        "label" => "Based on points",
                                        "required" => true
                                    ],
                                    "helper" => "Whether badge is earned by earning a certain amount of points"
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
                                    "type" => InputType::TEXT,
                                    "id" => "desc1",
                                    "placeholder" => "Description of level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Level 1",
                                        "required" => true,
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "reward1",
                                    "placeholder" => "Reward (XP) of level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Reward (XP)",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "goal1",
                                    "placeholder" => "Goal for level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Goal",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to reach level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::TEXT,
                                    "id" => "desc2",
                                    "placeholder" => "Description of level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Level 2",
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "reward2",
                                    "placeholder" => "Reward (XP) of level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "goal2",
                                    "placeholder" => "Goal for level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Goal",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to reach level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::TEXT,
                                    "id" => "desc3",
                                    "placeholder" => "Description of level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Level 3",
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 3"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "reward3",
                                    "placeholder" => "Reward (XP) of level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 3"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "id" => "goal3",
                                    "placeholder" => "Goal for level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Goal",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to reach level 3"
                                ] // NOTE: limit of 3 levels
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
                                    "scope" => ActionScope::ALL,
                                    "id" => "name",
                                    "placeholder" => "Badge name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Badge name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for badge"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "description",
                                    "placeholder" => "Badge description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "required" => true,
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Badge description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description of how to earn the badge"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-3",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "full",
                                    "type" => InputType::FILE,
                                    "scope" => ActionScope::ALL,
                                    "id" => "image",
                                    "options" => [
                                        "accept" => [".svg", ".png", ".jpg", ".jpeg"],
                                        "size" => "xs",
                                        "color" => "primary",
                                        "label" => "Image"
                                    ],
                                    "helper" => "Image to reperesent badge"
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::RADIO,
                                    "scope" => ActionScope::ALL,
                                    "id" => "isCount",
                                    "options" => [
                                        "group" => "badge-type",
                                        "optionValue" => "isCount",
                                        "label" => "Based on counts",
                                        "required" => true
                                    ],
                                    "helper" => "Whether badge is earned by counting ocurrences of some type"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::RADIO,
                                    "scope" => ActionScope::ALL,
                                    "id" => "isPoint",
                                    "options" => [
                                        "group" => "badge-type",
                                        "optionValue" => "isPoint",
                                        "label" => "Based on points",
                                        "required" => true
                                    ],
                                    "helper" => "Whether badge is earned by earning a certain amount of points"
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
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "desc1",
                                    "placeholder" => "Description of level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Level 1",
                                        "required" => true,
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "reward1",
                                    "placeholder" => "Reward (XP) of level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Reward (XP)",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "goal1",
                                    "placeholder" => "Goal for level 1",
                                    "options" => [
                                        "color" => "primary",
                                        "topLabel" => "Goal",
                                        "required" => true,
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to earned to reach level 1"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "desc2",
                                    "placeholder" => "Description of level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Level 2",
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "reward2",
                                    "placeholder" => "Reward (XP) of level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "goal2",
                                    "placeholder" => "Goal for level 2",
                                    "options" => [
                                        "color" => "secondary",
                                        "topLabel" => "Goal",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to earned to reach level 2"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "desc3",
                                    "placeholder" => "Description of level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Level 3",
                                        "maxLength" => 100
                                    ],
                                    "helper" => "Description of how to reach badge level 3"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "reward3",
                                    "placeholder" => "Reward (XP) of level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Reward (XP)",
                                        "minValue" => 0
                                    ],
                                    "helper" => "XP reward to be given when reaching badge level 3"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/3",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "goal3",
                                    "placeholder" => "Goal for level 3",
                                    "options" => [
                                        "color" => "accent",
                                        "topLabel" => "Goal",
                                        "minValue" => 0
                                    ],
                                    "helper" => "Number of ocurrences/points to earned to reach level 3"
                                ] // NOTE: limit of 3 levels
                            ]
                        ]
                    ]
                ],
                Action::IMPORT => [
                    "extensions" => [".zip"]
                ]
            ]
        ];

        // Add tokens option as reward, if virtual currency enabled
        $VCModule = new VirtualCurrency($this->course);
        if ($VCModule->exists() && $VCModule->isEnabled()) {
            $VCName = $VCModule->getVCName();

            // Add option when creating badge
            array_splice($lists[0][Action::NEW]["contents"][3]["contents"], 2, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens1",
                    "placeholder" => "Reward ($VCName) of level 1",
                    "options" => [
                        "color" => "primary",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 1"
                ]
            ]);
            array_splice($lists[0][Action::NEW]["contents"][3]["contents"], 6, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens2",
                    "placeholder" => "Reward ($VCName) of level 2",
                    "options" => [
                        "color" => "secondary",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 2"
                ]
            ]);
            array_splice($lists[0][Action::NEW]["contents"][3]["contents"], 10, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens3",
                    "placeholder" => "Reward ($VCName) of level 3",
                    "options" => [
                        "color" => "accent",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 3"
                ]
            ]);
            $lists[0][Action::NEW]["contents"][3]["contents"] = array_map(function ($item) {
                $item["width"] = "1/4";
                return $item;
            }, $lists[0][Action::NEW]["contents"][3]["contents"]);

            // Add option when editing badge
            array_splice($lists[0][Action::EDIT]["contents"][3]["contents"], 2, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens1",
                    "placeholder" => "Reward ($VCName) of level 1",
                    "options" => [
                        "color" => "primary",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 1"
                ]
            ]);
            array_splice($lists[0][Action::EDIT]["contents"][3]["contents"], 6, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens2",
                    "placeholder" => "Reward ($VCName) of level 2",
                    "options" => [
                        "color" => "secondary",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 2"
                ]
            ]);
            array_splice($lists[0][Action::EDIT]["contents"][3]["contents"], 10, 0, [
                [
                    "contentType" => "item",
                    "width" => "1/4",
                    "type" => InputType::NUMBER,
                    "id" => "tokens3",
                    "placeholder" => "Reward ($VCName) of level 3",
                    "options" => [
                        "color" => "accent",
                        "topLabel" => "Reward ($VCName)",
                        "minValue" => 0
                    ],
                    "helper" => "$VCName reward to be given when reaching badge level 3"
                ]
            ]);
            $lists[0][Action::EDIT]["contents"][3]["contents"] = array_map(function ($item) {
                $item["width"] = "1/4";
                return $item;
            }, $lists[0][Action::EDIT]["contents"][3]["contents"]);
        }

        return $lists;
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item): ?string
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
                        "reward" => $item["reward" . $i],
                        "tokens" => $item["tokens" . $i] ?? 0
                    ];
                }

                if ($action == Action::NEW || $action == Action::DUPLICATE) {
                    // Format name
                    $name = $item["name"];
                    if ($action == Action::DUPLICATE) $name .= " (Copy)";

                    $badge = Badge::addBadge($courseId, $name, $item["description"], $item["isExtra"] ?? false,
                        $item["isBragging"] ?? false, $item["isCount"] === "isCount", $item["isPoint"] === "isPoint", $levels);

                    if ($action == Action::DUPLICATE)
                        Utils::copyDirectory(Badge::getBadgeByName($courseId, $item["name"])->getDataFolder() . "/", $badge->getDataFolder() . "/");

                } else {
                    $badge = Badge::getBadgeById($item["id"]);
                    $badge->editBadge($item["name"], $item["description"], $item["isExtra"] ?? false,
                        $item["isBragging"] ?? false, $item["isCount"] === "isCount", $item["isPoint"] === "isPoint",
                        $item["isActive"] ?? false, $levels);
                }

                if (isset($item["image"]) && !Utils::strStartsWith($item["image"], API_URL))
                    $badge->setImage($item["image"]);

            } elseif ($action == Action::DELETE) Badge::deleteBadge($item["id"]);
        }

        return null;
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
    public function exportListingItems(string $listName, array $items): ?array
    {
        if ($listName == "Badges") return Badge::exportBadges($this->course->getId(), $items);
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

    public function getMaxXP(): ?int
    {
        $maxXP = Core::database()->select(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()], "maxXP");
        if (!is_null($maxXP)) $maxXP = intval($maxXP);
        return $maxXP;
    }

    /**
     * @throws Exception
     */
    public function updateMaxXP(?int $max)
    {
        Core::database()->update(self::TABLE_BADGE_CONFIG, ["maxXP" => $max], ["course" => $this->course->getId()]);
    }

    public function getMaxExtraCredit(): ?int
    {
        $maxExtraCredit = Core::database()->select(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()], "maxExtraCredit");
        if (!is_null($maxExtraCredit)) $maxExtraCredit = intval($maxExtraCredit);
        return $maxExtraCredit;
    }

    /**
     * @throws Exception
     */
    public function updateMaxExtraCredit(?int $max)
    {
        Core::database()->update(self::TABLE_BADGE_CONFIG, ["maxExtraCredit" => $max], ["course" => $this->course->getId()]);
    }

    public function getBlankImage(): string
    {
        return API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . $this->id . "/assets/blank.png";
    }


    /*** ---------- Badges ---------- ***/

    /**
     * Gets users who have earned a given badge up to a certain level.
     * Option to order users by the date they acquired badge level.
     *
     * @param int $badgeId
     * @param int $level
     * @param bool $orderByDate
     * @return array
     * @throws Exception
     */
    public function getUsersWithBadge(int $badgeId, int $level, bool $orderByDate = true): array
    {
        $users = [];
        foreach ($this->getCourse()->getStudents() as $student) {
            $badgeLevel = $this->getUserBadgeLevel($student["id"], $badgeId);
            if ($badgeLevel >= $level) {
                if ($orderByDate) {
                    $awardsModule = new Awards($this->getCourse());
                    $awardDate = array_values(array_filter($awardsModule->getUserAwardsByType($student["id"], AwardType::BADGE, $badgeId), function ($award) use ($level) {
                        return Utils::strEndsWith($award["description"], "(level $level)");
                    }))[0]["date"];
                    $student["awardDate"] = $awardDate;
                }
                $users[] = $student;
            }
        }

        if ($orderByDate) usort($users, function ($a, $b) { return strcmp($a["awardDate"], $b["awardDate"]); });
        return $users;
    }

    /**
     * Gets badges earned by a given user.
     *
     * @param int $userId
     * @param bool|null $isExtra
     * @param bool|null $isBragging
     * @param bool|null $isCount
     * @param bool|null $isPoint
     * @return array
     * @throws Exception
     */
    public function getUserBadges(int $userId, bool $isExtra = null, bool $isBragging = null, bool $isCount = null,
                                  bool $isPoint = null): array
    {
        $awardsModule = new Awards($this->getCourse());
        $userBadgeAwards = $awardsModule->getUserBadgesAwards($userId, $isExtra, $isBragging, $isCount, $isPoint);

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
        $AutoGameIsRunning = AutoGame::isRunning($courseId);

        $cacheId = "badge_progression_u" . $userId . "_b" . $badgeId;
        $cacheValue = Cache::get($courseId, $cacheId);

        if ($AutoGameIsRunning && !empty($cacheValue)) {
            // NOTE: get value from cache while AutoGame is running
            //       since progression table is not stable
            return $cacheValue;

        } else {
            $badge = new Badge($badgeId);
            if ($badge->isPoint()) {
                $progression = Core::database()->select(self::TABLE_BADGE_PROGRESSION . " bp JOIN " . AutoGame::TABLE_PARTICIPATION . " p on bp.participation=p.id",
                    ["bp.user" => $userId, "bp.badge" => $badgeId], "SUM(rating)") ?? 0;

            } else $progression = Core::database()->select(self::TABLE_BADGE_PROGRESSION, ["user" => $userId, "badge" => $badgeId], "COUNT(*)");

            // Store in cache
            if (!$AutoGameIsRunning) {
                $cacheValue = $progression;
                Cache::store($courseId, $cacheId, $cacheValue);
            }

            return $progression;
        }
    }

    /**
     * Gets user progression information on a given badge,
     * e.g. description and links to posts.
     *
     * @param int $userId
     * @param int $badgeId
     * @return array
     */
    public function getUserBadgeProgressionInfo(int $userId, int $badgeId): array
    {
        $courseId = $this->getCourse()->getId();
        $AutoGameIsRunning = AutoGame::isRunning($courseId);

        $cacheId = "badge_progression_info_u" . $userId . "_b" . $badgeId;
        $cacheValue = Cache::get($courseId, $cacheId);

        if ($AutoGameIsRunning && !empty($cacheValue)) {
            // NOTE: get value from cache while AutoGame is running
            //       since progression table is not stable
            return $cacheValue;

        } else {
            $table = self::TABLE_BADGE_PROGRESSION . " bp LEFT JOIN " . AutoGame::TABLE_PARTICIPATION . " p on bp.participation=p.id";
            $progression = array_map(function ($p) {
                $info = ["description" => $p["description"]];
                if ($p["post"]) {
                    if ($p["source"] === Moodle::ID) {
                        $moodleModule = new Moodle($this->course);
                        $moodleURL = $moodleModule->getMoodleConfig()["moodleURL"];
                        $info["link"] = $moodleURL . (substr($moodleURL, -1) !== "/" ? "/" : "") . $p["post"];
                    }
                }
                return $info;
            }, Core::database()->selectMultiple($table, ["bp.user" => $userId, "bp.badge" => $badgeId], "p.*"));

            // Store in cache
            if (!$AutoGameIsRunning) {
                $cacheValue = $progression;
                Cache::store($courseId, $cacheId, $cacheValue);
            }

            return $progression;
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
        $awardsModule = new Awards($this->getCourse());
        $badgeAwards = $awardsModule->getUserAwardsByType($userId, AwardType::BADGE, $badgeId);
        return count($badgeAwards);
    }

    /**
     * Returns notifications to be sent to a user.
     *
     * @param int $userId
     * @throws Exception
     */
    public function getNotification($userId): ?string
    {
        foreach($this->getUserBadges($userId) as $badgesData) {
            $badge = new Badge($badgesData["id"]);

            // Not in max level yet
            if ($badgesData["level"] < $badgesData["nrLevels"]) {
                $nextLevel = $badge->getLevels()[$badgesData["level"]];
                $goal = $nextLevel["goal"];
                $progress = count($this->getUserBadgeProgressionInfo($userId, $badge->getId()));
    
                // Condition to give notification
                $instances = $goal - $progress;

                // Threshold to limit notifications and avoid spamming
                if (1 < $instances && $instances <= 2) {
                    $params["numberOfEventsLeft"] = $instances;
                    $params["badgeName"] = $badge->getName();
                    $params["badgeDescription"] = $badge->getDescription();
                    $params["nextLevelDescription"] = $nextLevel["description"];
                    $format = Core::database()->select(Notification::TABLE_NOTIFICATION_CONFIG, ["course" => $this->course->getId(), "module" => $this->getId()])["format"];
                    return Notification::getFinalNotificationText($this->course->getId(), $userId, $format, $params);
                }
            }
        }
        return null;
    }
}
