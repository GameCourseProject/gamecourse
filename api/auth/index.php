<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Api\API;
use GameCourse\Core;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();

if (Core::requireSetup(false))
    API::error("GameCourse is not yet setup.", 409);

if (array_key_exists("googleSheetsAuth", $_GET) && array_key_exists("state", $_GET)) {
    $receivedCourse = $_GET["state"];
    $code = $_GET["code"];
    if ($receivedCourse && $code) {
        $gs = new GoogleSheets($receivedCourse);
        $gs->saveTokenToDB($code);
    }
    echo "<script>window.close();window.opener.location.reload(false);</script>"; // FIXME: prob can remove

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
