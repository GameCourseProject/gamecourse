<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('..');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
Core::denyCLI();
if (!Core::requireSetup(false))
    header('Location: ..');

Core::performLogin();
header('Location: ..');
?>
