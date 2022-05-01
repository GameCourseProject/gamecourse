<?php
namespace GameCourse\Module\Config;

/**
 * This class holds all action types available in the module configuration
 * and emulates an enumerator (not available in PHP 7.3).
 */
class Action
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const NEW = "new";
    const EDIT = "edit";
    const DELETE = "delete";
    const DUPLICATE = "duplicate";
    const PREVIEW = "preview";
    const MOVE_UP = "move-up";
    const MOVE_DOWN = "move-down";
    const EXPORT = "export";
    // NOTE: insert here new types of actions
}
