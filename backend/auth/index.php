<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('..');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use Modules\Plugin\GoogleSheets;

Core::denyCLI();
if (Core::requireSetup(false))
    header('Location: ' . URL . '/setup');

if (array_key_exists("googleSheetsAuth", $_GET) && array_key_exists("state", $_GET)) {
    $receivedCourse = $_GET["state"];
    $code = $_GET["code"];
    if ($receivedCourse && $code) {
        Core::init();
        $gs = new GoogleSheets($receivedCourse);
        $gs->saveTokenToDB($code);
    }
    echo "<script>window.close();window.opener.location.reload(false);</script>";
} else {

    if (array_key_exists("google", $_GET)) {
        Core::performLogin("google");
    } else if (array_key_exists("facebook", $_GET)) {
        Core::performLogin("facebook");
    } else if (array_key_exists("linkedin", $_GET)) {
        Core::performLogin("linkedin");
    } else {
        Core::performLogin("fenix");
    }
    header('Location: ' . URL);
}
