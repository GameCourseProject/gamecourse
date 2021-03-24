<?php
namespace Modules\QR;

use GameCourse\API;
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
        $this->addTables("qr", "qr_code");

        API::registerFunction('settings', 'qrError', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $errors = Core::$systemDB->selectMultiple("qr_error", 
            ["course" => $courseId], 
            "date, studentNumber, msg, qrkey", 
            "date");
            API::response(["errors" => $errors]);
        });
    }
    public function is_configurable(){
        return true;
    }
    public function has_personalized_config (){ return true;}
    public function get_personalized_function(){
        return "qrPersonalizedConfig";
    }
    
    public function has_general_inputs (){ return false; }
    public function has_listing_items (){ return  false; }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function deleteDataRows($courseId)
    {
        Core::$systemDB->delete("badge", ["course" => $courseId]);
    }

    public function dropTables($moduleName)
    {
        parent::dropTables($moduleName);
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
