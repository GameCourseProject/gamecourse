<?php
/**
 * This is the Notifications script, which sends notifications
 * to students.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\NotificationSystem\Notification;

require __DIR__ . "/../../../../inc/bootstrap.php";

$courseId = $argv[1];
$course = Course::getCourseById($courseId);

$moduleId = $argv[2];
$module = Module::getModuleById($moduleId, $course);

// Send notification to each course student
$students = $course->getStudents(true);
foreach ($students as $student) {
    $message = $module->getNotification($student["id"]);

    if ($message) {
        Notification::addNotification($courseId, $student["id"], $message);
    }
}