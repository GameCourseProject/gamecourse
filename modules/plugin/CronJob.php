<?php

namespace Modules\Plugin;

use GameCourse\GoogleHandler;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\User;

class CronJob
{
    //tratar depois da periodicidade com base no que o user escolheu
    public function __construct($script, $course, $number, $time)
    {
        $cronFile = "/var/spool/cron/crontabs/root";
        $path = null;
        // tem de estar na mesma diretoria
        if ($script == "Moodle") {
            $path = getcwd() . "/MoodleScript.php";
        } else if ($script == "ClassCheck") {
            $path = getcwd() . "/ClassCheckScript.php";
        } else if ($script == "GoogleSheets") {
            $path = getcwd() . "/GoogleSheetsScript.php";
        }

        if ($path && file_exists($cronFile)) {
            $file = file_get_contents($cronFile);
            $lines = explode("\n", $file);
            $exclude = array();
            $found = false;
            foreach ($lines as $line) {
                if (strpos($line, $script) !== FALSE) {
                    $found = true;
                    continue;
                }
                $exclude[] = $line;
            }
            if ($found) {
                $file = implode("\n", $exclude);
            }

            $periodStr = "";
            if ($time == "minutes") {
                $periodStr = "*/" . $number . " * * * *";
            } else if ($time == "hours") {
                $periodStr = "0 */" . $number . " * * *";
            } else if ($time == "months") {
                $periodStr = "* * */" . $number . " * *";
            }

            $file .= $periodStr . " /usr/bin/php " . $path . " " . $course . "\n";
            file_put_contents($cronFile, $file);
        }
    }
}
