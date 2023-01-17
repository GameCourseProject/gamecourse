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
use Utils\Utils;

/**
 * This is the Virtual Currency module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class VirtualCurrency extends Module
{
    const TABLE_WALLET = "user_wallet";
    const TABLE_VC_SPENDING = "virtual_currency_spending";
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
        $copiedModule->setName($this->getName());

        // Copy image
        if ($this->hasImage()) {
            $path = $this->getDataFolder() . "/token.png";
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
            $copiedModule->setImage($base64);
        }
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->cleanDatabase();
        $this->removeDataFolder();
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
        $name = $this->getName();
        $img = $this->getImage();

        return [
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
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function saveListingItem(string $listName, string $action, array $item)
    {
        if ($listName == "Settings") {
            if ($action == Action::EDIT) {
                // Set name
                $this->setName($item["name"] ?? self::DEFAULT_NAME);

                // Set image
                if (isset($item["image"]) && !Utils::strStartsWith($item["image"], API_URL))
                    $this->setImage($item["image"]);
            }
        }
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
    public function getName(): string
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
    public function setName(string $name)
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
            throw new Exception("Can't exchange " . $this->getName() . " more than once.");

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
        $awardsModule->giveAward($userId, $this->getName() . " exchange", AwardType::BONUS, null, $earnedXP);

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
