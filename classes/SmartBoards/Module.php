<?php
namespace SmartBoards;

abstract class Module {
    public function __construct() {
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getDependencies() {
        return $this->dependencies;
    }

    public function getDir() {
        return $this->dir;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getData() {
        return $this->parent->getModuleData($this->getId());
    }

    public function setupResources() {
    }

    public function addResources(...$files) {
        $this->resources = array_unique(array_merge($this->resources, \Utils::discoverFiles($this->dir, ...$files)));
    }

    public function getResources() {
        return $this->resources;
    }

    public function init() {
    }

    public function initSettingsTabs() {
    }

    public function processRequest() {
    }

    public function cleanModuleData() {
    }

    public function __set($key, $value)  {
        $trace = debug_backtrace();
        if(isset($trace[1]['class']) && $trace[1]['class'] == 'SmartBoards\ModuleLoader') {
            return $this->$key = $value;
        }
        trigger_error('Cannot access private property ' . __CLASS__ . '::$' . $key, E_USER_ERROR);
    }

    private $id;
    private $name;
    private $version;
    private $dependencies;
    private $dir;
    private $parent;
    private $resources = array();
}
?>
