<?php
namespace Modules\QR;

use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class QR extends Module {
    
    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function init() {
        $user = Core::getLoggedUser();
        //TODO talvez usar uma imagem original 
        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            Core::addNavigation('images/qr-code.svg', 'QR', 'course.qr', true);

        $viewHandler = $this->getParent()->getModule('views')->getViewHandler();
        $viewHandler->registerView($this, 'qr', 'QR View', array(
            'type' => ViewHandler::VT_SINGLE
        ));
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
