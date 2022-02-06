<?php
namespace Modules\SideView;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class SideView extends Module
{
    const ID = 'sideview';

    const SIDE_VIEW_TEMPLATE = 'Side View - by sideview';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::SIDE_VIEW_TEMPLATE))
            Views::createTemplateFromFile(self::SIDE_VIEW_TEMPLATE, file_get_contents(__DIR__ . '/sideview.txt'), $courseId, self::ID);
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/side-view.css');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'side-view',
    'name' => 'Side View',
    'description' => 'Creates a view template with a side view with information of the userlogged in.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new SideView();
    }
));
