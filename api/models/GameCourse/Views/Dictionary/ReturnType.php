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

    const INT = "integer";
    const STRING = "string";
    const OBJECT = "object";
    const ARRAY = "array";
    const BOOLEAN = "boolean";
    const MIXED = "mixed";
    // NOTE: insert here new return types
}
