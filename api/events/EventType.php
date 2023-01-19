<?php
namespace Event;

/**
 * This class holds all event types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class EventType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const COURSE_ENABLED = "CE";                   // args: int $courseId
    const COURSE_DISABLED = "CD";                  // args: int $courseId

    const MODULE_ENABLED = "ME";                   // args: int $courseId, string $moduleId
    const MODULE_DISABLED = "MD";                  // args: int $courseId, string $moduleId

    const STUDENT_ADDED_TO_COURSE = "SAC";         // args: int $courseId, int $studentId
    const STUDENT_REMOVED_FROM_COURSE = "SRC";     // args: int $courseId, int $studentId

    const PAGE_VIEWED = "PV";                      // args: int $pageId, int $viewerId, int $userId
    // NOTE: insert here new types of events
}
