<?php
namespace Api;

use Error;

/**
 * Main API controller that holds functions to process an API request,
 * set permissions for a given endpoint and make verifications.
 */
class API
{
    private static $module;     // request module
    private static $endpoint;   // request endpoint
    private static $values;     // request values


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Process Request ------------------ ***/
    /*** ---------------------------------------------------- ***/

    public static function gatherRequestInfo() {
        $values = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
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
            $controllerClass = "\\Api\\". ucfirst(self::$module) . "Controller";
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

    public static function getValue($key) {
        return array_key_exists($key, static::$values) ? static::$values[$key] : null;
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
        if ($status != null) http_response_code($status);
        echo json_encode(array('data' => $data));
        exit;
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Permissions ----------------- ***/
    /*** ----------------------------------------------- ***/

//    /**
//     * Only allows access to admins and users of the course.
//     */
//    public static function requireCoursePermission() {
//        API::requireValues('courseId');
//        $courseUser = Course::getCourse(API::getValue('courseId'), false)->getLoggedUser();
//        $isCourseUser = (!is_a($courseUser, "GameCourse\NullCourseUser"));
//        if (!Core::getLoggedUser()->isAdmin() && !$isCourseUser) {
//            API::error('You don\'t have permission to access this course.', 403);
//        }
//    }
//
//    /**
//     * Only allows access to admins and teachers of the course.
//     */
//    public static function requireCourseAdminPermission() {
//        API::requireValues('courseId');
//        $courseAdmin = Course::getCourse(API::getValue('courseId'), false)->getLoggedUser()->hasRole('Teacher');
//        if (!Core::getLoggedUser()->isAdmin() && !$courseAdmin) {
//            API::error('You don\'t have permission to request this - only course admins can.', 403);
//        }
//    }
//
//    /**
//     * Only allows access to admins.
//     */
//    public static function requireAdminPermission() {
//        if (!Core::getLoggedUser()->isAdmin())
//            API::error('You don\'t have permission to request this - only admins can.', 403);
//    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Verifications ---------------- ***/
    /*** ----------------------------------------------- ***/

//    public static function verifyUserExists(int $userId): User
//    {
//        $user = new User($userId);
//        if (!$user->exists())
//            API::error('There is no user with id = ' . $userId);
//        return $user;
//    }
//
//    public static function verifyCourseExists(int $courseId): Course
//    {
//        $course = Course::getCourse($courseId, false);
//        if (!$course->exists())
//            API::error('There is no course with id = ' . $courseId);
//        return $course;
//    }
//
//    public static function verifyCourseUserExists(int $courseId, int $userId): CourseUser
//    {
//        $course = self::verifyCourseExists($courseId);
//        $courseUser = new CourseUser($userId, $course);
//        if (!$courseUser->exists())
//            API::error('There is no user with id = ' . $userId . ' in course \'' . $course->getName() . '\'');
//        return $courseUser;
//    }
//
//    public static function verifyModuleExists(string $moduleId, int $courseId = null)
//    {
//        if (!is_null($courseId)) {
//            $course = self::verifyCourseExists($courseId);
//            $module = $course->getModule($moduleId);
//        } else $module = ModuleLoader::getModule($moduleId);
//
//        if ($module == null)
//            API::error('There is no module with id = ' . $moduleId);
//        return $module;
//    }
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
//
//    public static function verifyCourseIsActive(int $courseId) {
//        self::verifyCourseExists($courseId);
//        if (empty(Core::$systemDB->select("course", ["id" => $courseId, "isActive" => true])))
//            API::error('Course with id = ' . $courseId . ' is not active.');
//    }
}
