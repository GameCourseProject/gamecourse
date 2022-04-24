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

    const STUDENT_ADDED_TO_COURSE = 0;          // args: int $courseId, int $studentId
    const STUDENT_REMOVED_FROM_COURSE = 1;      // args: int $courseId, int $studentId
    // NOTE: insert here new types of events
}
