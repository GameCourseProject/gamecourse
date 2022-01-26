<?php
namespace Modules\Profile;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Profile extends Module
{
    const STUDENT_SUMMARY_TEMPLATE = 'Student Summary - by profile';
    const STUDENT_AWARD_LIST_TEMPLATE = 'User Awards - by profile';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::STUDENT_SUMMARY_TEMPLATE))
            Views::createTemplateFromFile(self::STUDENT_SUMMARY_TEMPLATE, file_get_contents(__DIR__ . '/profileSummary.txt'), $courseId);

        if (!Views::templateExists($courseId, self::STUDENT_AWARD_LIST_TEMPLATE))
            Views::createTemplateFromFile(self::STUDENT_AWARD_LIST_TEMPLATE, file_get_contents(__DIR__ . '/userAwards.txt'), $courseId);
    }

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/profile.css');
        parent::addResources('imgs');
    }
    
    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'profile',
    'name' => 'Profile',
    'description' => 'Creates a view template for a profile page where all the stats of the user are shown.',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'charts', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Profile();
    }
));
