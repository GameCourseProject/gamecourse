<?php
namespace Modules\QR;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class QR extends Module
{
    const ID = 'qr';

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->setupData();
        $this->initAPIEndpoints();
    }

    public function initAPIEndpoints()
    {
        API::registerFunction('settings', 'qrError', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');
            $errors = Core::$systemDB->selectMultiple("qr_error q left join game_course_user u on q.user = u.id",
                ["course" => $courseId],
                "date, studentNumber, msg, qrkey",
                "date DESC");
            API::response(["errors" => $errors]);
        });
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    public function setupData(){
        $this->addTables("qr", "qr_code");
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool {
        return true;
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return "qrPersonalizedConfig";
    }

    public function deleteDataRows($courseId){
        
    }
}

ModuleLoader::registerModule(array(
    'id' => 'qr',
    'name' => 'QR',
    'description' => 'Generates a QR code to be used for student participation in class.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new QR();
    }
));
?>
