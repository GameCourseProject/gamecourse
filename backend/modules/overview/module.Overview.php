<?php

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Overview extends Module {

    const USERS_OVERVIEW_TEMPLATE_NAME = 'Users Overview - by overview';

    public function setupResources() {
        parent::addResources('js/');
    }

    public function init() {
        //page only meant for teachers
        //Core::addNavigation( 'Overview', 'course.overview', true,true);

        //$viewHandler = $viewsModule->getViewHandler();
        //$viewHandler->createPageOrTemplateIfNew('Overview',"page","ROLE_SINGLE");

        if (!Views::templateExists($this->getCourseId(), self::USERS_OVERVIEW_TEMPLATE_NAME))
            Views::createTemplateFromFile(self::USERS_OVERVIEW_TEMPLATE_NAME, file_get_contents(__DIR__ . '/usersOverview.txt'), $this->getCourseId());
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
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new Overview();
    }
));
