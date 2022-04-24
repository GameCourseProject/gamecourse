<?php
namespace GameCourse\XPLevels;

use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;

/**
 * This is the XP & Levels module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 * All logic related to xp and levels should be put in this file only.
 */
class XPLevels extends Module
{
    const TABLE_LEVEL = 'level';
    const TABLE_XP = 'user_xp';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "XPLevels";  // NOTE: must match the name of the class
    const NAME = "XP & Levels";
    const DESCRIPTION = "Enables user vocabulary to use the terms xp and points to use around the course.";
    const TYPE = "GameElement";

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
        $this->initDatabase();

        // Create level zero
        $level0Id = Core::database()->insert(self::TABLE_LEVEL, [
            "course" => $this->course->getId(),
            "number" => 0,
            "goal" => 0,
            "description" => "AWOL"
        ]);

        // Init XP for all students
        $students = $this->course->getStudents(true);
        foreach ($students as $student) {
            Core::database()->insert(self::TABLE_XP, [
                "course" => $this->course->getId(),
                "user" => $student["id"],
                "xp" => 0,
                "level" => $level0Id
            ]);
        }
    }

    public function disable()
    {
        $this->cleanDatabase();
    }

    protected function deleteEntries()
    {
        Core::database()->delete(self::TABLE_XP, ["course" => $this->course->getId()]);
        Core::database()->delete(self::TABLE_LEVEL, ["course" => $this->course->getId()]);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    // TODO
}
