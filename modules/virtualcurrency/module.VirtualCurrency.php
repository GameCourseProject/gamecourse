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
                    "course" => $courseId,
                    "skillCost" => DEFAULT_COST
                    
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
            array('name' => "Skill Cost", 'id' => 'skillcost', 'type' => "number", 'options' => "", 'current_val' => intval($this->getSkillCost($courseId)))
        ];
        return $input;
    }
    public function save_general_inputs($generalInputs, $courseId)
    {
        $currencyName = $generalInputs["name"];
        $this->saveCurrencyName($currencyName, $courseId);

        $skillCost = $generalInputs["skillcost"];
        if ($skillCost != "") {
            $this->saveSkillCost( $skillCost, $courseId);
        }
    }

    public function has_listing_items()
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