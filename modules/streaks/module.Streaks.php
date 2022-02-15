<?php

namespace Streaks;

use GameCourse\Core;
use Modules\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Course;

class Streaks extends Module
{

    // adaptar de acordo com a nova organizacao


    const STREAKS_TEMPLATE_NAME = 'Streaks block - by streaks';

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {

        $courseId = $this->getParent()->getId();
        $this->setupData($courseId);
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        $viewHandler->registerLibrary("streaks", "streaks", "This library provides information regarding Streaks. It is provided by the streaks module.");

        // streaks.getAllStreaks(isActive)
        $viewHandler->registerFunction(
            'streaks',
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
            null

        );

        //streaks.getStreak(name)
        $viewHandler->registerFunction(
            'streaks',
            'getStreak',
            function (string $name = null) {
                return $this->getStreak(false, ["name" => $name]);
            },
            "Returns the streak object with the specific name.",
            'object',
            'streak',
            'library',
            null
        );

        //%streak.description
        $viewHandler->registerFunction(
            'streaks',
            'description',
            function ($arg) {
                return $this->basicGetterFunction($arg, "description");
            },
            "Returns a string with information regarding the name of the streak, the goal to obtain it and the reward associated to it.",
            'string',
            null,
            'object',
            'streak'
        );

        //%streak.name
        $viewHandler->registerFunction(
            'streaks',
            'name',
            function ($streak) {
                return $this->basicGetterFunction($streak, "name");
            },
            "Returns a string with the name of the streak.",
            'string',
            null,
            'object',
            'streak'
        );

        //%streak.isActive
        $viewHandler->registerFunction(
            'streaks',
            'isActive',
            function ($streak) {
                return $this->basicGetterFunction($streak, "isActive");
            },
            "Returns a boolean regarding whether the steak is active.",
            'boolean',
            null,
            'object',
            'streak'
        );


        if (!$viewsModule->templateExists(self::STREAKS_TEMPLATE_NAME))
            $viewsModule->setTemplate(self::STREAKS_TEMPLATE_NAME, file_get_contents(__DIR__ . '/streaks.txt'), true);

    }

