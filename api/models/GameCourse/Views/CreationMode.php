<?php
namespace GameCourse\Views;

/**
 * This class holds all view creation modes available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class CreationMode
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const BY_VALUE = "value";          // View is copied; changes won't affect other views
    const BY_REFERENCE = "ref";        // View is linked to the original; changes will be propagated
    // NOTE: insert here new view creation modes
}
