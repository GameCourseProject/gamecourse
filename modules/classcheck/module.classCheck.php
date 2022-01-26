<?php
namespace Modules\ClassCheck;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\CronJob;

class ClassCheckModule extends Module
{
    private $classCheck;


    private function getClassCheckVars($courseId)
    {
        $classCheckDB = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

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
    private function setClassCheckVars($courseId, $classCheck)
    {
        $classCheckVars = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");

        $arrayToDb = ["course" => $courseId, "tsvCode" => $classCheck['tsvCode']];

        if (empty($classCheck["tsvCode"])) {
            return false;
        } else {
            if (empty($classCheckVars)) {
                Core::$systemDB->insert("config_class_check", $arrayToDb);
            } else {
                Core::$systemDB->update("config_class_check", $arrayToDb, ["course" => $courseId] );
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

            $classCheckVars = Core::$systemDB->select("config_class_check", ["course" => $courseId], "*");
            if ($classCheckVars){
                $result = ClassCheck::checkConnection($classCheckVars["tsvCode"]);
                if ($result){
                    new CronJob("ClassCheck", $courseId, $vars['number'], $vars['time']['name']);
                    Core::$systemDB->update("config_class_check", ["isEnabled" => 1, "periodicityNumber" =>$vars['number'], 'periodicityTime' => $vars['time']['name']], ["course" => $courseId]);
                    return array("result" => true);
                } else {
                    return array("result" => false, "errorMessage" => "Connection failed");
                }
            } else {
                return array("result" => false, "errorMessage" => "Please set the class check variables");
            }
        }
    }
    private function removeCronJob($courseId){
        $tableName = "config_class_check";

        if($tableName){
            Core::$systemDB->update($tableName, ["isEnabled" => 0, "periodicityNumber" => 0, 'periodicityTime' => NULL], ["course" => $courseId]);
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
                    $pluginName = (strcmp($name, "config_class_check") !== 0)? "ClassCheck" : null;
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

        if (Core::$systemDB->tableExists("config_class_check")) {
            $classCheckDB_ = Core::$systemDB->selectMultiple("config_class_check", ["course" => $courseId], "*");
            if ($classCheckDB_) {
                $ccArray = array();
                foreach ($classCheckDB_ as $classCheckDB) {
                    unset($classCheckDB["id"]);
                    array_push($ccArray, $classCheckDB);
                }
                $pluginArr["config_class_check"] = $ccArray;
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

        $this->addTables("classcheck", "config_class_check", "ConfigClassCheck");
        $this->classCheck = new ClassCheck(API::getValue('course'));

         API::registerFunction('settings', 'courseClassCheck', function () {
             API::requireCourseAdminPermission();
             $courseId = API::getValue('course');

             if (API::hasKey('classCheckPeriodicity')) {
                 $classCheck = API::getValue('classCheckPeriodicity');
                 //place to verify input values
                 $response = $this->setCronJob( $courseId, $classCheck);
                 if ($response["result"]) {
                     API::response(["updatedData" => ["Plugin Class Check enabled"]]);
                 } else {
                     API::error($response["errorMessage"]);
                 }
                 return;
             }
             if (API::hasKey('disableClassCheckPeriodicity')) {
                 //place to verify input values
                 $response = $this->removeCronJob($courseId);
                 if ($response["result"]) {
                     API::response(["updatedData" => ["Plugin Class Check disabled"]]);
                 } else {
                     API::error([$response["errorMessage"]]);
                 }
                 return;
             }
             if (API::hasKey('classCheck')) {
                 $classCheck = API::getValue('classCheck');
                 //place to verify input values
                 if ($this->setClassCheckVars($courseId, $classCheck)) {
                     API::response(["updatedData" => ["Variables for Class check saved"]]);
                 } else {
                     API::error("Please fill the mandatory fields");
                 }

                 return;
             }

             $classCheckVars = $this->getClassCheckVars($courseId);
             API::response(array('classCheckVars' => $classCheckVars));


         });

    }

    public function dropTables($moduleName)
    {
        $courseId = API::getValue('course');
        new CronJob("ClassCheck", $courseId, null, null, true);
        parent::dropTables($moduleName);
    }

    public function deleteDataRows($courseId)
    {
        new CronJob("ClassCheck", $courseId, null, null, true);
        Core::$systemDB->delete("config_class_check", ["course" => $courseId]);
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
        return "classCheckPersonalizedConfig";
    }

    public function has_general_inputs (){ return false; }
    public function has_listing_items (){ return  false; }
}

ModuleLoader::registerModule(array(
    'id' => 'classcheck',
    'name' => 'ClassCheck',
    'description' => 'Allows ClassCheck to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new ClassCheckModule();
    }
));

?>
