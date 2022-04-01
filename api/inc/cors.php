<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;
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