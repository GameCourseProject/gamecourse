<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Course;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;
use SmartBoards\Settings;
use Modules\Views\ViewHandler;

class Leaderboard extends Module {
    const LEADERBOARD_TEMPLATE_NAME = 'Leaderboard - by leaderboard';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/leaderboard.css');
    }

    public function init() {
        Core::addNavigation('images/leaderboard.svg', 'Leaderboard', 'course.leaderboard', true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerView($this, 'leaderboardview', 'Leaderboard View', array(
            'type' => ViewHandler::VT_ROLE_SINGLE
        ));

        if ($viewsModule->getTemplate(self::LEADERBOARD_TEMPLATE_NAME) == NULL) {
            $viewsModule->setTemplate(self::LEADERBOARD_TEMPLATE_NAME, unserialize(file_get_contents(__DIR__ . '/leaderboard.vt')),$this->getId());
        }
    }

    public function initSettingsTabs() {
    }
}

ModuleLoader::registerModule(array(
    'id' => 'leaderboard',
    'name' => 'Leaderboard',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Leaderboard();
    }
));
?>
