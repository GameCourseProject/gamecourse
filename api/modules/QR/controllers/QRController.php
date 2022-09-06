<?php
namespace API;

use Exception;
use GameCourse\Module\QR\ClassType;
use GameCourse\Module\QR\QR;

/**
 * This is the QR controller, which holds API endpoints for
 * QR class participation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="QR",
 *     description="API endpoints for QR class participation"
 * )
 */
class QRController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets all in-class participations.
     *
     * @throws Exception
     */
    public function getClassParticipations()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $QRModule = new QR($course);
        API::response($QRModule->getQRParticipations());
    }

    /**
     * Gets all QR code errors.
     *
     * @throws Exception
     */
    public function getQRCodeErrors()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $QRModule = new QR($course);
        API::response($QRModule->getQRErrors());
    }

    /**
     * Gets all class types registered in the system.
     *
     * @return void
     */
    public function getClassTypes()
    {
        API::response(ClassType::getTypes());
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Actions ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Generates a given number of QR codes.
     *
     * @throws Exception
     */
    public function generateQRCodes()
    {
        API::requireValues("courseId", "nrCodes");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $nrCodes = API::getValue("nrCodes", "int");
        $QRModule = new QR($course);
        API::response($QRModule->generateQRCodes($nrCodes));
    }

    /**
     * Submits a class participation for a given user.
     *
     * @throws Exception
     */
    public function submitQRParticipation()
    {
        API::requireValues("courseId", "userId", "lectureNr", "typeOfClass");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        $qrKey = API::getValue("key");
        if (!$qrKey) API::requireCourseAdminPermission($course);
        else API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $lectureNr = API::getValue("lectureNr", "int");
        $typeOfClass = API::getValue("typeOfClass");

        $QRModule = new QR($course);
        $QRModule->submitQRParticipation($userId, $lectureNr, $typeOfClass, $qrKey);
    }
}
