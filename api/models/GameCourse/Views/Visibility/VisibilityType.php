<?php
namespace GameCourse\Views\Visibility;

/**
 * This class holds all visibility types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class VisibilityType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const VISIBLE = "visible";
    const INVISIBLE = "invisible";
    const CONDITIONAL = "conditional";
    // NOTE: insert here new view visibility types & update view table definition
}
