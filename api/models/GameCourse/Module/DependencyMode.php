<?php
namespace GameCourse\Module;

/**
 * This class holds all dependency modes available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class DependencyMode
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Modes -------------------- ***/
    /*** ----------------------------------------------- ***/

    const HARD = "hard";         // Mandatory to work
    const SOFT = "soft";         // Not mandatory, but some features might not be available
    // NOTE: insert here new modes of dependencies
}
