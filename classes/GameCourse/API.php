<?php
namespace GameCourse;

class API {
    private static $functions = array();
    private static $requestModule;
    private static $requestFunction;
    private static $values = array();
    private static $uploadedFile = null;
    
    public static function getUploadedFile() {
        return static::$uploadedFile;
    }

    public static function error($message, $status = 400) {
        if ($status != null)
            http_response_code($status);
        echo json_encode(array('error' => $message));
        exit;
    }

    public static function response($data, $status = null) {
        if ($status != null)
            http_response_code($status);
        echo json_encode(array('data' => $data));
        exit;
    }

    public static function registerFunction($module, $request, $function) {
        if (!array_key_exists($module, static::$functions))
            static::$functions[$module] = array();
        if (array_key_exists($request, static::$functions[$module]))
            static::error('API functions overlap - ' . $module . ' - '. $request);
        static::$functions[$module][$request] = $function;
    }
    
    //only allows acess to admins and users of the course
    public static function requireCoursePermission() {
        API::requireValues('course');
        $courseUser = Course::getCourse(API::getValue('course'), false)->getLoggedUser();
        $isCourseUser = (!is_a($courseUser, "GameCourse\NullCourseUser"));
        if (!Core::getLoggedUser()->isAdmin() && !$isCourseUser) {
            API::error('You don\'t have permission acess this course!', 401);
            //GameCourse::log('WARNING: Unauthorized attempt to login into settings. UserID=' . (GameCourse::getLoggedUserID() != null ? GameCourse::getLoggedUserID() : 'None'));
        }
    }
    
    //only allows acess to admins and teachers of the course
    public static function requireCourseAdminPermission() {
        API::requireValues('course');
        $courseAdmin = Course::getCourse(API::getValue('course'), false)->getLoggedUser()->hasRole('Teacher');
        if (!Core::getLoggedUser()->isAdmin() && !$courseAdmin) {
            API::error('You don\'t have permission to request this!', 401);
            //GameCourse::log('WARNING: Unauthorized attempt to login into settings. UserID=' . (GameCourse::getLoggedUserID() != null ? GameCourse::getLoggedUserID() : 'None'));
        }
    }
    
    //only allows acess to admins
    public static function requireAdminPermission() {
        if (!Core::getLoggedUser()->isAdmin())
            API::error('Insufficient permissions to run this API function', 401);
    }

    public static function gatherRequestInfo() {
        $values = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (array_key_exists('uploadFile', $_GET)) {
                if ($_SERVER['CONTENT_LENGTH'] <= 5000000)
                    static::$uploadedFile = file_get_contents('php://input');
                else {
                    API::error('File too big, limit: 5MB', 413);
                }
            } else
                $values = json_decode(file_get_contents('php://input'), true);
        }
                    
        if (!array_key_exists('module', $_GET))
            API::error('Must specify a module!');

        if (!array_key_exists('request', $_GET))
            API::error('Must specify a request!');

        static::$requestModule = $_GET['module'];
        static::$requestFunction = $_GET['request'];
        unset($_GET['module']);
        unset($_GET['request']);
        
        $values = ($values == null) ? $_GET : array_merge($values, $_GET);
           
        static::$values = $values;
 
        if (API::hasKey('course') && (is_int(API::getValue('course')) || ctype_digit(API::getValue('course')))) {
            Course::getCourse(API::getValue('course'));
        }
        
        //Course::getCourse(1); // initializes the course
    }

    public static function processRequest() {
        if (!array_key_exists(static::$requestModule, static::$functions))
            static::error('Unknown module', 404);

        $moduleFunctions = static::$functions[static::$requestModule];

        if (!array_key_exists(static::$requestFunction, $moduleFunctions))
            static::error('Unknown request', 404);

        $func = $moduleFunctions[static::$requestFunction];

        $func();
    }

    public static function requireValues(...$keys) {
        foreach($keys as $key) {
            if (!static::hasKey($key))
                static::error('Missing key ' . $key);
        }
    }

    public static function hasKey($key) {
        return array_key_exists($key, static::$values);
    }

    public static function getValue($key) {
        return array_key_exists($key, static::$values) ? static::$values[$key] : null;
    }

    public static function getValues() {
        return static::$values;
    }
}
