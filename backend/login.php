<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'cors.php';

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\ModuleLoader;
use GameCourse\Views\Dictionary;

if (array_key_exists("logout", $_GET)) {
    Core::logout();
}

Core::denyCLI();
$isLoggedIn = Core::requireLogin();
Core::requireSetup();
Core::init();
$hasAccess = Core::checkAccess();

ModuleLoader::scanModules();

echo json_encode(['isLoggedIn' => $isLoggedIn]);