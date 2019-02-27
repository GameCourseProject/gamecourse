<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class AwardList extends Module {

    const AWARDS_PROFILE_TEMPLATE = 'Awards Profile - by awards';
    const FULL_AWARDS_TEMPLATE = 'Full Award List - by awards';

    public function setupResources() {
        parent::addResources('js/');
    }

    public function init() {
        $user = Core::getLoggedUser();
        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            Core::addNavigation('images/gear.svg', 'Award List', 'course.awardlist', true);

        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();
        $viewHandler->registerView($this, 'awardlist', 'Award List View', array(
            'type' => ViewHandler::VT_SINGLE
        ));

        $course = $this->getParent();
        $viewHandler->registerFunction('getAllAwards', function() use ($course) {
            //$users = \Smartboards\User::getAllInfo();
            $students = $course->getUsersWithRole('Student');
            $allAwards = array();
            foreach ($students as $id => $student) {
                $name = $student->get('name');
                $studentAwards = $course->getUserData($id)->get('awards');
                foreach ($studentAwards as $award) {
                    $award['user'] = array('id' => $id, 'name' => $name, 'username' => \SmartBoards\User::getUser($id)->getUsername());
                    $allAwards[] = \SmartBoards\DataRetrieverContinuation::buildForArray($award);
                }
            }
            return new \Modules\Views\Expression\ValueNode($allAwards);
        });

        if ($viewsModule->getTemplate(self::AWARDS_PROFILE_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::AWARDS_PROFILE_TEMPLATE, unserialize(file_get_contents(__DIR__ . '/awards_profile.vt')),$this->getId());

        if ($viewsModule->getTemplate(self::FULL_AWARDS_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::FULL_AWARDS_TEMPLATE, unserialize(file_get_contents(__DIR__ . '/full_awards.vt')),$this->getId());
    }
}
ModuleLoader::registerModule(array(
    'id' => 'awardlist',
    'name' => 'Award List',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new AwardList();
    }
));
?>
