<?php

namespace Modules\Plugin;

use GameCourse\GoogleHandler;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\User;

class CronJob
{
    //tratar depois da periodicidade com base no que o user escolheu
    public function __construct($script, $course)
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

        if ($path) {
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

            //corre de 5 em 5 minutos
            $file .= "*/5 * * * * /usr/bin/php " . $path . " " . $course . "\n";
            file_put_contents($cronFile, $file);
        }
    }
}
