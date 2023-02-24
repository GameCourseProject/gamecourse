<?php
namespace Utils;

use DateTime;
use Exception;
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

    /**
     * Checks if a given date1 comes before a given date2.
     *
     * @param string $date1
     * @param string $date2
     * @return string
     * @throws Exception
     */
    public static function isBefore(string $date1, string $date2): string
    {
        return self::earliest($date1, $date2) == $date1;
    }

    /**
     * Checks if a given date1 comes after a given date2.
     *
     * @param string $date1
     * @param string $date2
     * @return string
     * @throws Exception
     */
    public static function isAfter(string $date1, string $date2): string
    {
        return self::earliest($date1, $date2) == $date2;
    }

    /**
     * Compares two dates and returns the one that comes first.
     *
     * @param string $date1
     * @param string $date2
     * @return string
     * @throws Exception
     */
    public static function earliest(string $date1, string $date2): string
    {
        $ddate1 = new DateTime($date1);
        $ddate2 = new DateTime($date2);
        return min($ddate1, $ddate2)->format("Y-m-d H:i:s");
    }

    /**
     * Gets how much time there is between two dates.
     * Time type options: 'day', 'week', 'month'.
     *
     * @param string $date1
     * @param string $date2
     * @param string $type
     * @return int
     * @throws Exception
     */
    public static function timeBetween(string $date1, string $date2, string $type): int
    {
        $ddate1 = new DateTime($date1);
        $ddate2 = new DateTime($date2);

        if ($date2 < $date1) return self::timeBetween($date2, $date1, $type);
        $diff = $ddate1->diff($ddate2);

        if ($type === self::DAY) return $diff->days;
        elseif ($type === self::WEEK) return floor($diff->days / 7);
        elseif ($type === self::MONTH) return $diff->m;
        else throw new Exception("Can't get time between dates: type '$type' is not allowed.");
    }
}
