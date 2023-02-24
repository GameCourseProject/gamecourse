<?php

use Event\Event;
use GameCourse\Core\Core;
use GameCourse\Module\Module;
use GameCourse\Role\Role;
use GameCourse\Views\ViewHandler;
use Utils\Cache;
use Utils\Utils;

/**
 * This class holds a set of utility functions that can be
 * used for testing purposes.
 */
class TestingUtils
{
    /*** ---------------------------------------------------- ***/
    /*** ---------------- Setup & Tear Down ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(array $setup = [], array $mocks = [])
    {
        // Clean all tables in the database
        Core::database()->cleanDatabase();

        // Setup default roles
        if (in_array("roles", $setup)) Role::setupRoles();

        // Setup Modules
        if (in_array("modules", $setup)) Module::setupModules();

        // Setup Views
        if (in_array("views", $setup)) ViewHandler::setupViews();

        // Setup Events
        if (in_array("events", $setup)) Event::setupEvents();

        // Relocate important data temporarily
        if (file_exists(LOGS_FOLDER)) Utils::copyDirectory(LOGS_FOLDER . "/", LOGS_FOLDER . "_copy/");
        if (file_exists(COURSE_DATA_FOLDER)) Utils::copyDirectory(COURSE_DATA_FOLDER . "/", COURSE_DATA_FOLDER . "_copy/");
        if (file_exists(USER_DATA_FOLDER)) Utils::copyDirectory(USER_DATA_FOLDER . "/", USER_DATA_FOLDER . "_copy/");
        if (file_exists(CACHE_FOLDER)) Utils::copyDirectory(CACHE_FOLDER . "/", CACHE_FOLDER . "_copy/");
        Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions/", AUTOGAME_FOLDER . "/imported-functions_copy/", ["defaults.py"]);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/config/", AUTOGAME_FOLDER . "/config_copy/", ["samples"]);
        Utils::copyDirectory(ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries/", ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries_copy/");

        // Clean file structure
        self::cleanFileStructure();

        // Setup Mocks
        foreach ($mocks as $mock) {
            $method = "mock" . $mock;
            self::{$method}();
        }
    }

    /**
     * @throws Exception
     */
    public static function tearDownAfterClass()
    {
        // Clean all tables in the database
        Core::database()->cleanDatabase();

        // Clean file structure
        self::cleanFileStructure();

        // Relocate important data to its original place
        if (file_exists(LOGS_FOLDER . "_copy")) Utils::copyDirectory(LOGS_FOLDER . "_copy/", LOGS_FOLDER . "/", [], true);
        if (file_exists(COURSE_DATA_FOLDER . "_copy")) Utils::copyDirectory(COURSE_DATA_FOLDER . "_copy/", COURSE_DATA_FOLDER . "/", [], true);
        if (file_exists(USER_DATA_FOLDER . "_copy")) Utils::copyDirectory(USER_DATA_FOLDER . "_copy/", USER_DATA_FOLDER . "/", [], true);
        if (file_exists(CACHE_FOLDER . "_copy")) Utils::copyDirectory(CACHE_FOLDER . "_copy/", CACHE_FOLDER . "/", [], true);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions_copy/", AUTOGAME_FOLDER . "/imported-functions/", [], true);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/config_copy/", AUTOGAME_FOLDER . "/config/", [], true);
        Utils::copyDirectory(ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries_copy/", ROOT_PATH . "models/GameCourse/Views/Dictionary/libraries/", [], true);

        // Remove Mocks
        Mockery::close();
    }

    /**
     * @throws Exception
     */
    public static function cleanFileStructure()
    {
        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        if (file_exists(USER_DATA_FOLDER)) Utils::deleteDirectory(USER_DATA_FOLDER);
        if (file_exists(CACHE_FOLDER)) Cache::clean();
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }

    public static function cleanTables(array $tables)
    {
        foreach ($tables as $table) {
            Core::database()->deleteAll($table);
        }
    }

    public static function cleanEvents()
    {
        Event::stopAll();
    }

    public static function resetAutoIncrement(array $tables)
    {
        foreach ($tables as $table) {
            if (is_array($table)) { $value = $table[1]; $table = $table[0]; }
            Core::database()->resetAutoIncrement($table, $value ?? null);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Mocks ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Mock cron jobs so that they don't get created/removed.
     *
     * @return void
     */
    private static function mockCronJob()
    {
        Mockery::mock("overload:Utils\CronJob", [
            "__construct" => null,
            "removeCronJob" => null,
            "dateToExpression" => null
        ]);
    }
}
