<?php
namespace GameCourse\Module\Awards;

use ReflectionClass;

/**
 * This class holds all award types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class AwardType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const ASSIGNMENT = "assignment";
    const BADGE = "badge";
    const BONUS = "bonus";
    const EXAM = "exam";
    const LAB = "labs";
    const POST = "post";
    const PRESENTATION = "presentation";
    const QUIZ = "quiz";
    const SKILL = "skill";
    const STREAK = "streak";
    const TOKEN = "tokens";
    // NOTE: insert here new types of awards & update awards tables


    /**
     * Checks if a given award type is available in the system.
     *
     * @param string $type
     * @return bool
     */
    public static function exists(string $type): bool
    {
        $awardTypeClass = new ReflectionClass(AwardType::class);
        $awardTypes = array_values($awardTypeClass->getConstants());
        return in_array($type, $awardTypes);
    }
}
