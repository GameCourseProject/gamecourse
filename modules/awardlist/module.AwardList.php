<?php
use SmartBoards\API;
use SmartBoards\Core;
use SmartBoards\Module;
use SmartBoards\ModuleLoader;

use Modules\Views\ViewHandler;

class AwardList extends Module {

    const AWARDS_PROFILE_TEMPLATE = '(old) Awards Profile - by awards';
    const NEW_AWARDS_PROFILE_TEMPLATE = 'Awards Profile - by awards';
    const FULL_AWARDS_TEMPLATE = '(old) Full Award List - by awards';
    const NEW_FULL_AWARDS_TEMPLATE = 'Full Award List - by awards';

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
            $courseId = $course->getId();
            $allAwards = array();
            $awards = Core::$systemDB->selectMultiple("award",'*',["course"=>$courseId]);
            $studentNames = [];
            foreach($awards as $award){
                $id=$award['student'];
                if (!array_key_exists($id, $studentNames))
                    $studentNames[$id]=\SmartBoards\User::getUser($id)->getName();
                $award['Studentname'] = $studentNames[$id];
                $allAwards[] = $award;
            }
            return new \Modules\Views\Expression\ValueNode($allAwards);
        });

        //if ($viewsModule->getTemplate(self::AWARDS_PROFILE_TEMPLATE) == NULL)
        //    $viewsModule->setTemplate(self::AWARDS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/awards_profile.vt'),$this->getId());
        
        if ($viewsModule->getTemplate(self::NEW_AWARDS_PROFILE_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::NEW_AWARDS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/newprofileawards.txt'),$this->getId());
        
        //if ($viewsModule->getTemplate(self::FULL_AWARDS_TEMPLATE) == NULL)
        //    $viewsModule->setTemplate(self::FULL_AWARDS_TEMPLATE, file_get_contents(__DIR__ . '/full_awards.vt'),$this->getId());
        
        if ($viewsModule->getTemplate(self::NEW_FULL_AWARDS_TEMPLATE) == NULL)
            $viewsModule->setTemplate(self::NEW_FULL_AWARDS_TEMPLATE, file_get_contents(__DIR__ . '/newFullAwards.txt'),$this->getId());  
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
