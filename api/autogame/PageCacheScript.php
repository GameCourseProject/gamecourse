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
use GameCourse\Course\Course;
use Utils\Cache;

require __DIR__ . "/../inc/bootstrap.php";

const TABLE_VIEWS_CACHE = "view_cache";

$courseId = intval($argv[1]);
$targets = explode(",", $argv[2]);

try {
    $course = new Course($courseId);
    $courseUsers = $course->getCourseUsersWithRole(true, "Student");
    $pages = Page::getPages($courseId);

    foreach ($pages as $page) {
        $pageId = intval($page["id"]);
        $pageToCache = new Page($pageId);
        $userSpecific = $pageToCache->isUserSpecific();

        if ($userSpecific) {
            foreach ($targets as $target) {
                $targetId = intval($target);
                Core::database()->delete(TABLE_VIEWS_CACHE, ["page_id" => $pageId, "user_id" => $targetId]);

                $pageString = $pageToCache->renderPage($targetId, $targetId);
                Cache::storeUserViewInDatabase($pageId, $targetId, $pageString);
            }
        } else {
            Core::database()->delete(TABLE_VIEWS_CACHE, ["page_id" => $pageId]);

            foreach ($courseUsers as $courseUser) {
                $targetId = intval($courseUser["id"]);

                $pageString = $pageToCache->renderPage($targetId, $targetId);
                Cache::storeUserViewInDatabase($pageId, $targetId, $pageString);
            }
        }
    }

} catch (Throwable $e) {
    AutoGame::log($courseId, $e->getMessage());
}