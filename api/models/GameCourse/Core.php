<?php
namespace GameCourse;

use Database\Database;

class Core
{

    /*** ----------------------------------------------- ***/
    /*** ------------------ Database ------------------- ***/
    /*** ----------------------------------------------- ***/

    public static function database(): Database
    {
        return Database::get();
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Authentication ---------------- ***/
    /*** ----------------------------------------------- ***/

    // TODO


    /*** ----------------------------------------------- ***/
    /*** --------------------- CLI --------------------- ***/
    /*** ----------------------------------------------- ***/

    public static function isCLI(): bool
    {
        return php_sapi_name() == 'cli';
    }

    public static function denyCLI()
    {
        if (static::isCLI()) die('CLI access to this script is not allowed.');
    }
}
