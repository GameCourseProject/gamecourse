<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class Test extends Module {

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function init() {
        $user = Core::getLoggedUser();
        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            Core::addNavigation('images/gear.svg', 'Test', 'course.test', true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->registerView($this, 'test', 'Test View', array(
            'type' => ViewHandler::VT_SINGLE
        ));

    }
}
ModuleLoader::registerModule(array(
    'id' => 'test',
    'name' => 'Test',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Test();
    }
));
?>
