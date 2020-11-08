<?php

namespace Modules\Plugin;

use GameCourse\GoogleHandler;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\User;

class CronJob
{
    //tratar depois da periodicidade com base no que o user escolheu
    public function __construct($script, $course, $number, $time, $remove = false)
    {
        $cronFile = "/tmp/crontab.txt";
        $path = null;
        // tem de estar na mesma diretoria
        if ($script == "Moodle") {
            $path = getcwd() . "/MoodleScript.php";
        } else if ($script == "ClassCheck") {
            $path = getcwd() . "/ClassCheckScript.php";
        } else if ($script == "GoogleSheets") {
            $path = getcwd() . "/GoogleSheetsScript.php";
        }
        $output = shell_exec('crontab -l');
        if ($path && $output) {
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
                } else if ($time == "Months") {
                    $periodStr = "* * */" . $number . " * *";
                }
                $toWrite .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            }
            file_put_contents($cronFile, $toWrite);
            echo exec('crontab /tmp/crontab.txt');
        }
    }
}
