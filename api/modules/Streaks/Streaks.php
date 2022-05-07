<?php
namespace GameCourse\Streaks;

use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;

/**
 * This is the Streaks module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Streaks extends Module
{
    const TABLE_STREAK = 'streak';
    const TABLE_STREAK_CONFIG = 'streak_config';
    const TABLE_STREAK_PROGRESSION = 'streak_progression';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Streaks";  // NOTE: must match the name of the class
    const NAME = "Streaks";
    const DESCRIPTION = "Enables Streaks and xp points that can be atributed to a student in certain conditionsS.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        // TODO
    }

    public function disable()
    {
        // TODO
    }
}
