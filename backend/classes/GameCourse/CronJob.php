<?php

namespace GameCourse;

use Modules\ClassCheck\ClassCheckModule;
use Modules\GoogleSheets\GoogleSheetsModule;
use Modules\Moodle\MoodleModule;
use Modules\QR\QR;

class CronJob
{
    public function __construct($script, $course, $number, $time, $remove = false)
    {
        $cronFile = SERVER_PATH . "/crontab.txt";
        $path = null;
        if ($script == "Moodle") {
            $path = SERVER_PATH . "/modules/" . MoodleModule::ID. "/MoodleScript.php";
        } else if ($script == "ClassCheck") {
            $path = SERVER_PATH . "/modules/" . ClassCheckModule::ID. "/ClassCheckScript.php";
        } else if ($script == "GoogleSheets") {
            $path = SERVER_PATH . "/modules/" . GoogleSheetsModule::ID. "/GoogleSheetsScript.php";
        }else if ($script == "QR"){
            $path = SERVER_PATH . "/modules/" . QR::ID. "/QRScript.php";
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
                } else if ($time == "Day") {
                    $periodStr = "0 0 */" . $number . " * *";
                } else if ($time == "Daily"){
                    $periodStr = "0 " . $number . " * * *"; //assim so da para horas certas
                } else if ($time == "Weekly") {
                    $periodStr = "* * * * " . $number;  //falta definir as horas
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            }

            file_put_contents($cronFile, $toWrite);
            echo exec('crontab ' . $cronFile);
        }
    }
}
