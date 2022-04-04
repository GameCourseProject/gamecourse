<?php
/**
 * This file is used to bootstrap the application in test mode
 * by including the necessary files.
 */

const ROOT_PATH = __DIR__ . "/../";

// configuration file
require_once ROOT_PATH . "/inc/config.php";

// autoload classes
require_once ROOT_PATH . "/vendor/autoload.php";
