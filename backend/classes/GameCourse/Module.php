<?php
namespace GameCourse;

use GameCourse\Views\Dictionary;
use Utils;

abstract class Module
{
    private $id;
    private $name;
    private $description;
    private $version;
    private $compatibleVersions;
    private $dependencies;
    private $dir;
    private $parent;
    private $resources = array();
    private $configJson;


    public function __construct()
    {
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------- Getters ------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCompatibleVersions(): array
    {
        return $this->compatibleVersions;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getParent(): Course
    {
        return $this->parent;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function getConfigJson()
    {
        return $this->configJson;
    }

    public function getCourseId()
    {
        return $this->getParent()->getId();
    }

    public function getData()
    {
        return $this->getParent()->getModuleData($this->getId());
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------- Setters ------------------- ***/
    /*** ----------------------------------------------- ***/

    public function __set($key, $value)
    {
        $trace = debug_backtrace();
        if (isset($trace[1]['class']) && $trace[1]['class'] == 'GameCourse\ModuleLoader') {
            return $this->$key = $value;
        }
        trigger_error('Cannot access private property ' . __CLASS__ . '::$' . $key, E_USER_ERROR);
    }

    public function setParent(Course $course)
    {
        $this->parent = $course;
    }

    public function setConfigJson($config)
    {
        $this->configJson = $config;
    }

    public function setupResources()
    {
    }

    public function addResources(...$files)
    {
        $this->resources = array_unique(array_merge($this->resources, Utils::discoverFiles($this->dir, ...$files)));
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------- General ------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
    }

    /**
     * Creates default templates of module.
     *
     * @return void
     */
    public function initTemplates()
    {

    }

    /**
     * Registers libraries, functions, variables and/or view types of a
     * module in the Dictionary to make them available on the system.
     * @see Dictionary
     *
     * @return void
     */
    public function initDictionary()
    {

    }

    public function initSettingsTabs() // FIXME: prob can delete; after refactor not being used
    {
    }

    public function cleanModuleData()
    {
    }

    public static function deleteModule(string $moduleId)
    {
        $module = ModuleLoader::getModule($moduleId);
        $moduleObj = $module["factory"]();
        //disable desse módulo em todos os cursos
        Core::$systemDB->update("course_module", ["isEnabled" => 0], ["moduleId" => $moduleId]);
        //drop das tables relativas a esse module
        $moduleObj->dropTables($moduleId);
        //apagar o module da BD
        Core::$systemDB->delete("module", ["moduleId" => $moduleId]);
        //apagar a pasta do module
        Utils::deleteDirectory("modules/" . $moduleId);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool
    {
        return false;
    } //default is false

    public function has_personalized_config(): bool
    {
        return false;
    } //default is false

    public function get_personalized_function()
    {
    }

    public function has_general_inputs(): bool
    {
        return false;
    } //default is false

    public function get_general_inputs(int $courseId)
    {
    }

    public function save_general_inputs(array $generalInputs, int $courseId)
    {
    }

    public function has_listing_items(): bool
    {
        return false;
    } //default is false

    public function get_listing_items(int $courseId)
    {
    }

    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function addTables(string $moduleName, string $tableName, string $children = null): bool
    {
        $table = Core::$systemDB->executeQuery("show tables like '" . $tableName . "';")->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($table)) {
            Core::$systemDB->executeQuery(file_get_contents("modules/" . $moduleName . "/create" . $children . ".sql"));
            return true;
        }
        return false;
    }

    public function addTablesByQuery(string $tableName, array $columns): bool
    {
        $table = Core::$systemDB->executeQuery("show tables like '" . $tableName . "';")->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($table)) {
            $query = $this->createQuery($tableName, $columns);
            $fileName = "modules/plugin/create" . $tableName . ".sql";
            file_put_contents($fileName, $query);
            Core::$systemDB->executeQuery(file_get_contents($fileName));
        }
        return false;
    }

    public function createQuery(string $tableName, array $columns): string
    {
        $query = "create table " . $tableName . "(\nid int unsigned auto_increment primary key,\ncourse int unsigned not null, \n";

        foreach ($columns as $column) {
            $query .= $column . " varchar(200) null, \n";
        }
        $query .= "foreign key(course) references course(id) on delete cascade\n);";
        return $query;
    }

    public function deleteDataRows(int $courseId)
    {
    }

    public function dropTables(string $moduleName)
    {
        $file = "modules/" . $moduleName . "/delete.sql";
        if (file_exists($file)) {
            Core::$systemDB->executeQuery(file_get_contents($file));
        }
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Import / Export --------------- ***/
    /*** ----------------------------------------------- ***/

    public static function importModules(string $fileContents, string $fileName)
    {
        $name = substr($fileName, 0, strlen($fileName) - 4);
        $path = time() . ".zip";
        file_put_contents($path, $fileContents);

        $toPath = "modules";
        if ($name != "modules") {
            $toPath = "modules/" . $name;
            if (is_dir($toPath)) {
                Utils::deleteDirectory($toPath);
            }
            mkdir($toPath, 0777, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($path) === TRUE) {
            //mudar depois pra modules
            $zip->extractTo($toPath . "/");
            $zip->close();
        }
        unlink($path);
    }

    public static function exportModules(bool $all = false): string
    {
        $name = "badges";
        $zip = new \ZipArchive();

        $rootPath = realpath("modules");
        $zipName = "modules.zip";

        $courses = Core::$systemDB->selectMultiple("course");
        $modules = Core::$systemDB->selectMultiple("module");
        if (!$all) {
            $rootPath = realpath("modules/" . $name);
            $zipName = $name . ".zip";
            Module::exportModuleConfig($name, $courses);
        } else {
            foreach ($modules as $module) {
                Module::exportModuleConfig($module["moduleId"], $courses);
            }
        }

        if ($zip->open($zipName, \ZipArchive::CREATE) == TRUE) {

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($rootPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        }

        //remove config files
        foreach ($modules as $module) {
            $file = "modules/" . $module["moduleId"] . "/config.json";
            if (file_exists($file)) {
                unlink($file);
            }
        }
        return $zipName;
    }

    public static function exportModuleConfig(string $name, array $courses)
    {
        $moduleArr = array();
        $module = ModuleLoader::getModule($name);
        $handler = $module["factory"]();
        foreach ($courses as $course) {
            if ($handler->is_configurable() && ($name != "awardlist")) {
                $moduleArray = $handler->moduleConfigJson($course["id"]);
                if ($moduleArray) {
                    if (array_key_exists($name, $moduleArr)) {
                        array_push($moduleArr[$course["id"]], $moduleArray);
                    } else {
                        $moduleArr[$course["id"]] = $moduleArray;
                    }
                }
            }
        }
        if ($moduleArr) {
            file_put_contents("modules/" . $name . "/config.json", json_encode($moduleArr));
        }
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    // NOTE: used in functions of the expression language inside modules
    public function getUserId($user)
    {
        if (is_array($user))
            return $user["value"]["id"];
        else
            return $id = $user;
    }
}
