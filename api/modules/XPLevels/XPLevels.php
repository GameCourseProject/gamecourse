<?php
namespace GameCourse\XPLevels;

use Event\Event;
use Event\EventType;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;

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
            $this->initXPForStudent($student["id"], $level0Id);
        }

        $this->initEvents();
    }

    public function initEvents()
    {
        Event::listen(EventType::STUDENT_ADDED_TO_COURSE, function (int $courseId, int $studentId) {
            if ($courseId == $this->course->getId())
                $this->initXPForStudent($studentId);
        }, self::ID);

        Event::listen(EventType::STUDENT_REMOVED_FROM_COURSE, function (int $courseId, int $studentId) {
            // NOTE: this event targets cases where the course user only changed roles and
            //       is no longer a student; there's no need for an event when a user is
            //       completely removed from course, as SQL 'ON DELETE CASCADE' will do it
            if ($courseId == $this->course->getId())
                Core::database()->delete(self::TABLE_XP, ["course" => $courseId, "user" => $studentId]);
        }, self::ID);
    }

    public function disable()
    {
        $this->cleanDatabase();
        $this->removeEvents();
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


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** ------------ XP ------------ ***/

    /**
     * Sets 0 XP for a given student.
     * If student already has XP it will reset them.
     *
     * @param int $studentId
     * @param int|null $level0Id
     * @return void
     */
    public function initXPForStudent(int $studentId, int $level0Id = null)
    {
        $courseId = $this->course->getId();
        if ($level0Id === null) $level0Id = $this->getLevelZeroId();

        if ($this->studentHasXP($studentId)) // already has XP
            Core::database()->delete(self::TABLE_XP, ["course" => $courseId, "user" => $studentId]);

        Core::database()->insert(self::TABLE_XP, [
            "course" => $courseId,
            "user" => $studentId,
            "xp" => 0,
            "level" => $level0Id
        ]);
    }

    /**
     * Checks whether a given student has XP initialized.
     *
     * @param int $studentId
     * @return bool
     */
    public function studentHasXP(int $studentId): bool
    {
        return !empty(Core::database()->select(self::TABLE_XP, ["course" => $courseId, "user" => $studentId]));
    }

    /**
     * Gets total XP for a given student.
     *
     * @param int $studentId
     * @return int|null
     */
    public function getStudentXP(int $studentId): int
    {
        return intval(Core::database()->select(self::TABLE_XP,
            ["course" => $this->course->getId(), "user" => $studentId],
            "xp"
        ));
    }


    /*** ---------- Levels ---------- ***/

    /**
     * Gets ID of first level.
     *
     * @return int
     */
    public function getLevelZeroId(): int
    {
        return intval(Core::database()->select(self::TABLE_LEVEL, ["course" => $this->course->getId(), "number" => 0], "id"));
    }
}
