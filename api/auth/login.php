<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Core\Core;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();
Core::requireSetup();

if (array_key_exists("logout", $_POST))
    Core::logout();

$isLoggedIn = Core::requireLogin();
Core::checkAccess();

echo json_encode(['isLoggedIn' => $isLoggedIn]);
