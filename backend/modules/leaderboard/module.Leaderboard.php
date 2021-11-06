<?php

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Leaderboard extends Module {
    const LEADERBOARD_TEMPLATE_NAME = 'Leaderboard - by leaderboard';
    const RELATIVE_LEADERBOARD_TEMPLATE_NAME = 'Relative Leaderboard - by leaderboard';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/leaderboard.css');
        parent::addResources('imgs/');
    }

    public function init() {
        if (!Views::templateExists($this->getCourseId(), self::LEADERBOARD_TEMPLATE_NAME)) {
            Views::createTemplateFromFile(self::LEADERBOARD_TEMPLATE_NAME, file_get_contents(__DIR__ . '/leaderboard.txt'), $this->getCourseId());
        }
        if (!Views::templateExists($this->getCourseId(), self::RELATIVE_LEADERBOARD_TEMPLATE_NAME)) {
            Views::createTemplateFromFile(self::RELATIVE_LEADERBOARD_TEMPLATE_NAME, file_get_contents(__DIR__ . '/relativeLeaderboard.txt'), $this->getCourseId());
        }
    }

    public function initSettingsTabs() {}
    
    public function is_configurable(){
        return false;
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'leaderboard',
    'name' => 'Leaderboard',
    'description' => 'Creates a vew template with a leaderboard of the students progress on the course.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'charts', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Leaderboard();
    }
));
