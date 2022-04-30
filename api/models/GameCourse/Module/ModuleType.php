<?php
namespace GameCourse\Module;

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
    // NOTE: insert here new types of modules
}
