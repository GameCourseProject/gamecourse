<?php
namespace Modules\ClassCheck;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;

class ClassCheckModule extends Module
{
    const ID = 'classcheck';

    const TABLE_CONFIG = self::ID . '_config';

    static $classCheck;


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        /**
         * Gets classcheck variables.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getClassCheckVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(array('classCheckVars' => $this->getClassCheckVars($courseId)));
        });

        /**
         * Sets classcheck variables.
         *
         * @param int $courseId
         * @param $classCheck
         */
        API::registerFunction(self::ID, 'setClassCheckVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'classCheck');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $classCheck = API::getValue('classCheck');
            $this->setClassCheckVars($courseId, $classCheck);
        });
    }

    public function setupResources()
    {
        parent::addResources('css/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        self::$classCheck = new ClassCheck($courseId);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function disable(int $courseId)
    {
        new CronJob("ClassCheck", $courseId, null, null, true);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId)
    {
        $pluginArr = array();

        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $classCheckDB_ = Core::$systemDB->selectMultiple(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($classCheckDB_) {
                $ccArray = array();
                foreach ($classCheckDB_ as $classCheckDB) {
                    unset($classCheckDB["id"]);
                    array_push($ccArray, $classCheckDB);
                }
                $pluginArr[self::TABLE_CONFIG] = $ccArray;
            }
        }
        return $pluginArr;
    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false) {
        $tableName = array_keys($tables);
        $i = 0;
        foreach ($tables as $table) {
            foreach ($table as $entry) {
                $existingCourse = Core::$systemDB->select($tableName[$i], ["course" => $courseId], "course");
                if($update && $existingCourse){
                    Core::$systemDB->update($tableName[$i], $entry, ["course" => $courseId]);
                }else{
                    $entry["course"] = $courseId;
                    Core::$systemDB->insert($tableName[$i], $entry);
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

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId)
    {
        new CronJob("ClassCheck", $courseId, null, null, true);
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getClassCheckVars($courseId): array
    {
        $classCheckDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

        if (empty($classCheckDB)) {
            $classCheckVars = [
                "tsvCode" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes',
                "isEnabled" => false
            ];
        } else {
            if (!$classCheckDB["periodicityNumber"]) {
                $classCheckDB["periodicityNumber"] = 0;
            }
            if (!$classCheckDB["periodicityTime"]) {
                $classCheckDB["periodicityTime"] = 'Minutes';
            }
            $classCheckVars = [
                "tsvCode" => $classCheckDB["tsvCode"],
                "periodicityNumber" => intval($classCheckDB["periodicityNumber"]),
                "periodicityTime" => $classCheckDB["periodicityTime"],
                "isEnabled" => filter_var($classCheckDB["isEnabled"], FILTER_VALIDATE_BOOLEAN)
            ];
        }

        return  $classCheckVars;
    }

    private function setClassCheckVars($courseId, $classCheck)
    {
        $arrayToDb = [
            "course" => $courseId,
            "tsvCode" => $classCheck['tsvCode'],
            "periodicityNumber" => $classCheck['periodicityNumber'],
            "periodicityTime" => $classCheck['periodicityTime'],
            "isEnabled" => $classCheck['isEnabled'] ? 1 : 0
        ];

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
        }

        if (!$classCheck['isEnabled']) { // disable classcheck
            $this->removeCronJob($courseId);

        } else { // enable classcheck
            $this->setCronJob($courseId, $classCheck['periodicityNumber'], $classCheck['periodicityTime']);
        }
    }

    // periodicity time: Minutes | Hours | Days
    private function setCronJob(int $courseId, int $periodicityNumber, string $periodicityTime)
    {
        API::verifyCourseIsActive($courseId);

        $classCheckVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
        if ($classCheckVars){
            $result = ClassCheck::checkConnection($classCheckVars["tsvCode"]);
            if ($result) {
                new CronJob("ClassCheck", $courseId, $periodicityNumber, $periodicityTime);
                Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 1, "periodicityNumber" => $periodicityNumber, 'periodicityTime' => $periodicityTime], ["course" => $courseId]);
            } else {
                API::error("Connection failed");
            }

        } else {
            API::error("Please set the class check variables");
        }
    }

    public function setCourseCronJobs($courseId, $active)
    {
        if(!$active){
            new CronJob("ClassCheck", $courseId, null, null, true);
        }
        else {
            $plugins = $this->moduleConfigJson($courseId);
            $pluginNames = array_keys($plugins);
            foreach($pluginNames as $name){
                $entry = $plugins[$name][0];
                if($entry["isEnabled"]){
                    $pluginName = (strcmp($name, self::TABLE_CONFIG) !== 0)? "ClassCheck" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]);
                }
            }
        }
    }

    private function removeCronJob($courseId)
    {
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
        new CronJob( "ClassCheck", $courseId, null, null, true);
    }
}

ModuleLoader::registerModule(array(
    'id' => ClassCheckModule::ID,
    'name' => 'ClassCheck',
    'description' => 'Allows ClassCheck to be automaticaly included on GameCourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new ClassCheckModule();
    }
));
