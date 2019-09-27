<?php
use SmartBoards\API;
use SmartBoards\Course;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

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
        $viewHandler->registerPage($this, 'sideview', 'Side View', array(
            'type' => ViewHandler::VT_ROLE_SINGLE
        ));

        if (!$viewsModule->templateExists(self::SIDE_VIEW_TEMPLATE))
            $viewsModule->setTemplate(self::SIDE_VIEW_TEMPLATE, file_get_contents(__DIR__ . '/sideview.txt'),$this->getId());
  
        
    }
}

ModuleLoader::registerModule(array(
    'id' => 'side-view',
    'name' => 'Side View',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new SideView();
    }
));
?>
