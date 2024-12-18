<?php

namespace GameCourse\NotificationSystem;

use DateTime;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewType\ViewType;
use Utils\Utils;
use Utils\CronJob;

/**
 * This is the Notification model, which implements the necessary methods
 * to interact with notifications in the MySQL database.
 */
class Notification
{
    const TABLE_NOTIFICATION = "notification";
    const TABLE_NOTIFICATION_CONFIG = "notification_config";
    const TABLE_NOTIFICATION_DESCRIPTIONS = "notification_module_description";
    const TABLE_NOTIFICATION_SCHEDULED = "notification_scheduled";

    protected $id;

    const LOGS_FOLDER = "notifications";

    const HEADERS = [ // headers for import/export functionality
        "course", "user", "message", "isShowed"
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

    public function getCourse() : int
    {
        return $this->getData("course");
    }

    public function getUser(): int
    {
        return $this->getData("user");
    }

    public function getMessage(): string
    {
        return $this->getData("message");
    }

    public function isShowed(): bool
    {
        return $this->getData("isShowed");
    }

    public function getDateCreated(): string {
        return $this->getData("dateCreated");
    }

    public function getDateSeen(): string {
        return $this->getData("dateSeen");
    }

    /*** ---------------------------------------------------- ***/
    /*** --------------------- Setters ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setShowed(bool $isShowed, string $date){
        $this->setData(["isShowed" => +$isShowed, "dateSeen" => $date]);
    }

    /**
     * @throws Exception
     */
    public function setMessage(string $message){
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

        if (key_exists("message", $fieldValues)) self::validateMessage($fieldValues["message"]);

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_NOTIFICATION, $fieldValues, ["id" => $this->id]);

    }

    /*** ------------------------------------------------------------ ***/
    /*** -------------------------- Logging ------------------------- ***/
    /*** ------------------------------------------------------------ ***/

    /**
     * Creates a new Notifications log on a given course.
     *
     * @param int $courseId
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function log(int $courseId, string $message, string $type)
    {
        $logsFile = self::getLogsFile($courseId, false);
        Utils::addLog($logsFile, $message, $type);
    }

    /**
     * Gets Notifications logs file for a given course.
     *
     * @param int $courseId
     * @param bool $fullPath
     * @return string
     */
    private static function getLogsFile(int $courseId, bool $fullPath = true): string
    {
        $path = self::LOGS_FOLDER . "/" . "notifications_$courseId.txt";
        if ($fullPath) return LOGS_FOLDER . "/" . $path;
        else return $path;
    }

    /*** ------------------------------------------------------------ ***/
    /*** ----------------- Notification Manipulation ---------------- ***/
    /*** ------------------------------------------------------------ ***/

    /**
     * Adds a new notification to the Notification System.
     * Returns the newly created notification.
     *
     * @param int $courseId
     * @param int $userId
     * @param string $message
     * @param bool $isShowed
     * @return Notification
     *
     * @throws Exception
     */
    public static function addNotification(int $courseId, int $userId, string $message, bool $isShowed = false) : Notification
    {
        // Translate Expression Language
        $viewType = ViewType::getViewTypeById("text");
        $view = ["text" => $message];
        $viewType->compile($view);
        $visitor = new EvaluateVisitor(["course" => $courseId, "viewer" => $userId, "user" => $userId]);
        Core::dictionary()->setVisitor($visitor);
        $viewType->evaluate($view, $visitor);
        $message = $view["text"];

        self::trim($message);
        self::validateNotification($message, $isShowed);

        // Insert in database
        $dateCreated = new DateTime();
        $id = Core::database()->insert(self::TABLE_NOTIFICATION, [
            "course" => $courseId,
            "user" => $userId,
            "message" => $message,
            "isShowed" => +$isShowed,
            "dateCreated" => $dateCreated->format("Y-m-d H:i:s")
        ]);

        return new Notification($id);
    }

    /**
     * Edits an existing notification in the Notification System.
     * Returns the edited notification.
     *
     * @param int $courseId
     * @param int $userId
     * @param string $message
     * @param bool $isShowed
     * @return Notification
     * @throws Exception
     */
    public function editNotification(int $courseId, int $userId, string $message, bool $isShowed) : Notification
    {
        $this->setData([
            "course" => $courseId,
            "user" => $userId,
            "message" => $message,
            "isShowed" => +$isShowed,
        ]);

        return $this;
    }

    /**
     * Removes a notification from the Notification System.
     *
     * @param int $notificationId
     * @return void
     * @throws Exception
     */
    public static function removeNotification(int $notificationId)
    {
        Core::database()->delete(self::TABLE_NOTIFICATION, ["id" => $notificationId]);
        // Event::trigger(EventType::NOTIFICATION_REMOVED, $notificationId);

    }

    /**
     * Schedules a notification to be sent later.
     *
     * @param int $courseId
     * @param array $roles
     * @param string $message
     * @param string $frequency
     * @return void
     * @throws Exception
     */
    public static function scheduleNotification(int $courseId, array $roles, string $message, string $frequency)
    {
        $notificationId = Core::database()->insert(self::TABLE_NOTIFICATION_SCHEDULED, 
            ["course" => $courseId, "roles" => implode(',', $roles), "message" => $message, "frequency" => $frequency]);
        $script = ROOT_PATH . "models/GameCourse/NotificationSystem/scripts/ScheduledNotificationsScript.php";
        new CronJob($script, $frequency, $notificationId);
    }

