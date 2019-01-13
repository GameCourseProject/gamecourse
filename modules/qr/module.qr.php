<?php
namespace Modules\QR;

use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\DataSchema;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class QR extends Module {

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
        //parent::addResources('generator.php');
    }

    public function init() {
        DataSchema::register(array(
            DataSchema::courseModuleDataFields($this, array(
                DataSchema::makeMap('errors', null, DataSchema::makeField('errorId', 'Error Id', '1'),
                    DataSchema::makeObject('error', 'Error', array(
                        DataSchema::makeField('studentId', 'Student Id', '12345'),
                        DataSchema::makeField('ip', 'IP', '127.0.0.1.'),
                        DataSchema::makeField('qrCode', 'QR Code', '20181129121614;...'),
                        DataSchema::makeField('dateTime', 'Date and Time', '2018-11-29 12:40:10'),
                        DataSchema::makeField('msg', 'Error message', 'ERROR:  duplicate key...')
                    ))
                ),
                DataSchema::makeMap('participations', null, DataSchema::makeField('qrKey', 'QR Key', '20181129121614;...'),
                    DataSchema::makeObject('participation', 'Participation in Class', array(
                        DataSchema::makeField('studentId', 'Student Id', '12345'),
                        DataSchema::makeField('classNum', 'Class Number', '1'),
                        DataSchema::makeField('classType', 'Class Type', 'Lecture')
                    ))
                ),
                DataSchema::makeArray('qrCodes', 'QR Codes',
                            DataSchema::makeField('qrKey', 'Key of the QR Code', '20181129121614;...')
                )
            ))
        ));
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
