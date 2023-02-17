<?php
/**
 * This script is used to disable a given page automatically
 * once it reaches its defined visible until date.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Views\Page\Page;

require __DIR__ . "/../../../../inc/bootstrap.php";

$pageId = intval($argv[1]);
$page = Page::getPageById($pageId);

$page->setVisible(false);
$page->setAutoDisabling(null);