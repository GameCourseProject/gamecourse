<?php
use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Settings;
use Modules\Views\ViewHandler;

class Leaderboard extends Module {
    const LEADERBOARD_TEMPLATE_NAME = 'Leaderboard - by leaderboard';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/leaderboard.css');
    }

    public function init() {
        Core::addNavigation( 'Leaderboard', 'course.leaderboard', true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('Leaderboard',"page","ROLE_SINGLE");
        
        if (!$viewsModule->templateExists(self::LEADERBOARD_TEMPLATE_NAME)) {
            $viewsModule->setTemplate(self::LEADERBOARD_TEMPLATE_NAME, file_get_contents(__DIR__ . '/leaderboard.txt'));
        }
    }

    public function initSettingsTabs() {}
    
    public function is_configurable(){
        return false;
    }
}

ModuleLoader::registerModule(array(
    'id' => 'leaderboard',
    'name' => 'Leaderboard',
    'description' => 'Creates a vew template with a leaderboard of the students progress on the course.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Leaderboard();
    }
));
?>
