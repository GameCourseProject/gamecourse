<?php
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Bonus extends Module {
    public function setupResources() {}
    public function init() {}
}
ModuleLoader::registerModule(array(
    'id' => 'bonus',
    'name' => 'Bonus',
    'version' => '0.1',
    'dependencies' => array(),
    'factory' => function() {
        return new Bonus();
    }
));
?>
