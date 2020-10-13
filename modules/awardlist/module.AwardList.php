<?php

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

use Modules\Views\ViewHandler;

class AwardList extends Module
{

    const AWARDS_PROFILE_TEMPLATE = 'Awards Profile - by awards';
    const FULL_AWARDS_TEMPLATE = 'Full Award List - by awards';

    public function setupResources()
    {
        parent::addResources('js/');
    }

    public function init()
    {
        $user = Core::getLoggedUser();
        $viewsModule = $this->getParent()->getModule('views');
        $viewHandler = $viewsModule->getViewHandler();

        if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
            $viewHandler->createPageOrTemplateIfNew('AwardList', "page", "ROLE_SINGLE");

        // $viewHandler->registerView($this, 'awardlist', 'Award List View', array(
        //     'type' => ViewHandler::VT_SINGLE
        // ));

        $course = $this->getParent();
        $viewHandler->registerFunction(
            'awardlist',
            'getAllAwards',
            function () use ($course) {
                $courseId = $course->getId();
                $allAwards = array();
                $awards = Core::$systemDB->selectMultiple("award", ["course" => $courseId]);
                $studentNames = [];
                foreach ($awards as $award) {
                    $id = $award['student'];
                    if (!array_key_exists($id, $studentNames))
                        $studentNames[$id] = \GameCourse\User::getUser($id)->getName();
                    $award['Studentname'] = $studentNames[$id];
                    $allAwards[] = $award;
                }
                return new \Modules\Views\Expression\ValueNode($allAwards);
            },
            'collection',
            'Returns a collection with all the awards in the Course. The optional parameters can be used to find awards that specify a given combination of conditions:\nuser: id of a GameCourseUser.\ntype: Type of the event that led to the award.\nmoduleInstance: Name of an instance of an object from a Module.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.',
            'library'
        );

        if (!$viewsModule->templateExists(self::AWARDS_PROFILE_TEMPLATE))
            $viewsModule->setTemplate(self::AWARDS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/profileAwards.txt'));
            
        if (!$viewsModule->templateExists(self::FULL_AWARDS_TEMPLATE))
            $viewsModule->setTemplate(self::FULL_AWARDS_TEMPLATE, file_get_contents(__DIR__ . '/fullAwards.txt'));  
    }


    public function is_configurable(){
        return true;
    }
}
ModuleLoader::registerModule(array(
    'id' => 'awardlist',
    'name' => 'Award List',
    'description' => 'Enables Awards and creates a view template with list of awards per student.',
    'version' => '0.1',
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new AwardList();
    }
));
