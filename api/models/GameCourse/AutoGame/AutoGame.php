<?php
namespace GameCourse\AutoGame;

use Exception;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;
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


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Participations ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets all participations of a given course.
     * Option for a specific user and/or source and/or type.
     *
     * @param int $courseId
     * @param int|null $userId
     * @param string|null $source
     * @param string|null $type
     * @return array
     */
    public static function getParticipations(int $courseId, int $userId = null, string $source = null, string $type = null): array
    {
        $table = self::TABLE_PARTICIPATION;
        $where = ["course" => $courseId];
        if (!is_null($userId)) $where["user"] = $userId;
        if (!is_null($source)) $where["source"] = $source;
        if (!is_null($type)) $where["type"] = $type;
        $participations = Core::database()->selectMultiple($table, $where, "*", "date DESC");

        // Parse
        foreach ($participations as &$participation) {
            $participation["id"] = intval($participation["id"]);
            $participation["user"] = (new User($participation["user"]))->getData();
            $participation["course"] = intval($participation["course"]);
            if (isset($participation["rating"])) $participation["rating"] = intval($participation["rating"]);
            if (isset($participation["evaluator"])) $participation["evaluator"] = intval($participation["evaluator"]);
        }

        return $participations;
    }

    /**
     * Adds a new participation to a given course.
     *
     * @param int $courseId
     * @param int $userId
     * @param string $description
     * @param string $type
     * @param string $date
     * @param string|null $source
     * @param string|null $post
     * @param int|null $rating
     * @param int|null $evaluator
     * @return int
     */
    public static function addParticipation(int $courseId, int $userId, string $description, string $type, ?string $source = null,
                                            string $date = null, ?string $post = null, ?int $rating = null, ?int $evaluator = null): int
    {
        return Core::database()->insert(self::TABLE_PARTICIPATION, [
            "user" => $userId,
            "course" => $courseId,
            "source" => $source ?? "GameCourse",
            "description" => $description,
            "type" => $type,
            "post" => $post,
            "date" => $date ?? date("Y-m-d H:i:s", time()),
            "rating" => $rating,
            "evaluator" => $evaluator
        ]);
    }

    /**
     * Updates a given participation.
     *
     * @param int $id
     * @param string $description
     * @param string $type
     * @param string $date
     * @param string|null $source
     * @param string|null $post
     * @param int|null $rating
     * @param int|null $evaluator
     * @return void
     */
    public static function updateParticipation(int $id, string $description, string $type, string $date, ?string $source = null,
                                        ?string $post = null, ?int $rating = null, ?int $evaluator = null)
    {
        Core::database()->update(self::TABLE_PARTICIPATION, [
            "source" => $source ?? "GameCourse",
            "description" => $description,
            "type" => $type,
            "post" => $post,
            "date" => $date,
            "rating" => $rating,
            "evaluator" => $evaluator
        ], ["id" => $id]);
    }

    /**
     * Removes a given participation from the system.
     *
     * @param int $id
     * @return void
     */
    public static function removeParticipation(int $id)
    {
        Core::database()->delete(self::TABLE_PARTICIPATION, ["id" => $id]);
    }
}
