<?php
namespace GameCourse\AutoGame;

use Error;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the AutoGame model, which implements the necessary methods
 * to interact with the autogame in the MySQL database.
 */
class AutoGame
{
    const TABLE_AUTOGAME = "autogame";


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Initializes AutoGame for a given course.
     *
     * @param int $courseId
     * @param string|null $courseName
     * @param string|null $dataFolder
     * @return void
     */
    public static function initAutoGame(int $courseId, string $courseName = null, string $dataFolder = null)
    {
        if ($dataFolder === null)
            $dataFolder = (new Course($courseId))->getDataFolder(true, $courseName);

        // Insert line in autogame table
        Core::database()->insert(self::TABLE_AUTOGAME, ["course" => $courseId]);

        // Setup rules system
        $rulesFolder = $dataFolder . "/rules";
        $functionsFolder = AUTOGAME_FOLDER . "/imported-functions/" . $courseId;
        $functionsFileDefault = AUTOGAME_FOLDER . "/imported-functions/defaults.py";
        $defaultFunctionsFile = "/defaults.py";
        $metadataFile = AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt";

        mkdir($rulesFolder, 0777, true);
        mkdir($functionsFolder, 0777, true);

        file_put_contents($functionsFolder . $defaultFunctionsFile, file_get_contents($functionsFileDefault));
        file_put_contents($metadataFile, "");

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
     */
    public static function copyAutoGameInfo(int $courseId, int $copyFrom)
    {
       Utils::copyDirectory(AUTOGAME_FOLDER . "/imported-functions/" . $copyFrom . "/", ROOT_PATH . "autogame/imported-functions/" . $courseId . "/");
       file_put_contents(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt", file_get_contents(ROOT_PATH . "autogame/config/config_" . $copyFrom . ".txt"));
    }

    /**
     * Deletes AutoGame information related to imported functions,
     * configuration and logging from a given course.
     *
     * @param int $courseId
     * @return void
     */
    public static function deleteAutoGameInfo(int $courseId)
    {
        // Remove rules system info
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions/" . $courseId);
        unlink(AUTOGAME_FOLDER . "/config/config_" . $courseId . ".txt");

        // Remove logging info
        unlink(LOGS_FOLDER . "/autogame_" . $courseId . ".txt");
    }

    /**
     * Enables/disables autogame for a given course.
     *
     * @param int $courseId
     * @param bool $enable
     * @return void
     */
    public static function setAutoGame(int $courseId, bool $enable)
    {
        if ($enable) { // enable autogame
            if (!(new Course($courseId))->isActive())
                throw new Error("Course with ID = " . $courseId . " is not enabled: can't turn on AutoGame.");

            $periodicity = Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "periodicityNumber, periodicityTime");
            new CronJob("AutoGame", $courseId, intval($periodicity["periodicityNumber"]),  $periodicity["periodicityTime"], null);

        } else { // disable autogame
            CronJob::removeCronJob("AutoGame", $courseId);
        }
    }
}
