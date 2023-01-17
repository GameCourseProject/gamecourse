<?php
namespace GameCourse\Module\Overview;

use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;

/**
 * This is the Overview module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Overview extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Overview";  // NOTE: must match the name of the class
    const NAME = "Overview";
    const DESCRIPTION = "Provides different overview templates to check how students are doing in the course.";
    const TYPE = ModuleType::UTILITY;

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
