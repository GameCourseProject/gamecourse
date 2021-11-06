<?php

use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class AwardList extends Module
{

    const AWARDS_PROFILE_TEMPLATE = 'Awards Profile - by awards';
    const FULL_AWARDS_TEMPLATE = 'Full Award List - by awards';

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/awards.css');
        parent::addResources('imgs/');
    }

    public function init()
    {
        $user = Core::getLoggedUser();

        //if (($user != null && $user->isAdmin()) || $this->getParent()->getLoggedUser()->isTeacher())
        //    $viewHandler->createPageOrTemplateIfNew('AwardList', "page", "ROLE_SINGLE");

        // $viewHandler->registerView($this, 'awardlist', 'Award List View', array(
        //     'type' => ViewHandler::VT_SINGLE
        // ));

        $course = $this->getParent();
        /*$viewHandler->registerLibrary("awardlist", "awards", "This library provides information regarding Awards. It is provided by the award module.");
        $viewHandler->registerFunction(
            'awards',
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
            'Returns a collection with all the awards in the Course. The optional parameters can be used to find awards that specify a given combination of conditions:\nuser: id of a GameCourseUser.\ntype: Type of the event that led to the award.\nmoduleInstance: Name of an instance of an object from a Module.\ninitialDate: Start of a time interval in DD/MM/YYYY format.\nfinalDate: End of a time interval in DD/MM/YYYY format.',
            'collection',
            'award',
            'library',
            null
        );*/

        if (!Views::templateExists($this->getCourseId(), self::AWARDS_PROFILE_TEMPLATE))
            Views::createTemplate(self::AWARDS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/profileAwards.txt'), $this->getCourseId(), true);

        if (!Views::templateExists($this->getCourseId(), self::FULL_AWARDS_TEMPLATE))
            Views::createTemplate(self::FULL_AWARDS_TEMPLATE, file_get_contents(__DIR__ . '/fullAwards.txt'), $this->getCourseId(), true);
    }

    public function is_configurable()
    {
        return false;
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}
ModuleLoader::registerModule(array(
    'id' => 'awardlist',
    'name' => 'Award List',
    'description' => 'Enables Awards and creates a view template with list of awards per student.',
    'version' => '0.1',
    'compatibleVersions' => array("1.1", "1.2"),
    'dependencies' => array(),
    'factory' => function () {
        return new AwardList();
    }
));
