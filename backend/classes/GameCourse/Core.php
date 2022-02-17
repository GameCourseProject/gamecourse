<?php

namespace GameCourse;

use MagicDB\SQLDB;

require_once 'config.php';

require_once 'fenixedu-sdk/FenixEdu.class.php';
require_once 'GoogleHandler.php';
require_once 'Facebook.php';


$_FENIX_EDU['access_key'] = FENIX_CLIENT_ID;
$_FENIX_EDU['secret_key'] = FENIX_CLIENT_SECRET;
$_FENIX_EDU['callback_url'] = FENIX_REDIRECT_URL;
$_FENIX_EDU['api_base_url'] = FENIX_API_BASE_URL;
$GLOBALS['_FENIX_EDU'] = $_FENIX_EDU;

$_GOOGLE['access_key'] = GOOGLE_CLIENT_ID;
$_GOOGLE['secret_key'] = GOOGLE_CLIENT_SECRET;
$_GOOGLE['callback_url'] = GOOGLE_REDIRECT_URL;
$GLOBALS['_GOOGLE'] = $_GOOGLE;

$_FACEBOOK['access_key'] = FACEBOOK_CLIENT_ID;
$_FACEBOOK['secret_key'] = FACEBOOK_CLIENT_SECRET;
$_FACEBOOK['callback_url'] = FACEBOOK_REDIRECT_URL;
$GLOBALS['_FACEBOOK'] = $_FACEBOOK;

$_LINKEDIN['access_key'] = LINKEDIN_CLIENT_ID;
$_LINKEDIN['secret_key'] = LINKEDIN_CLIENT_SECRET;
$_LINKEDIN['callback_url'] = LINKEDIN_REDIRECT_URL;
$GLOBALS['_LINKEDIN'] = $_LINKEDIN;

class Core
{
    public static $systemDB;
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
        $needsSetup = !file_exists('setup.done');
        if ($needsSetup && $performSetup) {
            API::error('GameCourse is not yet setup.', 409);
        }
        return $needsSetup;
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

        if(!isset($_POST['loginType']))
            return $isLoggedIn;

        $client = null;
        if (!$isLoggedIn && $performLogin && !Core::requireSetup()) {
            $loginType = htmlspecialchars($_POST['loginType']);
            $_SESSION['type'] = $loginType;
            if ($loginType == "google") {
                $client = GoogleHandler::getSingleton();
            } else if ($loginType == "fenix") {
                $client = \FenixEdu::getSingleton();
            } else if ($loginType == "facebook") {
                $client = Facebook::getSingleton();
            } else if ($loginType == "linkedin") {
                $client = Linkedin::getSingleton();
            }
            if ($client) {
                $authorizationUrl = $client->getAuthUrl();
                if (array_key_exists('REQUEST_URI', $_SERVER))
                    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                echo json_encode(['redirectURL' => $authorizationUrl]);
                exit();
            }
        }
        return $isLoggedIn;
    }

    public static function performLogin($loginType)
    {
        if ($loginType == "fenix") {
            $client = \FenixEdu::getSingleton();
        } else if ($loginType == "google") {
            $client = GoogleHandler::getSingleton();
        } else if ($loginType == "facebook") {
            $client = Facebook::getSingleton();
        } else if ($loginType == "linkedin") {
            $client = Linkedin::getSingleton();
        }
        if ($client) {
            if (array_key_exists('error', $_GET)) {
                die($_GET['error']);
            } else if (array_key_exists('code', $_GET)) {
                $code = $_GET['code'];
                $accessToken = $client->getAccessTokenFromCode($code);
                if (!$accessToken) {
                    $authorizationUrl = $client->getAuthUrl();
                    header(sprintf("Location: $authorizationUrl"));
                }
                $person = $client->getPerson();
                $_SESSION['username'] =  $person->username;
                $_SESSION['email'] =  $person->email;
                $_SESSION['name'] =  $person->name;
                $_SESSION['pictureUrl'] = $loginType == "fenix" ? $person->photo->data : $person->pictureUrl;
                $_SESSION['loginDone'] = $loginType;
            }
        }
    }

    public static function checkAccess($redirect = true)
    {
        static::init(); // make sure its initialized
        if (array_key_exists('user', $_SESSION)) {
          static::$loggedUser = User::getUserByUsername($_SESSION['username']);
          $_SESSION['user'] = static::$loggedUser->getId();
          return true;
        }
        if (array_key_exists("loginDone", $_SESSION)) {
            $username = $_SESSION['username'];

            //verficar qual o tipo de login
            if (User::getUserAuthenticationService($username) == $_SESSION['type']) {
                static::$loggedUser = User::getUserByUsername($username);
            }

            if (static::$loggedUser != null) {
                $_SESSION['user'] = static::$loggedUser->getId();
                if (!file_exists('photos/' . $username . '.png')) { //se n existir foto
                    if (array_key_exists('type', $_SESSION) && array_key_exists('pictureUrl', $_SESSION)) {
                        if ($_SESSION['type'] == "fenix") {
                            $client = \FenixEdu::getSingleton();
                        } elseif ($_SESSION['type'] == "google") {
                            $client = GoogleHandler::getSingleton();
                        } else if ($_SESSION['type'] == "facebook") {
                            $client = Facebook::getSingleton();
                        } else if ($_SESSION['type'] == "linkedin") {
                            $client = Linkedin::getSingleton();
                        }
                        $client->downloadPhoto($_SESSION['pictureUrl'], $username);
                    }
                }
                return true;
            } else if ($redirect) {
                $_SESSION = [];
                API::error('Access denied.', 403);
            }
        }
        return false;
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];
        session_destroy();

        echo json_encode(['isLoggedIn' => false]);
        exit();
    }

    public static function getAuth()
    {
        return new Auth();
    }

    public static function getFenixInfo($url)
    {
        $fenixEduClient = \FenixEdu::getSingleton();
        return $fenixEduClient->get($url, true);
    }

    public static function getLoggedUser(): User
    {
        return static::$loggedUser;
    }


    public static function getActiveCourses()
    {
        return static::$systemDB->selectMultiple("course", ["isActive" => true]);
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

    public static function getCourses(): array
    {
        return static::$systemDB->selectMultiple("course");
    }
    public static function getCourse($id)
    {
        return static::$systemDB->select("course", ['id' => $id]);
    }

    //adds page info for navigation, last 2 args are used to make pages exclusive for teachers or admins
    public static function addNavigation($text, $ref, $isSRef = false, $seqId = null, $class = '', $children = false, $restrictAcess = false)
    {
        static::$navigation[] = [
            'text' => $text, ($isSRef ? 'sref' : 'href') => $ref, 'seqId' => $seqId, 'class' => $class, 'children' => $children,
            "restrictAcess" => $restrictAcess
        ];
    }

    public static function getNavigation()
    {
        return static::$navigation;
    }

    public static function setNavigation($navigation)
    {
        static::$navigation = $navigation;
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