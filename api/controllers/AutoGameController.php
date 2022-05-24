<?php
namespace API;

use GameCourse\AutoGame\AutoGame;

/**
 * This is the AutoGame controller, which holds API endpoints for
 * AutoGame related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="AutoGame",
 *     description="API endpoints for AutoGame related actions"
 * )
 */
class AutoGameController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getLastRun()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);
        API::response(AutoGame::getLastRun($courseId));
    }
}
