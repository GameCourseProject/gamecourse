<?php
namespace Modules\ClassCheck;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;

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
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getClassCheckVars($courseId): array
    {
        $classCheckDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
        $isEmpty = empty($classCheckDB);

        return [
            "tsvCode" => $isEmpty ? "" : $classCheckDB["tsvCode"]
        ];
    }

    private function setClassCheckVars($courseId, $classCheck)
    {
        $arrayToDb = [
            "course" => $courseId,
            "tsvCode" => $classCheck['tsvCode']
        ];

        // Verify connection
        if (!ClassCheck::checkConnection($classCheck["tsvCode"]))
            API::error("ClassCheck connection failed.");

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
        }
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
