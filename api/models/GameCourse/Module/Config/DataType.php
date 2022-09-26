<?php
namespace GameCourse\Module\Config;

/**
 * This class holds all data types available in the module configuration
 * and emulates an enumerator (not available in PHP 7.3).
 *
 * NOTE: see each input options on the frontend
 */
class DataType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const TEXT = "text";
    const NUMBER = "number";
    const DATE = "date";
    const TIME = "time";
    const DATETIME = "datetime";
    const COLOR = "color";
    const IMAGE = "image";
    const PILL = "pill";
    const BUTTON = "button";
    const AVATAR = "avatar";
    const CHECKBOX = "checkbox";
    const RADIO = "radio";
    const TOGGLE = "toggle";
    const CUSTOM = "custom";

    // NOTE: insert here new types of inputs
}
