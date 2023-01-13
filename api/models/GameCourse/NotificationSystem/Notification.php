<?php

namespace GameCourse\NotificationSystem;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use phpDocumentor\Reflection\Types\Boolean;
use Utils\Utils;

/**
 * This is the Notification model, which implements the necessary methods
 * to interact with notifications in the MySQL database.
 */
class Notification
{
    const TABLE_NOTIFICATION = "notification";

    protected $id;

    const HEADERS = [ // headers for import/export functionality
        "user", "message", "showed"
    ];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getCourse() : Course
    {
        return Course::getCourseById($this->getData("course"));
    }

    public function getUser(): int
    {
        return $this->id;
    }

    public function getMessage(): string
    {
        return $this->getMessage();
    }

    public function isShowed(): Boolean
    {
        return $this->isShowed();
    }

    /*** ---------------------------------------------------- ***/
    /*** --------------------- Setters ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setShowed(bool $isShowed){
        $this->setData(["showed" => +$isShowed]);
    }

    /**
     * @throws Exception
     */
    public function setMessage(?string $message){
        $this->setData(["message" => $message]);
    }

    /**
     * Sets notification data on the database.
     *
     * @example setData(["message" => "New message"])
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Validate data
        // TODO
    }

    /*** ------------------------------------------------------------ ***/
    /*** ----------------- Notification Manipulation ---------------- ***/
    /*** ------------------------------------------------------------ ***/

    /**
     * Adds a new notification to the Notification System.
     * Returns the newly created notification.
     *
     * @param int $courseId
     * @param string $message
     * @return Notification
     *
     * @throws Exception
     */
    public static function addNotification(int $courseId, int $userId, string $message, bool $showed) : Notification
    {
        self::trim($message);
        // self::validateMessage($message);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_NOTIFICATION, [
            "course" => $courseId,
            "user" => $userId,
            "message" => $message,
            "showed" => $showed

        ]);

        return new Notification($id);
    }


    /**
     * Gets notification data from the database.
     *
     * @example getData() --> gets all notification data
     * @example getData("course") --> gets notification course
     * @example getData("course", message") --> gets notification course & message
     *
     * @param string $field
     * @return array|int|string|bool|null
     */
    public function getData(string $field="*")
    {
        $table = self::TABLE_NOTIFICATION;
        $where = ["id" => $this->id];
        $res = Core::database()->select($table, $where, $field);
        return is_array($res) ? self::parse($res) : self::parse(null, $res, $field);
    }

    /**
     * Checks whether notification exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }

    /*** ---------------------------------------------------- ***/
    /*** --------------------- General ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets notifications in the Notification System for a given course.
     * Returns null if notification doesn't exist.
     *
     * @param int $courseId
     * @return array
     */
    public static function getNotifications(?bool $showed = null): array
    {
        $where = [];
        if ($showed !== null) $where["showed"] = $showed;
        $notifications = Core::database()->selectMultiple(
            self::TABLE_NOTIFICATION,
            $where,
            "*",
            "id"
        );

        foreach ($notifications as &$notification) { $notification = self::parse($notification); }
        return $notifications;
    }

    /**
     * Gets a notification by its ID.
     * Returns null if notification doesn't exist.
     *
     * @param int $id
     * @return Notification|null
     */
    public static function getNotificationById(int $id): ?Notification
    {
        $notification = new Notification($id);
        if ($notification->exists()) return $notification;
        else return null;
    }

    /**
     * Gets a notifications by its user ID.
     * Returns null if notification doesn't exist.
     *
     * @param int $userId
     * @return array|null
     *
     * @throws Exception
     */
    public static function getNotificationsByUser(int $userId): array
    {
        $loggedUser = Core::getLoggedUser();
        if ($loggedUser->getId() != $userId){
            throw new Exception("Id provided does not match with logged user id.");
        }

        $where = ["user" => $userId];
        $notifications = Core::database()->selectMultiple(self::TABLE_NOTIFICATION, $where);
        foreach ($notifications as &$notification){
            $notification = self::parse($notification);
        }

        return $notifications;
    }

    public static function getNotificationsByCourse(int $courseId): array
    {
        $table = self::TABLE_NOTIFICATION;
        $where = ["course" => $courseId];
        $notifications = Core::database()->selectMultiple($table, $where);

        foreach ($notifications as &$notification)
        {
            $notification = self::parse($notifications);
        }

        return $notifications;
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports notifications into the system from a .csv file.
     * Returns the nr. of notifications imported.
     *
     * @param string $file
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importNotifications(string $file, bool $replace = true): int
    {
        return Utils::importFromCSV(self::HEADERS, function ($notification, $indexes) use ($replace) {
            $course = self::parse(null, Utils::nullify($notification[$indexes["course"]]), "course");
            $user = self::parse(null, Utils::nullify($notification[$indexes["user"]]), "user");
            $message = Utils::nullify($notification[$indexes["message"]]);
            $showed = Utils::nullify($notification[$indexes["showed"]]);

            // TODO

        }, $file);
    }

    /**
     * Exports notifications from the system to a .csv file
     *
     * @param array $notificationIds
     * @return string
     */
    public static function exportNotifications(array $notificationIds): string
    {
        $notificationsToExport = array_values(array_filter(self::getNotifications(), function ($notification) use ($notificationIds)
        { return in_array($notification["id"], $notificationIds); }));
        return Utils::exportToCSV(
            $notificationsToExport,
            function ($notification) {
                return [$notification["course"], $notification["user"], $notification["message"], +$notification["showed"]];
            },
            self::HEADERS);
    }

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a notification coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $notification
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    public static function parse(array $notification = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course"];
        $stringValues = ["message"];

        return Utils::parse(["int" => $intValues, "string" => $stringValues], $notification, $field, $fieldName);
    }

    /**
     * Trims notification parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values){
        $params = ["message"];
        Utils::trim($params, ...$values);
    }
}