<?php
namespace GameCourse\Module\Profile;

use Exception;
use GameCourse\Adaptation\GameElement;
use GameCourse\Course\Course;
use GameCourse\Module\Awards\Awards;
use GameCourse\Module\Badges\Badges;
use GameCourse\Module\DependencyMode;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\Module\Skills\Skills;
use GameCourse\Module\Streaks\Streaks;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Module\XPLevels\XPLevels;

/**
 * This is the Profile module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Profile extends Module
{
    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Profile";  // NOTE: must match the name of the class
    const NAME = "Profile";
    const DESCRIPTION = "Provides templates for a profile page where user statistics are shown.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [
        ["id" => XPLevels::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::HARD],
        ["id" => VirtualCurrency::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Badges::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Skills::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
        ["id" => Streaks::ID, "minVersion" => "2.2.0", "maxVersion" => null, "mode" => DependencyMode::SOFT],
    ];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];

    const ADAPTATION_PROFILE = ["Profile" =>
        ["P001" => "Profile displays graphs comparing yourself vs. everyone else",
         "P002" => "Profile displays graphs comparing yourself vs. people with similar progress as you",
         "P003" => "Profile displays graphs with your progress (not comparing with anyone else)"]];

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->initTemplates();

        // Add adaptation roles
        $this->addAdaptationRolesToCourse(self::ADAPTATION_PROFILE);
        // initEvents(); // FIXME: Debug only
        GameElement::addGameElement($this->course->getId(), self::ID);
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
        $this->removeAdaptationRolesFromCourse(self::ADAPTATION_PROFILE);
        GameElement::removeGameElement($this->course->getId(), self::ID);
        $this->removeTemplates();
    }
}
