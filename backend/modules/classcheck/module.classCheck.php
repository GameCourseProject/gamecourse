<?php
namespace Modules\ClassCheck;

use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;

class ClassCheckModule extends Module
{
    const ID = 'classcheck';

    const TABLE_CONFIG = self::ID . '_config';

    private $classCheck;


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        /**
         * TODO: what does this function do?
         *
         * @param int $courseId
         * @param $periodicity (optional) // TODO: type?
         * @param $disablePeriodicity (optional) // TODO: type?
         * @param $classCheck (optional) // TODO: type?
         */
        API::registerFunction(self::ID, 'courseClassCheck', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = Course::getCourse($courseId, false);

            if (!$course->exists())
                API::error('There is no course with id = ' . $courseId);

            if (API::hasKey('periodicity')) {
                $periodicity = API::getValue('periodicity');
                $response = $this->setCronJob($courseId, $periodicity);

                if ($response["result"]) API::response(["updatedData" => ["Plugin Class Check enabled"]]);
                else API::error($response["errorMessage"]);
                return;
            }

            if (API::hasKey('disablePeriodicity')) {
                $response = $this->removeCronJob($courseId);

                if ($response["result"]) API::response(["updatedData" => ["Plugin Class Check disabled"]]);
                else API::error([$response["errorMessage"]]);
                return;
            }

            if (API::hasKey('classCheck')) {
                $classCheck = API::getValue('classCheck');

                if ($this->setClassCheckVars($courseId, $classCheck)) API::response(["updatedData" => ["Variables for Class check saved"]]);
                else API::error("Please fill the mandatory fields");
                return;
            }

            $classCheckVars = $this->getClassCheckVars($courseId);
            API::response(array('classCheckVars' => $classCheckVars));
        });
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        $this->classCheck = new ClassCheck($courseId);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
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
        return "classCheckPersonalizedConfig";
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function dropTables(string $moduleId)
    {
        $courseId = $this->getCourseId();
        new CronJob("ClassCheck", $courseId, null, null, true);
        parent::dropTables($moduleId);
    }

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
                "periodicityTime" => 'Minutes'
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
                "periodicityTime" => $classCheckDB["periodicityTime"]
            ];
        }

        return  $classCheckVars;
    }

    private function setClassCheckVars($courseId, $classCheck): bool
    {
        $classCheckVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

        $arrayToDb = ["course" => $courseId, "tsvCode" => $classCheck['tsvCode']];

        if (empty($classCheck["tsvCode"])) {
            return false;
        } else {
            if (empty($classCheckVars)) {
                Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
            } else {
                Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId] );
            }
            return true;
        }
    }

    private function setCronJob($courseId, $vars): array
    {
        if(!Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])){
            return array("result" => false, "errorMessage" => "Course must be active to enable plugins");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {

            $classCheckVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($classCheckVars){
                $result = ClassCheck::checkConnection($classCheckVars["tsvCode"]);
                if ($result){
                    new CronJob("ClassCheck", $courseId, $vars['number'], $vars['time']['name']);
                    Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 1, "periodicityNumber" =>$vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                    return array("result" => true);
                } else {
                    return array("result" => false, "errorMessage" => "Connection failed");
                }
            } else {
                return array("result" => false, "errorMessage" => "Please set the class check variables");
            }
        }
    }

    private function removeCronJob($courseId): array
    {
        if (self::TABLE_CONFIG) {
            Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
            new CronJob( "ClassCheck", $courseId, null, null, true);
            return array("result" => true);
        }else{
            return array("result" => false, "errorMessage" => "Could not find a table in DB for that "."Moodle". " plugin");
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
}

ModuleLoader::registerModule(array(
    'id' => 'classcheck',
    'name' => 'ClassCheck',
    'description' => 'Allows ClassCheck to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new ClassCheckModule();
    }
));

?>
