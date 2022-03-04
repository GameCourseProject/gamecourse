<?php
namespace VirtualCurrency;

use GameCourse\Core;
use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;
use Modules\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Course;

class VirtualCurrency extends Module
{
    const ID = 'virtualcurrency';

    const TABLE_WALLET = 'user_wallet';
    const TABLE_CONFIG ='config_virtual_currency';

    const CURRENCY_TEMPLATE_NAME = 'Currency block - by virtualcurrency';



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

        Dictionary::registerLibrary("virtualcurrency", "virtualcurrency", "This library provides information regarding Virtual Currency. It is provided by the Virtual Currency module.");


        //virtualcurrency.name
        Dictionary::registerFunction(
            self::ID,
            'name',
            function ($arg) {
                return ictionary::basicGetterFunction($arg, "name");
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
                return ictionary::basicGetterFunction($arg, "skillCost");
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
                return ictionary::basicGetterFunction($arg, "wildcardCost");
            },
            "Returns a string with the cost of a wildcard.",
            'object',
            'virtualcurrency',
            'library',
            null
        );

        // TODO: user_wallet
    }


    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::CURRENCY_TEMPLATE_NAME))
            Views::createTemplateFromFile(self::CURRENCY_TEMPLATE_NAME, file_get_contents(__DIR__ . '/currency.txt'), $courseId, self::ID);
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }

    public function setupData(int $courseId)
    {
        $this->addTables("virtualcurrency", "config_virtual_currency");

        if ( empty(Core::$systemDB->select("config_virtual_currency", ["course" => $courseId]))) {
            Core::$systemDB->insert("config_virtual_currency",
                [
                    "name" => "",
                    "course" => $courseId,
                    "skillCost" => DEFAULT_COST,
                    "wildcardCost" => DEFAULT_COST,
                    "attemptRating" => ""

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

        if (Core::$systemDB->tableExists("config_virtual_currency")) {
            $currencyConfigVarDB = Core::$systemDB->selectMultiple("config_virtual_currency", ["course" => $courseId], "*");
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
                if ($tableName[$i] == "config_virtual_currency") {
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
            array('name' => "Minimum Rating for Attempt", 'id' => 'attemptrating', 'type' => "number", 'options' => "", 'current_val' => intval($this->getAttemptRating($courseId)))

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

    public function has_listing_items(): bool
    {
        return  false;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function dropTables($moduleName)
    {
        parent::dropTables($moduleName);
    }
    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete(self::TABLE_WALLET, ["course" => $courseId]);
    }

    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getCurrencyName($courseId)
    {
        return Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], "name");
    }
    public function saveCurrencyName($currencyName ,$courseId)
    {
        Core::$systemDB->update("config_virtual_currency", ["name" => $currencyName], ["course" => $courseId]);
    }

    public function getSkillCost( $courseId)
    {
        return Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], "skillCost");

    }
    public function saveSkillCost($value, $courseId)
    {
        Core::$systemDB->update("config_virtual_currency", ["skillCost" => $value], ["course" => $courseId]);
    }

    public function getWildcardCost( $courseId)
    {
        return Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], "wildcardCost");

    }
    public function saveWildcardCost($value, $courseId)
    {
        Core::$systemDB->update("config_virtual_currency", ["wildcardCost" => $value], ["course" => $courseId]);
    }

    public function getAttemptRating( $courseId)
    {
        return Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], "attemptRating");

    }
    public function saveAttemptRating($value, $courseId)
    {
        Core::$systemDB->update("config_virtual_currency", ["attemptRating" => $value], ["course" => $courseId]);
    }

}

ModuleLoader::registerModule(array(
    'id' => 'virtualcurrency',
    'name' => 'Virtual Currency',
    'description' => 'Allows Virtual Currency to be automaticaly included on gamecourse.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new VirtualCurrency();
    }
));

?>