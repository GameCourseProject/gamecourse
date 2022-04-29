<?php

use GameCourse\Core\Core;
use GameCourse\Module\Module;
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

    public static function setUpBeforeClass(bool $setupModules = false, array $mocks = [])
    {
        // Clean all tables in the database
        Core::database()->cleanDatabase();

        // Setup Modules
        if ($setupModules) Module::setupModules();

        // Relocate important data temporarily
        if (file_exists(LOGS_FOLDER)) Utils::copyDirectory(LOGS_FOLDER . "/", LOGS_FOLDER . "_copy/");
        if (file_exists(COURSE_DATA_FOLDER)) Utils::copyDirectory(COURSE_DATA_FOLDER . "/", COURSE_DATA_FOLDER . "_copy/");
        Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions/", AUTOGAME_FOLDER . "/imported-functions_copy/", ["defaults.py"]);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/config/", AUTOGAME_FOLDER . "/config_copy/", ["samples"]);

        // Clean file structure
        self::cleanFileStructure();

        // Setup Mocks
        foreach ($mocks as $mock) {
            $method = "mock" . $mock;
            self::{$method}();
        }
    }

    public static function tearDownAfterClass()
    {
        // Clean all tables in the database
        Core::database()->cleanDatabase();

        // Clean file structure
        self::cleanFileStructure();

        // Relocate important data to its original place
        if (file_exists(LOGS_FOLDER . "_copy")) Utils::copyDirectory(LOGS_FOLDER . "_copy/", LOGS_FOLDER . "/", [], true);
        if (file_exists(COURSE_DATA_FOLDER . "_copy")) Utils::copyDirectory(COURSE_DATA_FOLDER . "_copy/", COURSE_DATA_FOLDER . "/", [], true);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions_copy/", AUTOGAME_FOLDER . "/imported-functions/", [], true);
        Utils::copyDirectory(AUTOGAME_FOLDER . "/config_copy/", AUTOGAME_FOLDER . "/config/", [], true);

        // Remove Mocks
        Mockery::close();
    }

    public static function cleanFileStructure()
    {
        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }

    public static function cleanTables(array $tables)
    {
        foreach ($tables as $table) {
            Core::database()->deleteAll($table);
        }
    }

    public static function resetAutoIncrement(array $tables)
    {
        foreach ($tables as $table) {
            Core::database()->resetAutoIncrement($table);
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
        ]);
    }
}
