<?php
namespace GameCourse\Badges;

use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Config\InputType;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use Utils\Utils;

/**
 * This is the Badges module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class Badges extends Module
{
    const TABLE_BADGE = Badge::TABLE_BADGE;
    const TABLE_BADGE_LEVEL = 'badge_level';
    const TABLE_BADGE_CONFIG = 'badges_config';
    const TABLE_BADGE_PROGRESSION = 'badge_progression';

    const COURSE_DATA_FOLDER = 'badges';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "Badges";  // NOTE: must match the name of the class
    const NAME = "Badges";
    const DESCRIPTION = "Enables badges to be given to students under certain conditions.";
    const TYPE = ModuleType::GAME_ELEMENT;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = ['assets/', 'styles/'];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();

        // Create folder in course_data
        $folder = $this->course->getDataFolder() . "/" . self::COURSE_DATA_FOLDER;
        if (!file_exists($folder)) mkdir($folder);
        else Utils::deleteDirectory($folder, false);
    }

    public function disable()
    {
        $this->cleanDatabase();

        // Remove folder in course_data
        $folder = $this->course->getDataFolder() . "/" . self::COURSE_DATA_FOLDER;
        Utils::deleteDirectory($folder);
    }

    protected function deleteEntries()
    {
        Core::database()->delete(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()]);
        Core::database()->delete(self::TABLE_BADGE, ["course" => $this->course->getId()]);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function hasGeneralInputs(): bool
    {
        return true;
    }

    public function getGeneralInputs(): array
    {
        return [
            ["id" => "maxReward", "label" => "Max Reward", "type" => InputType::NUMBER, "value" => $this->getMaxBonusReward()],
            ["id" => "extraOverlay", "label" => "Overlay for extra", "type" => InputType::IMAGE, "value" => null],
            ["id" => "braggingOverlay", "label" => "Overlay for bragging", "type" => InputType::IMAGE, "value" => null],
            ["id" => "lvl2Overlay", "label" => "Overlay for level 2", "type" => InputType::IMAGE, "value" => null],
            ["id" => "lvl3Overlay", "label" => "Overlay for level 3", "type" => InputType::IMAGE, "value" => null],
        ];
    }

    public function saveGeneralInputs(array $inputs)
    {
        // TODO
    }

    // TODO


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ---------- Config ---------- ***/

    public function getMaxBonusReward(): int
    {
        // FIXME: should be general in XP & Levels
        return intval(Core::database()->select(self::TABLE_BADGE_CONFIG, ["course" => $this->course->getId()], "maxBonusReward"));
    }


    /*** ---------- Badges ---------- ***/

    // NOTE: use Badge model to access badge methods
}
