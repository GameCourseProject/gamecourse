<?php

namespace GameCourse;

use Modules\Views\Expression\ValueNode;

abstract class Module
{
    private $id;
    private $name;
    private $description;
    private $version;
    private $dependencies;
    private $dir;
    private $parent;
    private $resources = array();

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }
    public function getCourseId()
    {
        return $this->getParent()->getId();
    }

    public function getName()
    {
        return $this->name;
    }
    public function getDescription()
    {
        return $this->description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getDependencies()
    {
        return $this->dependencies;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getData()
    {
        return $this->parent->getModuleData($this->getId());
    }

    public function setupResources()
    {
    }

    public function addResources(...$files)
    {
        $this->resources = array_unique(array_merge($this->resources, \Utils::discoverFiles($this->dir, ...$files)));
    }

    public function getResources()
    {
        return $this->resources;
    }
    public function addTables($moduleName, $tableName, $children = null)
    {
        $table = Core::$systemDB->executeQuery("show tables like '" . $tableName . "';")->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($table)) {
            Core::$systemDB->executeQuery(file_get_contents("modules/" . $moduleName . "/create" . $children . ".sql"));
            return true;
        }
        return false;
    }

    public function addTablesByQuery($tableName, $columns)
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

    public function createQuery($tableName, $columns)
    {
        $query = "create table " . $tableName . "(\nid int unsigned auto_increment primary key,\ncourse int unsigned not null, \n";

        foreach ($columns as $column) {
            $query .= $column . " varchar(200) null, \n";
        }
        $query .= "foreign key(course) references course(id) on delete cascade\n);";
        return $query;
    }
    public function dropTables($moduleName)
    {
        $file = "modules/" . $moduleName . "/delete.sql";
        if (file_exists($file)) {
            Core::$systemDB->executeQuery(file_get_contents($file));
        }
    }
    public static function importModules($fileContents)
    {
        $path = time() . ".zip";
        file_put_contents($path, $fileContents);

        $zip = new \ZipArchive;
        if ($zip->open($path) === TRUE) {
            //mudar depois pra modules
            $zip->extractTo('testeModules');
            $zip->close();
            echo 'ok';
        } else {
            echo 'failed';
        }

        unlink($path); 
    }

    public static function exportModules()
    {
        //verificar se o utilizador dá path e extensão
        $zip = new \ZipArchive();
        if ($zip->open('modules.zip', \ZipArchive::CREATE) == TRUE) {
            $rootPath = realpath("modules");
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
    }
    public function deleteDataRows($courseId)
    {
    }
    public function init()
    {
    }

    public function initSettingsTabs()
    {
    }

    public function processRequest()
    {
    }

    public function cleanModuleData()
    {
    }

    public function __set($key, $value)
    {
        $trace = debug_backtrace();
        if (isset($trace[1]['class']) && $trace[1]['class'] == 'GameCourse\ModuleLoader') {
            return $this->$key = $value;
        }
        trigger_error('Cannot access private property ' . __CLASS__ . '::$' . $key, E_USER_ERROR);
    }

    //functions that are used in the modules in the functions of the expression language

    public function getUserId($user)
    {
        if (is_array($user))
            return $user["value"]["id"];
        else
            return $id = $user;
    }
    //create value node of an object or collection
    public function createNode($value, $lib = null, $type = "object", $parent = null)
    {
        if ($type == "collection") {
            foreach ($value as &$v) {
                if ($parent !== null)
                    $v["parent"] = $parent;
                if (is_array($v) && ($lib !== null || !array_key_exists("libraryOfVariable", $v)))
                    $v["libraryOfVariable"] = $lib;
            }
        } else if (is_array($value) && ($lib !== null || !array_key_exists("libraryOfVariable", $value))) {
            $value["libraryOfVariable"] = $lib;
        }
        return new ValueNode(["type" => $type, "value" => $value]);
    }
    //get award or participations from DB, (moduleInstance can be name or id
    public function getAwardOrParticipation($courseId, $user, $type, $moduleInstance, $initialDate = null, $finalDate = null, $where = [], $object = "award")
    {
        if ($user !== null) {
            $where["user"] = $this->getUserId($user);
        }
        //expected date format DD/MM/YYY needs to be converted to YYYY-MM-DD
        $whereDate = [];
        if ($initialDate !== null) {
            $date = implode("-", array_reverse(explode("/", $initialDate)));
            array_push($whereDate, ["date", ">", $date]);
        }
        if ($finalDate !== null) {
            //tecnically the final date on the results will be the day before the one given
            //because the timestamp is of that day at midnigth
            $date = implode("-", array_reverse(explode("/", $finalDate)));
            array_push($whereDate, ["date", "<", $date]);
        }

        if ($type !== null) {
            $where["type"] = $type;
            //should only use module instance if the type is specified (so we know if we should use skils or badges)
            if ($moduleInstance !== null && ($type == "badge" || $type == "skill")) {
                if (is_numeric($moduleInstance)) {
                    $where["moduleInstance"] = $moduleInstance;
                    $table = $object . " a ";
                    $field = "*";
                } else {
                    $where["name"] = $moduleInstance;
                    $table = $object . " a join " . $type . " m on moduleInstance=m.id";
                    $field = "a.*,m.name";
                }
                $where["a.course"] = $courseId;
                return (Core::$systemDB->selectMultiple($table, $where, $field, null, [], $whereDate));
            }
        }
        $where["course"] = $courseId;
        return Core::$systemDB->selectMultiple($object, $where, "*", null, [], $whereDate);
    }
    //checks if object/collection array is correctly formated, may also check if a parameter belongs to an object
    public function checkArray($array, $type, $functionName, $parameter = null)
    {
        if (!is_array($array) || !array_key_exists("type", $array) || $array["type"] != $type) {
            throw new \Exception("The function '." . $functionName . "' must be called on " . $type);
        }
        if ($parameter !== null) {
            if ($type == "object" && !array_key_exists($parameter, $array["value"])) {
                throw new \Exception("In function '." . $functionName . "': the object does not contain " . $parameter);
            }
        }
    }
    //return valuenode of the field of the object
    public function basicGetterFunction($object, $field)
    {
        $this->checkArray($object, "object", $field, $field);
        return new ValueNode($object["value"][$field]);
    }

    //functions for the module configuration page
    public function is_configurable(){ return false; } //default is false

     
    public function has_personalized_config (){ return false; } //default is false
    public function get_personalized_function(){}
    
    public function has_general_inputs (){ return false; } //default is false
    public function get_general_inputs ($courseId){}
    public function save_general_inputs($generalInputs,$courseId){}

    public function has_listing_items (){ return false; } //default is false
    public function get_listing_items ($courseId){}
    public function save_listing_item ($actiontype, $listingItem, $courseId){}
}
