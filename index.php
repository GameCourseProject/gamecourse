<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\ModuleLoader;

Core::denyCLI();
Core::requireLogin();
Core::requireSetup();
Core::init();
Core::checkAccess();    

ModuleLoader::scanModules();

include 'pages/main.php';
