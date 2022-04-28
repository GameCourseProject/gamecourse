<?php
/**
 * This file is used to bootstrap the application in test mode
 * by including the necessary files and initializing a separate
 * testing database.
 */

use GameCourse\Core\Core;

const ROOT_PATH = __DIR__ . "/../";

// configuration file
require_once ROOT_PATH . "/inc/config.php";

// autoload classes
require_once ROOT_PATH . "/vendor/autoload.php";

// testing utilities
include ROOT_PATH . "/tests/TestingUtils.php";

// init testing environment
Core::database()->initForTesting();