<?php
namespace GameCourse\Views\Logging;

use Exception;

/**
 * This is the Logging class, which implements the necessary methods
 * to interact with view logging.
 *
 * By keeping logs of actions to perform, views can be manipulated
 * individually without the need to traverse the entire view tree
 * each time there's an update.
 */
abstract class Logging
{
    /**
     * Processes a list of logs by going over each one and performing
     * the action it reflects.
     * Before processing, it simplifies the list to a canonical form.
     *
     * @param array $logs
     * @param array $views
     * @param int $courseId
     * @return void
     * @throws Exception
     */
    public static function processLogs(array $logs, array $views, int $courseId)
    {
        self::simplifyLogs($logs);
        foreach ($logs as $log) {
            $log->process($views, $courseId);
        }
    }

    /**
     * Simplifies a list of logs into a canonical form by reducing the
     * number of actions that actually need to be performed.
     *
     * @example [add view #1, delete view #1] --> []
     * @example [add view #1, update view #1] --> [add view #1] (info is alredy updated on $views)
     * @example [move view #1, move view #1] --> [move view #1] (to its final place)
     *
     * @param array $logs
     * @return void
     */
    private static function simplifyLogs(array &$logs)
    {
        // TODO
    }
}
