<?php
namespace Modules\Moodle;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;

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
        Core::$systemDB->delete(self::TABLE_CONFIG, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    private function getMoodleVars($courseId): array
    {
        $moodleVarsDB = Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*");
        $isEmpty = empty($moodleVarsDB);

        return [
            "dbServer" => $isEmpty ? "db.rnl.tecnico.ulisboa.pt" : $moodleVarsDB["dbServer"],
            "dbUser" => $isEmpty ? "pcm_moodle" : $moodleVarsDB["dbUser"],
            "dbPass" => $isEmpty ? "" : $moodleVarsDB["dbPass"],
            "dbName" => $isEmpty ? "pcm_moodle" : $moodleVarsDB["dbName"],
            "dbPort" => $isEmpty ? "3306" : $moodleVarsDB["dbPort"],
            "tablesPrefix" => $isEmpty ? "mdl_" : $moodleVarsDB["tablesPrefix"],
            "moodleTime" => $isEmpty ? "0" : $moodleVarsDB["moodleTime"],
            "moodleCourse" => $isEmpty ? "" : $moodleVarsDB["moodleCourse"],
            "moodleUser" => $isEmpty ? "" : $moodleVarsDB["moodleUser"]
        ];
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
            "moodleUser" => $moodle["moodleUser"]
        ];

        // Verify connection to Moodle database
        if (!Moodle::checkConnection($moodle["dbServer"], $moodle["dbUser"], $moodle["dbPass"], $moodle["dbName"], $moodle["dbPort"]))
            API::error("Moodle connection failed.");

        if (empty(Core::$systemDB->select(self::TABLE_CONFIG, ["course" => $courseId], "*"))) {
            Core::$systemDB->insert(self::TABLE_CONFIG, $arrayToDb);
        } else {
            Core::$systemDB->update(self::TABLE_CONFIG, $arrayToDb, ["course" => $courseId]);
        }
    }
}

ModuleLoader::registerModule(array(
    'id' => MoodleModule::ID,
    'name' => 'Moodle',
    'description' => 'Allows Moodle to be automaticaly included on GameCourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new MoodleModule();
    }
));
