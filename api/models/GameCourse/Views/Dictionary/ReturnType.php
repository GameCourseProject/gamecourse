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
}
