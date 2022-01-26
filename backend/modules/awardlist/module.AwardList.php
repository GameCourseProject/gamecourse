<?php
namespace Modules\AwardList;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class AwardList extends Module
{
    const AWARDS_PROFILE_TEMPLATE = 'Awards Profile - by awards';
    const FULL_AWARDS_TEMPLATE = 'Full Award List - by awards';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::AWARDS_PROFILE_TEMPLATE))
            Views::createTemplateFromFile(self::AWARDS_PROFILE_TEMPLATE, file_get_contents(__DIR__ . '/profileAwards.txt'), $courseId);

        if (!Views::templateExists($courseId, self::FULL_AWARDS_TEMPLATE))
            Views::createTemplateFromFile(self::FULL_AWARDS_TEMPLATE, file_get_contents(__DIR__ . '/fullAwards.txt'), $courseId);
    }

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/awards.css');
        parent::addResources('imgs/');
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
