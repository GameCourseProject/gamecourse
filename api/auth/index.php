<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Core\AuthService;
use GameCourse\Core\Core;

require __DIR__ . "/../inc/bootstrap.php";

Core::denyCLI();
Core::requireSetup();

if (array_key_exists("googleSheetsAuth", $_GET) && array_key_exists("state", $_GET)) {
    $receivedCourse = $_GET["state"];
    $code = $_GET["code"];
    if ($receivedCourse && $code) {
        $gs = new GoogleSheets($receivedCourse);
        $gs->saveTokenToDB($code);
    }
    echo "<script>window.close();window.opener.location.reload(false);</script>"; // FIXME: prob can remove

} else {
    if (array_key_exists(AuthService::GOOGLE, $_GET)) Core::performLogin(AuthService::GOOGLE);
    else if (array_key_exists(AuthService::FACEBOOK, $_GET)) Core::performLogin(AuthService::FACEBOOK);
    else if (array_key_exists(AuthService::LINKEDIN, $_GET)) Core::performLogin(AuthService::LINKEDIN);
    else Core::performLogin(AuthService::FENIX);
    header('Location: ' . URL);
}
