<?php
namespace Modules\Overview;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Overview extends Module
{
    const USERS_OVERVIEW_TEMPLATE = 'Users Overview - by overview';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::USERS_OVERVIEW_TEMPLATE))
            Views::createTemplateFromFile(self::USERS_OVERVIEW_TEMPLATE, file_get_contents(__DIR__ . '/usersOverview.txt'), $courseId);
    }

    public function setupResources() {
        parent::addResources('js/');
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
