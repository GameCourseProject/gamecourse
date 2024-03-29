<?php
namespace Event;

use GameCourse\Core\Core;
use GameCourse\Views\Page\Page;
use Utils\Cache;
use Utils\Utils;

/**
 * Holds functionality to listen to a specific event and perform
 * an action when it's triggered.
 * Event types available in class 'EventType'.
 */
class Event
{
    private static $events = [];


    /*** --------------------------------------------- ***/
    /*** ------------------- Setup ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Listens for events available in the system.
     * This is only performed once during system setup.
     *
     * @return void
     */
    public static function setupEvents()
    {
        Event::listen(EventType::PAGE_VIEWED, function (int $pageId, int $viewerId, ?int $userId) {
            $page = Page::getPageById($pageId);
            $course = $page->getCourse();

            if ($course->getCourseUserById($viewerId) && (is_null($userId) || $course->getCourseUserById($userId))) {
                Core::database()->insert(Page::TABLE_PAGE_HISTORY, [
                    "course" => $course->getId(),
                    "page" => $pageId,
                    "viewer" => $viewerId,
                    "user" => $userId
                ]);
            }
        });
    }


    /*** --------------------------------------------- ***/
    /*** -------------- Initialization --------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Initializes events on each request so that they can
     * be triggered.
     *
     * @return void
     */
    public static function initEvents()
    {
        self::$events = Cache::get(null, "events");
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Getters ------------------ ***/
    /*** --------------------------------------------- ***/

    public static function getEvents(): array
    {
        return self::$events;
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Start listening to an event of a given type and perform
     * callback function when triggered.
     * Option to pass a custom ID for event.
     *
     * @param string $type
     * @param $callback
     * @param string|null $prefix
     * @return string|null
     */
    public static function listen(string $type, $callback, string $prefix = null): string
    {
        $id = uniqid($prefix !== null ? $prefix : "");
        self::$events[$type][$id] = $callback;
        Cache::store(null, "events", self::$events);
        return $id;
    }

    /**
     * Trigger an event of a given type.
     * Option to pass any number of arguments.
     *
     * @param string $type
     * @param ...$args
     * @return void
     */
    public static function trigger(string $type, ...$args)
    {
        if (isset(self::$events[$type])) {
            foreach (self::$events[$type] as $id => $callback) {
                $callback(...$args);
            }
        }
    }

    /**
     * Stop listening to an event of a given type and ID.
     *
     * @param string $type
     * @param string $id
     * @return void
     */
    public static function stop(string $type, string $id)
    {
        unset(self::$events[$type][$id]);
        if (count(self::$events[$type]) == 0) unset(self::$events[$type]);
        Cache::store(null, "events", self::$events);
    }

    /**
     * Stop listening to either all events or events that have
     * a given prefix on their IDs.
     *
     * @param string|null $prefix
     * @return void
     */
    public static function stopAll(string $prefix = null)
    {
        if (is_null($prefix)) self::$events = [];
        else {
            foreach (self::$events as $type => $event) {
                foreach (self::$events[$type] as $id => $callback) {
                    if (Utils::strStartsWith($id, $prefix))
                        unset(self::$events[$type][$id]);
                }
                if (count(self::$events[$type]) == 0) unset(self::$events[$type]);
            }
        }
        Cache::store(null, "events", self::$events);
    }
}
