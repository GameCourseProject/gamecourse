<?php
// The job of index.php is to determine what file to load

$action = isset($_GET['action']) ? $_GET['action'] : null;

if (!$action) {
    die('Error: no action found');
}

switch ($action) {
    case 'login':
        require('API/login.php');
        break;

    default:
        echo 'Error: action \'' . $_GET['action'] . '\' not found';
        break;
}