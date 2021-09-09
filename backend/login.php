<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header("Access-Control-Allow-Origin: http://localhost:4200"); // TODO: change for deploy
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\ModuleLoader;

if (array_key_exists("logout", $_GET)) {
    Core::logout();
}

Core::denyCLI();
$isLoggedIn = Core::requireLogin();
//Core::requireSetup();
Core::init();
$hasAccess = Core::checkAccess();

ModuleLoader::scanModules();

echo json_encode(['isLoggedIn' => $isLoggedIn]);