<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('..');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

Core::denyCLI();
if (!Core::requireSetup(false))
    header('Location: ..');

if (array_key_exists("google", $_GET)) {
    Core::performLogin("google");
} else {
    Core::performLogin("fenix");
}
header('Location: ..');
