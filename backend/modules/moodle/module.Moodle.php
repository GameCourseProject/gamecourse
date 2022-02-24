<?php
namespace Modules\Moodle;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\CronJob;
use Modules\ClassCheck\ClassCheck;

class MoodleModule extends Module
{
    const ID = 'moodle';

    const TABLE_CONFIG = self::ID . '_config';

    static $moodle;

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init(){
        $this->setupData($this->getCourseId());
    }

    public function initAPIEndpoints()
    {
        /**
         * Gets moodle variables.
         *
         * @param int $courseId
         */
        API::registerFunction(self::ID, 'getMoodleVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            API::response(array('moodleVars' => $this->getMoodleVars($courseId)));
        });

        /**
         * Sets moodle variables.
         *
         * @param int $courseId
         * @param $moodle
         */
        API::registerFunction(self::ID, 'setMoodleVars', function () {
            API::requireCourseAdminPermission();
            API:: requireValues('courseId', 'moodle');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $moodle = API::getValue('moodle');
            $this->setMoodleVars($courseId, $moodle);
        });
    }

    public function setupResources()
    {
        parent::addResources('css/');
    }

    public function setupData(int $courseId)
    {
        $this->addTables(self::ID, self::TABLE_CONFIG);
        self::$moodle = new Moodle($courseId);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function disable(int $courseId)
    {
        new CronJob("Moodle", $courseId, null, null, true);
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
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

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
                "dbServer" => "localhost",
                "dbUser" => "root",
                "dbPass" => "",
                "dbName" => "moodle",
                "dbPort" => "3306",
                "tablesPrefix" => "mdl_",
                "moodleTime" => "0",
                "moodleCourse" => "",
                "moodleUser" => "",
                "periodicityNumber" => 0,
                "periodicityTime" => 'Minutes',
                "isEnabled" => false
            ];
        } else {
            if (!$moodleVarsDB["periodicityNumber"]) {
                $moodleVarsDB["periodicityNumber"] = 0;
            }
            if (!$moodleVarsDB["periodicityTime"]) {
                $moodleVarsDB["periodicityTime"] = 'Minutes';
            }
            $moodleVars = [
                "dbServer" => $moodleVarsDB["dbServer"],
                "dbUser" => $moodleVarsDB["dbUser"],
                "dbPass" => $moodleVarsDB["dbPass"],
                "dbName" => $moodleVarsDB["dbName"],
                "dbPort" => $moodleVarsDB["dbPort"],
                "tablesPrefix" => $moodleVarsDB["tablesPrefix"],
                "moodleTime" => $moodleVarsDB["moodleTime"],
                "moodleCourse" => $moodleVarsDB["moodleCourse"],
                "moodleUser" => $moodleVarsDB["moodleUser"],
                "periodicityNumber" => intval($moodleVarsDB["periodicityNumber"]),
                "periodicityTime" => $moodleVarsDB["periodicityTime"],
                "isEnabled" => filter_var($moodleVarsDB["isEnabled"], FILTER_VALIDATE_BOOLEAN)
            ];
        }

        return $moodleVars;
    }

    private function setMoodleVars($courseId, $moodle)
    {
        $arrayToDb = [
            "course" => $courseId,
            "dbServer" => $moodle['dbServer'],
            "dbUser" => $moodle['dbUser'],
            "dbPass" => $moodle['dbPass'],
            "dbName" => $moodle['dbName'],
            "dbPort" => $moodle["dbPort"],
            "tablesPrefix" => $moodle["tablesPrefix"],
            "moodleTime" => $moodle["moodleTime"],
            "moodleCourse" => $moodle["moodleCourse"],
            "moodleUser" => $moodle["moodleUser"],
            "periodicityNumber" => $moodle['periodicityNumber'],
            "periodicityTime" => $moodle['periodicityTime'],
            "isEnabled" => $moodle['isEnabled']
        ];

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
        }

        if (!$moodle['isEnabled']) { // disable classcheck
            $this->removeCronJob($courseId);

        } else { // enable classcheck
            $this->setCronJob($courseId, $moodle['periodicityNumber'], $moodle['periodicityTime']);
        }
    }

    // periodicity time: Minutes | Hours | Days
    private function setCronJob(int $courseId, int $periodicityNumber, string $periodicityTime)
    {
        API::verifyCourseIsActive($courseId);

        $moodleVars = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
        if ($moodleVars){
            $result = Moodle::checkConnection($moodleVars["dbServer"], $moodleVars["dbUser"], $moodleVars["dbPass"], $moodleVars["dbName"], $moodleVars["dbPort"]);
            if ($result) {
                new CronJob("Moodle", $courseId, $periodicityNumber, $periodicityTime);
                Core::$systemDB->update(self::TABLE_CONFIG, ["isEnabled" => 1, "periodicityNumber" => $periodicityNumber, 'periodicityTime' => $periodicityTime], ["course" => $courseId]);
            } else {
                API::error("Connection failed");
            }

        } else {
            API::error("Please set the moodle variables");
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

    private function removeCronJob($courseId)
    {
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
        new CronJob( "Moodle", $courseId, null, null, true);
    }
}

ModuleLoader::registerModule(array(
    'id' => MoodleModule::ID,
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
