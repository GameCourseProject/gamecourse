<?php

namespace API;

use Exception;
use GameCourse\Core\Core;
use GameCourse\NotificationSystem\Notification;

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
    /*public function createNotification()
    {
        API::requireValues('course', 'user', 'message', 'showed');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course); // Not sure


        // Add notification to system
        $notification = Notification::addNotification($courseId, "This is the notification message");

        $notificationInfo = $notification->getData();
        API::response($notificationInfo);
    }*/

    /**
     * Gets all notification given a course -- CHANGE LATER
     *
     * @throws Exception
     */
    public function getNotifications()
    {
        API::requireValues('course');

        $courseId = API::getValue("course", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireAdminPermission($course); // Not sure

        // Get notifications
        $notifications = Notification::getNotifications($courseId);
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