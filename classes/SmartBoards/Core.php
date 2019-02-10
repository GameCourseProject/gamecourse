<?php
namespace SmartBoards;

//use MagicDB\MagicDB;
//use MagicDB\MagicWrapper;
use MagicDB\SQLDB; 

require_once 'config.php';

require_once 'fenixedu-sdk/FenixEdu.class.php';
$_FENIX_EDU['access_key'] = FENIX_CLIENT_ID;
$_FENIX_EDU['secret_key'] = FENIX_CLIENT_SECRET;
$_FENIX_EDU['callback_url'] = FENIX_REDIRECT_URL;
$_FENIX_EDU['api_base_url'] = FENIX_API_BASE_URL;
$GLOBALS['_FENIX_EDU'] = $_FENIX_EDU;

class Core {
    public static $sistemDB;
    //public static $active_courses = array();
    //public static $courses = array();   //?
    public static $theme = 'default';
    public static $pending_invites = array();
    private static $apiKey;

    private static $loggedUser = null;
    private static $navigation = array();
    //private static $mainConfigDB = null;
    public static function isCLI() {
        return php_sapi_name() == 'cli';
    }

    public static function denyCLI() {
        if (static::isCLI()) {
            die('CLI access to this script is not allowed.');
        }
    }

    public static function init() {
        if (static::$sistemDB == null) {
            static::$sistemDB = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
        }

        /*if (static::$mainConfigDB != null)
            return;
        /*DataSchema::register(array(
            DataSchema::userFields(array(
                //DataSchema::makeField('id', 'User ID', '12345'),
                DataSchema::makeField('name', 'Name', 'Person Name'),
                DataSchema::makeField('email', 'Email', 'test@test.com'),
                DataSchema::makeField('username', 'Username', 'ist112345'),
            )),
            DataSchema::courseUserFields(array(
                DataSchema::makeField('id', 'User ID', '12345'),
                DataSchema::makeField('name', 'Name', 'Person Name'),
                DataSchema::makeArray('roles', 'Roles', DataSchema::makeField('role', 'Role', 'Teacher')),
                DataSchema::makeField('campus', 'Campus', 'A'),
                DataSchema::makeField('lastActivity', 'Last Activity', '1234567890'),
                DataSchema::makeField('previousActivity', 'Activity before last activity', '1234567890')
            ))
        ));
        static::initMainConfig();*/
    }

    public static function requireSetup($performSetup = true) {
        $setup = file_exists('setup.done');
        if (!$setup && $performSetup) {
            $setupOkay = (include 'pages/setup.php');
            if ($setupOkay == 'setup-done'){            
                return true; 
            }
            else
                exit;
        }
        return $setup;
    }

    public static function requireLogin($performLogin = true) {
        // Sigma does not allow to write sessions very well..
        // if a session expires you lose access to writing to that file
        // so you need to regenerate the id to create a new file
        ob_start();
        session_start();
        $result = ob_get_clean();
        ob_end_clean();
        if ($result !== '') {
            session_regenerate_id();
        }

        $isLoggedIn = array_key_exists('username', $_SESSION);

        if (!$isLoggedIn && $performLogin) {
            $fenixEduClient = \FenixEdu::getSingleton();
            $authorizationUrl = $fenixEduClient->getAuthUrl();
            if (array_key_exists('REQUEST_URI', $_SERVER))
                $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header(sprintf("Location: %s", $authorizationUrl));
            exit();
        }

        return $isLoggedIn;
    }

    public static function performLogin() {
        if (array_key_exists('error', $_GET)) {
            die($_GET['error']);
        } else if (array_key_exists('code', $_GET)) {
            $code = $_GET['code'];
            $fenixEduClient = \FenixEdu::getSingleton();
            if (!$fenixEduClient->getAccessTokenFromCode($code)) { // this may cause infinite loop, but is better than exposing credentials i guess
                $authorizationUrl = $fenixEduClient->getAuthUrl();
                header(sprintf("Location: %s", $authorizationUrl));
            }

            $person = $fenixEduClient->getPerson();
            $_SESSION['username'] = $person->username;
            $_SESSION['name'] = $person->name;
            $_SESSION['email'] = $person->email;

            if (array_key_exists('redirect_url', $_SESSION)) {
                header(sprintf("Location: %s", $_SESSION['redirect_url']));
                unset($_SESSION['redirect_url']);
                exit();
            }
        }
    }

    public static function checkAccess($redirect = true) {
        static::init(); // make sure its initialized

        if (array_key_exists('user', $_SESSION)) {
            static::$loggedUser = User::getUser($_SESSION['user']);
            return true;
        }

        $fenixAuth = static::getFenixAuth();
        $username = $fenixAuth->getUsername();
  
        if (static::$pending_invites != null && array_key_exists($username, static::$pending_invites)) {
            $pendingInvite = static::$pending_invites[$username];
            $user = User::getUser($pendingInvite['id'])->initialize($fenixAuth->getName(), $fenixAuth->getEmail());
            $user->setUsername($username);
            if (array_key_exists('isAdmin', $pendingInvite))
                $user->setAdmin($pendingInvite['isAdmin']);
            unset(static::$pending_invites[$username]);
        }

        static::$loggedUser = User::getUserByUsername($username);
        if (static::$loggedUser != null) {
            $_SESSION['user'] = static::$loggedUser->getId();
            return true;
        } else if ($redirect) {
            include 'pages/no-access.php';
            exit;
        }
        return false;
    }

    public static function getLoggedUser() {
        return static::$loggedUser;
    }

    public static function getTheme() {
        //return static::$mainConfigDB->get('theme');
        return static::$theme;
    }

    public static function setTheme($theme) {
        //return static::$mainConfigDB->set('theme', $theme);
        static::$theme=$theme;
    }

    public static function getActiveCourses() {
        //return static::$mainConfigDB->get('active-courses');
        return static::$sistemDB->selectMultiple("course",'*',["active"=>true]);
    }

    //public static function getActiveCoursesWrapped() {
    //    return static::$mainConfigDB->getWrapped('active-courses');
    //}

    public static function getPendingInvites() {
        //return static::$mainConfigDB->get('pending-invites');
        return static::$pending_invites;
    }

    //public static function getPendingInvitesWrapped() {
    //    return static::$mainConfigDB->getWrapped('pending-invites');
    //}

    public static function getCourses() {
        return static::$sistemDB->selectMultiple("course");
        //return static::$mainConfigDB->get('courses');     
    }
    public static function getCourse($id) {
        return static::$sistemDB->select("course",'*',['id'=>$id]);  
    }
    //public static function getCoursesWrapped() {
    //    return static::$mainConfigDB->getWrapped('courses');
    //}

    public static function getApiKey() {
        //return static::$mainConfigDB->getWrapped('apiKey');
        return static::$apiKey;
    }

    public static function getFenixAuth() {
        return new FenixAuth();
    }

    public static function addNavigation($image, $text, $ref, $isSRef = false, $subtext = '') {
        static::$navigation[] = array('image' => $image, 'text' => $text, ($isSRef ? 'sref' : 'href') => $ref, 'subtext' => $subtext);
    }

    public static function getNavigation() {
        return static::$navigation;
    }

    //public static function getViews() {//ToDo
    //    return static::$mainConfigDB->getWrapped('views');
    //}

    //public static function getConfig() {
    //    return static::$mainConfigDB;
    //}

    //private static function initMainConfig() {
    //    static::$mainConfigDB = new MagicWrapper(new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'config'));
    //}


}
