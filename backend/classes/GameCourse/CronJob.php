<?php

namespace GameCourse;

class CronJob
{
    public function __construct($script, $course, $number, $time, $remove = false)
    {
        $cronFile = "/var/www/html/gamecourse/crontab.txt";
        $path = null;
        if ($script == "Moodle") {
            $path = "/var/www/html/gamecourse/modules/plugin/MoodleScript.php";
        } else if ($script == "ClassCheck") {
            $path = "/var/www/html/gamecourse/modules/plugin/ClassCheckScript.php";
        } else if ($script == "GoogleSheets") {
            $path = "/var/www/html/gamecourse/modules/plugin/GoogleSheetsScript.php";
        }else if ($script == "QR"){
            $path = "/var/www/html/gamecourse/modules/qr/QRScript.php";

        }
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
                } else if ($time == "Months") {
                    $periodStr = "* * */" . $number . " * *";
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            }
            if(file_exists($cronFile)){ // FIXME: remove
                file_put_contents($cronFile, $toWrite);
                echo exec('crontab /var/www/html/gamecourse/crontab.txt');
            }
        }
    }
}
