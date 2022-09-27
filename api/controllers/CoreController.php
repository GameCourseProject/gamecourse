<?php
namespace API;

use Exception;
use Utils\Utils;

/**
 * This is the Core controller, which holds API endpoints for
 * core actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Core",
 *     description="API endpoints for core actions"
 * )
 */
class CoreController
{
    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Deletes a given file after download is complete.
     *
     * @param string path
     * @param int $courseId (optional)
     * @throws Exception
     */
    public function cleanAfterDownloading()
    {
        API::requireValues("path");

        $courseId = API::getValue("courseId");
        if ($courseId) {
            $course = API::verifyCourseExists($courseId);
            API::requireCourseAdminPermission($course);

        } else API::requireAdminPermission();

        $path = API::getValue("path");
        $path = str_replace("/temp/", "", $path);

        // NOTE: this prevents attacks that try to delete folders outside /temp
        $path = str_replace("../", "", $path);

        $parts = explode(DIRECTORY_SEPARATOR, $path);
        if (count($parts) == 1) $parts = explode("/", $path);
        if (count($parts) == 1) $parts = explode("\/", $path);
        if (count($parts) == 1) $parts = explode("\\", $path);

        $from = $parts[0];
        $filename = $parts[1];

        $tempFolder = ROOT_PATH . "temp/";
        Utils::deleteFile($tempFolder . $from, $filename);
        if (Utils::getDirectorySize($tempFolder) == 0)
            Utils::deleteDirectory($tempFolder);
    }
}
