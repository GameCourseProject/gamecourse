<?php
namespace GameCourse\Views\Dictionary;

/**
 * This class holds all return types possible for the dictionary
 * and emulates an enumerator (not available in PHP 7.3).
 */
class ReturnType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const TEXT = "text";
    const NUMBER = "number";
    const COLLECTION = "collection";
    const TIME = "time";
    const BOOLEAN = "boolean";
    const OBJECT = "object";
    const MIXED = "mixed";
    const VOID = "void";
    // NOTE: insert here new return types

    // Specific Collections
    const AWARDS_COLLECTION = "collection of awards";
    const USERS_COLLECTION = "collection of users";
    const BADGES_COLLECTION = "collection of badges";
    const BADGE_LEVELS_COLLECTION = "collection of badge levels";
    const BADGE_PROGRESSION_COLLECTION = "collection of badge progression";
    const SKILLS_COLLECTION = "collection of skills";
    const JOURNEYS_COLLECTION = "collection of journeys";
    const TIERS_COLLECTION = "collection of tiers";
    const TREES_COLLECTION = "collection of trees";
    const STREAKS_COLLECTION = "collection of streaks";
    const SPENDING_COLLECTION = "collection of spending";
    const LEVELS_COLLECTION = "collection of levels";
}
