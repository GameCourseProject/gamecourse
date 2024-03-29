<?php
/**
 * This file is the entry-point of the application.
 * It acts as a front-controller.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use API\API;
use GameCourse\Core\Core;

require __DIR__ . "/inc/bootstrap.php";

Core::denyCLI();
Core::requireSetup();

if (!Core::requireLogin(false))
    API::error("Not logged in!", 401);

Core::checkAccess();

API::gatherRequestInfo();
API::processRequest();
