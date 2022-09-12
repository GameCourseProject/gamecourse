<?php
namespace GameCourse\Views;

/**
 * This class holds all mutability types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class Mutability
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const STATIC = "static";            // Data on view is always the same
    const FLEXIBLE = "flexible";        // Data on view depends on a user
    // NOTE: insert here new mutability types
}
