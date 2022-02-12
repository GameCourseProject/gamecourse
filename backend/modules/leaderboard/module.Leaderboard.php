<?php
namespace Modules\Leaderboard;

use GameCourse\Module;
use GameCourse\ModuleLoader;
use GameCourse\Views\Views;

class Leaderboard extends Module
{
    const ID = 'leaderboard';

    const LEADERBOARD_TEMPLATE = 'Leaderboard - by leaderboard';
    const RELATIVE_LEADERBOARD_TEMPLATE = 'Relative Leaderboard - by leaderboard';


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->initTemplates();
    }

    public function initTemplates()
    {
        $courseId = $this->getCourseId();

        if (!Views::templateExists($courseId, self::LEADERBOARD_TEMPLATE))
            Views::createTemplateFromFile(self::LEADERBOARD_TEMPLATE, file_get_contents(__DIR__ . '/leaderboard.txt'), $courseId, self::ID);

        if (!Views::templateExists($courseId, self::RELATIVE_LEADERBOARD_TEMPLATE))
            Views::createTemplateFromFile(self::RELATIVE_LEADERBOARD_TEMPLATE, file_get_contents(__DIR__ . '/relativeLeaderboard.txt'), $courseId, self::ID);
    }

    public function setupResources() {
        parent::addResources('css/leaderboard.css');
        parent::addResources('imgs/');
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }
}

ModuleLoader::registerModule(array(
    'id' => 'leaderboard',
    'name' => 'Leaderboard',
    'description' => 'Creates a vew template with a leaderboard of the students progress on the course.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'charts', 'mode' => 'hard')
    ),
    'factory' => function() {
        return new Leaderboard();
    }
));
