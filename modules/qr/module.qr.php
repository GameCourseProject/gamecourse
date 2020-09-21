<?php
namespace Modules\QR;

use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use Modules\Views\ViewHandler;

class QR extends Module {
    
    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function init() {
        Core::addNavigation('QR', 'course.qr', true,true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('QR',"page");
        //ToDo add QR tables to database
    }
    public function is_configurable(){
        return false;
    }
}
ModuleLoader::registerModule(array(
    'id' => 'qr',
    'name' => 'QR',
    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis laoreet non nulla at nullam.',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new QR();
    }
));
?>
