<?php
/**
 * This script is used to enable a given page automatically
 * once it reaches its defined visible from date.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Views\Page\Page;

require __DIR__ . "/../../../../inc/bootstrap.php";

$pageId = intval($argv[1]);
$page = Page::getPageById($pageId);

$page->setVisible(true);

