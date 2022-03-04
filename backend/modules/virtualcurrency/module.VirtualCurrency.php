<?php
namespace VirtualCurrency;

use GameCourse\Core;
use GameCourse\Views\Dictionary;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class VirtualCurrency extends Module
{
    const ID = 'virtualcurrency';

    const TABLE_WALLET = 'user_wallet';
    const TABLE_CONFIG = 'virtual_currency_config';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {

        $this->setupData($this->getCourseId());
        $this->initDictionary();
    }

    public function initDictionary(){

        Dictionary::registerLibrary(self::ID, self::ID, "This library provides information regarding Virtual Currency. It is provided by the Virtual Currency module.");

        //virtualcurrency.name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "name");
            },
            "Returns the Virtual Currency object with the specific name.",
            'object',
            'virtualcurrency',
            'library',
            null
        );

        //virtualcurrency.skillCost
        Dictionary::registerFunction(
            self::ID,
            'skillCost',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "skillCost");
            },
            "Returns a string with the retry cost of a skill.",
            'object',
            'virtualcurrency',
            'library',
            null
        );

        //virtualcurrency.wildcardCost
        Dictionary::registerFunction(
            self::ID,
            'wildcardCost',
            function ($arg) {
                return Dictionary::basicGetterFunction($arg, "wildcardCost");
            },
            "Returns a string with the cost of a wildcard.",
            'object',
            'virtualcurrency',
            'library',
            null
        );

        // TODO: user_wallet
    }

    public function setupResources()
    {
        parent::addResources('css/virtualcurrency.css');
        parent::addResources('imgs/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId]))) {
            Core::$systemDB->insert(self::TABLE_CONFIG,
                [
                    "name" => "",
                    "course" => $courseId,
                    "skillCost" => DEFAULT_COST,
                    "wildcardCost" => DEFAULT_COST,
                    "attemptRating" => 0
                ]);
        }
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

        $currencyConfigArray = array();

        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $currencyConfigVarDB = Core::$systemDB->selectMultiple(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($currencyConfigVarDB) {
                $currencyArray = array();
                foreach ($currencyConfigVarDB as $currencyConfigVarDB) {
                    unset($currencyConfigVarDB["course"]);
                    unset($currencyConfigVarDB["id"]);
                    array_push($currencyArray, $currencyConfigVarDB);
                }
                $currencyConfigArray["config_moodle"] = $currencyArray;
            }
        }
        return $currencyConfigArray;
    }

    public function readConfigJson($courseId, $tables, $update = false)
    {
        $tableName = array_keys($tables);
        $i = 0;
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
                }
            }
            $i++;
        }
        return false;

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
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => "", 'current_val' => $this->getCurrencyName($courseId)),
            array('name' => "Skill Cost", 'id' => 'skillcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getSkillCost($courseId))),
            array('name' => "Wildcard Cost", 'id' => 'wildcardcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getWildcardCost($courseId))),
            array('name' => "Min. Rating for Attempt", 'id' => 'attemptrating', 'type' => "number", 'options' => "", 'current_val' => intval($this->getAttemptRating($courseId)))
        ];
        return $input;
    }
    public function save_general_inputs(array $generalInputs, int $courseId)
    {
        $currencyName = $generalInputs["name"];
        $this->saveCurrencyName($currencyName, $courseId);

        $skillCost = $generalInputs["skillcost"];
        if ($skillCost != "") {
            $this->saveSkillCost( $skillCost, $courseId);
        }
        $wildcardCost = $generalInputs["wildcardcost"];
        if ($wildcardCost != "") {
            $this->saveWildcardCost( $wildcardCost, $courseId);
        }
        $attemptRating = $generalInputs["attemptrating"];
        $this->saveAttemptRating($attemptRating, $courseId);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete(self::TABLE_WALLET, ["course" => $courseId]);
    }

    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getCurrencyName($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "name");
    }
    public function saveCurrencyName($currencyName ,$courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["name" => $currencyName], ["course" => $courseId]);
    }

    public function getSkillCost($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "skillCost");

    }
    public function saveSkillCost($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["skillCost" => $value], ["course" => $courseId]);
    }

    public function getWildcardCost($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "wildcardCost");

    }
    public function saveWildcardCost($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["wildcardCost" => $value], ["course" => $courseId]);
    }

    public function getAttemptRating($courseId)
    {
        return Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "attemptRating");

    }
    public function saveAttemptRating($value, $courseId)
    {
        Core::$systemDB->update(self::TABLE_CONFIG, ["attemptRating" => $value], ["course" => $courseId]);
    }

}

ModuleLoader::registerModule(array(
    'id' => VirtualCurrency::ID,
    'name' => 'Virtual Currency',
    'description' => 'Allows Virtual Currency to be automaticaly included on GameCourse.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new VirtualCurrency();
    }
));