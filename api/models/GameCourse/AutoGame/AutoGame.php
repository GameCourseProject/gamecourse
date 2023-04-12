<?php
namespace GameCourse\AutoGame;

use Exception;
use GameCourse\AutoGame\RuleSystem\RuleSystem;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;
use Throwable;
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

    const LOGS_FOLDER = "autogame";


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
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
        $logsFile = self::getLogsFile($courseId, false);
        Utils::initLogging($logsFile);
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
        $logsFile = self::getLogsFile($courseId, false);
        Utils::removeLogging($logsFile);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Status ---------------------- ***/
    /*** ---------------------------------------------------- ***/

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
        $script = ROOT_PATH . "models/GameCourse/AutoGame/AutoGameScript.php";
        if ($enable) { // enable autogame
            if (!(new Course($courseId))->isActive())
                throw new Exception("Course with ID = " . $courseId . " is not enabled: can't enable AutoGame.");

            $expression = Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "frequency");
            new CronJob($script, $expression, $courseId);

        } else { // disable autogame
            CronJob::removeCronJob($script, $courseId);
        }
        Core::database()->update(self::TABLE_AUTOGAME, ["isEnabled" => +$enable], ["course" => $courseId]);
    }

    /**
     * Checks whether AutoGame is enabled for a given course.
     *
     * @param int $courseId
     * @return bool
     */
    public static function isEnabled(int $courseId): bool
    {
        return boolval(Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "isEnabled"));
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Running --------------------- ***/
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
     * Triggers AutoGame to run on the next iteration for targets
     * with new data after a given checkpoint.
     *
     * @param int $courseId
     * @param string $checkpoint
     * @return void
     */
    public static function setToRun(int $courseId, string $checkpoint)
    {
        $autogameInfo = Core::database()->select(self::TABLE_AUTOGAME, ["course" => $courseId], "runNext, checkpoint");
        $runNext = boolval($autogameInfo["runNext"]);
        $previousCheckpoint = $autogameInfo["checkpoint"];

        if (!$runNext || (!is_null($previousCheckpoint) && strtotime($checkpoint) < strtotime($previousCheckpoint))) {
            Core::database()->update(self::TABLE_AUTOGAME, [
                "runNext" => 1,
                "checkpoint" => $checkpoint
            ], ["course" => $courseId]);
        }
    }

    /**
     * Updates AutoGame status for a given course.
     * Option to only update AutoGame status and leave started &
     * finished running timestamps unaltered.
     *
     * @param int $courseId
     * @param bool $isRunning
     * @param bool $onlyStatus
     * @return void
     */
    private static function setIsRunning(int $courseId, bool $isRunning, bool $onlyStatus = false)
    {
        $where = ["isRunning" => +$isRunning];
        if (!$onlyStatus) $where[$isRunning ? "startedRunning" : "finishedRunning"] = date("Y-m-d H:i:s", time());
        Core::database()->update(self::TABLE_AUTOGAME, $where, ["course" => $courseId]);
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

    /**
     * Checks whether AutoGame is stuck running (in the database)
     * due to an error being thrown.
     *
     * When errors are thrown during the execution of AutoGame,
     * the log file ends without a proper separator.
     *
     * @param int $courseId
     * @return bool
     */
    private static function isStuck(int $courseId): bool
    {
        $logs = self::getLogs($courseId);
        return !(substr(trim($logs), -strlen(Utils::LOGS_SEPARATOR)) == Utils::LOGS_SEPARATOR);
    }

    /**
     * Runs AutoGame for a given course.
     * Option for targets to run and whether to run on test mode.
     *
     * @param int $courseId
     * @param bool $all
     * @param array|null $targets
     * @param bool $testMode
     * @return void
     */
    public static function run(int $courseId, bool $all = false, ?array $targets = null, bool $testMode = false)
    {
        if (!Course::getCourseById($courseId)) {
            self::log($courseId, "There's no course with ID = $courseId.");
            return;
        }

        if (self::isRunning($courseId)) {
            if (self::isStuck($courseId)) {
                self::setIsRunning($courseId, false, true);

            } else {
                self::log($courseId, "AutoGame is already running.", "WARNING");
                return;
            }
        }

        if (self::isSocketOpen()) {
            self::callAutoGame($courseId, $all, $targets, $testMode);

        } else {
            self::setIsRunning(0, false);
            self::setIsRunning($courseId, false, true);
            self::startSocket($courseId, $all, $targets, $testMode);
        }
    }

    /**
     * Calls AutoGame python script for a given course.
     * Format:
     * $ python3 run_autogame.py <course-ID> <targets> <rules-folder> <logs-file> <db-name> <db-user> <db-password>
     *
     * @param int $courseId
     * @param bool $all
     * @param array|null $targets
     * @param bool $testMode
     * @return void
     */
    private static function callAutoGame(int $courseId, bool $all = false, ?array $targets = null, bool $testMode = false)
    {
        $AutoGamePath = ROOT_PATH . "autogame/" . ($testMode ? "run_autogame_test.py" : "run_autogame.py");
        $rulesFolder = RuleSystem::getDataFolder($courseId);
        $logsFile = self::getLogsFile($courseId);

        $cmd = "python3 $AutoGamePath $courseId ";
        if ($all) {
            // Running for all targets
            $cmd .= "all ";

        } else if (!is_null($targets)) {
            // Running for certain targets
            $cmd .= "\"[" . implode(",", $targets) . "]\" ";

        } else {
            // Running only for targets w/ new data
            $cmd .= "new ";
        }

        $cmd .= "\"$rulesFolder\" \"$logsFile\" \"" . DB_HOST . "\" \"" . DB_NAME . "\" \"" . DB_USER . "\" \"" . DB_PASSWORD . "\"";
        $cmd .= " &"; // NOTE: this will run autogame in the background
        system($cmd);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Socket ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    // NOTE: course 0 is restricted for AutoGame socket

    private static $host = "127.0.0.1";
    private static $port = 8004;

    /**
     * Starts AutoGame socket.
     *
     * @param int $courseId
     * @param bool $all
     * @param array|null $targets
     * @param bool $testMode
     * @return void|null
     */
    private static function startSocket(int $courseId, bool $all = false, ?array $targets = null, bool $testMode = false)
    {
        try {
            $address = "tcp://" . self::$host . ":" . self::$port;
            $socket = stream_socket_server($address, $errorCode, $errorMsg);
            if (!$socket) {
                self::log($courseId, "Could not create socket.\n\n$errorMsg");
                return;
            }

            self::setIsRunning(0, true);
            self::callAutoGame($courseId, $all, $targets, $testMode);

            while (true) {
                $connection = stream_socket_accept($socket);
                if (!$connection) {
                    self::log($courseId, "No connections received on the socket.", "WARNING");
                    return;
                }

                $endMsg = "end gamerules;\n";
                $msg = fgets($connection);

                if ($msg == $endMsg) { // exit msg received
                    break;

                } else { // otherwise correctly process data
                    $requestCourseId = intval(trim($msg)); // gamerules instance that made the request
                    $course = Course::getCourseById($requestCourseId);

                    $libraryId = trim(fgets($connection));
                    $funcName = trim(fgets($connection));
                    $args = json_decode(fgets($connection));

                    // Call dictionary function
                    $result = Core::dictionary()->callFunction($course, $libraryId, $funcName, !empty($args) ? $args : []);

                    // Determine type of data to be sent
                    $resultType = is_iterable($result) ? "collection" : "other";
                    fwrite($connection, $resultType);

                    // NOTE: this OK is used only for synching purposes
                    $ok = fgets($connection);

                    if ($resultType == "collection") {
                        foreach ($result as $res) {
                            fwrite($connection, json_encode($res) . "\n");
                        }

                    } else {
                        fwrite($connection, json_encode($result));
                    }

                    fclose($connection);
                }
            }

        } catch (Throwable $e) {
            self::log($courseId, "Caught an error on startSocket().\n\n" . $e->getMessage());

        } finally {
            if (isset($connection) && $connection) fclose($connection);

            $nrCoursesRunning = Core::database()->select(self::TABLE_AUTOGAME, ["isRunning" => true], "COUNT(*)", null, [["course", 0]]);
            if ($nrCoursesRunning == 0 && $socket) fclose($socket);

            self::setIsRunning(0, false);
            self::setIsRunning($courseId, false, true);
        }
    }

    /**
     * Checks whether AutoGame socket is open.
     *
     * @return bool
     */
    private static function isSocketOpen(): bool
    {
        return self::isRunning(0) && fsockopen(self::$host, self::$port);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Logging --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets AutoGame logs for a given course.
     *
     * @param int $courseId
     * @return string
     */
    public static function getLogs(int $courseId): string
    {
        $logsFile = self::getLogsFile($courseId, false);
        return Utils::getLogs($logsFile);
    }

    /**
     * Creates a new AutoGame log on a given course.
     *
     * @param int $courseId
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function log(int $courseId, string $message, string $type = "ERROR")
    {
        $logsFile = self::getLogsFile($courseId, false);
        Utils::addLog($logsFile, $message, $type);
    }

    /**
     * Gets AutoGame logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "autogame_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Participations ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets all participations of a given course.
     * Option for a specific user, type, rating, evaluator and/or
     * source, as well as an initial and/or end date.
     *
     * @param int $courseId
     * @param int|null $userId
     * @param string|null $type
     * @param int|null $rating
     * @param int|null $evaluatorId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $source
     * @return array
     */
    public static function getParticipations(int $courseId, int $userId = null, string $type = null, int $rating = null,
                                             int $evaluatorId = null, string $startDate = null, string $endDate = null,
                                             string $source = null): array
    {
        $table = self::TABLE_PARTICIPATION;

        $where = ["course" => $courseId];
        if (!is_null($userId)) $where["user"] = $userId;
        if (!is_null($type)) $where["type"] = $type;
        if (!is_null($rating)) $where["rating"] = $rating;
        if (!is_null($evaluatorId)) $where["evaluator"] = $evaluatorId;
        if (!is_null($source)) $where["source"] = $source;

        $whereCompare = [];
        if (!is_null($startDate)) $whereCompare[] = ["date", ">=", $startDate];
        if (!is_null($endDate)) $whereCompare[] = ["date", "<=", $endDate];

        $participations = Core::database()->selectMultiple($table, $where, "*", "date DESC", [], $whereCompare);

        // Parse
        foreach ($participations as &$participation) {
            $participation["id"] = intval($participation["id"]);
            $participation["user"] = intval($participation["user"]);
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
     * @param string|null $source
     * @param string|null $date
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
