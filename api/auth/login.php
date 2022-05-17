<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Core\Core;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();
Core::requireSetup();

if (array_key_exists("logout", $_GET))
    Core::logout();

$isLoggedIn = Core::requireLogin();
$hasAccess = Core::checkAccess();

echo json_encode(['isLoggedIn' => $isLoggedIn]);
