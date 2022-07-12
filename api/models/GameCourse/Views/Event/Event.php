<?php
namespace GameCourse\Views\Event;

use GameCourse\Core\Core;

/**
 * This is the Event model, which implements the necessary methods
 * to interact with view events in the MySQL database.
 */
class Event
{
    const TABLE_EVENT = "view_event";

    protected $viewId;
    protected $type;
    protected $action;

    public function __construct(int $viewId, string $type, string $action)
    {
        $this->viewId = $viewId;
        $this->type = $type;
        $this->action = $action;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getViewId(): int
    {
        return $this->viewId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAction(): string
    {
        return $this->action;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a view event by its type.
     * Returns null if event doesn't exist.
     *
     * @param int $viewId
     * @param string $type
     * @return Event|null
     */
    public static function getEventByType(int $viewId, string $type): ?Event
    {
        $data = Core::database()->select(self::TABLE_EVENT, ["view" => $viewId, "type" => $type]);
        if ($data) return new Event($viewId, $type, $data["action"]);
        else return null;
    }

    /**
     * Gets events available in a view.
     *
     * @param int $viewId
     * @return array
     */
    public static function getEventsOfView(int $viewId): array
    {
        return Core::database()->selectMultiple(self::TABLE_EVENT, ["view" => $viewId], "type, action");
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Event Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds an event to the database.
     * Returns the newly created event.
     *
     * @param int $viewId
     * @param string $type
     * @param string $action
     * @return Event
     */
    public static function addEvent(int $viewId, string $type, string $action): Event
    {
        Core::database()->insert(self::TABLE_EVENT, [
            "view" => $viewId,
            "type" => $type,
            "action" => $action
        ]);
        return new Event($viewId, $type, $action);
    }

    /**
     * Deletes an event from the database.
     *
     * @param int $viewId
     * @param string $type
     * @return void
     */
    public static function deleteEvent(int $viewId, string $type)
    {
        Core::database()->delete(self::TABLE_EVENT, ["view" => $viewId, "type" => $type]);
    }

    /**
     * Deletes all events of a given view from the database.
     *
     * @param int $viewId
     * @return void
     */
    public static function deleteAllEvents(int $viewId)
    {
        Core::database()->delete(self::TABLE_EVENT, ["view" => $viewId]);
    }
}