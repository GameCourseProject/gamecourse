<?php
namespace GameCourse;

use MagicDB\SQLDB; 

require_once 'config.php';

require_once 'fenixedu-sdk/FenixEdu.class.php';
$_FENIX_EDU['access_key'] = FENIX_CLIENT_ID;
$_FENIX_EDU['secret_key'] = FENIX_CLIENT_SECRET;
$_FENIX_EDU['callback_url'] = FENIX_REDIRECT_URL;
$_FENIX_EDU['api_base_url'] = FENIX_API_BASE_URL;
$GLOBALS['_FENIX_EDU'] = $_FENIX_EDU;

class Core {
    public static $systemDB;
    //public static $active_courses = array();
    //public static $courses = array();   
    public static $theme = 'default';

    private static $loggedUser = null;
    private static $navigation = array();
    public static function isCLI() {
        return php_sapi_name() == 'cli';
    }

    public static function denyCLI() {
        if (static::isCLI()) {
            die('CLI access to this script is not allowed.');
        }
    }

    public static function init() {
        if (static::$systemDB == null) {
            static::$systemDB = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
        }
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
        
        /*
        $invites = Core::getPendingInvites();
        $pending_invites = array_combine(array_column($invites,"username"), $invites);
        if ( !empty($pending_invites) && array_key_exists($username, $pending_invites)) {
            $pendingInvite = $pending_invites[$username];
            $user = User::getUser($pendingInvite['id']);
            $user->addUserToDB($fenixAuth->getName(), $username, $fenixAuth->getEmail());
            if (array_key_exists('isAdmin', $pendingInvite))
                $user->setAdmin($pendingInvite['isAdmin']);
            Core::removePendingInvites($pendingInvite["id"]);
        }
        */
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

    
    public static function getActiveCourses() {
        return static::$systemDB->selectMultiple("course",["active"=>true]);
    }

    /*public static function getPendingInvites() {
        return static::$systemDB->selectMultiple("pending_invite");
    }
    public static function pendingInviteExists($id) {
        return !empty(static::$systemDB->select("pending_invite",['id'=>$id],'id'));
    }
    public static function addPendingInvites($data) {
        return static::$systemDB->insert("pending_invite",$data);
    }
    public static function removePendingInvites($id) {
        return static::$systemDB->delete("pending_invite",["id"=>$id]);
    }*/

    public static function getCourses() {
        return static::$systemDB->selectMultiple("course");
    }
    public static function getCourse($id) {
        return static::$systemDB->select("course",['id'=>$id]);  
    }

    public static function getApiKey() {
        return static::$systemDB->selectMultiple("system_info",null,"apiKey")[0]["apiKey"];
    }
    public static function setApiKey($key) {
        return static::$systemDB->update("system_info",["apiKey"=>$key]);
    }

    public static function getFenixAuth() {
        return new FenixAuth();
    }
    
    //adds page info for navigation, last 2 args are used to make pages exclusive for teachers or admins
    public static function addNavigation($image, $text, $ref, $isSRef = false, $subtext = '',$restrictAcess=false) {
        static::$navigation[] = ['image' => $image, 'text' => $text, ($isSRef ? 'sref' : 'href') => $ref, 'subtext' => $subtext,
                                "restrictAcess"=>$restrictAcess];
    }

    public static function getNavigation() {
        return static::$navigation;
    }
}
