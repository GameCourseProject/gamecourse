<?php
/**
 * This is the Page Cache script, which runs automatically after an
 * execution of AutoGame
 *
 * It is responsible for deleting previous cache in database,
 * render views with new data and populate the cache.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Views\Page\Page;
use Utils\Cache;

require __DIR__ . "/../../../../../inc/bootstrap.php";

const TABLE_VIEWS_CACHE = "views_cache";

$courseId = intval($argv[1]);
$targets = explode(",", $argv[2]);

try {
    $pages = Page::getPages($courseId);

    foreach ($pages as $page) {
        $pageId = intval($page["id"]);
        $pageToCache = new Page($pageId);
        $mockUser = intval($targets[0]);

        if ($page["type"] === "individual") {
            foreach ($targets as $target) {
                $targetId = intval($target);
                Core::database()->delete(TABLE_VIEWS_CACHE, ["page_id" => $pageId, "user_id" => $targetId]);

                $pageToCache->renderPage($targetId, $targetId);
                Cache::storeViewsInDatabase($pageId, $targetId);
            }
        } else if ($page["type"] === "generic") {
            Core::database()->delete(TABLE_VIEWS_CACHE, ["page_id" => $pageId]);
            $pageToCache->renderPage($mockUser, $mockUser);
            Cache::storeViewsInDatabase($pageId);
        }
    }

} catch (Throwable $e) {
    AutoGame::log($courseId, $e->getMessage());
}