<?php
namespace Streaks;

use CacheSystem;
use GameCourse\Core;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Course;
use Modules\AwardList\AwardList;

class Streaks extends Module
{
    const ID = 'streaks';

    const TABLE = 'streak';
    const TABLE_CONFIG = 'streaks_config';
    const TABLE_PROGRESSION = 'streak_progression';

    const STREAKS_TEMPLATE_NAME = 'Streaks block - by streaks';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->setupData($this->getCourseId());
        $this->initDictionary();
        $this->initTemplates();
    }

    public function initDictionary(){

        /*** ------------ Libraries ------------ ***/

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding Streaks. It is provided by the streaks module.");


        /*** ------------ Functions ------------ ***/
        // streaks.getAllStreaks(isActive)
        Dictionary::registerFunction(
            self::ID,
            'getAllStreaks',
            function (bool $isActive = true) {
                $where = [];
                $where["isActive"] = $isActive;
                return $this->getStreak(true, $where);
            },
            "Returns a collection with all the streaks in the Course. The optional parameters can be used to find badges that specify a given combination of conditions:\nisActive: Streak is active.",
            'collection',
            'streak',
            'library',
            null,
            true
        );

        //streaks.getStreak(name)
        Dictionary::registerFunction(
            self::ID,
            'getStreak',
            function (string $name = null) {
                return $this->getStreak(false, ["name" => $name]);
            },
            "Returns the streak object with the specific name.",
            'object',
            'streak',
            'library',
            null,
            true
        );

        //%streak.description
        Dictionary::registerFunction(
            self::ID,
            'description',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "description");
            },
            "Returns a string with information regarding the name of the streak, the goal to obtain it and the reward associated to it.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "name");
            },
            "Returns a string with the name of the streak.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.color
        Dictionary::registerFunction(
            self::ID,
            'color',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "color");
            },
            "Returns a string with the reference of the color in hexadecimal of the streak.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.count
        Dictionary::registerFunction(
            self::ID,
            'count',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "count");
            },
            "Returns a number with the count of the streak.",
            'number',
            null,
            'object',
            'streak',
            true
        );

        //%streak.reward(type)
        Dictionary::registerFunction(
            self::ID,
            'reward',
            function ($streak, $type) {
                if ($type === "xp") return Dictionary::basicGetterFunction($streak, "reward");
                else if ($type === "token") return Dictionary::basicGetterFunction($streak, "tokens");
            },
            "Returns a string with the reward of completing a streak.",
            'string',
            null,
            'object',
            'streak',
            true
        );
        
        //%streak.tokens
        Dictionary::registerFunction(
            self::ID,
            'tokens',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "reward");
            },
            "Returns a string with the tokens of completing a streak.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.periodicity
        Dictionary::registerFunction(
            self::ID,
            'periodicity',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "periodicity");
            },
            "Returns a string with periodicity to respect.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.periodicityTime
        Dictionary::registerFunction(
            self::ID,
            'periodicityTime',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "periodicityTime");
            },
            "Returns a string with periodicity's time.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.isRepeatable
        Dictionary::registerFunction(
            self::ID,
            'isRepeatable',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "isRepeatable");
            },
            "Returns a boolean regarding whether the steak is repeatable.",
            'boolean',
            null,
            'object',
            'streak',
            true
        );

        //%streak.isCount
        Dictionary::registerFunction(
            self::ID,
            'isCount',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "isCount");
            },
            "Returns a boolean regarding whether the steak is count.",
            'boolean',
            null,
            'object',
            'streak',
            true
        );

        //%streak.isPeriodic
        Dictionary::registerFunction(
            self::ID,
            'isPeriodic',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "isPeriodic");
            },
            "Returns a boolean regarding whether the steak is periodic.",
            'boolean',
            null,
            'object',
            'streak',
            true
        );

        //%streak.isAtMost
        Dictionary::registerFunction(
            self::ID,
            'isAtMost',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "isAtMost");
            },
            "Returns a boolean regarding whether the steak is periodic and peridodicity is at most x participations.",
            'boolean',
            null,
            'object',
            'streak',
            true
        );

        //%streak.isActive
        Dictionary::registerFunction(
            self::ID,
            'isActive',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "isActive");
            },
            "Returns a boolean regarding whether the steak is active.",
            'boolean',
            null,
            'object',
            'streak',
            true
        );

        //%streak.image
        Dictionary::registerFunction(
            self::ID,
            'image',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "image");
            },
            "Returns the streak image path.",
            'string',
            null,
            'object',
            'streak',
            true
        );

        //%streak.counts returns collection of number ranging from 1 to the streak count
        Dictionary::registerFunction(
            self::ID,
            'counts',
            function ($streak) {
                Dictionary::checkArray($streak, "object", 'counts');
                $counts = [];
                for ($i = 1; $i <= $streak["value"]["count"]; $i++) {
                    $counts[] = ["number" => $i];
                }
                return Dictionary::createNode($counts, self::ID, "collection", $streak);
            },
            'Returns a collection of Level objects from that badge.',
            'collection',
            'level',
            'object',
            'streak',
            true
        );

        //%count.number
        Dictionary::registerFunction(
            self::ID,
            'number',
            function ($count) {
                return Dictionary::basicGetterFunction($count, "number");
            },
            'Returns a number that is the count.',
            'number',
            null,
            'object',
            'count',
            true
        );

        //streak.progression(user)
        Dictionary::registerFunction(
            self::ID,
            'progression',

            function ($streak, int $user) {
                Dictionary::checkArray($streak, "object", 'progression');
                $progressionNum = $this->getStreakProgression($streak, $user);
                $progression = ["number" => $progressionNum];
                unset($streak["value"]["description"]);
                $progression["libraryOfVariable"] = self::ID;
                $progression = array_merge($streak["value"], $progression);
                return Dictionary::createNode($progression, self::ID);
            },
            'Returns a collection object corresponding to the intermediate progress of a GameCourseUser identified by user for that streak.',
            'collection',
            'streak',
            'library',
            null,
            true
        );

        //streak.done(user)
        Dictionary::registerFunction(
            self::ID,
            'done',

            function ($streak, int $user) {
                Dictionary::checkArray($streak, "object", 'done');
                $nrStreaksMade = $this->getStreaksDone($streak, $user);
                $streaks = ["number" => $nrStreaksMade];
                unset($streak["value"]["description"]);
                $streaks["libraryOfVariable"] = self::ID;
                $streaks = array_merge($streak["value"], $streaks);
                return Dictionary::createNode($streaks, self::ID);
            },
            'Returns a collection object corresponding to the number of streaks a user has made in a streak.',
            'collection',
            'streak',
            'library',
            null,
            true
        );

        //streaks.getStreaksTokens(user) returns value of streak tokens for user
        Dictionary::registerFunction(
            self::ID,
            'getStreaksTokens',
            function ($user) {
                $userId = $this->getUserId($user);
                $tokens = $this->calculateStreakTokens($userId);
                return new ValueNode($tokens);
            },
            'Returns the sum of XP that all Streaks provide as reward from a GameCourseUser identified by user.',
            'integer',
            null,
            'library',
            null,
            true
        );
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::STREAKS_TEMPLATE_NAME))
            Views::createTemplateFromFile(self::STREAKS_TEMPLATE_NAME, file_get_contents(__DIR__ . '/streaks.txt'), $courseId, self::ID);
    }

    public function setupResources()
    {
        parent::addResources('css/streaks.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        if ($this->addTables(self::ID, self::TABLE) || empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, ["maxBonusReward" => MAX_BONUS_STREAKS, "course" => $courseId]);
        }

        $folder = Course::getCourseDataFolder($courseId, Course::getCourse($courseId, false)->getName());
        if (!file_exists($folder . "/" . self::ID))
            mkdir($folder . "/" . self::ID);
    }

    public function update_module($compatibleVersions)
    {
        /*
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = "modules/" . self::ID . "/config.json";
        $contents = array();
        if (file_exists($configFile)) {
            $contents = json_decode(file_get_contents($configFile));
            unlink($configFile);
        }
        */
        //verificar compatibilidade

    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
    {
        $streaksConfigArray = array();
        $streaksArray = array();

        $streaksArr = array();
        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $streaksConfigVarDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($streaksConfigVarDB) {
                unset($streaksConfigVarDB["course"]);
                array_push($streaksConfigArray, $streaksConfigVarDB);
            }
        }
        if (Core::$systemDB->tableExists(self::TABLE)) {
            $streaksVarDB = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");
            if ($streaksVarDB) {
                unset($streaksConfigVarDB["course"]);
                foreach (streakssVarDB as $streak) {
                    array_push($streaksArray, $streak);
                    
                }
            }
        }

        $streaksArr[self::TABLE_CONFIG] = $streaksConfigArray;
        $streaksArr[self::TABLE] = $streaksArray;

        if ($streaksConfigArray || $streaksArray ) {
            return $streaksArr;
        } else {
            return false;
        }
    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false): array
    {
        $tableName = array_keys($tables);
        $i = 0;
        $streakIds = array();
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
                    $streakIds[$importId] = $newId;
                }
            }
            $i++;
        }
        return $streakIds;
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
        $input = array('name' => "Max Streaks Reward", 'id' => 'maxBonusReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId)));
        return [$input];
    }
    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $maxVal = $generalInputs["maxBonusReward"];
        $this->saveMaxReward($maxVal, $courseId);
    }

    public function has_listing_items(): bool
    {
        return  true;
    }
    public function get_listing_items(int $courseId): array
    {

        $header = ['Name', 'Description', 'Count', 'Reward', 'Tokens', 'Color', 'is Repeatable', 'is Periodic', 'is Count', 'is At Most', 'Periodicity', 'Periodicity Time', 'Active'];
        $displayAtributes = [
            ['id' => 'name', 'type' => 'text'],
            ['id' => 'description', 'type' => 'text'],
            ['id' => 'count', 'type' => 'number'],
            ['id' => 'reward', 'type' => 'number'],
            ['id' => 'tokens', 'type' => 'number'],
            ['id' => 'color', 'type' => 'text'],
            ['id' => 'isRepeatable', 'type' => 'on_off button'],
            ['id' => 'isPeriodic', 'type' => 'on_off button'],
            ['id' => 'isCount', 'type' => 'on_off button'],
            ['id' => 'isAtMost', 'type' => 'on_off button'],
            ['id' => 'periodicity', 'type' => 'number'],
            ['id' => 'periodicityTime', 'type' => 'text'],
            ['id' => 'isActive', 'type' => 'on_off button']
        ];
        $actions = ['duplicate', 'edit', 'delete', 'export'];

        $items = $this->getStreaks($courseId);

        // Arguments for adding/editing
        $allAtributes = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Accomplishments Count", 'id' => 'count', 'type' => "number", 'options' => ""),
            array('name' => "Reward", 'id' => 'reward', 'type' => "number", 'options' => ""),
            array('name' => "Tokens", 'id' => 'tokens', 'type' => "number", 'options' => ""),
            array('name' => "Color", 'id' => 'color', 'type' => "color", 'options' => "", 'current_val' => ""),
            array('name' => "Is Repeatable", 'id' => 'isRepeatable', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Periodic", 'id' => 'isPeriodic', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Count", 'id' => 'isCount', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is At Most", 'id' => 'isAtMost', 'type' => "on_off button", 'options' => ""),
            array('name' => "Periodicity", 'id' => 'periodicity', 'type' => "number", 'options' => ""),
            array('name' => "Periodicity Time", 'id' => 'periodicityTime', 'type' => "text", 'options' => ""),
            array('name' => "Is Active", 'id' => 'isActive', 'type' => "on_off button", 'options' => "")
        ];
        return array('listName' => 'Streaks', 'itemName' => 'streak', 'header' => $header, 'displayAttributes' => $displayAtributes, 'actions' => $actions, 'items' => $items, 'allAttributes' => $allAtributes);
    }
    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
        if ($actiontype == 'new' || $actiontype == 'duplicate') {
            $this->newStreak($listingItem, $courseId);
        } elseif ($actiontype == 'edit') {
            $this->editStreak($listingItem, $courseId);
        } elseif ($actiontype == 'delete') {
            $this->deleteStreak($listingItem);
        }
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

    public function importItems(string $fileData, bool $replace = true): int{

        $courseId = $this->getCourseId();

        $nrItemsImported = 0;
        $separator = ",";
        $headers = ["name", "description", "color", "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isActive",
            "periodicity", "periodicityTime", "count", "reward", 'tokens', "image"];
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

                $streakData = [
                    "name" => $item[array_search("name", $headers)],
                    "description" => $item[array_search("description", $headers)],
                    "color" => $item[array_search("color", $headers)],
                    "isRepeatable" => intval($item[array_search("isRepeatable", $headers)]),
                    "isCount" => intval($item[array_search("isCount", $headers)]),
                    "isPeriodic" => intval($item[array_search("isPeriodic", $headers)]),
                    "isAtMost" => intval($item[array_search("isAtMost", $headers)]),
                    "isActive" => intval($item[array_search("isActive", $headers)]),
                    "periodicity" => intval($item[array_search("periodicity", $headers)]),
                    "periodicityTime" => $item[array_search("periodicityTime", $headers)],
                    "count" => intval($item[array_search("count", $headers)]),
                    "reward" => intval($item[array_search("reward", $headers)]),
                    "tokens" => intval($item[array_search("tokens", $headers)]),
                    "image" => $item[array_search("image", $headers)]
                ];


                if ($itemId && $replace) { // replace item
                    $streakData["id"] = $itemId;
                    Streaks::editStreak($streakData, $courseId);

                } else { // create item
                    Streaks::newStreak($streakData, $courseId);
                    $nrItemsImported++;
                }
            }
        }

        return $nrItemsImported;

    }

    public function exportItems(int $itemId = null): array{
        $courseId = $this->getCourseId();
        $course = Course::getCourse($courseId, false);

        // Get streaks to export
        if (!is_null($itemId))
            $listOfStreaks = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId, "id" => $itemId], "*");
        else
            $listOfStreaks = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*");

        $file = "";
        $separator = ",";
        $len = count($listOfStreaks);

        // Append headers
        $headers = ["name", "description", "color", "isRepeatable", "isCount", "isPeriodic", "isAtMost", "isActive",
            "periodicity", "periodicityTime", "count", "reward", "tokens", "image"];
        $file .= implode($separator, $headers) . "\n";

        // Go over each badge and append it to file
        foreach ($listOfStreaks as $index => $streak) {
            $params = [$streak["name"], $streak["description"], $streak["color"], $streak["isRepeatable"], $streak["isCount"],
                $streak["isPeriodic"], $streak["isAtMost"], $streak["isActive"], $streak["periodicity"],  $streak["periodicityTime"],
                $streak["count"], $streak["reward"], $streak["tokens"], $streak["image"]];

            $file .= implode($separator, $params);
            if ($index != $len - 1) $file .= "\n";
        }

        return ["Streaks - " . $course->getName(), $file];
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getStreaks($courseId){
        $streaks = Core::$systemDB->selectMultiple(self::TABLE, ["course" => $courseId], "*", "name");
        foreach ($streaks as &$streak) {
            //information to match needing fields
            $streak['isRepeatable'] = boolval($streak["isRepeatable"]);
            $streak['isCount'] = boolval($streak["isCount"]);
            $streak['isPeriodic'] = boolval($streak["isPeriodic"]);
            $streak['isAtMost'] = boolval($streak["isAtMost"]);
            $streak['isActive'] = boolval($streak["isActive"]);
        }
        return $streaks;
    }

    public function getStreak($selectMultiple, $where): ValueNode
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $streakArray = Core::$systemDB->selectMultiple(self::TABLE, $where);
            $type = "collection";
        } else {
            $streakArray = Core::$systemDB->select(self::TABLE, $where);
            if (empty($streakArray))
                throw new \Exception("In function streaks.getStreak(name): couldn't find streak with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return Dictionary::createNode($streakArray, self::ID, $type);

    }

    public function calculateStreakTokens($userId)
    {
        //streaks tokens
        $table = AwardList::TABLE . " a join " . Streaks::TABLE . " s on moduleInstance=s.id";
        $where = ["a.course" => $this->getCourseId(), "user" => $userId, "type" => "tokens"];
        $streaks = Core::$systemDB->selectMultiple($table, array_merge($where, ["isActive" => true]), "a.reward");
        $streakTokens = array_reduce($streaks, function ($carry, $streak) {
            $carry += intval($streak["reward"]);
            return $carry;
        });
        return $streakTokens ?? 0;
    }

    public function getStreaksDone($streak, $user): int {
        $cacheId = 'streaksDone-' . $streak["value"]["id"] . '-' . $user;
        list($hasCache, $cacheValue) = CacheSystem::get($cacheId);
        $autogameIsRunning = Core::$systemDB->select("autogame", ["course" => $this->getCourseId()], "isRunning");

        if ($autogameIsRunning && $hasCache) { // get from cache due to progression table being cleaned
            return intval($cacheValue);

        } else { // calculate
            $streakCounts = intval(Core::$systemDB->select(self::TABLE_PROGRESSION, ["course" => $this->getCourseId(), "user" => $user, "streakId" => $streak["value"]["id"]], "count(*)"));
            $streaksDone = floor($streakCounts / $streak["value"]["count"]);
            CacheSystem::store($cacheId, $streaksDone);
            return $streaksDone;
        }
    }
    // getUsersWithStreak

    public function getStreakProgression($streak, $user): int
    {
        $streakCounts = intval(Core::$systemDB->select(self::TABLE_PROGRESSION, ["course" => $this->getCourseId(), "user" => $user, "streakId" => $streak["value"]["id"]], "count(*)"));
        return $streakCounts % $streak["value"]["count"];
    }

    public function saveMaxReward($max, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["maxBonusReward" => $max], ["course" => $courseId]);
    }

    public function getMaxReward($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "maxBonusReward");
    }

    public static function newStreak($achievement, $courseId)
    {
        $streakData = [
            "name" => $achievement['name'],
            "course" => $courseId,
            "description" => $achievement['description'],
            "color" => $achievement['color'],
            "periodicity" => $achievement['periodicity'],
            "periodicityTime" => $achievement['periodicityTime'],
            "count" => $achievement['count'],
            "reward" => $achievement['reward'],
            "tokens" => $achievement['tokens'],
            "isRepeatable" => ($achievement['isRepeatable']) ? 1 : 0,
            "isCount" => ($achievement['isCount']) ? 1 : 0,
            "isPeriodic" => ($achievement['isPeriodic']) ? 1 : 0,
            "isAtMost" => ($achievement['isAtMost']) ? 1 : 0,
            "isActive" => ($achievement['isActive']) ? 1 : 0,
            "image" => array_key_exists("image", $achievement) ? $achievement['image'] : null
        ];

        Core::$systemDB->insert(self::TABLE, $streakData);
    }

    public static function editStreak($achievement, $courseId)
    {
        $originalStreak = Core::$systemDB->select(self::TABLE, ["course" => $courseId, 'id' => $achievement['id']], "*");

        if(!empty($originalStreak)) {
            $streakData = [
                "name" => $achievement['name'],
                "description" => $achievement['description'],
                "color" => $achievement['color'],
                "periodicity" => $achievement['periodicity'],
                "periodicityTime" => $achievement['periodicityTime'],
                "count" => $achievement['count'],
                "reward" => $achievement['reward'],
                "tokens" => $achievement['tokens'],
                "isRepeatable" => ($achievement['isRepeatable']) ? 1 : 0,
                "isCount" => ($achievement['isCount']) ? 1 : 0,
                "isPeriodic" => ($achievement['isPeriodic']) ? 1 : 0,
                "isAtMost" => ($achievement['isAtMost']) ? 1 : 0
            ];

            Core::$systemDB->update(self::TABLE, $streakData, ["id" => $achievement["id"]]);
        }
    }

    public function deleteStreak($streak)
    {
        Core::$systemDB->delete(self::TABLE, ["id" => $streak['id']]);
    }

    public function toggleItemParam(int $itemId, string $param)
    {
        $state = Core::$systemDB->select(self::TABLE, ["id" => $itemId], $param);
        Core::$systemDB->update(self::TABLE, [$param => $state ? 0 : 1], ["id" => $itemId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Rules -------------------- ***/
    /*** ----------------------------------------------- ***/

    // generateStreakRule
    // deleteGeneratedRule


}


ModuleLoader::registerModule(array(
    'id' => Streaks::ID,
    'name' => 'Streaks',
    'description' => 'Enables Streaks and xp points that can be atributed to a student in certain conditions.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function () {
        return new Streaks();
    }
));
