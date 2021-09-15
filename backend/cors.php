<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';

$origin = $_SERVER['HTTP_ORIGIN'];
$allowed_domains = [URL];

if (in_array($origin, $allowed_domains)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

// Allow OPTIONS request to prevent preflight error in browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}