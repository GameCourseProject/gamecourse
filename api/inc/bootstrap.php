<?php
/**
 * This file is used to bootstrap the application by
 * including the necessary files and making the necessary
 * initializations.
 */

use Event\Event;

const ROOT_PATH = __DIR__ . "/../";

// configuration file
require_once ROOT_PATH . "/inc/config.php";

// prevent 'blocked by CORS policy'
require_once ROOT_PATH . "/inc/cors.php";

// autoload classes
require_once ROOT_PATH . "/vendor/autoload.php";

// set default timezone
date_default_timezone_set('Europe/Lisbon');

// init events
Event::initEvents();
