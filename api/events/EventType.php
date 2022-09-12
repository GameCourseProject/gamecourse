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

    const COURSE_ENABLED = 0;                   // args: int $courseId
    const COURSE_DISABLED = 1;                  // args: int $courseId
    const STUDENT_ADDED_TO_COURSE = 2;          // args: int $courseId, int $studentId
    const STUDENT_REMOVED_FROM_COURSE = 3;      // args: int $courseId, int $studentId
    const PAGE_VIEWED = 4;                      // args: int $pageId, int $viewerId, int $userId
    // NOTE: insert here new types of events
}
