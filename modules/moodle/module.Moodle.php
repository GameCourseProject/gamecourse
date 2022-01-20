<?php
namespace Modules\Moodle;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\CronJob;

class Moodle extends Module
{
    private $moodle;

    private function getMoodleVars($courseId)
    {
        $moodleVarsDB = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");

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
    private function setMoodleVars($courseId, $moodleVar)
    {
        $moodleVars = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");

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
                Core::$systemDB->insert("config_moodle", $arrayToDb);
            } else {
                Core::$systemDB->update("config_moodle", $arrayToDb, ["course" => $courseId] );
            }
            return true;
        }
    }

    private function setCronJob($courseId, $vars)
    {
        if(!Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])){
            return array("result" => false, "errorMessage" => "Course must be active to enable plugins");
        }
        if (empty($vars['number']) || empty($vars['time'])) {
            return array("result" => false, "errorMessage" => "Select a periodicity");
        } else {

            $moodleVars = Core::$systemDB->select("config_moodle", ["course" => $courseId], "*");
            if ($moodleVars){
                //verificar ligaçao à bd
                $result = MoodleModule::checkConnection($moodleVars["dbServer"], $moodleVars["dbUser"], $moodleVars["dbPass"], $moodleVars["dbName"], $moodleVars["dbPort"]);
                if($result){
                    new CronJob("Moodle", $courseId, $vars['number'], $vars['time']['name']);
                    Core::$systemDB->update("config_moodle", ["isEnabled" => 1, "periodicityNumber" => $vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                    return array("result"=> true);
                }else{
                    return array("result" => false, "errorMessage" =>"Connection failed");
                }
            } else{
                return array("result"=> false, "errorMessage" => "Please set the moodle variables");
            }

        }
    }
    private function removeCronJob($courseId){
        $tableName = "config_moodle";

        if($tableName){
            Core::$systemDB->update($tableName, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
            new CronJob( "Moodle", $courseId, null, null, true);
            return array("result" => true);
        }else{
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
                    $pluginName =  (strcmp($name, "config_moodle") !== 0)? "Moodle" : null;
                    new CronJob($pluginName,  $courseId, $entry["periodicityNumber"], $entry["periodicityTime"]);
                }
            }
        }
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function moduleConfigJson($courseId)
    {
        $pluginArr = array();

        if (Core::$systemDB->tableExists("config_moodle")) {
            $moodleVarsDB_ = Core::$systemDB->selectMultiple("config_moodle", ["course" => $courseId], "*");
            if ($moodleVarsDB_) {
                $moodleArray = array();
                foreach ($moodleVarsDB_ as $moodleVarsDB) {
                    unset($moodleVarsDB["course"]);
                    unset($moodleVarsDB["id"]);
                    array_push($moodleArray, $moodleVarsDB);
                }
                $pluginArr["config_moodle"] = $moodleArray;
            }
        }
        return $pluginArr;

    }

    public function readConfigJson($courseId, $tables, $update=false){
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

    public function init(){

        $this->addTables("moodle", "config_moodle", "ConfigMoodle");
        $this->moodle = new Moodle(API::getValue('course'));

         API::registerFunction('settings', 'courseMoodle', function () {
             API::requireCourseAdminPermission();
             $courseId = API::getValue('course');

             if (API::hasKey('moodle')) {
                 $moodle = API::getValue('moodle');
                 //place to verify input values
                 if ($this->setMoodleVars($courseId, $moodle)) {
                     API::response(["updatedData" => ["Variables for moodle saved"]]);
                 } else {
                     API::error("Please fill the mandatory fields");
                 }
                 return;
             }
             if (API::hasKey('moodlePeriodicity')) {
                 $moodle = API::getValue('moodlePeriodicity');
                 //place to verify input values
                 $response = $this->setCronJob($courseId, $moodle);
                 if ($response["result"]) {
                     API::response(["updatedData" => ["Plugin Moodle enabled"]]);
                 } else {
                     API::error($response["errorMessage"]);
                 }
                 return;
             }

             if (API::hasKey('disableMoodlePeriodicity')) {
                 $moodle = API::getValue('moodlePeriodicity');
                 //place to verify input values
                 $response = $this->removeCronJob( $courseId);
                 if ($response["result"]) {
                     API::response(["updatedData" => ["Plugin Moodle disabled"]]);
                 } else {
                     API::error($response["errorMessage"]);
                 }
                 return;
             }


             $moodleVars = $this->getMoodleVars($courseId);
             API::response(array('moodleVars' => $moodleVars));
         });

    }

    public function dropTables($moduleName)
    {
        $courseId = API::getValue('course');
        new CronJob("Moodle", $courseId, null, null, true);
        parent::dropTables($moduleName);
    }

    public function deleteDataRows($courseId)
    {
        new CronJob("Moodle", $courseId, null, null, true);
        Core::$systemDB->delete("config_moodle", ["course" => $courseId]);
    }


    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function is_configurable(){
        return true;
    }
    public function has_personalized_config (){ return true;}
    public function get_personalized_function(){
        return "moodlePersonalizedConfig";
    }

    public function has_general_inputs (){ return false; }
    public function has_listing_items (){ return  false; }
}

ModuleLoader::registerModule(array(
    'id' => 'moodle',
    'name' => 'Moodle',
    'description' => 'Allows Moodle to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Moodle();
    }
));

?>
