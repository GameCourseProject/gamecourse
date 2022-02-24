<?php
namespace VirtualCurrency;

use GameCourse\Core;
use Modules\Views\Expression\ValueNode;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Course;

class VirtualCurrency extends Module
{

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {

        $courseId = $this->getParent()->getId();
        $this->setupData($courseId);
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        $viewHandler->registerLibrary("virtualcurrency", "virtualcurrency", "This library provides information regarding Virtual Currency. It is provided by the Virtual Currency module.");


        //streaks.getVirtualCurrency(name)
        $viewHandler->registerFunction(
            'virtualcurrency',
            'getVirtualCurrency',
            function (string $name = null) {
                return $this->getVirtualCurrency(false, ["name" => $name]);
            },
            "Returns the Virtual Currency object with the specific name.",
            'object',
            'streak',
            'library',
            null
        );


    }

    // public function initDictionary

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/badges.css');
    }

    public function setupData($courseId)
    {
        $this->addTables("virtualcurrency", "config_virtual_currency");

        if ( empty(Core::$systemDB->select("config_virtual_currency", ["course" => $courseId]))) {
            Core::$systemDB->insert("config_virtual_currency",
                [
                    "name" => "",
                    "initialTokens" => INITIAL_TOKENS,
                    "course" => $courseId,
                    "cost1" => DEFAULT_COST,
                    "cost2" => DEFAULT_COST,
                    "cost3" => DEFAULT_COST,
                    "costWildcard" => DEFAULT_COST

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
        $input = [
            array('name' => "Name", 'id' => 'name', 'type' => "text", 'options' => "", 'current_val' => $this->getCurrencyName($courseId)),
            array('name' => "Initial Tokens", 'id' => 'initialtokens', 'type' => "number", 'options' => "", 'current_val' => intval($this->getInitialTokens($courseId))),
            array('name' => "Cost Tier 1", 'id' => 'cost1', 'type' => "number", 'options' => "", 'current_val' => intval($this->getTierCosts('cost1', $courseId))),
            array('name' => "Cost Tier 2", 'id' => 'cost2', 'type' => "number", 'options' => "", 'current_val' => intval($this->getTierCosts('cost2', $courseId))),
            array('name' => "Cost Tier 3", 'id' => 'cost3', 'type' => "number", 'options' => "", 'current_val' => intval($this->getTierCosts('cost3', $courseId))),
            array('name' => "Cost Wildcard Tier", 'id' => 'costWildcard', 'type' => "number", 'options' => "", 'current_val' => intval($this->getTierCosts('costWildcard', $courseId)))
        ];
        return $input;
    }
    public function save_general_inputs($generalInputs, $courseId)
    {
        $currencyName = $generalInputs["name"];
        $this->saveCurrencyName($currencyName, $courseId);

        $initialTokensVal = $generalInputs["initialtokens"];
        $this->saveInitialTokens($initialTokensVal, $courseId);

        $costTier1 = $generalInputs["cost1"];
        if ($costTier1 != "") {
            $this->saveTierCosts('cost1', $costTier1, $courseId);
        }
        $costTier2 = $generalInputs["cost2"];
        if ($costTier2 != "") {
            $this->saveTierCosts('cost2', $costTier2, $courseId);
        }
        $costTier3 = $generalInputs["cost3"];
        if ($costTier3 != "") {
            $this->saveTierCosts('cost3', $costTier3, $courseId);
        }
        $costTierW = $generalInputs["costWildcard"];
        if ($costTierW != "") {
            $this->saveTierCosts('costWildcard', $costTierW, $courseId);
        }
    }

    public function has_listing_items()
    {
        return  false;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows($courseId)
    {

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

    public function getInitialTokens($courseId)
    {
        return Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], "initialTokens");
    }
    public function saveInitialTokens($initialtokens, $courseId)
    {
        Core::$systemDB->update("config_virtual_currency", ["initialTokens" => $initialtokens], ["course" => $courseId]);
    }

    public function getTierCosts($cost, $courseId)
    {
        $result = Core::$systemDB->select("config_virtual_currency", ["course" => $courseId], $cost);
        if ($result == NULL)
            return "";
        return $result;
    }
    public function saveTierCosts($cost, $value, $courseId)
    {
        Core::$systemDB->update("config_virtual_currency", [$cost => $value], ["course" => $courseId]);
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