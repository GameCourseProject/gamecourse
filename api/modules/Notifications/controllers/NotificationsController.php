<?php
namespace API;

use GameCourse\Core\Core;
use GameCourse\Module\Notifications\Notifications;

/**
 * This is the Notifications controller, which holds API endpoints for
 * QR class participation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="API endpoints for QR class participation"
 * )
 */
class NotificationsController
{
    /*** --------------------------------------------- ***/
    /*** -------------- Progress Report -------------- ***/
    /*** --------------------------------------------- ***/

    public function getProgressReportConfig()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $notifications = new Notifications($course);
        API::response($notifications->getProgressReportConfig());
    }

    public function saveProgressReportConfig()
    {
        API::requireValues("courseId", "progressReport");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $progressReport = API::getValue("progressReport");
        $notifications = new Notifications($course);
        $notifications->saveProgressReportConfig($progressReport["endDate"], $progressReport["periodicityTime"], $progressReport["periodicityHours"], $progressReport["periodicityDay"], $progressReport["isEnabled"]);
    }

    public function getReports()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $notifications = new Notifications($course);
        API::response($notifications->getReports());
    }

    public function getStudentsWithReport()
    {
        API::requireValues("courseId", "seqNr");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $seqNr = API::getValue("seqNr", "int");
        $notifications = new Notifications($course);
        API::response($notifications->getStudentsWithReport($seqNr));
    }

    public function getStudentProgressReport()
    {
        API::requireValues("courseId", "userId", "seqNr");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("userId", "int");
        $seqNr = API::getValue("seqNr", "int");

        $notifications = new Notifications($course);
        API::response($notifications->getUserProgressReport($userId, $seqNr)[0]);
    }
}
