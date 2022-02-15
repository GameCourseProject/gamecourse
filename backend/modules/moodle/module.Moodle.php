<?php
namespace Modules\Moodle;

use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;

class MoodleModule extends Module
{
    const ID = 'moodle';

    const TABLE_CONFIG = 'config_moodle';

    private $moodle;

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
        $this->initAPIEndpoints();
    }

    public function initAPIEndpoints()
    {
        /**
         * TODO: what does this function do?
         *
         * @param int $courseId
         * @param $periodicity (optional) // TODO: type?
         * @param $disablePeriodicity (optional) // TODO: type?
         * @param $moodle (optional) // TODO: type?
         */
        API::registerFunction(self::ID, 'courseMoodle', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = Course::getCourse($courseId, false);

            if (!$course->exists())
                API::error('There is no course with id = ' . $courseId);

            if (API::hasKey('periodicity')) {
                $periodicity = API::getValue('periodicity');
                $response = $this->setCronJob($courseId, $periodicity);

                if ($response["result"]) API::response(["updatedData" => ["Moodle enabled"]]);
                else API::error($response["errorMessage"]);
                return;
            }

            if (API::hasKey('disablePeriodicity')) {
                $response = $this->removeCronJob( $courseId);
                if ($response["result"]) API::response(["updatedData" => ["Moodle disabled"]]);
                else API::error($response["errorMessage"]);
                return;
            }

            if (API::hasKey('moodle')) {
                $moodle = API::getValue('moodle');
                if ($this->setMoodleVars($courseId, $moodle)) API::response(["updatedData" => ["Variables for moodle saved"]]);
                else API::error("Please fill the mandatory fields");
                return;
            }

            $moodleVars = $this->getMoodleVars($courseId);
            API::response(array('moodleVars' => $moodleVars));
        });
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG, "ConfigMoodle");
        $this->moodle = new Moodle($courseId);
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function moduleConfigJson(int $courseId): array
    {
        $pluginArr = array();

        if (Core::$systemDB->tableExists(self::TABLE_CONFIG)) {
            $moodleVarsDB_ = Core::$systemDB->selectMultiple(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($moodleVarsDB_) {
                $moodleArray = array();
                foreach ($moodleVarsDB_ as $moodleVarsDB) {
                    unset($moodleVarsDB["course"]);
                    unset($moodleVarsDB["id"]);
                    array_push($moodleArray, $moodleVarsDB);
                }
                $pluginArr[self::TABLE_CONFIG] = $moodleArray;
            }
        }
        return $pluginArr;

    }

    public function readConfigJson(int $courseId, array $tables, bool $update = false): bool
    {
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

    public function get_personalized_function(): string
    {
        return "moodlePersonalizedConfig";
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function dropTables(string $moduleId)
    {
        $courseId = $this->getCourseId();
        new CronJob("Moodle", $courseId, null, null, true);
        parent::dropTables($moduleId);
    }

    public function deleteDataRows(int $courseId)
    {
        new CronJob("Moodle", $courseId, null, null, true);
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getMoodleVars($courseId): array
    {
        $moodleVarsDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

        if (empty($moodleVarsDB)) {
            $moodleVars = [
                "dbserver" => "localhost",
                "dbuser" => "root",
                "dbpass" => "",
                "db" => "moodle",
                "dbport" => "3306",
                "prefix" => "mdl_",
                "time" => "0",
                "course" => "",
                "user" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes'
            ];
        } else {
            if (!$moodleVarsDB["periodicityNumber"]) {
                $moodleVarsDB["periodicityNumber"] = 0;
            }
            if (!$moodleVarsDB["periodicityTime"]) {
                $moodleVarsDB["periodicityTime"] = 'Minutes';
            }
            $moodleVars = [
                "dbserver" => $moodleVarsDB["dbServer"],
                "dbuser" => $moodleVarsDB["dbUser"],
                "dbpass" => $moodleVarsDB["dbPass"],
                "db" => $moodleVarsDB["dbName"],
                "dbport" => $moodleVarsDB["dbPort"],
                "prefix" => $moodleVarsDB["tablesPrefix"],
                "time" => $moodleVarsDB["moodleTime"],
                "course" => $moodleVarsDB["moodleCourse"],
                "user" => $moodleVarsDB["moodleUser"],
                "periodicityNumber" => intval($moodleVarsDB["periodicityNumber"]),
                "periodicityTime" => $moodleVarsDB["periodicityTime"]
            ];
        }

        return $moodleVars;
    }

    private function setMoodleVars($courseId, $moodleVar): bool
    {
        $moodleVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");

        $arrayToDb = [
            "course" => $courseId,
            "dbServer" => $moodleVar['dbserver'],
            "dbUser" => $moodleVar['dbuser'],
            "dbPass" => $moodleVar['dbpass'],
            "dbName" => $moodleVar['db'],
            "dbPort" => $moodleVar["dbport"],
            "tablesPrefix" => $moodleVar["prefix"],
            "moodleTime" => $moodleVar["time"],
            "moodleCourse" => $moodleVar["course"],
            "moodleUser" => $moodleVar["user"]
        ];

        if (empty($moodleVar['dbserver']) || empty($moodleVar['dbuser']) || empty($moodleVar['db'])) {
            return false;
        } else {
            if (empty($moodleVars)) {
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
            return array("result" => false, "errorMessage" => "Course must be active to enable Moodle");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {

            $moodleVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
            if ($moodleVars){
                //verificar ligaçao à bd
                $result = Moodle::checkConnection($moodleVars["dbServer"], $moodleVars["dbUser"], $moodleVars["dbPass"], $moodleVars["dbName"], $moodleVars["dbPort"]);
                if($result){
                    new CronJob("Moodle", $courseId, $vars['number'], $vars['time']['name']);
                    Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 1, "periodicityNumber" => $vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                    return array("result"=> true);
                }else{
                    return array("result" => false, "errorMessage" =>"Connection failed");
                }
            } else{
                return array("result"=> false, "errorMessage" => "Please set the moodle variables");
            }

        }
    }

    private function removeCronJob($courseId): array
    {
        if (self::TABLE_CONFIG){
            Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
            new CronJob( "Moodle", $courseId, null, null, true);
            return array("result" => true);
        } else {
            return array("result" => false, "errorMessage" => "Could not find a table in DB for that "."Moodle". " plugin");
        }
    }

    //removes or adds all active cronjobs according to course's active state
    public function setCourseCronJobs($courseId, $active)
    {
        if(!$active){
            new CronJob("Moodle",  $courseId, null, null, true);
        }
        else {
            $plugins = $this->moduleConfigJson($courseId);
            $pluginNames = array_keys($plugins);
            foreach($pluginNames as $name){
                $entry = $plugins[$name][0];
                if($entry["isEnabled"]){
                    $pluginName =  (strcmp($name, self::TABLE_CONFIG) !== 0)? "Moodle" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]);
                }
            }
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => 'moodle',
    'name' => 'Moodle',
    'description' => 'Allows Moodle to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new MoodleModule();
    }
));

?>
