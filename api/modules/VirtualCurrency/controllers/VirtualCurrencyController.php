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

    public function hasExchangedUserTokens()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $courseUser = API::verifyCourseUserExists($course, $userId);

        $VCModule = new VirtualCurrency($course);
        API::response($VCModule->hasExchanged($userId));
    }

    /**
     * Exchanges tokens for XP.
     *
     * @return void
     * @throws Exception
     */
    public function exchangeUserTokens()
    {
        API::requireValues("courseId", "userId", "ratio", "threshold", "extra");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $parts = explode(":", API::getValue("ratio"));
        $ratio = intval($parts[0]) / intval($parts[1]);
        $threshold = API::getValue("threshold", "int");
        $extra = API::getValue("extra", "bool");

        $VCModule = new VirtualCurrency($course);
        $earnedXP = $VCModule->exchangeTokensForXP($userId, $ratio, $threshold, $extra);
        API::response($earnedXP);
    }
}
