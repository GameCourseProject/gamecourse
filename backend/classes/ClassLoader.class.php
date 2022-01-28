<?php
spl_autoload_register(function ($class) {
    if (strpos($class, '\\') !== FALSE) { // has namespace
        $names = explode('\\', $class);
        if ($names[0] == 'Modules' && count($names) > 1) {
            $path = MODULES_FOLDER . '/' . strtolower($names[1]) . '/' . implode('/', array_slice($names, 2)) . '.php';
            if (file_exists($path)) 
                include $path;
            else
                include MODULES_FOLDER . '/' . strtolower($names[1]) . '/module.' . implode('/', array_slice($names, 2)) . '.php';
        } else {
            $path = 'classes/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($path)) 
                include $path;
        }
    }
    else if (file_exists('classes/' . $class . '.class.php')) {
        include 'classes/' . $class . '.class.php';
    }
});
