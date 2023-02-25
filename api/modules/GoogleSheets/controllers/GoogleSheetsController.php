<?php
namespace API;

use GameCourse\Module\GoogleSheets\GoogleSheets;;

/**
 * This is the Google Sheets controller, which holds API endpoints for
 * Google Sheets configuration.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Google Sheets",
 *     description="API endpoints for Google Sheets configuration"
 * )
 */
class GoogleSheetsController
{
    /*** --------------------------------------------- ***/
    /*** ------------------- Config ------------------ ***/
    /*** --------------------------------------------- ***/

    public function getConfig()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $googleSheets = new GoogleSheets($course);
        API::response(["config" => $googleSheets->getGoogleSheetsConfig(), "needsAuth" => !$googleSheets->getAccessToken()]);
    }

    public function saveConfig()
    {
        API::requireValues("courseId", "spreadsheetId", "sheetNames", "ownerNames");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $spreadsheetId = API::getValue("spreadsheetId");
        $sheetNames = API::getValue("sheetNames", "array");
        $ownerNames = API::getValue("ownerNames", "array");

        $googleSheets = new GoogleSheets($course);
        $googleSheets->saveGoogleSheetsConfig($spreadsheetId, $sheetNames, $ownerNames);
    }


    /*** --------------------------------------------- ***/
    /*** -------------- Authentication --------------- ***/
    /*** --------------------------------------------- ***/

    public function authenticate()
    {
        API::requireValues("courseId", "credentials");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $credentials = API::getValue("credentials");
        $googleSheets = new GoogleSheets($course);

        $authURL = $googleSheets->saveCredentials($credentials);
        API::response($authURL);
    }
}
