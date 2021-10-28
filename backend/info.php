<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include('classes/ClassLoader.class.php');
include('api_functions/api_endpoints.php');

use GameCourse\API;
use GameCourse\Core;
use GameCourse\ModuleLoader;
use GameCourse\Views\Dictionary;

require_once 'cors.php';

Core::denyCLI();
Core::init();

if (!Core::requireLogin(false))
    API::error("Not logged in!", 400);

if (Core::requireSetup(false))
    API::error("GameCourse is not yet setup.", 400);

if (!Core::checkAccess(false))
    API::error("Access denied.", 400);

ModuleLoader::scanModules();
Dictionary::init();

API::gatherRequestInfo();
API::processRequest();
