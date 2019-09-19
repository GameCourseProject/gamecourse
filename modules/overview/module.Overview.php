<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class Overview extends Module {

    const USERS_OVERVIEW_TEMPLATE_NAME = 'Users Overview - by overview';

    public function setupResources() {
        parent::addResources('js/');
    }

    public function init() {
        //page only meant for teachers
        Core::addNavigation('images/gear.svg', 'Overview', 'course.overview', true,null,true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('Overview',"page",ViewHandler::VT_ROLE_SINGLE);

        //if ($viewsModule->getTemplate(self::USERS_OVERVIEW_TEMPLATE_NAME) == NULL)
        //    $viewsModule->setTemplate(self::USERS_OVERVIEW_TEMPLATE_NAME, file_get_contents(__DIR__ . '/usersOverview.txt'),$this->getId());
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
