<?php
/**
 * This is the Notifications script, which sends notifications
 * to students.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Course\Course;
use GameCourse\Core\Core;
use GameCourse\NotificationSystem\Notification;

require __DIR__ . "/../../../../inc/bootstrap.php";

// Get the scheduled notification
$notificationId = $argv[1];
$notification = Core::database()->select(Notification::TABLE_NOTIFICATION_SCHEDULED, ["id" => $notificationId]);

$courseId = $notification["course"];
$course = Course::getCourseById($courseId);

$roles = $notification["roles"];
$arrayRoles = explode(',', $roles);

foreach ($arrayRoles as $role) {
    $users = $course->getCourseUsersWithRole(true, $role);

    foreach ($users as $user) {
        Notification::addNotification($courseId, $user["id"], $notification["message"]);
    }
}