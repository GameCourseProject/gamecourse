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
     * Gets all unused QR codes.
     *
     * @throws Exception
     */
    public function getUnusedQRCodes()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $QRModule = new QR($course);
        API::response($QRModule->getUnusedQRCodes());
    }

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
     * Deletes a given QR code.
     *
     * @throws Exception
     */
    public function deleteQRCode()
    {
        API::requireValues("courseId", "key");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $qrKey = API::getValue("key");

        $QRModule = new QR($course);
        $QRModule->deleteQRCode($qrKey);
    }

    /**
     * Adds a class participation for a given user.
     *
     * @throws Exception
     */
    public function addQRParticipation()
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
        $QRModule->addQRParticipation($userId, $lectureNr, $typeOfClass, $qrKey);
    }

    /**
     * Edits a class participation.
     *
     * @throws Exception
     */
    public function editQRParticipation()
    {
        API::requireValues("courseId", "key", "lectureNr", "typeOfClass");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $qrKey = API::getValue("key");
        $lectureNr = API::getValue("lectureNr", "int");
        $typeOfClass = API::getValue("typeOfClass");

        $QRModule = new QR($course);
        $QRModule->editQRParticipation($qrKey, $lectureNr, $typeOfClass);
    }

    /**
     * Deletes a class participation.
     *
     * @throws Exception
     */
    public function deleteQRParticipation()
    {
        API::requireValues("courseId", "key");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $qrKey = API::getValue("key");

        $QRModule = new QR($course);
        $QRModule->deleteQRParticipation($qrKey);
    }
}
