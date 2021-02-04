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
        //Core::addNavigation('QR', 'course.qr', true,true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('QR',"page");
        //ToDo add QR tables to database
        $this->addTables("qr", "qr_code");
        $this->addTables("qr", "qr_error");
        $this->addTables("qr", "config_qr");
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
    'id' => 'qr',
    'name' => 'QR',
    'description' => 'Generates a QR code to be used for student participation in class.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new QR();
    }
));
?>
