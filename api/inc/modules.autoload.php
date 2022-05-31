<?php
/**
 * This file is used to autoload modules' endpoints and
 * dictionary functions.
 */

use GameCourse\Module\Module;
use Utils\Utils;

spl_autoload_register(function ($class) {
    // Autoload modules endpoints
    if (Utils::strStartsWith($class, "API")) {
        $parts = explode("\\", $class);
        $controller = end($parts);

        $moduleIDs = Module::getModules(true);
        foreach ($moduleIDs as $moduleID) {
            $path = MODULES_FOLDER . "/" . $moduleID . "/controllers/" . $controller . ".php";
            if (file_exists($path)) include_once $path;
        }
    }

    // Autoload modules dictionary
    // TODO
});
