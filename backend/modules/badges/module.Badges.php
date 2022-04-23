<?php
namespace Modules\Badges;

use GameCourse\Core;
use GameCourse\RuleSystem;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Views\Views;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Course;
use Modules\AwardList\AwardList;

class Badges extends Module
{
    const ID = 'badges';

    const TABLE = 'badge';
    const TABLE_LEVEL = self::TABLE . '_level';
    const TABLE_CONFIG = self::ID . '_config';
    const TABLE_PROGRESSION = self::TABLE . '_progression';

    const BADGES_PROFILE_TEMPLATE = 'Badges Profile - by badges';
    const BADGES_RULE_TEMPLATE = 'rule_badge_template.txt';
    const BADGES_IS_POST_RULE_TEMPLATE  = 'rule_badge_isPost_template.txt';



    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
        $this->initTemplates();
    }

    public function initDictionary()
    {
        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding Badges and their levels. It is provided by the " . self::ID . " module.");


        /*** ------------ Functions ------------ ***/

        //badges.getAllBadges(isExtra,isBragging,isActive)
        Dictionary::registerFunction(
            self::ID,
            'getAllBadges',
            function (bool $isExtra = null, bool $isBragging = null, bool $isActive = true) {
                $where = [];
                if ($isExtra !== null)
                    $where["isExtra"] = $isExtra;
                if ($isBragging !== null)
                    $where["isBragging"] = $isBragging;
                $where["isActive"] = $isActive;
                return $this->getBadge(true, $where);
            },
            "Returns a collection with all the badges in the Course. The optional parameters can be used to find badges that specify a given combination of conditions:\nisExtra: Badge has a reward.\nisBragging: Badge has no reward.\nisActive: Badge is active.",
            'collection',
            'badge',
            'library',
            null,
            true

        );
        //badges.getBadge(name)
        Dictionary::registerFunction(
            self::ID,
            'getBadge',
            function (string $name = null) {
                return $this->getBadge(false, ["name" => $name]);
            },
            "Returns the badge object with the specific name.",
            'object',
            'badge',
            'library',
            null,
            true
        );
        //badges.getBadgesCount(user) returns num of badges of user (if specified) or of course
        Dictionary::registerFunction(
            self::ID,
            'getBadgesCount',
            function ($user = null) {
                return new ValueNode($this->getBadgeCount($user));
            },
            "Returns an integer with the number of badges of the GameCourseUser identified by user. If no argument is provided, the function returns the number of badges of the course.",
            'integer',
            null,
            'library',
            null,
            true
        );
        //badges.doesntHaveBadge(%badge, %level, %active) returns True if there are no students with this badge, False otherwise
        Dictionary::registerFunction(
            self::ID,
            'doesntHaveBadge',
            function ($badge, $level, $active = true) {
                $users = $this->getUsersWithBadge($badge, $level, $active);
                return new ValueNode(empty($users->getValue()['value']));
            },
            "Returns an object with value True if there are no students with this badge, False otherwise.\nactive: if True only returns data regarding active students. When False, returns data reagrding all students. Defaults to True.",
            'object',
            'badge',
            'library',
            null,
            true
        );
        //users.getUsersWithBadge(%badge, %level, %active) returns an object with all users that earned that badge on that level
        Dictionary::registerFunction(
            'users',
            'getUsersWithBadge',
            function ($badge, $level, $active = true) {
                $users = $this->getUsersWithBadge($badge, $level, $active);
                return $users;
            },
            "Returns an object with all users that earned that badge on that level.\nactive: If set to True, returns information regarding active students only. Otherwise, returns information regarding all students. Defaults to True.",
            'collection',
            'user',
            'library',
            null,
            true
        );
        //%badge.description
        // FIXME: there's another function here with the same lib and name that overrides this one
        Dictionary::registerFunction(
            self::ID,
            'description',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "description");
            },
            "Returns a string with information regarding the name of the badge, the goal to obtain it and the reward associated to it.",
            'string',
            null,
            'object',
            'badge',
            true
        );
        //%badge.name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "name");
            },
            "Returns a string with the name of the badge.",
            'string',
            null,
            'object',
            'badge',
            true
        );
        //%badge.maxLevel
        Dictionary::registerFunction(
            self::ID,
            'maxLevel',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "maxLevel");
            },
            "Returns a Level object corresponding to the maximum Level from that badge.",
            'object',
            'level',
            'object',
            'badge',
            true
        );
        //%badge.isExtra
        Dictionary::registerFunction(
            self::ID,
            'isExtra',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "isExtra");
            },
            "Returns a boolean regarding whether the badge provides reward.",
            'boolean',
            null,
            'object',
            'badge',
            true
        );
        //%badge.isCount
        Dictionary::registerFunction(
            self::ID,
            'isCount',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "isCount");
            },
            '',
            'boolean',
            null,
            'object',
            'badge',
            true
        );
        //%badge.isPost
        Dictionary::registerFunction(
            self::ID,
            'isPost',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "isPost");
            },
            '',
            'boolean',
            null,
            'object',
            'badge',
            true
        );
        //%badge.isBragging
        Dictionary::registerFunction(
            self::ID,
            'isBragging',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "isBragging");
            },
            "Returns a boolean regarding whether the badge provides no reward.",
            'boolean',
            null,
            'object',
            'badge',
            true
        );
        //%badge.isActive
        Dictionary::registerFunction(
            self::ID,
            'isActive',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "isActive");
            },
            "Returns a boolean regarding whether the badge is active.",
            'boolean',
            null,
            'object',
            'badge',
            true
        );
        //%badge.renderPicture(number) return expression for the image of the badge in the specified level
        Dictionary::registerFunction(
            self::ID,
            'renderPicture',
            function ($badge, $level) {
                //$level num or object
                if (is_array($level))
                    $levelNum = $level["number"];
                else
                    $levelNum = $level;
                $name = str_replace(' ', '', $badge["value"]["name"]);
                return new ValueNode("modules/" . self::ID . "/imgs/" . $name . "-" . $levelNum . ".png");
            },
            'Return a picture of a badge’s level.',
            'picture',
            null,
            'object',
            'badge',
            true
        );
        //%badge.levels returns collection of level objects
        Dictionary::registerFunction(
            self::ID,
            'levels',
            function ($badge) {
                Dictionary::checkArray($badge, "object", 'levels');
                return $this->getLevel(null, $badge);
            },
            'Returns a collection of Level objects from that badge.',
            'collection',
            'level',
            'object',
            'badge',
            true
        );
        //%badge.getLevel(number) returns level object
        Dictionary::registerFunction(
            self::ID,
            'getLevel',
            function ($badge, $level) {
                Dictionary::checkArray($badge, "object", 'getLevel');
                Dictionary::checkArray($level, "object", 'getLevel');
                return $this->getLevel($level, $badge);
            },
            'Returns a Level object corresponding to Level number from that badge.',
            'object',
            'level',
            'object',
            'badge',
            true
        );
        //%badge.currLevel(%user) returns object of the current level of user
        Dictionary::registerFunction(
            self::ID,
            'currLevel',
            function ($badge, int $user) {
                Dictionary::checkArray($badge, "object", 'currLevel');
                $levelNum = $this->getLevelNum($badge, $user);
                return $this->getLevel($levelNum, $badge);
            },
            'Returns a Level object corresponding to the current Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge',
            true
        );
        //%badge.nextLevel(user) %level.nextLevel  returns level object
        Dictionary::registerFunction(
            self::ID,
            'nextLevel',
            function ($arg, $user = null) {
                Dictionary::checkArray($arg, "object", 'nextLevel');
                if ($user === null) { //arg is a level
                    $levelNum = $arg["value"]["number"];
                } else { //arg is badge
                    $levelNum = $this->getLevelNum($arg, $user);
                }
                return $this->getLevel($levelNum + 1, $arg);
            },
            'Returns a Level object corresponding to the next Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge',
            true
        );
        //%badge.previousLevel(user) %level.previousLevel  returns level object
        Dictionary::registerFunction(
            self::ID,
            'previousLevel',
            function ($arg, $user = null) {
                Dictionary::checkArray($arg, "object", 'previousLevel');
                if ($user === null) { //arg is a level
                    $levelNum = $arg["value"]["number"];
                } else { //arg is badge
                    $levelNum = $this->getLevelNum($arg, $user);
                }
                return $this->getLevel($levelNum - 1, $arg);
            },
            'Returns a Level object corresponding to the previous Level of a GameCourseUser identified by user from that badge.',
            'object',
            'level',
            'object',
            'badge',
            true
        );
        //badges.badgeProgression(badge,user)
        Dictionary::registerFunction(
            self::ID,
            'badgeProgression',

            function ($badge, int $user) {
                $badgeParticipation = $this->getBadgeProgression($badge, $user);
                return Dictionary::createNode($badgeParticipation, self::ID, 'collection');
            },
            'Returns a collection object corresponding to the intermediate progress of a GameCourseUser identified by user for that badge.',
            'collection',
            'badge',
            'library',
            null,
            true
        );
        //%badgeProgression.post
        Dictionary::registerFunction(
            self::ID,
            'post',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "post");
            },
            'Returns a post from a collection of badge progression participations.',
            'string',
            null,
            'object',
            'badge',
            true
        );
        //%badgeProgression.description
        Dictionary::registerFunction(
            self::ID,
            'description',
            function ($badge) {
                return Dictionary::basicGetterFunction($badge, "description");
            },
            'Returns a post description from a collection of badge progression participations.',
            'string',
            null,
            'object',
            'badge',
            true
        );
        //%collection.countBadgesProgress  returns size of the collection or points obtained
        Dictionary::registerFunction(
            self::ID,
            'countBadgesProgress',
            function ($collection, $badge) {
                $count = $this->countBadgesProgress($collection, $badge);
                return new ValueNode($count);
            },
            'Returns the number of elements (posts or points) in the collection.',
            'integer',
            null,
            'collection',
            null,
            true
        );
        //%level.goal
        Dictionary::registerFunction(
            self::ID,
            'goal',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "goal");
            },
            'Returns a string with the goal of the Level.',
            'string',
            null,
            'object',
            'level',
            true
        );
        //%level.reward
        Dictionary::registerFunction(
            self::ID,
            'reward',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "reward");
            },
            'Returns a string with the reward of the Level.',
            'string',
            null,
            'object',
            'level',
            true
        );
        //%level.number
        Dictionary::registerFunction(
            self::ID,
            'number',
            function ($level) {
                return Dictionary::basicGetterFunction($level, "number");
            },
            'Returns a string with the number of the Level.',
            'string',
            null,
            'object',
            'level',
            true
        );
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::BADGES_PROFILE_TEMPLATE))
            Views::createTemplateFromFile(self::BADGES_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/badges.txt'), $courseId, self::ID);
    }

    public function setupResources()
    {
        parent::addResources('css/badges.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        if ($this->addTables(self::ID, self::TABLE) || empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, ["maxBonusReward" => MAX_BONUS_BADGES, "course" => $courseId]);
        }

        $folder = Course::getCourseDataFolder($courseId, Course::getCourse($courseId, false)->getName());
        if (!file_exists($folder . "/" . self::ID))
            mkdir($folder . "/" . self::ID);
        if (!file_exists($folder . "/" . self::ID . "/Extra"))
            mkdir($folder . "/" . self::ID . "/Extra");
        if (!file_exists($folder . "/" . self::ID . "/Bragging"))
            mkdir($folder . "/" . self::ID . "/Bragging");
        if (!file_exists($folder . "/" . self::ID . "/Level2"))
            mkdir($folder . "/" . self::ID . "/Level2");
        if (!file_exists($folder . "/" . self::ID . "/Level3"))
            mkdir($folder . "/" . self::ID . "/Level3");
    }

    public function update_module($compatibleVersions)
    {
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = "modules/" . self::ID . "/config.json";
        $contents = array();
        if (file_exists($configFile)) {
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }

        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
    {
        $badgesConfigArray = array();
        $badgesArray = array();
        $badgesLevelArray = array();

        $badgesArr = array();
        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $badgesConfigVarDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($badgesConfigVarDB) {
                unset($badgesConfigVarDB["course"]);
                array_push($badgesConfigArray, $badgesConfigVarDB);
            }
        }
        if (Core::$systemDB->tableExists(self::TABLE)) {
            $badgesVarDB = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");
            if ($badgesVarDB) {
                unset($badgesConfigVarDB["course"]);
                foreach ($badgesVarDB as $badge) {
                    array_push($badgesArray, $badge);

                    $badgesLevelVarDB_ = Core::$systemDB->selectMultiple(self::TABLE_LEVEL, ["badgeId" => $badge["id"]], "*");
                    foreach ($badgesLevelVarDB_ as $badgesLevelVarDB) {
                        array_push($badgesLevelArray, $badgesLevelVarDB);
                    }
                }
            }
        }

        $badgesArr[self::TABLE_CONFIG] = $badgesConfigArray;
        $badgesArr[self::TABLE] = $badgesArray;
        $badgesArr[self::TABLE_LEVEL] = $badgesLevelArray;

        if ($badgesConfigArray || $badgesArray || $badgesLevelArray) {
            return $badgesArr;
        } else {
            return false;
        }
    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false): array
    {
        $tableName = array_keys($tables);
        $i = 0;
        $badgeIds = array();
        $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if ($tableName[$i] == self::TABLE_CONFIG) {
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        Core::$systemDB->insert($tableName[$i], $entry);
                    }
                } else  if ($tableName[$i] == self::TABLE) {
                    $importId = $entry["id"];
                    unset($entry["id"]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                    $badgeIds[$importId] = $newId;
                } else  if ($tableName[$i] == self::TABLE_LEVEL) {
                    $oldBadgeId = $badgeIds[$entry["badgeId"]];
                    $entry["badgeId"] = $oldBadgeId;
                    unset($entry["id"]);
                    if ($update) {
                        Core::$systemDB->update($tableName[$i], $entry, ["badgeId" => $oldBadgeId, "number" => $entry["number"]]);
                    } else {
                        Core::$systemDB->insert($tableName[$i], $entry);
                    };
                }
            }
            $i++;
        }
        return $badgeIds;
    }

    public function is_configurable(): bool
    {
        return true;
    }

    public function has_general_inputs(): bool
    {
        return true;
    }

    public function get_general_inputs(int $courseId): array
    {
        $input = [
            array('name' => "Max Reward", 'id' => 'maxReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId))),
            array('name' => "Overlay for extra", 'id' => 'isExtra', 'type' => "image", 'options' => "Extra", 'current_val' => $this->getGeneralImages('imageExtra', $courseId)),
            array('name' => "Overlay for bragging", 'id' => 'bragging', 'type' => "image", 'options' => "Bragging", 'current_val' => $this->getGeneralImages('imageBragging', $courseId)),
            array('name' => "Overlay for level 2", 'id' => 'level2', 'type' => "image", 'options' => "Level2", 'current_val' => $this->getGeneralImages('imageLevel2', $courseId)),
            array('name' => "Overlay for level 3", 'id' => 'level3', 'type' => "image", 'options' => "Level3", 'current_val' => $this->getGeneralImages('imageLevel3', $courseId))
        ];
        return $input;
    }

    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $maxVal = $generalInputs["maxReward"];
        $this->saveMaxReward($maxVal, $courseId);

        $extraImg = $generalInputs["isExtra"];
        if ($extraImg != "") {
            $this->saveGeneralImages('imageExtra', $extraImg, $courseId);
        }
        $braggingImg = $generalInputs["bragging"];
        if ($braggingImg != "") {
            $this->saveGeneralImages('imageBragging', $braggingImg, $courseId);
        }
        $imageL2 = $generalInputs["level2"];
        if ($imageL2 != "") {
            $this->saveGeneralImages('imageLevel2', $imageL2, $courseId);
        }
        $imageL3 = $generalInputs["level3"];
        if ($imageL3 != "") {
            $this->saveGeneralImages('imageLevel3', $imageL3, $courseId);
        }
    }

    public function has_listing_items(): bool
    {
        return  true;
    }

    public function get_listing_items(int $courseId): array
    {
        $header = ['Name', 'Description', 'Image', '# Levels', 'Is Count', 'Is Post', 'Is Point', 'Is Extra', 'Active'];
        $displayAtributes = [
            ['id' => 'name', 'type' => 'text'],
            ['id' => 'description', 'type' => 'text'],
            ['id' => 'image', 'type' => 'image'],
            ['id' => 'maxLevel', 'type' => 'number'],
            ['id' => 'isCount', 'type' => 'on_off button'],
            ['id' => 'isPost', 'type' => 'on_off button'],
            ['id' => 'isPoint', 'type' => 'on_off button'],
            ['id' => 'isExtra', 'type' => 'on_off button'],
            ['id' => 'isActive', 'type' => 'on_off button'],
        ];
        $actions = ['duplicate', 'edit', 'delete', 'export'];

        $items = $this->getBadges($courseId);

        // Arguments for adding/editing
        $allAtributes = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Level 1", 'id' => 'desc1', 'type' => "text", 'options' => ""),
            array('name' => "XP1", 'id' => 'xp1', 'type' => "number", 'options' => ""),
            array('name' => "Level 2", 'id' => 'desc2', 'type' => "text", 'options' => ""),
            array('name' => "XP2", 'id' => 'xp2', 'type' => "number", 'options' => ""),
            array('name' => "Level 3", 'id' => 'desc3', 'type' => "text", 'options' => ""),
            array('name' => "XP3", 'id' => 'xp3', 'type' => "number", 'options' => ""),
            array('name' => "Is Count", 'id' => 'isCount', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Post", 'id' => 'isPost', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Point", 'id' => 'isPoint', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Extra", 'id' => 'isExtra', 'type' => "on_off button", 'options' => ""),
            array('name' => "Count 1", 'id' => 'count1', 'type' => "number", 'options' => ""),
            array('name' => "Count 2", 'id' => 'count2', 'type' => "number", 'options' => ""),
            array('name' => "Count 3", 'id' => 'count3', 'type' => "number", 'options' => ""),
            array('name' => "Badge images", 'id' => 'image', 'type' => "image", 'options' => "")
        ];

        return array('listName' => 'Badges', 'itemName' => 'badge', 'header' => $header, 'displayAttributes' => $displayAtributes, 'actions' => $actions, 'items' => $items, 'allAttributes' => $allAtributes);
    }

    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
        if ($actiontype == 'new' || $actiontype == 'duplicate') $this->newBadge($listingItem, $courseId);
        elseif ($actiontype == 'edit') $this->editBadge($listingItem, $courseId);
        elseif ($actiontype == 'delete') $this->deleteBadge($listingItem, $courseId);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        Core::$systemDB->delete(self::TABLE, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public function importItems(string $fileData, bool $replace = true): int
    {
        $courseId = $this->getCourseId();

        $nrItemsImported = 0;
        $separator = ",";
        $headers = ["name", "description", "maxLevel", "isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive",
            "image", "desc1", "xp1", "count1", "desc2", "xp2", "count2", "desc3", "xp3", "count3"];
        $lines = array_filter(explode("\n", $fileData), function ($line) { return !empty($line); });

        if (count($lines) > 0) {
            // Check if has header to ignore it
            $firstLine = array_map('trim', explode($separator, trim($lines[0])));
            $hasHeaders = true;
            foreach ($headers as $header) {
                if (!in_array($header, $firstLine)) $hasHeaders = false;
            }
            if ($hasHeaders) array_shift($lines);

            // Import each item
            foreach ($lines as $line) {
                $item = array_map('trim', explode($separator, trim($line)));
                $itemId = Core::$systemDB->select(self::TABLE, ["course" => $courseId, "name" => $item[array_search("name", $headers)]], "id");

                $badgeData = [
                    "name" => $item[array_search("name", $headers)],
                    "description" => $item[array_search("description", $headers)],
                    "isExtra" => intval($item[array_search("isExtra", $headers)]),
                    "isBragging" => intval($item[array_search("isBragging", $headers)]),
                    "isCount" => intval($item[array_search("isCount", $headers)]),
                    "isPost" => intval($item[array_search("isPost", $headers)]),
                    "isPoint" => intval($item[array_search("isPoint", $headers)]),
                    "isActive" => intval($item[array_search("isActive", $headers)]),
                    "image" => $item[array_search("image", $headers)],
                    "desc1" => $item[array_search("desc1", $headers)],
                    "desc2" => $item[array_search("desc2", $headers)],
                    "desc3" => $item[array_search("desc3", $headers)],
                    "xp1" => $item[array_search("xp1", $headers)],
                    "xp2" => intval($item[array_search("xp2", $headers)]),
                    "xp3" => intval($item[array_search("xp3", $headers)]),
                    "count1" => intval($item[array_search("count1", $headers)]),
                    "count2" => intval($item[array_search("count2", $headers)]),
                    "count3" => intval($item[array_search("count3", $headers)])
                ];

                if ($itemId && $replace) { // replace item
                    $badgeData["id"] = $itemId;
                    Badges::editBadge($badgeData, $courseId);

                } else { // create item
                    Badges::newBadge($badgeData, $courseId);
                    $nrItemsImported++;
                }
            }
        }

        return $nrItemsImported;
    }

    public function exportItems(int $itemId = null): array
    {
        $courseId = $this->getCourseId();
        $course = Course::getCourse($courseId, false);

        // Get badges to export
        if (!is_null($itemId))
            $listOfBadges = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId, "id" => $itemId], "*");
        else
            $listOfBadges = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");

        $file = "";
        $separator = ",";
        $len = count($listOfBadges);

        // Append headers
        $headers = ["name", "description", "maxLevel", "isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive",
            "image", "desc1", "xp1", "count1", "desc2", "xp2", "count2", "desc3", "xp3", "count3"];
        $file .= implode($separator, $headers) . "\n";

        // Go over each badge and append it to file
        foreach ($listOfBadges as $index => $badge) {
            $params = [$badge["name"], $badge["description"], $badge["maxLevel"], $badge["isExtra"], $badge["isBragging"],
                $badge["isCount"], $badge["isPost"], $badge["isPoint"], $badge["isActive"], $badge["image"]];

            // Get levels info
            for ($j = 1; $j <= 3; $j++) {
                if ($j <= $badge["maxLevel"]) {
                    $level = Core::$systemDB->select(self::TABLE_LEVEL, ["badgeId" => $badge["id"], "number" => $j]);
                    $params[] = $level["description"];
                    $params[] = $level["reward"];
                    $params[] = $level["goal"];

                } else {
                    $params[] = "";
                    $params[] = "";
                    $params[] = "";
                }
            }

            $file .= implode($separator, $params);
            if ($index != $len - 1) $file .= "\n";
        }

        return ["Badges - " . $course->getName(), $file];
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getBadges($courseId)
    {
        $badges = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*", "name");
        foreach ($badges as &$badge) {
            //information to match needing fields
            $badge['isCount'] = boolval($badge["isCount"]);
            $badge['isPost'] = boolval($badge["isPost"]);
            $badge['isPoint'] = boolval($badge["isPoint"]);
            $badge['isExtra'] = boolval($badge["isExtra"]);
            $badge['isBragging'] = boolval($badge["isBragging"]);
            $badge['isActive'] = boolval($badge["isActive"]);

            $levels = Core::$systemDB->selectMultiple(
                self::TABLE_LEVEL . " join " . self::TABLE . " on " . self::TABLE . ".id=badgeId",
                ["course" => $courseId, "badgeId" => $badge['id']],
                self::TABLE_LEVEL . '.description , goal, reward, number'
            );
            foreach ($levels as $level) {
                $badge['desc' . $level['number']] = $level['description']; //string
                $badge['count' . $level['number']] = intval($level['goal']); //int
                $badge['xp' . $level['number']] = intval($level['reward']); //int
            }
        }
        return $badges;
    }

    public function getBadge($selectMultiple, $where): ValueNode
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $badgeArray = Core::$systemDB->selectMultiple(self::TABLE, $where);
            $type = "collection";
        } else {
            $badgeArray = Core::$systemDB->select(self::TABLE, $where);
            if (empty($badgeArray))
                throw new \Exception("In function badges.getBadge(name): couldn't find badge with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return Dictionary::createNode($badgeArray, self::ID, $type);
    }

    public function getBadgeCount($user = null): int
    {
        $courseId = $this->getCourseId();
        if ($user === null) {
            $count = Core::$systemDB->select(self::TABLE, ["course" => $courseId, "isActive" => true], "sum(maxLevel)");
            if (is_null($count))
                return 0;
            else
                return $count;
        }
        $id = $this->getUserId($user);
        return  Core::$systemDB->select(AwardList::TABLE, ["course" => $courseId, "type" => "badge", "user" => $id], "count(*)");
    }

    public function getUsersWithBadge($badge, $level, $active = false): ValueNode
    {
        $usersWithBadge = array();
        $courseId = $this->getCourseId();
        $course = new Course($courseId);
        $users = $course->getUsers($active);
        foreach ($users as $user) {
            $userId = $user["id"];
            $userObj = Dictionary::createNode($course->getUser($userId)->getAllData(), 'users');
            //print_r($userObj);
            $userLevel = $this->getLevelNum($badge, $userId);
            $levelNum = Dictionary::basicGetterFunction($level, "number")->getValue();
            if ($userLevel >= $levelNum) {
                array_push($usersWithBadge, $userObj->getValue()["value"]);
            }
        }
        return Dictionary::createNode($usersWithBadge, 'users', "collection");
    }

    public function getLevel($levelNum, $badge): ValueNode
    {
        $type = "object";
        $parent = null;
        if ($levelNum === 0) {
            $level = ["number" => 0, "description" => ""];
        } else if ($levelNum > $badge["value"]["maxLevel"] || $levelNum < 0) {
            $level = ["number" => null, "description" => null];
        } else {
            $table = self::TABLE . " join " . self::TABLE_LEVEL . " on " . self::TABLE . ".id=badgeId";
            $badgeId = $badge["value"]["id"];
            if ($levelNum == null) {
                $level = Core::$systemDB->selectMultiple($table, ["badgeId" => $badgeId]);
                $type = "collection";
                $parent = $badge;
            } else {
                $level = Core::$systemDB->select($table, ["badgeId" => $badgeId, "number" => $levelNum]);
            }
        }
        unset($badge["value"]["description"]);
        unset($level["id"]);
        if ($type == "collection") {
            foreach ($level as &$l) {
                $l["libraryOfVariable"] = self::ID;
                $l = array_merge($badge["value"], $l);
            }
        } else {
            $level["libraryOfVariable"] = self::ID;
            $level = array_merge($badge["value"], $level);
        }
        return Dictionary::createNode($level, self::ID, $type, $parent);
    }

    public function getLevelNum($badge, $user): int
    {
        $id = $this->getUserId($user);
        $badgeId = $badge["value"]["id"];
        $levelNum = Core::$systemDB->select(AwardList::TABLE, ["user" => $id, "type" => "badge", "moduleInstance" => $badgeId], "count(*)");
        return (int)$levelNum;
    }

    public function getMaxReward($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "maxBonusReward");
    }

    public function getGeneralImages($image, $courseId): string
    {
        $result = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], $image);
        if ($result == NULL)
            return "";
        return $result;
    }

    public function getBadgeProgression($badge, $user)
    {
        $badgePosts = Core::$systemDB->selectMultiple(self::TABLE_PROGRESSION . " b left join " . self::TABLE . " on b.badgeId=" . self::TABLE . ".id left join participation on b.participationId=participation.id", ["b.user" => $user, "badgeId" => $badge], "isPost, post, participation.description, participation.rating");

        for ($i = 0; $i < sizeof($badgePosts); $i++) {
            if ($badgePosts[$i]["isPost"]) {
                if (substr($badgePosts[$i]["post"], 0, 9) === "mod/quiz/") {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/resource/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(" . strval($badgePosts[$i]["description"]) . ")";
                } else {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(" . strval($i + 1) . ")";
                }
            } else {
                if (substr($badgePosts[$i]["post"], 0, 8) === "view.php") {
                    $badgePosts[$i]["post"] = "https://pcm.rnl.tecnico.ulisboa.pt/moodle/mod/resource/" . $badgePosts[$i]["post"];
                    $badgePosts[$i]["description"] = "(" . strval($badgePosts[$i]["description"]) . ")";
                } else if (empty($badgePosts[$i]["description"])) {
                    $badgePosts[$i]["description"] = "(" . strval($i + 1) . ")";
                } else if (strlen($badgePosts[$i]["description"]) > 23) {
                    $badgePosts[$i]["post"] = $badgePosts[$i]["description"];
                    $badgePosts[$i]["description"] = "(" . strval($i + 1) . ")";
                } else {
                    $desc = $badgePosts[$i]["description"];
                    $badgePosts[$i]["description"] = "(" . $desc . ")";
                }
            }
        }

        return $badgePosts;
    }


    public function saveMaxReward($max, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["maxBonusReward" => $max], ["course" => $courseId]);
    }

    public function saveGeneralImages($image, $value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, [$image => $value], ["course" => $courseId]);
    }


    public function deleteLevels($courseId)
    {
        if (Core::$systemDB->tableExists(self::TABLE_LEVEL)) {
            $badges = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], 'id');
            foreach ($badges as $badge) {
                Core::$systemDB->delete(self::TABLE_LEVEL, ["badgeId" => $badge["id"]]);
            }
        }
    }

    public function countBadgesProgress($collection, $badge): int
    {
        $badgeParams = Core::$systemDB->selectMultiple(self::TABLE, ["id" => $badge], "isPost, isPoint, isCount");
        $count = 0;
        if (!empty($collection["value"])) {
            if ($badgeParams[0]["isPoint"]) {
                foreach ($collection["value"] as $line) {
                    $count += intval($line["rating"]);
                }
            } else {
                $count = sizeof($collection["value"]);
            }
        }
        return $count;
    }

    public static function newBadge($achievement, $courseId)
    {
        $maxLevel = empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);

        $badgeData = [
            "name" => $achievement['name'],
            "course" => $courseId,
            "description" => $achievement['description'],
            "maxLevel" => $maxLevel,
            "isExtra" => ($achievement['isExtra']) ? 1 : 0,
            "isBragging" => ($achievement['xp1'] == 0) ? 1 : 0,
            "isCount" => ($achievement['isCount']) ? 1 : 0,
            "isPost" => ($achievement['isPost']) ? 1 : 0,
            "isPoint" => ($achievement['isPoint']) ? 1 : 0,
            "isActive" => 0,
            "image" => array_key_exists("image", $achievement) ? $achievement['image'] : null
        ];

        $badgeId = Core::$systemDB->insert(self::TABLE, $badgeData);
        for ($level = 1; $level <= $maxLevel; $level++) {
            Core::$systemDB->insert(self::TABLE_LEVEL, [
                "badgeId" => $badgeId,
                "number" => $level,
                "goal" => $achievement['count' . $level],
                "description" => $achievement['desc' . $level],
                "reward" => abs($achievement['xp' . $level])
            ]);
        }

        $course = Course::getCourse($courseId, false);
        $badgeId = Core::$systemDB->select(self::TABLE, ["course" => $courseId, "name" => $achievement["name"]], "id");
        $levelsArray = array();
        $levelCount1 = Core::$systemDB->select(self::TABLE_LEVEL, ["badgeId" => $badgeId, "number" => 1], "goal");
        $levelCount2 = Core::$systemDB->select(self::TABLE_LEVEL, ["badgeId" => $badgeId, "number" => 2], "goal");
        $levelCount3 = Core::$systemDB->select(self::TABLE_LEVEL, ["badgeId" => $badgeId, "number" => 3], "goal");
        if ( !empty($levelCount2) ){
            array_push($levelsArray, $levelCount1, $levelCount2, $levelCount3);
        }else{
            array_push($levelsArray, $levelCount1);
        }
        
        $badge = new Badges();
        $badge->generateBadgeRule($course, $achievement['name'],  $achievement['isCount'], $levelsArray );

    }

    public static function editBadge($achievement, $courseId)
    {
        $originalBadge = Core::$systemDB->select(self::TABLE, ["course" => $courseId, 'id' => $achievement['id']], "*");

        if(!empty($originalBadge)){
            $maxLevel = empty($achievement['desc2']) ? 1 : (empty($achievement['desc3']) ? 2 : 3);
            $badgeData = [
                "maxLevel" => $maxLevel, "name" => $achievement['name'],
                "course" => $courseId, "description" => $achievement['description'],
                "isExtra" => ($achievement['isExtra']) ? 1 : 0,
                "isBragging" => ($achievement['xp1'] == 0) ? 1 : 0,
                "isCount" => ($achievement['isCount']) ? 1 : 0,
                "isPost" => ($achievement['isPost']) ? 1 : 0,
                "isPoint" => ($achievement['isPoint']) ? 1 : 0
            ];
            if (array_key_exists("image", $achievement)) {
                $badgeData["image"] = $achievement['image'];
            }
            Core::$systemDB->update(self::TABLE, $badgeData, ["id" => $achievement["id"]]);

            if ($originalBadge["maxLevel"] <= $maxLevel) {
                for ($i = 1; $i <= $maxLevel; $i++) {

                    if ($i > $originalBadge["maxLevel"]) {
                        //if they are new levels they need to be inserted and not updated
                        Core::$systemDB->insert(self::TABLE_LEVEL, [
                            "badgeId" => $achievement['id'],
                            "number" => $i,
                            "goal" => $achievement['count' . $i],
                            "description" => $achievement['desc' . $i],
                            "reward" => abs($achievement['xp' . $i])
                        ]);
                    } else {
                        Core::$systemDB->update(self::TABLE_LEVEL, [
                            "badgeId" => $achievement['id'],
                            "number" => $i,
                            "goal" => $achievement['count' . $i],
                            "description" => $achievement['desc' . $i],
                            "reward" => abs($achievement['xp' . $i])
                        ], ["number" => $i, "badgeId" => $achievement['id']]);
                    }
                }
            } else {
                //deletes original badge levels
                Core::$systemDB->delete(self::TABLE_LEVEL, ["badgeId" => $originalBadge['id']]);
                //creates new ones
                for ($i = 1; $i <= $maxLevel; $i++) {
                    Core::$systemDB->insert(self::TABLE_LEVEL, [
                        "badgeId" => $achievement['id'],
                        "number" => $i,
                        "goal" => $achievement['count' . $i],
                        "description" => $achievement['desc' . $i],
                        "reward" => abs($achievement['xp' . $i])
                    ]);
                }
            }
        }
    }

    public function deleteBadge($badge, $courseId)
    {
        Core::$systemDB->delete(self::TABLE, ["id" => $badge['id']]);
        Core::$systemDB->delete(self::TABLE_LEVEL, ["badgeId" => $badge['id']]);

        $course = Course::getCourse($courseId);
        $this->deleteGeneratedRule($course, $badge['name']);
    }

    public function toggleItemParam(int $itemId, string $param)
    {
        $state = Core::$systemDB->select(self::TABLE, ["id" => $itemId], $param);
        Core::$systemDB->update(self::TABLE, [$param => $state ? 0 : 1], ["id" => $itemId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Rules -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function generateBadgeRule(Course $course, string $badgeName, int $badgeisPost, array $levelsCount)
    {
        if ($badgeisPost == 0) {
            $template = file_get_contents(MODULES_FOLDER . "/" . self::ID . "/rules/" . self::BADGES_RULE_TEMPLATE);
        } else {
            $template = file_get_contents(MODULES_FOLDER . "/" . self::ID . "/rules/" . self::BADGES_IS_POST_RULE_TEMPLATE);
        }

        if (sizeof($levelsCount) == 3) {
            $levelsString =  "$levelsCount[0]" . ", " .  "$levelsCount[1]" . ", " . "$levelsCount[2]";
        }
        else {
            $levelsString =  ".$levelsCount[0]." ;
        }

        $newRule = str_replace("<badge-name>", $badgeName, $template);
        $newRule = str_replace("<lvs-count>", $levelsString, $newRule);

        $rs = new RuleSystem($course);
        
        $rule = array();
        $rule["module"] = self::ID;
        $filename = $rs->getFilename(self::ID);
        if ($filename == null) {
            $filename = $rs->createNewRuleFile(self::ID, 1);
            $rs->fixPrecedences();
            $filename = $rs->getFilename(self::ID);
        }
        $rule["rulefile"] = $filename;
        $rs->addRule($newRule, null, $rule); // add to end

    }

    public function deleteGeneratedRule(Course $course, string $badgeName)
    {
        $rs = new RuleSystem($course);
        $rule = array();

        $rule["name"] = $badgeName;
        $rule["rulefile"] = $rs->getFilename(self::ID);
        $position = $rs->getRulePosition($rule);

        if ($position !== false)
            $rs->removeRule($rule, $position);
    }

}

ModuleLoader::registerModule(array(
    'id' => Badges::ID,
    'name' => 'Badges',
    'description' => 'Enables Badges with 3 levels and xp points that can be atributed to a student in certain conditions.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Badges();
    }
));
