<?php

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class SideView extends Module {

    const SIDE_VIEW_TEMPLATE = 'Side View - by sideview';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/side-view.css');
    }

    public function init() {
        if (!Views::templateExists($this->getCourseId(), self::SIDE_VIEW_TEMPLATE)) {
            Views::createTemplateFromFile(self::SIDE_VIEW_TEMPLATE, file_get_contents(__DIR__ . '/sideview.txt'), $this->getCourseId());
        }
        
    }
    public function is_configurable(){
        return false;
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'side-view',
    'name' => 'Side View',
    'description' => 'Creates a view template with a side view with information of the userlogged in.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new SideView();
    }
));
