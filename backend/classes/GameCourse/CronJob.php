<?php
namespace GameCourse;

class CronJob
{
    public function __construct($script, $course, $number, $time, $day, $remove = false)
    {
        $cronFile = SERVER_PATH . "/crontab.txt";

        $path = null;
        if ($script == "AutoGame") $path = SERVER_PATH . "/AutoGameScript.php";
        if ($script == "ProgressReport") $path = SERVER_PATH . "/modules/notifications/ProgressReportScript.php";
        $output = shell_exec('crontab -l');

        if ($path) {
            $file = $output;
            $lines = explode("\n", $file);
            $toWrite = "";
            foreach ($lines as $line) {
                $separated = explode(" ", $line);
                if ((strpos($line, $path) === false or end($separated) != $course) and $line != '') {
                    $toWrite .= $line . "\n";
                }
            }

            if(!$remove){
                $periodStr = "";
                if ($time == "Minutes") {
                    $periodStr = "*/" . $number . " * * * *";
                } else if ($time == "Hours") {
                    $periodStr = "0 */" . $number . " * * *";
                } else if ($time == "Day") {
                    $periodStr = "0 0 */" . $number . " * *";
                } else if ($time == "Daily"){
                    $periodStr = "0 " . $number . " * * *"; //assim so da para horas certas
                } else if ($time == "Weekly" && $day != null) {
                    $periodStr = "0 " . $number . " * * " . $day; // (0-sunday, 1-monday, 2-tuesday, etc)
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            }

            file_put_contents($cronFile, $toWrite);
            echo exec('crontab ' . $cronFile);
        }
    }
}
