<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class Overview extends Module {

    const USERS_OVERVIEW_TEMPLATE_NAME = '(old) Users Overview - by overview';
    const NEW_USERS_OVERVIEW_TEMPLATE_NAME = 'Users Overview - by overview';

    public function setupResources() {
        parent::addResources('js/');
    }

    public function init() {
        $user = Core::getLoggedUser();
        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            Core::addNavigation('images/gear.svg', 'Overview', 'course.overview', true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerView($this, 'overview', 'Overview View', array(
            'type' => ViewHandler::VT_SINGLE
        ));

        //if ($viewsModule->getTemplate(self::USERS_OVERVIEW_TEMPLATE_NAME) == NULL)
        //    $viewsModule->setTemplate(self::USERS_OVERVIEW_TEMPLATE_NAME, file_get_contents(__DIR__ . '/users_overview.vt'),$this->getId());
        if ($viewsModule->getTemplate(self::NEW_USERS_OVERVIEW_TEMPLATE_NAME) == NULL)
            $viewsModule->setTemplate(self::NEW_USERS_OVERVIEW_TEMPLATE_NAME, file_get_contents(__DIR__ . '/newUsersOverview.txt'),$this->getId());  
    }
}
ModuleLoader::registerModule(array(
    'id' => 'overview',
    'name' => 'Overview',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Overview();
    }
));
?>
