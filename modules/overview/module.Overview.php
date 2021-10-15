<?php
use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use Modules\Views\ViewHandler;

class Overview extends Module {

    const USERS_OVERVIEW_TEMPLATE_NAME = 'Users Overview - by overview';

    public function setupResources() {
        parent::addResources('js/');
    }

    public function init() {
        //page only meant for teachers
        //Core::addNavigation( 'Overview', 'course.overview', true,true);

        $viewsModule = $this->getParent()->getModule('views');
        //$viewHandler = $viewsModule->getViewHandler();
        //$viewHandler->createPageOrTemplateIfNew('Overview',"page","ROLE_SINGLE");

        if (!$viewsModule->templateExists(self::USERS_OVERVIEW_TEMPLATE_NAME))
           $viewsModule->setTemplate(self::USERS_OVERVIEW_TEMPLATE_NAME, file_get_contents(__DIR__ . '/usersOverview.txt'), true);
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
    'id' => 'overview',
    'name' => 'Overview',
    'description' => 'Creates a view template with all the skills done.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Overview();
    }
));