    // public function initDictionary

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }

    public function setupData($courseId)
    {
        if ($this->addTables("streaks", "streak") || empty(Core::$systemDB->select("streaks_config", ["course" => $courseId]))) {
            Core::$systemDB->insert("streaks_config", ["maxBonusReward" => MAX_BONUS_STREAKS, "course" => $courseId]);
        }

        $folder = Course::getCourseDataFolder($courseId, Course::getCourse($courseId, false)->getName());
        if (!file_exists($folder . "/streaks"))
            mkdir($folder . "/streaks");
    }

    public function update_module($compatibleVersions)
    {
        /*
        //obter o ficheiro de configuração do module para depois o apagar
        $configFile = "modules/badges/config.json";
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

    public function moduleConfigJson($courseId)
    {
        $streaksConfigArray = array();
        $streaksArray = array();
        $streaksLevelArray = array();

        $streaksArr = array();
        if (Core::$systemDB->tableExists("streaks_config")) {
            $streaksConfigVarDB = Core::$systemDB->select("streaks_config", ["course" => $courseId], "*");
            if ($streaksConfigVarDB) {
                unset($streaksConfigVarDB["course"]);
                array_push($streaksConfigArray, $streaksConfigVarDB);
            }
        }
        if (Core::$systemDB->tableExists("streak")) {
            $streaksVarDB = Core::$systemDB->selectMultiple("streak", ["course" => $courseId], "*");
            if ($streaksVarDB) {
                unset($streaksConfigVarDB["course"]);
                foreach (streakssVarDB as $streak) {
                    array_push($streaksArray, $streak);

                    $streaksLevelVarDB_ = Core::$systemDB->selectMultiple("streak_level", ["streakId" => $streak["id"]], "*");
                    foreach ($streaksLevelVarDB_ as $streaksLevelVarDB) {
                        array_push($streaksLevelArray, $streaksLevelVarDB);
                    }
                }
            }
        }

        $streaksArr["streaks_config"] = $streaksConfigArray;
        $streaksArr["streak"] = $streaksArray;
        $streaksArr["streak_level"] = $streaksLevelArray;

        if ($streaksConfigArray || $streaksArray || $streaksLevelArray) {
            return $streaksArr;
        } else {
            return false;
        }
    }

    public function readConfigJson($courseId, $tables, $update = false)
    {
        $tableName = array_keys($tables);
        $i = 0;
        $streakIds = array();
        $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                if ($tableName[$i] == "streaks_config") {
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        Core::$systemDB->insert($tableName[$i], $entry);
                    }
                } else  if ($tableName[$i] == "streak") {
                    $importId = $entry["id"];
                    unset($entry["id"]);
                    if ($update && $existingCourse) {
                        Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                    } else {
                        $entry["course"] = $courseId;
                        $newId = Core::$systemDB->insert($tableName[$i], $entry);
                    }
                    $streakIds[$importId] = $newId;
                } /*else  if ($tableName[$i] == "badge_level") {
                    $oldBadgeId = $streakIds[$entry["badgeId"]];
                    $entry["badgeId"] = $oldBadgeId;
                    unset($entry["id"]);
                    if ($update) {
                        Core::$systemDB->update($tableName[$i], $entry, ["badgeId" => $oldBadgeId, "number" => $entry["number"]]);
                    } else {
                        Core::$systemDB->insert($tableName[$i], $entry);
                    };
                } */
            }
            $i++;
        }
        return $streakIds;
    }

    public function is_configurable()
    {
        return true;
    }

    public function has_general_inputs()
    {
        return true;
    }
    public function get_general_inputs($courseId)
    {

        $input = array('name' => "Max Streaks Reward", 'id' => 'maxBonusReward', 'type' => "number", 'options' => "", 'current_val' => intval($this->getMaxReward($courseId)));
        return [$input];
    }
    public function save_general_inputs($generalInputs, $courseId)
    {
        $maxVal = $generalInputs["maxBonusReward"];
        $this->saveMaxReward($maxVal, $courseId);
    }

    public function has_listing_items()
    {
        return  true;
    }
    public function get_listing_items($courseId)
    {

        //tenho de dar header
        $header = ['Name', 'Description', 'Count', 'Periodicity', 'Periodicity Time', 'is Repeatable', 'is Periodic', 'is Count', 'Reward', 'Color', 'Active'];
        $displayAtributes = ['name', 'description', 'count', 'periodicity', 'periodicityTime', 'isRepeatable', 'isPeriodic', 'isCount', 'reward' , 'color', 'isActive'];
        // items (pela mesma ordem do header)
        $items = $this->getStreaks($courseId);
        //argumentos para add/edit
        $allAtributes = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => ""),
            array('name' => "Description", 'id' => 'description', 'type' => "text", 'options' => ""),
            array('name' => "Accomplishments Count", 'id' => 'count', 'type' => "number", 'options' => ""),
            array('name' => "Reward", 'id' => 'reward', 'type' => "number", 'options' => ""),
            array('name' => "Color", 'id' => 'color', 'type' => "color", 'options' => "", 'current_val' => ""),
            array('name' => "Is Repeatable", 'id' => 'repeatable', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Periodic", 'id' => 'periodic', 'type' => "on_off button", 'options' => ""),
            array('name' => "Is Count", 'id' => 'count', 'type' => "on_off button", 'options' => ""),
            array('name' => "Periodicity", 'id' => 'periodicity', 'type' => "number", 'options' => ""),
            array('name' => "Periodicity Time", 'id' => 'periodicityTime', 'type' => "select", 'options' => ["Minutes","Days","Weeks"])
            
        ];
        return array('listName' => 'Streaks', 'itemName' => 'Streak', 'header' => $header, 'displayAtributes' => $displayAtributes, 'items' => $items, 'allAtributes' => $allAtributes);
    }
    public function save_listing_item($actiontype, $listingItem, $courseId)
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

    public function deleteDataRows($courseId)
    {
        //$this->deleteLevels($courseId);
        Core::$systemDB->delete("streak", ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public static function importItems($course, $fileData, $replace = true){

    }

    public static function exportItems($course){

    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getStreak($selectMultiple, $where)
    {
        $where["course"] = $this->getCourseId();
        if ($selectMultiple) {
            $streakArray = Core::$systemDB->selectMultiple("streak", $where);
            $type = "collection";
        } else {
            $streakArray = Core::$systemDB->select("streak", $where);
            if (empty($streakArray))
                throw new \Exception("In function streaks.getStreak(name): couldn't find streak with name '" . $where["name"] . "'.");
            $type = "object";
        }
        return $this->createNode($streakArray, 'streaks', $type);

    }

    // getStreakCount

    // getUsersWithStreak - perguntar DG


    public function saveMaxReward($max, $courseId)
    {
        Core::$systemDB->update("streaks_config", ["maxBonusReward" => $max], ["course" => $courseId]);
    }

    public function getMaxReward($courseId)
    {
        return Core::$systemDB->select("streaks_config", ["course" => $courseId], "maxBonusReward");
    }


    // name description color isRepeatable periodicity #participations
    public static function newStreak($achievement, $courseId)
    {

    }

    public static function editStreak($achievement, $courseId)
    {

    }

    // resets streak due to periodicity or if it is repeatable & the users has finished it.
    public static function resetStreak($achievement, $courseId)
    {

    }

    public function deleteStreak($streak)
    {
        Core::$systemDB->delete("streak", ["id" => $streak['id']]);
    }


    public function getStreaks($courseId)
    {

    }


    public function activeItem($itemId)
    {
        $active = Core::$systemDB->select("streak", ["id" => $itemId], "isActive");
        if(!is_null($active)){
            Core::$systemDB->update("streak", ["isActive" => $active ? 0 : 1], ["id" => $itemId]);
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
    'id' => 'streaks',
    'name' => 'Streaks',
    'description' => 'Enables Streaks and xp points that can be atributed to a student in certain conditions.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Streaks();
    }
));
