<?php
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

class Quiz extends Module {
    public function setupResources() {}
    public function init() {}
}
ModuleLoader::registerModule(array(
    'id' => 'quiz',
    'name' => 'Quiz',
    'version' => '0.1',
    'dependencies' => array(),
    'factory' => function() {
        return new Quiz();
    }
));
?>
