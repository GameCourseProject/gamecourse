<?php
/**
 * This file is the entry-point of the application.
 * It acts as a front-controller.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Api\API;
use GameCourse\Core;

require __DIR__ . "/inc/bootstrap.php";

Core::denyCLI();

API::gatherRequestInfo();
API::processRequest();
