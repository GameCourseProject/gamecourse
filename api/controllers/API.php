<?php
namespace API;

use Error;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\User\CourseUser;
use GameCourse\User\User;
use GameCourse\Views\Page\Page;

/**
 * Main API controller that holds functions to process an API request,
 * set permissions for a given endpoint and make verifications.
 *
 * @OA\Info(
 *     title="GameCourse API",
 *     description="<h2>Authentication</h2><p>This API is only available to authorized users. You must be logged in to GameCourse to make requests.</p>",
 *     version=API_VERSION
 * )
 * @OA\Server(
 *     url=API_URL,
 *     description="Primary"
 * )
 */
class API
{
    private static $module;     // request module
    private static $endpoint;   // request endpoint
    private static $values;     // request values


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Request Processing ---------------- ***/
    /*** ---------------------------------------------------- ***/

    public static function gatherRequestInfo() {
        $values = null;

        if ($_SERVER['REQUEST_METHOD'] != 'GET')
            $values = json_decode(file_get_contents('php://input'), true);

        if (!array_key_exists('module', $_GET))
            self::error('Must specify a module!');

        if (!array_key_exists('request', $_GET))
            self::error('Must specify a request!');

        static::$module = $_GET['module'];
        static::$endpoint = $_GET['request'];
        unset($_GET['module']);
        unset($_GET['request']);

        static::$values = ($values == null) ? $_GET : array_merge($values, $_GET);
    }

    public static function processRequest() {
        try {
            $controllerClass = "\\API\\" . ucfirst(self::$module) . "Controller";
            $controller = new $controllerClass();
            $controller->{self::$endpoint}();

        } catch (Error $error) {
            $msg = $error->getMessage();
            if ($msg == "Class '" . $controllerClass . "' not found")
                self::error("Unknown request module '" . self::$module . "'", 404);

            if ($msg == "Call to undefined method " . substr($controllerClass, 1) . "::" . self::$endpoint . "()")
                self::error("Unknown request endpoint '" . self::$endpoint . "'", 404);
        }
    }

    public static function requireValues(...$keys) {
        foreach($keys as $key) {
            if (!static::hasKey($key))
                static::error('Missing key ' . $key);
        }
    }

    public static function hasKey($key): bool
    {
        return array_key_exists($key, static::$values);
    }

    public static function getValue($key, $type = null) {
        if (array_key_exists($key, static::$values)) {
            $value = static::$values[$key];
            if ($type == "bool") return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            elseif ($type == "int") return intval($value);
            return $value;

        } else return null;
    }

    public static function getValues(): array
    {
        return static::$values;
    }

    public static function error($message, $status = 400) {
        if ($status != null) http_response_code($status);
        echo json_encode(array('error' => $message));
        exit;
    }

    public static function response($data, $status = null) {
        if ($status !== null) http_response_code($status);
        echo json_encode(array('data' => $data));
        exit;
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Permissions ----------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Only allow access to admins of the system.
     */
    public static function requireAdminPermission()
    {
        $loggedUser = Core::getLoggedUser();
        if (!$loggedUser)
            API::error("You don't have permission to request this - no user currently logged in.", 403);

        if (!$loggedUser->isAdmin())
            API::error("You don't have permission to request this - only Admins can.", 403);
    }

    /**
     * Only allow access to admins and users of the course.
     */
    public static function requireCoursePermission(Course $course) {
        $loggedUser = Core::getLoggedUser();
        $courseUser = $course->getCourseUserById($loggedUser->getId());
        if (!$loggedUser->isAdmin() && !$courseUser)
            API::error("You don't have permission to access this course.", 403);
    }

    /**
     * Only allow access to admins and teachers of the course.
     * @throws Exception
     */
    public static function requireCourseAdminPermission(Course $course) {
        $loggedUser = Core::getLoggedUser();
        $courseUser = $course->getCourseUserById($loggedUser->getId());
        $courseAdmin = $courseUser && $courseUser->isTeacher();
        if (!$loggedUser->isAdmin() && !$courseAdmin)
            API::error("You don't have permission to request this - only course admins can.", 403);
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Verifications ---------------- ***/
    /*** ----------------------------------------------- ***/

    public static function verifyUserExists(int $userId): User
    {
        $user = User::getUserById($userId);
        if (!$user)
            API::error('There is no user with ID = ' . $userId . '.', 404);
        return $user;
    }

    public static function verifyCourseExists(int $courseId): Course
    {
        $course = Course::getCourseById($courseId);
        if (!$course)
            API::error('There is no course with ID = ' . $courseId . '.', 404);
        return $course;
    }

    public static function verifyCourseUserExists(Course $course, int $userId): CourseUser
    {
        $courseUser = new CourseUser($userId, $course);
        if (!$courseUser->exists())
            API::error("There is no user with ID = " . $userId . " in course with ID = " . $course->getId() . ".", 404);
        return $courseUser;
    }

    public static function verifyModuleExists(string $moduleId, ?Course $course): Module
    {
        if ($course) {
            $module = $course->getModuleById($moduleId);
            if (!$module)
                API::error("There is no module with ID = " . $moduleId . " in course with ID = " . $course->getId() . ".", 404);

        } else {
            $module = Module::getModuleById($moduleId, null);
            if (!$module)
                API::error("There is no module with ID = " . $moduleId . " in the system.", 404);
        }
        return $module;
    }

    public static function verifyPageExists(int $pageId): Page
    {
        $page = Page::getPageById($pageId);
        if (!$page)
            API::error('There is no page with ID = ' . $pageId . '.', 404);
        return $page;
    }
//
//    public static function verifyPageExists(int $courseId, int $pageId): array
//    {
//        $course = self::verifyCourseExists($courseId);
//        $page = Views::getPage($courseId, $pageId);
//        if (!$page)
//            API::error('Page with id = ' . $pageId . ' doesn\'t exist in course \'' . $course->getName() . '\'');
//        return $page;
//    }
//
//    public static function verifyTemplateExists(int $courseId, int $templateId): array
//    {
//        $course = self::verifyCourseExists($courseId);
//        if (!Views::templateExists($courseId, null, $templateId))
//            API::error('There is no template with id = ' . $templateId . ' in course \'' . $course->getName() . '\'');
//        return Views::getTemplate($templateId);
//    }
//
//    public static function verifySkillExists(int $courseId, int $skillId)
//    {
//        $course = self::verifyCourseExists($courseId);
//        if (empty(Core::$systemDB->select(Skills::TABLE, ["id" => $skillId])))
//            API::error('There is no skill with id = ' . $skillId . ' in course \'' . $course->getName() . '\'');
//    }
}
