<?php

namespace Streaks;

use GameCourse\Core;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;
use GameCourse\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Course;

class Streaks extends Module
{

    const ID = 'streaks';

    const TABLE = 'streak';
    const TABLE_CONFIG = self::ID . '_config';

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

        //%streak.reward
        Dictionary::registerFunction(
            self::ID,
            'reward',
            function ($streak) {
                return Dictionary::basicGetterFunction($streak, "reward");
            },
            "Returns a string with the reward of completing a streak.",
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

        //streaks.streakProgression(streak,user)
        Dictionary::registerFunction(
            self::ID,
            'streakProgression',

            function ($streak, int $user) {
                $streakParticipation = $this->getStreakProgression($streak, $user);
                return Dictionary::createNode($streakParticipation, self::ID, 'collection');
            },
            'Returns a collection object corresponding to the intermediate progress of a GameCourseUser identified by user for that streak.',
            'collection',
            'badge',
            'library',
            null,
            true
        );

        //streakProgression.post
        //streakProgression.description

    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::STREAKS_TEMPLATE_NAME))
            Views::createTemplateFromFile(self::STREAKS_TEMPLATE_NAME, file_get_contents(__DIR__ . '/streaks.txt'), $courseId, self::ID);
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
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

        $header = ['Name', 'Description', 'Count', 'Periodicity', 'Periodicity Time', 'is Repeatable', 'is Periodic', 'is Count', 'is At Most' , 'Reward', 'Color', 'Active'];
        $displayAtributes = ['name', 'description', 'count', 'periodicity', 'periodicityTime', 'isRepeatable', 'isPeriodic', 'isCount', 'isAtMost', 'reward' , 'color', 'isActive'];
        $items = $this->getStreaks($courseId);

        // Arguments for adding/editing
        $allAtributes = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Accomplishments Count", 'id' => 'count', 'type' => "number", 'options' => ""),
            array('name' => "Reward", 'id' => 'reward', 'type' => "number", 'options' => ""),
            array('name' => "Color", 'id' => 'color', 'type' => "color", 'options' => "", 'current_val' => ""),
            array('name' => "Is Repeatable", 'id' => 'repeatable', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Periodic", 'id' => 'periodic', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Count", 'id' => 'countBased', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is At Most", 'id' => 'atMost', 'type' => "on_off button", 'options' => ""),
            array('name' => "Periodicity", 'id' => 'periodicity', 'type' => "number", 'options' => ""),
            array('name' => "Periodicity Time", 'id' => 'periodicityTime', 'type' => "select", 'options' => ["Minutes","Hours","Days","Weeks_"])
        ];
        return array('listName' => 'Streaks', 'itemName' => 'Streak', 'header' => $header, 'displayAtributes' => $displayAtributes, 'items' => $items, 'allAtributes' => $allAtributes);
    }
    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
        if ($actiontype == 'new') {
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

    }

    public function exportItems(int $itemId = null): array{

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

    // getStreakCount
    // getUsersWithStreak

    public function getStreakProgression($badge, $user)
    {


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
            "isRepeatable" => ($achievement['repeatable']) ? 1 : 0,
            "isCount" => ($achievement['countBased']) ? 1 : 0,
            "isPeriodic" => ($achievement['periodic']) ? 1 : 0,
            "isAtMost" => ($achievement['atMost']) ? 1 : 0

        ];

        Core::$systemDB->insert(self::TABLE, $streakData);
    }

    public static function editStreak($achievement, $courseId)
    {
        $originalStreak = Core::$systemDB->select(self::TABLE, ["course" => $courseId, 'id' => $achievement['id']], "*");

        if(!empty($originalStreak)) {
            $streakData = [
                "color" => $achievement['color'],
                "periodicity" => $achievement['periodicity'],
                "periodicityTime" => $achievement['periodicityTime'],
                "count" => $achievement['count'],
                "reward" => $achievement['reward'],
                "isRepeatable" => ($achievement['repeatable']) ? 1 : 0,
                "isCount" => ($achievement['countBased']) ? 1 : 0,
                "isPeriodic" => ($achievement['periodic']) ? 1 : 0,
                "isAtMost" => ($achievement['atMost']) ? 1 : 0

            ];

            Core::$systemDB->update(self::TABLE, $streakData, ["id" => $achievement["id"]]);
        }
    }

    public function deleteStreak($streak)
    {
        Core::$systemDB->delete(self::TABLE, ["id" => $streak['id']]);
    }


    public function activeItem($itemId)
    {
        $active = Core::$systemDB->select(self::TABLE, ["id" => $itemId], "isActive");
        if(!is_null($active)){
            Core::$systemDB->update(self::TABLE, ["isActive" => $active ? 0 : 1], ["id" => $itemId]);
            //ToDo: ADD RULE MANIPULATION HERE
        }
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
    'dependencies' => array(
    ),
    'factory' => function () {
        return new Streaks();
    }
));
