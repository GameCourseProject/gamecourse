<?php
namespace GameCourse\Views\Event;

/**
 * This class holds all event types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class EventType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const CLICK = "click";
    const DOUBLE_CLICK = "dblclick";
    const MOUSE_OVER = "mouseover";
    const MOUSE_OUT = "mouseout";
    const MOUSE_UP = "mouseup";
    const WHEEL = "wheel";
    const DRAG = "drag";
    // NOTE: insert here new view event types & update view_event table definition
}
