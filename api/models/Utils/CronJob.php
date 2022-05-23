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

    public function __construct(string $script, int $courseId, ?int $number, ?string $time, int $day = null, string $datetime = null)
    {
        self::updateCronTab($script, $courseId, $number, $time, $day, $datetime);
    }

    public static function removeCronJob(string $script, int $courseId)
    {
        self::updateCronTab($script, $courseId, null, null, null, null, true);
    }

    private static function updateCronTab(string $script, int $courseId, ?int $number, ?string $time, int $day = null, string $datetime = null, bool $remove = false)
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
                // TODO: replace with getExpression()
                $periodStr = "";
                if (!is_null($datetime)) {
                    // NOTE: max. working datetime = datetime + 1 year
                    $day = intval(substr($datetime, 8, 2));
                    $month = intval(substr($datetime, 5, 2));
                    $hour = intval(substr($datetime, 11, 2));
                    $minute = intval(substr($datetime, 14, 2));
                    $periodStr = $minute . " " . $hour . " " . $day . " " . $month . " *";

                } else if ($time == "Minutes") {
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
            case "AutoDisabling":
                return ROOT_PATH . "models/GameCourse/Course/AutoDisablingScript.php";
            case "AutoGame":
                return AUTOGAME_FOLDER . "/AutoGameScript.php";
            case "ProgressReport": // FIXME: should be compartimentalized inside module
                return MODULES_FOLDER . "/" . Notifications::ID . "/ProgressReportScript.php";
            default:
                return null;
        }
    }

    /**
     * TODO: add more flexibility and replace section in updateCrontab function
     * Gets a cron schedule expression based on specifications given.
     * Extensive editor on: https://crontab.guru
     *
     * @return string
     */
    private static function getExpression(): string
    {
        return "";
    }
}
