<?php

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Profile extends Module {

    const STUDENT_SUMMARY_TEMPLATE = 'Student Summary - by profile';
    const STUDENT_AWARD_LIST = 'User Awards - by profile';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/profile.css');
        parent::addResources('imgs');
    }

    public function init() {
        $user = $this->getParent()->getLoggedUser();

        if (!Views::templateExists(self::STUDENT_SUMMARY_TEMPLATE, $this->getCourseId()))
            Views::createTemplateFromFile(self::STUDENT_SUMMARY_TEMPLATE, file_get_contents(__DIR__ . '/profileSummary.txt'), $this->getCourseId());

        if (!Views::templateExists(self::STUDENT_AWARD_LIST, $this->getCourseId()))
            Views::createTemplateFromFile(self::STUDENT_AWARD_LIST, file_get_contents(__DIR__ . '/userAwards.txt'), $this->getCourseId());
       
    }

    public function initSettingsTabs() {
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
