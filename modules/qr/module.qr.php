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
        Core::addNavigation('images/qr-code.svg', 'QR', 'course.qr', true,null,true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('QR',"page");
        //ToDo add QR tables to database
    }
}
ModuleLoader::registerModule(array(
    'id' => 'qr',
    'name' => 'QR',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new QR();
    }
));
?>
