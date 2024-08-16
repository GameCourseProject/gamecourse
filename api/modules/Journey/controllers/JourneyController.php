<?php
namespace API;

use Exception;
use GameCourse\Module\Journey\Journey;
use GameCourse\Module\Journey\JourneyPath;

/**
 * This is the Journey controller, which holds API endpoints for
 * journey related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Journey",
 *     description="API endpoints for journey related actions"
 * )
 */
class JourneyController
{
    /*** --------------------------------------------- ***/
    /*** --------------- JourneyPaths ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets all Journey paths of a given course.
     *
     * @throws Exception
     */
    public function getJourneyPaths()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        API::response(JourneyPath::getJourneyPaths($courseId));
    }

    /**
     * @throws Exception
     */
    public function createJourneyPath()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues("name", "color");

        // Get values
        $name = API::getValue("name");
        $color = API::getValue("color");

        // Add new path
        JourneyPath::addJourneyPath($courseId, $name, $color);
    }

    /**
     * @throws Exception
     */
    public function editJourneyPath()
    {
        API::requireValues("courseId", "pathId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);
        API::requireValues("name", "color");

        // Get values
        $name = API::getValue("name");
        $color = API::getValue("color");
        $pathId = API::getValue("pathId", "int");

        // Edit path
        $path = JourneyPath::getJourneyPathById($pathId);
        $path->editJourneyPath($name, $color);
    }

    /**
     * @throws Exception
     */
    public function deleteJourneyPath()
    {
        API::requireValues("courseId", "pathId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $pathId = API::getValue("pathId", "int");
        JourneyPath::deleteJourneyPath($pathId);
    }

}