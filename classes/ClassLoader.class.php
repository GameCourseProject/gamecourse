<?php
spl_autoload_register(function ($class) {
    if (strpos($class, '\\') !== FALSE) { // has namespace
        $names = explode('\\', $class);
        if ($names[0] == 'Modules' && count($names) > 1) {
            include 'modules/' . strtolower($names[1]) . '/' . implode('/', array_slice($names, 2)) . '.php';
        } else
            include 'classes/' . str_replace('\\', '/', $class) . '.php';
    }
    else if (file_exists('classes/' . $class . '.class.php'))
        include 'classes/' . $class . '.class.php';
});
