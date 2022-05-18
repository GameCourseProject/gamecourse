<?php
/**
 * This file is used to prevent 'blocked by CORS policy' by adding
 * the necessary headers to requests to allow a given origin to
 * communicate with GameCourse's RESTful API.
 */

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;
$allowed_domains = [URL];

if (in_array($origin, $allowed_domains)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
}

// Allow OPTIONS request to prevent preflight error in browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    exit();