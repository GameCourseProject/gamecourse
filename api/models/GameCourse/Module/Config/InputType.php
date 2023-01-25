<?php
namespace GameCourse\Module\Config;

/**
 * This class holds all input types available in the module configuration
 * and emulates an enumerator (not available in PHP 7.3).
 *
 * NOTE: see each input options on the frontend
 */
class InputType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    // Checkbox & Radio
    const CHECKBOX = "checkbox";
    const RADIO = "radio";

    // Code
    const CODE = "code";

    // Color
    const COLOR = "color";

    // Date & Time
    const DATE = "date";
    const TIME = "time";
    const DATETIME = "datetime";

    // General
    const TEXT = "text";
    const TEXTAREA = "textarea";
    const NUMBER = "number";
    const URL = "url";
    const FILE = "file";

    // Markdown
    const MARKDOWN = "markdown";

    // Personal info
    const EMAIL = "text";

    // Select
    const SELECT = "select";
    const PERIODICITY = "periodicity";
    const WEEKDAY = "weekday";

    // Toggle
    const TOGGLE = "toggle";

    // NOTE: insert here new types of inputs
}
