<?php
namespace Utils;

use ReflectionClass;

/**
 * This class holds all types of time available in the
 * system and emulates an enumerator (not available in PHP 7.3).
 */
class Time
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const SECOND = "second";
    const MINUTE = "minute";
    const HOUR = "hour";
    const DAY = "day";
    const WEEK = "week";
    const MONTH = "month";
    const YEAR = "year";
    // NOTE: insert here new types of time & update table definitions


    /**
     * Checks if a given time type is available in the system.
     *
     * @param string $time
     * @return bool
     */
    public static function exists(string $time): bool
    {
        $timeClass = new ReflectionClass(Time::class);
        $times = array_values($timeClass->getConstants());
        return in_array($time, $times);
    }
}
