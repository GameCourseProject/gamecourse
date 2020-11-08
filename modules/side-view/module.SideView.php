<?php
use GameCourse\API;
use GameCourse\Course;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use Modules\Views\ViewHandler;

class SideView extends Module {

    const SIDE_VIEW_TEMPLATE = 'Side View - by sideview';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/side-view.css');
    }

    public function init() {
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('Side View',"page","ROLE_SINGLE");

        // $viewHandler->registerPage($this, 'sideview', 'Side View', array(
        //     'type' => ViewHandler::VT_ROLE_SINGLE
        // ));

        if (!$viewsModule->templateExists(self::SIDE_VIEW_TEMPLATE))
            $viewsModule->setTemplate(self::SIDE_VIEW_TEMPLATE, file_get_contents(__DIR__ . '/sideview.txt'));
  
        
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
    'id' => 'side-view',
    'name' => 'Side View',
    'description' => 'Creates a view template with a side view with information of the userlogged in.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new SideView();
    }
));
?>
