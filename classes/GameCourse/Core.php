<?php

namespace GameCourse;

use MagicDB\SQLDB;

require_once 'config.php';

require_once 'fenixedu-sdk/FenixEdu.class.php';
require_once 'Google.php';
$_FENIX_EDU['access_key'] = FENIX_CLIENT_ID;
$_FENIX_EDU['secret_key'] = FENIX_CLIENT_SECRET;
$_FENIX_EDU['callback_url'] = FENIX_REDIRECT_URL;
$_FENIX_EDU['api_base_url'] = FENIX_API_BASE_URL;
$GLOBALS['_FENIX_EDU'] = $_FENIX_EDU;

$_GOOGLE['access_key'] = GOOGLE_CLIENT_ID;
$_GOOGLE['secret_key'] = GOOGLE_CLIENT_SECRET;
$_GOOGLE['callback_url'] = GOOGLE_REDIRECT_URL;
$GLOBALS['_GOOGLE'] = $_GOOGLE;

class Core
{
    public static $systemDB;
    //public static $active_courses = array();
    //public static $courses = array();   
    public static $theme = 'default';

    private static $loggedUser = null;
    private static $navigation = array();
    private static $settings = array();
    public static function isCLI()
    {
        return php_sapi_name() == 'cli';
    }

    public static function denyCLI()
    {
        if (static::isCLI()) {
            die('CLI access to this script is not allowed.');
        }
    }

    public static function init()
    {
        if (static::$systemDB == null) {
            static::$systemDB = new SQLDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD);
        }
    }

    public static function requireSetup($performSetup = true)
    {
        $setup = file_exists('setup.done');
        if (!$setup && $performSetup) {
            $setupOkay = (include 'pages/setup.php');
            if ($setupOkay == 'setup-done') {
                return true;
            } else
                exit;
        }
        return $setup;
    }

    public static function requireLogin($performLogin = true)
    {
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
            $loginType = (include 'pages/login.php');
            if ($loginType == "google") {
                $_SESSION['type'] = "google";
                $google = Google::getSingleton();
                $authorizationUrl = $google->getAuthUrl();
                if (array_key_exists('REQUEST_URI', $_SERVER))
                    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                header("Location: $authorizationUrl");
            } else {
                $_SESSION['type'] = "fenix";
                $fenixEduClient = \FenixEdu::getSingleton();
                $authorizationUrl = $fenixEduClient->getAuthUrl();
                if (array_key_exists('REQUEST_URI', $_SERVER))
                    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                header("Location: $authorizationUrl");
                exit();
            }
        }
        return $isLoggedIn;
    }

    public static function performLogin($loginType)
    {
        if ($loginType == "fenix") {
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
                $_SESSION['loginDone'] = "fenix";
            }
        } else {
            if (array_key_exists('error', $_GET)) {
                die($_GET['error']);
            } else if (array_key_exists('code', $_GET)) {
                $code = $_GET['code'];
                $google = Google::getSingleton();
                $client = $google->getAccessTokenFromCode($code);
                if (!$client) {
                    $authorizationUrl = $google->getAuthUrl();
                    header(sprintf("Location: %s", $authorizationUrl));
                }
                $person = $google->getPerson($client);
                $_SESSION['username'] =  $person->email;
                $_SESSION['email'] =  $person->email;
                $_SESSION['name'] =  $person->name;
                $_SESSION['loginDone'] = "google";
            }
        }
    }

    public static function checkAccess($redirect = true)
    {
        $username = $_SESSION['username'];
        static::init(); // make sure its initialized
        if (array_key_exists('user', $_SESSION)) {
            static::$loggedUser = User::getUser($_SESSION['user']);
            return true;
        }

        if ($_SESSION['loginDone'] == "fenix") {
            $fenixAuth = static::getFenixAuth();
            $username = $fenixAuth->getUsername();
            static::$loggedUser = User::getUserByUsername($username);
            if (static::$loggedUser != null) {
                $_SESSION['user'] = static::$loggedUser->getId();
                return true;
            } else if ($redirect) {
                include 'pages/no-access.php';
                exit;
            }
        }
        if ($_SESSION['loginDone'] == "google") {
            $googleAuth = static::getGoogleAuth();
            $username = $googleAuth->getUsername();
            static::$loggedUser = User::getUserByUsername($username);
            if (static::$loggedUser != null) {
                $_SESSION['user'] = static::$loggedUser->getId();
                return true;
            } else if ($redirect) {
                include 'pages/no-access.php';
                exit;
            }
        }
        return false;
    }

    public static function getFenixInfo($url)
    {
        $fenixEduClient = \FenixEdu::getSingleton();
        return $fenixEduClient->get($url, true);
    }

    public static function getLoggedUser()
    {
        return static::$loggedUser;
    }


    public static function getActiveCourses()
    {
        return static::$systemDB->selectMultiple("course", ["active" => true]);
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

    public static function getCourses()
    {
        return static::$systemDB->selectMultiple("course");
    }
    public static function getCourse($id)
    {
        return static::$systemDB->select("course", ['id' => $id]);
    }

    public static function getFenixAuth()
    {
        return new FenixAuth();
    }

    public static function getGoogleAuth()
    {
        return new GoogleAuth();
    }

    //adds page info for navigation, last 2 args are used to make pages exclusive for teachers or admins
    public static function addNavigation($text, $ref, $isSRef = false, $class = '', $children = false, $restrictAcess = false)
    {
        static::$navigation[] = [
            'text' => $text, ($isSRef ? 'sref' : 'href') => $ref, 'class' => $class, 'children' => $children,
            "restrictAcess" => $restrictAcess
        ];
    }

    public static function getNavigation()
    {
        return static::$navigation;
    }

    public static function addSettings($text, $ref, $isSRef = false, $restrictAcess = false)
    {
        static::$settings[] = [
            'text' => $text, ($isSRef ? 'sref' : 'href') => $ref,
            "restrictAcess" => $restrictAcess
        ];
    }
    public static function getSettings()
    {
        return static::$settings;
    }
}
