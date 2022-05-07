<?php
namespace GameCourse\Skills;

use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;

/**
 * This is the Skills module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Skills extends Module
{
    const TABLE_TREE = 'skill_tree';
    const TABLE_TIER = 'skill_tier';
    const TABLE_SKILL = 'skill';
    const TABLE_SKILL_DEPENDENCY = 'skill_dependency';
    const TABLE_SUPER_SKILLS = 'dependency';
    const TABLE_WILDCARD = 'award_wildcard';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Skills";  // NOTE: must match the name of the class
    const NAME = "Skills";
    const DESCRIPTION = "Generates a skill tree where students have to complete several skills to achieve a higher layer.";
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
