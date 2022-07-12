<?php
namespace GameCourse\Views\Logging;

/**
 * This class holds all view log types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class LogType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const ADD_VIEW = "add-view";            // params: int viewId, string mode
    const EDIT_VIEW = "edit-view";          // params: int viewId
    const DELETE_VIEW = "delete-view";      // params: int viewId
    const MOVE_VIEW = "move-view";          // params: int viewRoot, ?array from [int parent, int pos], ?array to [int parent, int pos]
    // NOTE: insert here new view log types
}