    /**
     * Cancels a scheduled notification.
     *
     * @param int $notificationId
     * @return void
     * @throws Exception
     */
    public static function cancelScheduledNotification(int $notificationId)
    {
        $script = ROOT_PATH . "models/GameCourse/NotificationSystem/scripts/ScheduledNotificationsScript.php";
        CronJob::removeCronJob($script, $notificationId);
        Core::database()->delete(self::TABLE_NOTIFICATION_SCHEDULED, ["id" => $notificationId]);
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
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates notification parameters.
     *
     * @param $message
     * @param $isShowed
     * @return void
     * @throws Exception
     */
    private static function validateNotification($message, $isShowed)
    {
        self::validateMessage($message);

        if (!is_bool($isShowed))
            throw new Exception("'isShowed' must be either true or false.");
    }

    /**
     * Validates rule name.
     *
     * @param $message
     * @return void
     * @throws Exception
     */
    private static function validateMessage($message)
    {
        if (!is_string($message) || empty(trim($message)))
            throw new Exception("Notification message can't be null neither empty");

        if (iconv_strlen($message) > 150)
            throw new Exception("Notification message is too long: maximum of 150 characters.");
    }

    /*** ---------------------------------------------------- ***/
    /*** --------------------- General ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets notifications in the Notification System for a given course.
     * Returns null if notification doesn't exist.
     *
     * @param bool $isShowed (optional)
     * @return array
     */
    public static function getNotifications(?bool $isShowed = null): array
    {
        $where = [];
        if ($isShowed !== null) $where["isShowed"] = $isShowed;
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
            $notification = self::parse($notification);
        }

        return $notifications;
    }

    public static function getScheduledNotificationsByCourse(int $courseId): array
    {
        $table = self::TABLE_NOTIFICATION_SCHEDULED;
        $where = ["course" => $courseId];
        $notifications = Core::database()->selectMultiple($table, $where);

        return $notifications;
    }

    public static function isNotificationInDB(int $course, int $user, string $message): bool
    {
        $table = self::TABLE_NOTIFICATION;
        $where = ["course" => $course, "user" => $user, "message" => $message];
        $notification = Core::database()->select($table, $where);

        if ($notification) return true;
        else return false;
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
            $isShowed = Utils::nullify($notification[$indexes["isShowed"]]);

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
                return [$notification["course"], $notification["user"], $notification["message"], +$notification["isShowed"]];
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
     * @return void
     */
    private static function trim(mixed &...$values){
        $params = ["message"];
        Utils::trim($params, ...$values);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Modules --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Enables/disables notifications of a module in a course
     *
     * @param int $courseId
     * @param string $moduleId
     * @param bool $isEnabled
     * @param string $frequency
     * @param string $format
     * @return void
     * @throws Exception
     */
    public static function setModuleNotifications(int $courseId, string $moduleId, bool $isEnabled, string $frequency, string $format)
    {
        if (!(new Course($courseId))->getModuleById($moduleId)->isEnabled())
            throw new Exception("Course with ID = " . $courseId . " does not have " 
                . $moduleId . " enabled: can't change Notification settings related to it.");

        Core::database()->update(self::TABLE_NOTIFICATION_CONFIG, 
            ["isEnabled" => $isEnabled ? "1" : "0", "frequency" => $frequency, "format" => $format],
            ["course" => $courseId, "module" => $moduleId]);

        $script = ROOT_PATH . "models/GameCourse/NotificationSystem/scripts/ModuleNotificationsScript.php";
        if ($isEnabled) {
            new CronJob($script, $frequency, $courseId, $moduleId);
        } else {
            CronJob::removeCronJob($script, $courseId, $moduleId);
        }
    }

    /**
     * Gets configuration of a module's notifications
     *
     * @param int $courseId
     * @return void
     */
    public static function getModuleNotificationsConfig(int $courseId): array
    {
        $table = self::TABLE_NOTIFICATION_CONFIG . " c JOIN " . self::TABLE_NOTIFICATION_DESCRIPTIONS . " d on c.module=d.module";
        $configs = Core::database()->selectMultiple($table, ["course" => $courseId], "c.module, isEnabled, frequency, format, description, variables");

        foreach ($configs as &$module) {
            $module["id"] = $module["module"];
            unset($module["module"]);
            
            $module["name"] = (Module::getModuleById($module["id"], Course::getCourseById($courseId)))->getName();
            $module["isEnabled"] = $module["isEnabled"] == "1";
            $module["variables"] = explode(",", $module["variables"]);
        }
        return $configs;
    }

    /**
     * Passes the parameters into the text and checks if notification was already sent before
     *
     * @param int $courseId
     * @param int $userId
     * @param string $notification
     * @param array $params
     * @return string
     * @throws Exception
     */
    public static function getFinalNotificationText(int $courseId, int $userId, string $notification, array $params): ?string {
        foreach (array_keys($params) as $key) {
            $notification = str_replace("%" . $key, $params[$key], $notification);
        }

        // Check if notification is new
        $alreadySent = Core::database()->select(Notification::TABLE_NOTIFICATION, ["course" => $courseId, "user" => $userId, "message" => $notification]);
        if (!$alreadySent) {
            return $notification;
        }

        return null;
    }
}