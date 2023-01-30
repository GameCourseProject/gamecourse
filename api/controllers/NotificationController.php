<?php

namespace API;

use Exception;
use GameCourse\Core\Core;
use GameCourse\NotificationSystem\Notification;

/**
 * This is the Notification controller, which holds API endpoints for
 * notification related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Notification",
 *     description="API endpoints for user related actions"
 * )
 */
class NotificationController
{

    /**
     * Gets notifications by its ID
     *
     * @param int $notificationId
     */
    public function getNotificationById()
    {
        API::requireValues("notificationId");

        $notificationId = API::getValue("notificationId", "int");
        $notification = Notification::getNotificationById($notificationId);

        $notificationInfo = $notification->getData();

        API::response($notificationInfo);
    }

    /**
     * Gets notifications by its user ID
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function getNotificationsByUser()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $notifications = Notification::getNotificationsByUser($userId);
        foreach ($notifications as &$notificationInfo){
            Notification::getNotificationById($notificationInfo["id"]);
        }

        API::response($notifications);

    }

    /**
     * Gets notifications by its course ID
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function getNotificationsByCourse()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        $notifications = Notification::getNotificationsByCourse($courseId);
        foreach ($notifications as &$notificationInfo){
            Notification::getNotificationById($notificationInfo["id"]);
        }

        API::response($notifications);
    }


    /**
     * Creates a new notification in the system
     *
     * @throws Exception
     */
    public function createNotification()
    {
        API::requireValues("course", "user", "message", "isShowed");

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course); // Not sure if it needs admin permission

        $userId = API::getValue("user", "int");
        $user = API::verifyUserExists($userId);

        $message = API::getValue("message");
        $isShowed = API::getValue("isShowed");

        // Add notification to system
        $notification = Notification::addNotification($courseId, $userId, $message, $isShowed);

        $notificationInfo = $notification->getData();
        API::response($notificationInfo);
    }

    /**
     * Edits notification in the system
     *
     * @throws Exception
     */
    public function editNotification()
    {
        API::requireValues('id', 'course', 'user', 'message', 'isShowed');

        // Get values
        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("user", "int");
        $user = API::verifyUserExists($userId);

        $message = API::getValue("message");
        $isShowed = API::getValue("isShowed");

        $notificationId = API::getValue("id", "int");
        $notification = Notification::getNotificationById($notificationId);


        // Edit notification
        $notification->editNotification($courseId, $userId, $message, $isShowed);

        $notificationInfo = $notification->getData();
        API::response($notificationInfo);
    }

    /**
     * Edits notification in the system
     *
     * @throws Exception
     */
    public function removeNotification()
    {
        API::requireValues('notificationId');

        $notificationId = API::getValue("notificationId", "int");
        $notification = Notification::getNotificationById($notificationId);
        $notification->removeNotification($notificationId);
    }


    /**
     * Gets all notifications from the system
     *
     * @param bool $isShowed (optional)
     *
     * @throws Exception
     */
    public function getNotifications()
    {
        // DOES IT NEED ADMIN PERMISSION ??
        // API::requireAdminPermission();

        $isShowed = API::getValue("isShowed");

        $notifications = Notification::getNotifications($isShowed);
        foreach ($notifications as &$notificationInfo){
            Notification::getNotificationById($notificationInfo["id"]);
        }
        API::response($notifications);

    }


    /**
     * Sets notification showed status
     *
     * @throws Exception
     */
    public function setShowed()
    {
        API::requireValues("notificationId", "isShowed");

        $notificationId = API::getValue("notificationId", "int");
        $notification = Notification::getNotificationById($notificationId);

        $isShowed = API::getValue("isShowed", "bool");
        $notification->setShowed($isShowed);

        $notificationInfo = $notification->getData();
        API::response($notificationInfo);
    }

    /**
     * Checks whether a notification is showed or not
     *
     * @throws Exception
     */
    public function isShowed()
    {
        API::requireValues("notificationId");

        $notificationId = API::getValue("notificationId", "int");
        $notification = Notification::getNotificationById($notificationId);

        API::response($notification->isShowed());
    }

    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Import notifications into the system.
     *
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importNotifications()
    {
        API::requireAdminPermission();
        API::requireValues("file", "replace");

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrNotificationsImported = Notification::importNotifications($file, $replace);
        API::response($nrNotificationsImported);
    }

    /**
     * Export notifications from the system into a .csv file.
     *
     * @param $notificationsIds
     */
    public function exportNotifications()
    {
        API::requireValues("notificationIds");
        $notificationIds = API::getValue("notificationIds", "array");

        API::requireAdminPermission();
        $csv = Notification::exportNotifications($notificationIds);

        API::response($csv);
    }

}