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
        if ($user->exists())
            Core::addNavigation('photos/' . Core::getLoggedUser()->getUsername() . '.png', 'My Profile', 'course.myprofile', true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->createPageOrTemplateIfNew('Profile',"page","ROLE_INTERACTION");

        if (!$viewsModule->templateExists(self::STUDENT_SUMMARY_TEMPLATE))
            $viewsModule->setTemplate(self::STUDENT_SUMMARY_TEMPLATE, file_get_contents(__DIR__ . '/profileSummary.txt'));
        if (!$viewsModule->templateExists(self::STUDENT_AWARD_LIST))
            $viewsModule->setTemplate(self::STUDENT_AWARD_LIST, file_get_contents(__DIR__ . '/userAwards.txt'));
       
    }

    public function initSettingsTabs() {
    }
}

ModuleLoader::registerModule(array(
    'id' => 'profile',
    'name' => 'Profile',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Profile();
    }
));
?>
