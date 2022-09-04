<?php
/**
 * This file is used to bootstrap the application in test mode
 * by including the necessary files and making the necessary
 * initializations, including initializing a separate testing
 * database.
 */

use GameCourse\Core\Core;

const ROOT_PATH = __DIR__ . "/../";

// configuration file
require_once ROOT_PATH . "/inc/config.php";

// autoload classes
require_once ROOT_PATH . "/vendor/autoload.php";

// testing utilities
require_once ROOT_PATH . "/tests/TestingUtils.php";

// set default timezone
date_default_timezone_set('Europe/Lisbon');

// init testing environment
Core::initTestDatabase();