<?php
namespace GameCourse\AutoGame;

use Exception;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the AutoGame model, which implements the necessary methods
 * to interact with the autogame in the MySQL database.
 */
abstract class AutoGame
{
    const TABLE_AUTOGAME = "autogame";
    const TABLE_PARTICIPATION = "participation";


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Initializes AutoGame for a given course.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function initAutoGame(int $courseId)
    {
        // Insert line in autogame table
        Core::database()->insert(self::TABLE_AUTOGAME, ["course" => $courseId]);

        // Setup rules system
        RuleSystem::initRuleSystem($courseId);

        // Setup logging
        if (!file_exists(LOGS_FOLDER)) mkdir(LOGS_FOLDER, 0777, true);
        $logsFile = LOGS_FOLDER . "/autogame_" . $courseId . ".txt";
        file_put_contents($logsFile, "");
    }

    /**
     * Copies AutoGame information related to imported functions and
     * configuration from a given course to another.
     *
     * @param int $courseId
     * @param int $copyFrom
     * @return void
     * @throws Exception
     */
    public static function copyAutoGameInfo(int $courseId, int $copyFrom)
    {
       Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions/" . $copyFrom . "/", AUTOGAME_FOLDER . "/imported-functions/" . $courseId . "/");
       file_put_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt", file_get_contents(AUTOGAME_FOLDER . "/config/config_" . $copyFrom . ".txt"));
    }

    /**
     * Deletes AutoGame information related to imported functions,
     * configuration and logging from a given course.
     *
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function deleteAutoGameInfo(int $courseId)
    {
        // Remove rules system info
        RuleSystem::deleteRuleSystemInfo($courseId);

        // Remove logging info
        Utils::deleteFile(LOGS_FOLDER, "autogame_" . $courseId . ".txt");
    }

    /**
     * Enables/disables autogame for a given course.
     *
     * @param int $courseId
     * @param bool $enable
     * @return void
     * @throws Exception
     */
    public static function setAutoGame(int $courseId, bool $enable)
    {
        if ($enable) { // enable autogame
            if (!(new Course($courseId))->isActive())
                throw new Exception("Course with ID = " . $courseId . " is not enabled: can't enable AutoGame.");

            $periodicity = Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "periodicityNumber, periodicityTime");
            new CronJob("AutoGame", $courseId, intval($periodicity["periodicityNumber"]),  $periodicity["periodicityTime"]);

        } else { // disable autogame
            CronJob::removeCronJob("AutoGame", $courseId);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Information -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets last time AutoGame ran for a given course.
     *
     * @param int $courseId
     * @return string
     */
    public static function getLastRun(int $courseId): ?string
    {
        return Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "finishedRunning");
    }

    /**
     * Checks whether AutoGame is running for a given course.
     *
     * @param int $courseId
     * @return bool
     */
    public static function isRunning(int $courseId): bool
    {
        return boolval(Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "isRunning"));
    }
}
