<?php
namespace GameCourse\Module;

use ReflectionClass;

/**
 * This class holds all module types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class ModuleType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const GAME_ELEMENT = "GameElement";         // Modules that represent game elements
    const DATA_SOURCE = "DataSource";           // Modules that input data into the system
    const UTILITY = "Util";                     // Modules that add utility functionality
    // NOTE: insert here new types of modules & update module table definition


    /**
     * Checks if a given module type is available in the system.
     *
     * @param string $type
     * @return bool
     */
    public static function exists(string $type): bool
    {
        $typeClass = new ReflectionClass(ModuleType::class);
        $types = array_values($typeClass->getConstants());
        return in_array($type, $types);
    }
}
