<?php
namespace API;

use Exception;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;

/**
 * This is the Virtual Currency controller, which holds API endpoints for
 * QR class participation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Virtual Currency",
 *     description="API endpoints for QR class participation"
 * )
 */
class VirtualCurrencyController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets virtual currency name.
     *
     * @throws Exception
     */
    public function getVCName()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $VCModule = new VirtualCurrency($course);
        API::response($VCModule->getVCName());
    }


    /*** --------------------------------------------- ***/
    /*** ------------------- Users ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets user tokens of a given user.
     *
     * @throws Exception
     */
    public function getUserTokens()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $VCModule = new VirtualCurrency($course);
        API::response($VCModule->getUserTokens($userId));
    }
}
