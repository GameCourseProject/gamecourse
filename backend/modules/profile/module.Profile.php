<?php
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use Modules\Views\ViewHandler;

class Profile extends Module {

    const STUDENT_SUMMARY_TEMPLATE = 'Student Summary - by profile';
    const STUDENT_AWARD_LIST = 'User Awards - by profile';

    public function setupResources() {
        parent::addResources('js/');
        parent::addResources('css/profile.css');
    }

    public function init() {
        $user = $this->getParent()->getLoggedUser();
        //if ($user->exists())
            //Core::addNavigation('photos/' . Core::getLoggedUser()->getUsername() . '.png', 'My Profile', 'course.myprofile', true);
            //Core::addNavigation('My Profile', 'course.myprofile', true);

        $viewsModule = $this->getParent()->getModule('views');
        //$viewHandler = $viewsModule->getViewHandler();
        //$viewHandler->createPageOrTemplateIfNew('Profile',"page","ROLE_INTERACTION");

        if (!$viewsModule->templateExists(self::STUDENT_SUMMARY_TEMPLATE))
            $viewsModule->setTemplate(self::STUDENT_SUMMARY_TEMPLATE, file_get_contents(__DIR__ . '/profileSummary.txt'), true);
        if (!$viewsModule->templateExists(self::STUDENT_AWARD_LIST))
            $viewsModule->setTemplate(self::STUDENT_AWARD_LIST, file_get_contents(__DIR__ . '/userAwards.txt'), true);
       
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
        array('id' => 'views', 'mode' => 'hard'),
        array('id' => 'charts', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Profile();
    }
));
