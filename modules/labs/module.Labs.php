<?php
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Labs extends Module {
    public function setupResources() {}
    public function init() {}
}
ModuleLoader::registerModule(array(
    'id' => 'labs',
    'name' => 'Labs',
    'version' => '0.1',
    'dependencies' => array(),
    'factory' => function() {
        return new Labs();
    }
));
?>
