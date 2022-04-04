<?php
namespace Modules\Overview;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Overview extends Module
{
    const ID = 'overview';

    const USERS_OVERVIEW_TEMPLATE = 'Users Overview - by overview';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates() // FIXME: refactor templates
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::USERS_OVERVIEW_TEMPLATE))
            Views::createTemplateFromFile(self::USERS_OVERVIEW_TEMPLATE, file_get_contents(__DIR__ . '/usersOverview.txt'), $courseId, self::ID);
    }

    public function setupResources() {
        parent::addResources('css/overview.css');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}
ModuleLoader::registerModule(array(
    'id' => Overview::ID,
    'name' => 'Overview',
    'description' => 'Creates a view template with all the skills done.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new Overview();
    }
));
