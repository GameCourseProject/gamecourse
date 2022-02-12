<?php
namespace GameCourse;

use GameCourse\Views\Dictionary;
use GameCourse\Views\Views;
use Utils;

abstract class Module
{
    private $id;
    private $name;
    private $description;
    private $type;
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

    public function getType()
    {
        return $this->type;
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
     */
    public function initTemplates()
    {

    }

    /**
     * Registers libraries, functions, variables and/or view types of a
     * module in the Dictionary to make them available on the system.
     * @see Dictionary
     */
    public function initDictionary()
    {

    }

    /**
     * Registers API functions of a module.
     */
    public function initAPIEndpoints()
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
        Utils::deleteDirectory(MODULES_FOLDER . "/" . $moduleId);
    }

    /**
     * Cleans up module data in a given course.
     *
     * @param string $moduleId
     * @param int $courseId
     */
    public function cleanUp(string $moduleId, int $courseId)
    {
        // Delete module templates in course
        $templates = array_map(function ($item) {
            return $item['templateId'];
        }, Core::$systemDB->selectMultiple("template_module", ["moduleId" => $moduleId], "templateId"));
        Core::$systemDB->delete("template_module", ["moduleId" => $moduleId]);

        foreach ($templates as $templateId) {
            Views::deleteTemplate($courseId, $templateId);
        }

        // Drop module tables if not enabled in any course
        if (empty(Core::$systemDB->select("course_module", ["moduleId" => $moduleId])))
            self::dropTables($moduleId);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool
    {
        return false;
    } //default is false

    public function has_general_inputs(): bool
    {
        return false;
    } //default is false

    public function get_general_inputs(int $courseId): array
    {
        return [];
    }

    public function save_general_inputs(array $generalInputs, int $courseId)
    {
    }

    public function has_listing_items(): bool
    {
        return false;
    } //default is false

    public function get_listing_items(int $courseId): array
    {
        return [];
    }

    public function save_listing_item(string $actiontype, array $listingItem, int $courseId)
    {
    }

    public function has_personalized_config(): bool
    {
        return false;
    } //default is false

    public function get_personalized_function(): string
    {
        return "";
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function addTables(string $moduleName, string $tableName, string $children = null): bool
    {
        $table = Core::$systemDB->executeQuery("show tables like '" . $tableName . "';")->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($table)) {
            Core::$systemDB->executeQuery(file_get_contents(MODULES_FOLDER . "/" . $moduleName . "/create" . $children . ".sql"));
            return true;
        }
        return false;
    }

    public function addTablesByQuery(string $tableName, array $columns): bool
    {
        $table = Core::$systemDB->executeQuery("show tables like '" . $tableName . "';")->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($table)) {
            $query = $this->createQuery($tableName, $columns);
            $fileName = MODULES_FOLDER . "/plugin/create" . $tableName . ".sql";
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

    public function dropTables(string $moduleId)
    {
        $file = MODULES_FOLDER . "/" . $moduleId . "/delete.sql";
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

        $toPath = MODULES_FOLDER;
        if ($name != MODULES_FOLDER) {
            $toPath = MODULES_FOLDER . "/" . $name;
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

        $rootPath = realpath(MODULES_FOLDER);
        $zipName = "modules.zip";

        $courses = Core::$systemDB->selectMultiple("course");
        $modules = Core::$systemDB->selectMultiple("module");
        if (!$all) {
            $rootPath = realpath(MODULES_FOLDER . "/" . $name);
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
            $file = MODULES_FOLDER . "/" . $module["moduleId"] . "/config.json";
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
            file_put_contents(MODULES_FOLDER . "/" . $name . "/config.json", json_encode($moduleArr));
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
