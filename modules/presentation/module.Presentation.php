<?php
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Presentation extends Module {
    public function setupResources() {}
    public function init() {}
}
ModuleLoader::registerModule(array(
    'id' => 'presentation',
    'name' => 'Presentation',
    'version' => '0.1',
    'dependencies' => array(),
    'factory' => function() {
        return new Presentation();
    }
));
?>
