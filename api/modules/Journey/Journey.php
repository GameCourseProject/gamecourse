<?php
namespace GameCourse\Module\Journey;

use GameCourse\Course\Course;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skills;

/**
 * This is the Journey module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Journey extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Journey";  // NOTE: must match the name of the class
    const NAME = "Journey";
    const DESCRIPTION = "Provides a different, sequential, way of completing the Skills from the Skill Tree.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Skills::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initTemplates();
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        $this->removeTemplates();
    }
}
