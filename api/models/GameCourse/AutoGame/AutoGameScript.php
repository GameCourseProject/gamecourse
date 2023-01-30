<?php
/**
 * This is the AutoGame script, which runs automatically at given
 * periods of time.
 *
 * It is responsible for triggering AutoGame, which will fire any
 * available rules in the course specified, trying to award targets
 * with prizes.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = intval($argv[1]);

try {
    // Checks whether to run AutoGame (there's new data from data sources) and runs if so
    $run = boolval(Core::database()->select(AutoGame::TABLE_AUTOGAME, ["course" => $courseId], "runNext"));
    if ($run) AutoGame::run($courseId);

} catch (Exception $e) {
    AutoGame::log($courseId, $e->getMessage());
}