<?php
namespace Modules\GoogleSheets;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

Core::init();

$googleSheets = new GoogleSheets($argv[1]);

if ($googleSheets->readGoogleSheets()) {
    return true;
} else return false;
