<?php

namespace GameCourse;

class CronJob
{
    public function __construct($script, $course, $number, $time, $remove = false)
    {
        $cronFile = "/tmp/crontab.txt";
        $path = null;
        if ($script == "Moodle") {
            $path = "/modules/plugin/MoodleScript.php";
        } else if ($script == "ClassCheck") {
            $path = "/modules/plugin/ClassCheckScript.php";
        } else if ($script == "GoogleSheets") {
            $path = "/modules/plugin/GoogleSheetsScript.php";
        }else if ($script == "QR"){
            $path = "/modules/qr/QRScript.php";

        }
        $output = shell_exec('crontab -l');
        if ($path) {
            $file = $output;
            $lines = explode("\n", $file);
            $exclude = array();
            $found = false;
            $toWrite = "";
            foreach ($lines as $line) {
                if (strpos($line, $script) === false) {
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
                } else if ($time == "Weekly") {
                    $periodStr = "* * * * " . $number;  //falta definir as horas
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            }
            if(file_exists('/tmp/crontab.txt')){
                file_put_contents($cronFile, $toWrite);
                echo exec('crontab /tmp/crontab.txt');
            }
        }
    }
}
