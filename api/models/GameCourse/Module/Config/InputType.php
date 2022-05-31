<?php
namespace GameCourse\Module\Config;

/**
 * This class holds all input types available in the module configuration
 * and emulates an enumerator (not available in PHP 7.3).
 */
class InputType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const TEXT = "text";
    const TEXTAREA = "textarea";
    const NUMBER = "number";
    const CHECKBOX = "checkbox";
    const RADIO = "radio";
    const SELECT = "select";
    const TOGGLE = "toggle";
    const DATE = "date";
    const TIME = "time";
    const DATETIME = "datetime";
    const IMAGE = "image";
    const FILE = "file";
    const COLOR = "color";
    // NOTE: insert here new types of inputs
}
