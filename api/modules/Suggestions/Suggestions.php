<?php
namespace GameCourse\Module\Suggestions;

use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Course\Course;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Profiling\Profiling;

/**
 * This is the Suggestions module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Suggestions extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Suggestions";  // NOTE: must match the name of the class
    const NAME = "Suggestions";
    const DESCRIPTION = "Gives students suggestions based on their profile, through notifications and a page.";
    const TYPE = ModuleType::UTILITY;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => Profiling::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        // Nothing to do here
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        // Nothing to do here
    }
}