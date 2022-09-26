<?php
namespace Utils;

use GameCourse\Module\ProgressReport\ProgressReport;

/**
 * This class is responsible to enable/disable cron jobs,
 * which are periodic scripts that run on a schedule.
 *
 * Editor for cron schedule expressions: https://crontab.guru
 */
class CronJob
{
    const CRONFILE = ROOT_PATH . "crontab.txt";

    public function __construct(string $script, int $itemId, ?int $number, ?string $time, int $day = null, string $datetime = null)
    {
        self::updateCronTab($script, $itemId, $number, $time, $day, $datetime);
    }

    public static function removeCronJob(string $script, int $itemId)
    {
        self::updateCronTab($script, $itemId, null, null, null, null, true);
    }

    private static function updateCronTab(string $script, int $itemId, ?int $number, ?string $time, int $day = null, string $datetime = null, bool $remove = false)
    {
        // FIXME: allow for arguments to be passed
        $path = self::getScriptPath($script);
        $output = shell_exec('crontab -l');

        if ($path) {
            $file = $output;
            $lines = explode("\n", $file);
            $toWrite = "";
            foreach ($lines as $line) {
                $separated = explode(" ", $line);
                if ((strpos($line, $path) === false or end($separated) != $itemId) and $line != '') {
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
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $itemId . "\n";
            }

            file_put_contents(self::CRONFILE, $toWrite);
            echo exec('crontab ' . self::CRONFILE);
        }
    }

    private static function getScriptPath(string $script): ?string
    {
        switch ($script) {
            case "AutoCourseEnabling":
                return ROOT_PATH . "models/GameCourse/Course/AutoEnablingScript.php";

            case "AutoCourseDisabling":
                return ROOT_PATH . "models/GameCourse/Course/AutoDisablingScript.php";

            case "AutoGame":
                return ROOT_PATH . "models/GameCourse/AutoGame/AutoGameScript.php";

            case "AutoPageEnabling":
                return ROOT_PATH . "models/GameCourse/Views/Page/AutoEnablingScript.php";

            case "AutoPageDisabling":
                return ROOT_PATH . "models/GameCourse/Views/Page/AutoDisablingScript.php";

            case "ProgressReport": // FIXME: should be compartimentalized inside module
                return MODULES_FOLDER . "/" . ProgressReport::ID . "/scripts/ProgressReportScript.php";

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
