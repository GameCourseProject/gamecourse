<?php
namespace GameCourse\Module\Leaderboard;

use Exception;
use GameCourse\Course\Course;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\XPLevels\XPLevels;

/**
 * This is the Leaderboard module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Leaderboard extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Leaderboard";  // NOTE: must match the name of the class
    const NAME = "Leaderboard";
    const DESCRIPTION = "Provides different leaderboard templates with students' progress on the course.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => Badges::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT]
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];

    const ADAPTATION_LEADERBOARD = [ "Leaderboard" => ["LB001", "LB002"]];

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initTemplates();
        $this->addAdaptationRolesToCourse(self::ADAPTATION_LEADERBOARD);
        $this->addEditableGameElement();
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    /**
     * @throws Exception
     */
    public function disable()
    {
        $this->removeAdaptationRolesFromCourse(self::ADAPTATION_LEADERBOARD);
        $this->removeEditableGameElement();
        $this->removeTemplates();
    }
}
