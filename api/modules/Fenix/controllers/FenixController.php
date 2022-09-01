<?php
namespace API;

use Exception;
use GameCourse\Module\Fenix\Fenix;

/**
 * This is the Fenix controller, which holds API endpoints for
 * Fenix related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Fenix",
 *     description="API endpoints for Fenix related actions"
 * )
 */
class FenixController
{
    /**
     * Imports students into the course from a .csv file got
     * from Fénix with all students enrolled.
     *
     * @throws Exception
     */
    public function importFenixStudents()
    {
        API::requireValues("courseId", "file");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $fenixModule = new Fenix($course);
        API::response($fenixModule->importFenixStudents(API::getValue("file")));
    }
}
