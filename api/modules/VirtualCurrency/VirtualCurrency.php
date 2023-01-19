<?php
namespace GameCourse\Module\VirtualCurrency;

use Event\Event;
use Event\EventType;
use Exception;
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
use GameCourse\Module\XPLevels\XPLevels;
use GameCourse\User\User;
use Utils\Utils;

/**
 * This is the Virtual Currency module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class VirtualCurrency extends Module
{
    const TABLE_WALLET = "user_wallet";
    const TABLE_VC_SPENDING = "virtual_currency_spending";
    const TABLE_VC_AUTO_ACTION = AutoAction::TABLE_VC_AUTO_ACTION;
    const TABLE_VC_CONFIG = "virtual_currency_config";

    const DEFAULT_NAME = "Token(s)";
    const DEFAULT_IMAGE = "default.png";

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "VirtualCurrency";  // NOTE: must match the name of the class
    const NAME = "Virtual Currency";
    const DESCRIPTION = "Enables virtual currency to be given to students as a reward.";
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

    const DATA_FOLDER = 'virtual_currency';
    const RULE_SECTION = "Virtual Currency";


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
        Core::database()->insert(self::TABLE_VC_CONFIG, ["course" => $this->course->getId()]);

        // Init wallet for all students
        $students = $this->course->getStudents();
        foreach ($students as $student) {
            $this->initWalletForUser($student["id"]);
        }

        $this->initEvents();
    }

    public function initEvents()
    {
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) {
            if ($courseId == $this->course->getId())
                $this->initWalletForUser($studentId);
        }, self::ID);

        Event::listen(EventType::STUDENT_REMOVED_FROM_COURSE, function (int $courseId, int $studentId) {
            // NOTE: this event targets cases where the course user only changed roles and
            //       is no longer a student; there's no need for an event when a user is
            //       completely removed from course, as SQL 'ON DELETE CASCADE' will do it
            if ($courseId == $this->course->getId())
                Core::database()->delete(self::TABLE_WALLET, ["course" => $courseId, "user" => $studentId]);
        }, self::ID);
    }

    /**
     * @throws Exception
     */
    public function copyTo(Course $copyTo)
    {
        $copiedModule = new VirtualCurrency($copyTo);

        // Copy config
        $copiedModule->setVCName($this->getVCName());
        if ($this->hasImage()) {
            $path = $this->getDataFolder() . "/token.png";
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
            $copiedModule->setImage($base64);
        }

        // Copy auto actions
        $actions = AutoAction::getActions($this->course->getId(), null, "id");
        foreach ($actions as $action) {
            $action = new AutoAction($action["id"]);
            $action->copyAction($copyTo);
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
        $this->removeEvents();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getLists(): array
    {
        $name = $this->getVCName();
        $img = $this->getImage();

        $actions = AutoAction::getActions($this->course->getId());
        $actionTypes = [
            "assignment grade" => "Assignment grade",
            "attended lab" => "Attended lab",
            "attended lecture" => "Attended lecture",
            "attended lecture (late)" => "Attended lecture late",
            "lab grade" => "Lab grade",
            "participated in lecture" => "Participated in lecture",
            "participated in invited lecture" => "Participated in invited lecture",
            "peergraded post" => "Peergraded colleagues' posts",
            "presentation grade" => "Presentation grade",
            "questionnaire submitted" => "Questionnaire submitted",
            "quiz grade" => "Quiz grade"
        ];

        $lists = [
            [
                "name" => "Settings",
                "itemName" => "settings",
                "headers" => [
                    ["label" => "Name", "align" => "middle"],
                    ["label" => "Image", "align" => "middle"]
                ],
                "data" => [
                    [
                        ["type" => DataType::TEXT, "content" => ["text" => $name]],
                        ["type" => DataType::IMAGE, "content" => ["imgSrc" => $img, "imgShape" => "round"]]
                    ]
                ],
                "actions" => [
                    ["action" => Action::EDIT, "scope" => ActionScope::ALL]
                ],
                "options" => [
                    "searching" => false,
                    "lengthChange" => false,
                    "paging" => false,
                    "info" => false,
                    "hasColumnFiltering" => false,
                    "hasFooters" => false,
                    "columnDefs" => [
                        ["orderable" => false, "targets" => [0, 1]]
                    ]
                ],
                "items" => [
                    ["name" => $name, "image" => $img]
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
                                    "id" => "name",
                                    "placeholder" => "Virtual currency name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Display name for virtual currency"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::FILE,
                                    "scope" => ActionScope::ALL,
                                    "id" => "image",
                                    "options" => [
                                        "accept" => ".svg, .png, .jpg, .jpeg",
                                        "size" => "xs",
                                        "color" => "primary",
                                        "label" => "Image"
                                    ],
                                    "helper" => "Image to represent virtual currency"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                "name" => "Automated actions",
                "itemName" => "action",
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
                    ["label" => "Action", "align" => "left"],
                    ["label" => "Type", "align" => "middle"],
                    ["label" => "$name", "align" => "middle"],
                    ["label" => "Active", "align" => "middle"]
                ],
                "data" => array_map(function ($action) use ($actionTypes, $img) {
                    return [
                        ["type" => DataType::TEXT, "content" => ["text" => $action["name"], "subtitle" => $action["description"]]],
                        ["type" => DataType::TEXT, "content" => ["text" => $actionTypes[$action["type"]]]],
                        ["type" => DataType::CUSTOM, "content" => ["html" => "<div class='flex items-center justify-center'>
                            <span class='prose text-sm'>" . $action["amount"] . "</span><img class='h-4 w-4 object-contain ml-2' src='$img'></div>", "searchBy" => strval($action["amount"])]],
                        ["type" => DataType::TOGGLE, "content" => ["toggleId" => "isActive", "toggleValue" => $action["isActive"]]]
                    ];
                }, $actions),
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
                        ["type" => "natural", "targets" => [0, 1, 2]],
                        ["searchable" => false, "targets" => [3]],
                        ["orderable" => false, "targets" => [3]]
                    ]
                ],
                "items" => $actions,
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
                                    "id" => "name",
                                    "placeholder" => "Action name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Action name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for action"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Action description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Action description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description for action"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::SELECT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "type",
                                    "placeholder" => "Select a type of action to award or penalize",
                                    "options" => [
                                        "options" => array_map(function ($description, $type) {
                                            return ["value" => $type, "text" => $description];
                                        }, $actionTypes, array_keys($actionTypes)),
                                        "topLabel" => "Type",
                                        "required" => true
                                    ],
                                    "helper" => "Type of action to award or penalize"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "amount",
                                    "placeholder" => "Amount of $name to award or penalize",
                                    "options" => [
                                        "topLabel" => "Amount",
                                        "required" => true
                                    ],
                                    "helper" => "Amount of $name to award (positive amount) or penalize (negative amount)"
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
                                    "id" => "name",
                                    "placeholder" => "Action name",
                                    "options" => [
                                        "topLabel" => "Name",
                                        "required" => true,
                                        "pattern" => "^[x00-\\xFF\\w()&\\s-]+$",
                                        "patternErrorMessage" => "Action name is not allowed. Allowed characters: alphanumeric  _  (  )  -  &",
                                        "maxLength" => 50
                                    ],
                                    "helper" => "Name for action"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "id" => "description",
                                    "placeholder" => "Action description",
                                    "options" => [
                                        "topLabel" => "Description",
                                        "pattern" => "(?!^\\d+$)^.+$",
                                        "patternErrorMessage" => "Action description can't be composed of only numbers",
                                        "maxLength" => 150
                                    ],
                                    "helper" => "Description for action"
                                ]
                            ],
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap mt-5",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::SELECT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "type",
                                    "placeholder" => "Select a type of action to award or penalize",
                                    "options" => [
                                        "options" => array_map(function ($description, $type) {
                                            return ["value" => $type, "text" => $description];
                                        }, $actionTypes, array_keys($actionTypes)),
                                        "topLabel" => "Type",
                                        "required" => true
                                    ],
                                    "helper" => "Type of action to award or penalize"
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "id" => "amount",
                                    "placeholder" => "Amount of $name to award or penalize",
                                    "options" => [
                                        "topLabel" => "Amount",
                                        "required" => true
                                    ],
                                    "helper" => "Amount of $name to award or penalize"
                                ]
                            ]
                        ]
                    ]
                ],
                Action::IMPORT => [
                    "extensions" => [".csv", ".txt"]
                ]
            ]
        ];

        // Exchange tokens, if XP & Levels enabled
        $xpModule = $this->course->getModuleById(XPLevels::ID);
        if ($xpModule && $xpModule->isEnabled()) {
            $wallets = Core::database()->selectMultiple(self::TABLE_WALLET . " w LEFT JOIN user u on w.user=u.id", [
                "course" => $this->course->getId()
            ], "w.*, u.name");
            $wallets = array_map(function ($userWallet) {
                $userWallet["ratio"] = null;
                $userWallet["threshold"] = null;
                return $userWallet;
            }, $wallets);
            usort($wallets, function ($a, $b) { return strcmp($a["name"], $b["name"]); });

            $exchanged = array_filter($wallets, function ($userWallet) { return $userWallet["exchanged"] || intval($userWallet["tokens"]) <= 0; });
            $exchanged = array_map(function ($userWallet, $index) { return $index; }, $exchanged, array_keys($exchanged));
            $scope = "[" . implode(", ", $exchanged) . "]";

            $lists[] = [
                "name" => "Exchanging $name",
                "itemName" => null,
                "topActions" => [
                    "right" => [
                        ["action" => "Exchange multiple", "icon" => "feather-repeat", "color" => "success"]
                    ]
                ],
                "headers" => [
                    ["label" => "Student", "align" => "left"],
                    ["label" => "Student Nr", "align" => "middle"],
                    ["label" => "Exchanged", "align" => "left"],
                    ["label" => "Total", "align" => "middle"]
                ],
                "data" => array_map(function ($userWallet) use ($name, $img) {
                    $user = User::getUserById($userWallet["user"]);
                    $userImg = $user->getImage() ?? URL . "/assets/imgs/user-" . (Core::getLoggedUser()->getTheme() ?? "light") . ".png";
                    $tokens = self::getUserTokens($user->getId());
                    return [
                        ["type" => DataType::AVATAR, "content" => ["avatarSrc" => $userImg , "avatarTitle" => $user->getName(), "avatarSubtitle" => $user->getMajor()]],
                        ["type" => DataType::NUMBER, "content" => ["value" => $user->getStudentNumber(), "valueFormat" => "none"]],
                        ["type" => DataType::COLOR, "content" => ["color" => $userWallet["exchanged"] ? "#36D399" : "#EF6060", "colorLabel" => $userWallet["exchanged"] ? "Exchanged $name" : "Hasn't exchanged yet"]],
                        ["type" => DataType::CUSTOM, "content" => ["html" => "<div class='flex items-center justify-center'>
                            <span class='prose text-sm'>$tokens</span><img class='h-4 w-4 object-contain ml-2' src='$img'></div>", "searchBy" => strval($tokens)]],
                    ];
                }, $wallets),
                "actions" => [
                    ["action" => 'Exchange ' . $name, "icon" => 'feather-repeat', "color" => "success", "scope" => $scope]
                ],
                "options" => [
                    "order" => [[0, "asc"]],
                    "columnDefs" => [
                        ["type" => "natural", "targets" => [0, 1, 3]],
                        ["searchable" => false, "targets" => [2]],
                        ["filterable" => false, "targets" => [2]],
                        ["orderable" => false, "targets" => [2]]
                    ]
                ],
                "items" => $wallets,
                "Exchange multiple" => [
                    "modalSize" => "sm",
                    "contents" => [
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "full",
                                    "type" => InputType::SELECT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "users",
                                    "placeholder" => "Select students to exchange $name",
                                    "options" => [
                                        "options" => array_map(function ($userWallet) {
                                            return ["value" => "id-" . $userWallet["user"], "text" => $userWallet["name"]];
                                        }, $wallets),
                                        "multiple" => true,
                                        "closeOnSelect" => false,
                                        "hideSelectedOption" => true,
                                        "topLabel" => "Students",
                                        "required" => true
                                    ]
                                ]
                            ]
                        ],
                        [
                            "contentType" => "container",
                            "classList" => "flex flex-wrap",
                            "contents" => [
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::TEXT,
                                    "scope" => ActionScope::ALL,
                                    "id" => "ratio",
                                    "placeholder" => "2:1 (double), 1:1 (same), ...",
                                    "options" => [
                                        "topLabel" => "Ratio",
                                        "required" => true,
                                        "pattern" => "^\\d+:\\d+$",
                                        "patternErrorMessage" => "Ratio format must be 'number:number' (ex: 2:1, 1:1, ...)"
                                    ],
                                    "helper" => "How many XP per 1 " . $name
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "threshold",
                                    "placeholder" => "Threshold",
                                    "options" => [
                                        "topLabel" => "Threshold"
                                    ],
                                    "helper" => "Max. $name that can be exchanged"
                                ]
                            ]
                        ]
                    ]
                ],
                "Exchange $name" => [
                    "modalSize" => "sm",
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
                                    "id" => "ratio",
                                    "placeholder" => "2:1 (double), 1:1 (same), ...",
                                    "options" => [
                                        "topLabel" => "Ratio",
                                        "required" => true,
                                        "pattern" => "^\\d+:\\d+$",
                                        "patternErrorMessage" => "Ratio format must be 'number:number' (ex: 2:1, 1:1, ...)"
                                    ],
                                    "helper" => "How many XP per 1 " . $name
                                ],
                                [
                                    "contentType" => "item",
                                    "width" => "1/2",
                                    "type" => InputType::NUMBER,
                                    "scope" => ActionScope::ALL,
                                    "id" => "threshold",
                                    "placeholder" => "Threshold",
                                    "options" => [
                                        "topLabel" => "Threshold"
                                    ],
                                    "helper" => "Max. $name that can be exchanged"
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $lists;
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item): ?string
    {
        $name = $this->getVCName();

        if ($listName == "Settings") {
            if ($action == Action::EDIT) {
                // Set name
                $this->setVCName($item["name"] ?? self::DEFAULT_NAME);

                // Set image
                if (isset($item["image"]) && !Utils::strStartsWith($item["image"], API_URL))
                    $this->setImage($item["image"]);
            }

        } elseif ($listName == "Automated actions") {
            if ($action == Action::NEW || $action == Action::DUPLICATE || $action == Action::EDIT) {
                if ($action == Action::NEW || $action == Action::DUPLICATE) {
                    // Format name
                    $name = $item["name"];
                    if ($action == Action::DUPLICATE) $name .= " (Copy)";

                    $action = AutoAction::addAction($this->course->getId(), $name, $item["description"], $item["type"],
                        $item["amount"]);

                } else {
                    $action = AutoAction::getActionById($item["id"]);
                    $action->editAction($item["name"], $item["description"], $item["type"], $item["amount"], $item["isActive"] ?? false);
                }

            } elseif ($action == Action::DELETE) AutoAction::deleteAction($item["id"]);

        } elseif ($listName == "Exchanging $name") {
            if ($action == "Exchange $name" || $action == "Exchange multiple") {
                if ($action == "Exchange $name") $users = [$item["user"]];
                else $users = array_map(function ($userId) {
                    return intval(substr($userId, 3));
                }, $item["users"]);

                foreach ($users as $userId) {
                    if (!self::hasExchanged($userId)) {
                        $parts = explode(":", $item["ratio"]);
                        $ratio = round(intval($parts[0]) / intval($parts[1]));
                        $threshold = $item["threshold"] ?? null;
                        $earnedXP = self::exchangeTokensForXP($userId, $ratio, $threshold);

                        if ($action == "Exchange $name") return $item["name"] . " earned $earnedXP XP";
                    }
                }
                return "Exchanged $name!";
            }
        }

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
        return AutoAction::generateRuleParams(...$args);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    /**
     * Gets Virtual Currency name.
     *
     * @return string
     */
    public function getVCName(): string
    {
        return Core::database()->select(self::TABLE_VC_CONFIG, ["course" => $this->course->getId()], "name");
    }

    /**
     * Sets Virtual Currency name.
     *
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function setVCName(string $name)
    {
        self::validateName($name);
        Core::database()->update(self::TABLE_VC_CONFIG, ["name" => $name], ["course" => $this->course->getId()]);
    }


    /**
     * Gets Virtual Currency image.
     *
     * @return string
     */
    public function getImage(): string
    {
        if ($this->hasImage()) return API_URL . "/" . $this->getCourse()->getDataFolder(false) . "/" .
            $this->getDataFolder(false) . "/token.png";
        else return API_URL . "/" . Utils::getDirectoryName(MODULES_FOLDER) . "/" . $this::ID . "/assets/" . self::DEFAULT_IMAGE;
    }

    /**
     * Sets Virtual Currency image.
     *
     * @throws Exception
     */
    public function setImage(string $base64)
    {
        Utils::uploadFile($this->getDataFolder(), $base64, "token.png");
    }

    /**
     * Checks whether an image for Virtual Currency is set.
     *
     * @return bool
     */
    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/token.png");
    }


    /*** ---------- Wallet ---------- ***/

    /**
     * Sets 0 tokens for a given user.
     * If student already has a wallet it will reset it.
     *
     * @param int $userId
     * @return void
     * @throws Exception
     */
    private function initWalletForUser(int $userId)
    {
        $courseId = $this->course->getId();

        if ($this->userHasWallet($userId)) // already has a wallet
            Core::database()->update(self::TABLE_WALLET, [
                "tokens" => 0,
                "exchanged" => +false
            ], ["course" => $courseId, "user" => $userId]);

        else
            Core::database()->insert(self::TABLE_WALLET, [
                "course" => $courseId,
                "user" => $userId,
                "tokens" => 0,
                "exchanged" => +false
            ]);
    }

    /**
     * Gets total tokens for a given user.
     *
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public function getUserTokens(int $userId): int
    {
        if (!$this->userHasWallet($userId))
            throw new Exception("User with ID = " . $userId . " doesn't have a wallet initialized.");

        return intval(Core::database()->select(self::TABLE_WALLET,
            ["course" => $this->course->getId(), "user" => $userId],
            "tokens"
        ));
    }

    /**
     * Sets total tokens for a given user.
     *
     * @param int $userId
     * @param int $tokens
     * @return void
     * @throws Exception
     */
    public function setUserTokens(int $userId, int $tokens)
    {
        if (!$this->userHasWallet($userId))
            throw new Exception("User with ID = " . $userId . " doesn't have a wallet initialized.");

        $courseId = $this->course->getId();
        Core::database()->update(self::TABLE_WALLET, ["tokens" => $tokens], ["course" => $courseId, "user" => $userId]);
    }

    /**
     * Adds or removes tokens for a given user.
     *
     * @param int $userId
     * @param int $tokens
     * @return void
     * @throws Exception
     */
    public function updateUserTokens(int $userId, int $tokens)
    {
        $newTokens = $this->getUserTokens($userId) + $tokens;
        $this->setUserTokens($userId, $newTokens);
    }

    /**
     * Checks whether a given user has a wallet initialized.
     *
     * @param int $userId
     * @return bool
     */
    public function userHasWallet(int $userId): bool
    {
        return !empty(Core::database()->select(self::TABLE_WALLET, ["course" => $this->course->getId(), "user" => $userId]));
    }


    /*** ---- Exchanging Tokens ----- ***/

    /**
     * Exchanges a given user's tokens for XP according to
     * a specific ratio and threshold.
     * Returns the total amount of XP earned.
     *
     * NOTE: threshold is related to tokens, not XP
     *
     * @param int $userId
     * @param float $ratio
     * @param int|null $threshold
     * @return int
     * @throws Exception
     */
    public function exchangeTokensForXP(int $userId, float $ratio = 1, ?int $threshold = null): int
    {
        $this->checkDependency(XPLevels::ID);

        // Check if already exchanged
        if ($this->hasExchanged($userId))
            throw new Exception("Can't exchange " . $this->getVCName() . " more than once.");

        $totalTokens = $this->getUserTokens($userId);
        $exchangeableTokens = min($totalTokens, $threshold ?? PHP_INT_MAX);
        $earnedXP = intval(round($exchangeableTokens * $ratio));

        // Remove tokens & set flag
        $this->updateUserTokens($userId, -$exchangeableTokens);
        Core::database()->update(self::TABLE_WALLET, ["exchanged" => true], ["course" => $this->course->getId(), "user" => $userId]);

        // Update user XP
        $xpLevels = new XPLevels($this->course);
        $xpLevels->updateUserXP($userId, $earnedXP);

        // Give award
        $awardsModule = new Awards($this->course);
        $awardsModule->giveAward($userId, $this->getVCName() . " exchange", AwardType::BONUS, null, $earnedXP);

        return $earnedXP;
    }

    /**
     * Checks whether a given user has alrady exchanged tokens.
     *
     * @param int $userId
     * @return bool
     */
    public function hasExchanged(int $userId): bool
    {
        return boolval(Core::database()->select(self::TABLE_WALLET, ["course" => $this->course->getId(), "user" => $userId], "exchanged"));
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Validations ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Validates Virtual Currency name.
     *
     * @param $name
     * @return void
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty(trim($name)))
            throw new Exception("Virtual Currency name can't be null neither empty.");

        if (iconv_strlen($name) > 50)
            throw new Exception("Virtual Currency name is too long: maximum of 50 characters.");
    }
}
