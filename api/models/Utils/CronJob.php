<?php
namespace Utils;

use GameCourse\Notifications;

/**
 * This class is responsible to enable/disable cron jobs,
 * which are periodic scripts that run on a schedule.
 *
 * Editor for cron schedule expressions: https://crontab.guru
 */
class CronJob
{
    const CRONFILE = ROOT_PATH . "crontab.txt";

    public function __construct(string $script, int $courseId, int $number, string $time, int $day)
    {
        self::updateCronTab($script, $courseId, $number, $time, $day);
    }

    public static function removeCronJob(string $script, int $courseId)
    {
        self::updateCronTab($script, $courseId, null, null, null, true);
    }

    private static function updateCronTab(string $script, int $courseId, int $number, string $time, int $day, bool $remove = false)
    {
        $path = self::getScriptPath($script);
        $output = shell_exec('crontab -l');

        if ($path) {
            $file = $output;
            $lines = explode("\n", $file);
            $toWrite = "";
            foreach ($lines as $line) {
                $separated = explode(" ", $line);
                if ((strpos($line, $path) === false or end($separated) != $courseId) and $line != '') {
                    $toWrite .= $line . "\n";
                }
            }

            if (!$remove) {
                $periodStr = "";
                if ($time == "Minutes") {
                    $periodStr = "*/" . $number . " * * * *";
                } else if ($time == "Hours") {
                    $periodStr = "0 */" . $number . " * * *";
                } else if ($time == "Day") {
                    $periodStr = "0 0 */" . $number . " * *";
                } else if ($time == "Daily"){
                    $periodStr = "0 " . $number . " * * *";
                } else if ($time == "Weekly" && $day != null) {
                    $periodStr = "0 " . $number . " * * " . $day;   // (0-sunday, 1-monday, 2-tuesday, etc)
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $courseId . "\n";
            }

            file_put_contents(self::CRONFILE, $toWrite);
            echo exec('crontab ' . self::CRONFILE);
        }
    }

    private static function getScriptPath(string $script): ?string
    {
        switch ($script) {
            case "AutoGame":
                return ROOT_PATH . "AutoGameScript.php";
            case "ProgressReport":
                return ROOT_PATH . MODULES_FOLDER . "/" . Notifications::ID . "/ProgressReportScript.php";
            default:
                return null;
        }
    }
}
