<?php
namespace GameCourse\Awards;

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
}
