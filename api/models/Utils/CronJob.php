<?php
namespace Utils;

use Exception;

/**
 * This class is responsible for scheduling cron jobs,
 * which are periodic scripts that run on a schedule
 * automatically.
 *
 * Editor for cron schedule expressions: https://crontab.guru
 */
class CronJob
{
    const CRONFILE = ROOT_PATH . "crontab.txt";

    /*** ----------------------------------------------- ***/
    /*** ----------- Cron Jobs Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Creates or updates a given cron job.
     * Cron jobs are identified by their script path and arguments.
     *
     * @param string $script
     * @param string $expression
     * @param ...$args
     * @throws Exception
     */
    public function __construct(string $script, string $expression, ...$args)
    {
        self::updateCronTab($script, $expression, ...$args);
    }

    /**
     * Removes a given cron job.
     * Cron jobs are identified by their script path and arguments.
     *
     * @param string $script
     * @param ...$args
     * @return void
     * @throws Exception
     */
    public static function removeCronJob(string $script, ...$args)
    {
        self::updateCronTab($script, null, ...$args);
    }

    /**
     * Updates Cron jobs scheduled by adding (when expression passed)
     * or removing (no expression passed).
     *
     * @param string $script
     * @param string|null $expression
     * @param ...$args
     * @return void
     * @throws Exception
     */
    private static function updateCronTab(string $script, ?string $expression, ...$args)
    {
        // Check script exists
        if (!file_exists($script))
            throw new Exception("Script '$script' doesn't exist: can't update Cron job.");

        // Get scheduled cron jobs list
        $output = shell_exec('crontab -l');
        $cronjobs = array_filter(array_map('trim', explode("\n", trim($output))), function ($line) { return !empty($line); });

        // Filter out given script
        $cronjobs = array_filter($cronjobs, function ($cronjob) use ($script, $args) {
            $parts = explode(" ", $cronjob);
            $scr = $parts[6];
            $arg = count($parts) > 7 ? array_slice($parts, 7) : [];
            return $scr != $script || implode(" ", $arg) != implode(" ", $args);
        });

        // Add updated cron job
        if ($expression) $cronjobs[] = implode(" ", array_merge([$expression, "/usr/bin/php", $script], $args));

        // Save scheduled cron jobs
        file_put_contents(self::CRONFILE, implode("\n", $cronjobs) . "\n"); // NOTE: must end with newline
        echo exec('crontab ' . self::CRONFILE);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Converts a given datetime into a cron expression.
     * NOTE: don't forget to remove cron job when date has come,
     *       otherwise it will repeat every year on given date.
     *
     * @param string $datetime
     * @return string
     */
    public static function dateToExpression(string $datetime): string
    {
        $day = substr($datetime, 8, 2);
        $month = substr($datetime, 5, 2);
        $hour = substr($datetime, 11, 2);
        $minute = substr($datetime, 14, 2);
        return "$minute $hour $day $month *";
    }
}
