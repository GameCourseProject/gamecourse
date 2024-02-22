<?php
namespace API;

use GameCourse\Module\ProgressReport\ProgressReport;

/**
 * This is the Progress Report controller, which holds API endpoints for
 * progress reports related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Progress Report",
 *     description="API endpoints for progress reports"
 * )
 */
class ProgressReportController
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

        $progressReportModule = new ProgressReport($course);
        API::response($progressReportModule->getProgressReportConfig());
    }

    public function saveProgressReportConfig()
    {
        API::requireValues("courseId", "progressReport");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $progressReport = API::getValue("progressReport");
        $progressReportModule = new ProgressReport($course);
        $progressReportModule->saveProgressReportConfig($progressReport["frequency"], $progressReport["isEnabled"]);
    }

    public function getReports()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $progressReportModule = new ProgressReport($course);
        API::response($progressReportModule->getReports());
    }

    public function getStudentsWithReport()
    {
        API::requireValues("courseId", "seqNr");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $seqNr = API::getValue("seqNr", "int");
        $progressReportModule = new ProgressReport($course);
        API::response($progressReportModule->getStudentsWithReport($seqNr));
    }

    public function getStudentProgressReport()
    {
        API::requireValues("courseId", "userId", "seqNr");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $userId = API::getValue("userId", "int");
        $seqNr = API::getValue("seqNr", "int");

        $progressReportModule = new ProgressReport($course);
        API::response($progressReportModule->getUserProgressReport($userId, $seqNr)[0]);
    }
}
