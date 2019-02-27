<?php
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;
use Modules\Views\ViewHandler;

class Profile extends Module {

    const STUDENT_SUMMARY_TEMPLATE = 'Student Summary - by profile';

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
        $viewHandler->registerView($this, 'profile', 'Profile View', array(
            'type' => ViewHandler::VT_ROLE_INTERACTION
        ));

        if ($viewsModule->getTemplate(self::STUDENT_SUMMARY_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::STUDENT_SUMMARY_TEMPLATE, unserialize(file_get_contents(__DIR__ . '/summary.vt')),$this->getId());
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
