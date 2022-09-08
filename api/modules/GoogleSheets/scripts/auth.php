<?php
/**
 * This script is used for Google Sheets authentication.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\GoogleSheets\GoogleSheets;

require __DIR__ . "/../../../inc/bootstrap.php";

Core::denyCLI();
Core::requireSetup();

if (array_key_exists("state", $_GET)) {
    $courseId = intval($_GET["state"]);
    $authCode = $_GET["code"];

    if ($courseId && $authCode) {
        $googleSheets = new GoogleSheets(new Course($courseId));
        $googleSheets->saveToken($authCode);
    }

    // Close authentication window
    echo "<script>window.close();window.opener.location.reload(false);</script>";
}