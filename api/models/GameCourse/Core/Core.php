<?php
namespace GameCourse\Core;

use API\API;
use Database\Database;
use Exception;
use Facebook;
use FenixEdu;
use FenixEduException;
use GameCourse\User\User;
use GoogleHandler;
use Linkedin;
use Utils\Utils;

require_once ROOT_PATH . "lib/fenixedu/FenixEdu.php";
require_once ROOT_PATH . "lib/google/Google.php";

/**
 * This is the Core class which holds core functionality like making
 * the bridge between models and the database, handling setup and
 * authentication, and has some utility functions regarding CLI.
 */
class Core
{
    private static $loggedUser;


    /*** ----------------------------------------------- ***/
    /*** ------------------ Database ------------------- ***/
    /*** ----------------------------------------------- ***/

    public static function database(): Database
    {
        return Database::get();
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public static function requireSetup(bool $performSetup = true): bool
    {
        $needsSetup = !file_exists(ROOT_PATH . 'setup/setup.done');
        if ($needsSetup && $performSetup)
            API::error('GameCourse is not yet setup.', 409);
        return $needsSetup;
    }

    public static function resetGameCourse()
    {
        Core::database()->cleanDatabase(true);
        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Authentication ---------------- ***/
    /*** ----------------------------------------------- ***/

    public static function requireLogin(bool $performLogin = true)
    {
        // Sigma does not allow writing sessions very well...
        // If a session expires, you lose access to writing to that file,
        // so you need to regenerate the id to create a new file

        ob_start();
        session_start();
        $result = ob_get_clean();
        ob_end_clean();
        if ($result !== '') session_regenerate_id();
        $isLoggedIn = array_key_exists('username', $_SESSION);

        if (!isset($_POST['loginType']))
            return $isLoggedIn;

        $client = null;
        if (!$isLoggedIn && $performLogin && !Core::requireSetup()) {
            $loginType = htmlspecialchars($_POST['loginType']);
            $_SESSION['type'] = $loginType;

            if ($loginType == "google") {
                $client = GoogleHandler::getSingleton();
            } else if ($loginType == "fenix") {
                $client = FenixEdu::getSingleton();
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

    /**
     * @throws FenixEduException
     * @throws Exception
     */
    public static function performLogin(string $loginType)
    {
        if ($loginType == "fenix") {
            $client = FenixEdu::getSingleton();
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

    public static function checkAccess(bool $redirect = true): bool
    {
        if (array_key_exists('user', $_SESSION)) {
            static::$loggedUser = User::getUserByUsername($_SESSION['username']);
            $_SESSION['user'] = static::$loggedUser->getId();
            return true;
        }

        if (array_key_exists("loginDone", $_SESSION)) {
            $username = $_SESSION['username'];
            $user = User::getUserByUsername($username);

            // Verify login type
            if ($user->getAuthService() == $_SESSION['type'])
                static::$loggedUser = $user;

            if (static::$loggedUser != null) {
                $_SESSION['user'] = static::$loggedUser->getId();

                // User doesn't have a photo yet
                if (!file_exists(ROOT_PATH . 'photos/' . $username . '.png')) {
                    if (array_key_exists('type', $_SESSION) && array_key_exists('pictureUrl', $_SESSION)) {
                        if ($_SESSION['type'] == "fenix") {
                            $client = FenixEdu::getSingleton();
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

    public static function getLoggedUser(): ?User
    {
        return static::$loggedUser;
    }

    public static function setLoggedUser(User $user)
    {
        self::$loggedUser = $user;
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];
        session_destroy();

        echo json_encode(['isLoggedIn' => false]);
        exit();
    }


    /*** ----------------------------------------------- ***/
    /*** --------------------- CLI --------------------- ***/
    /*** ----------------------------------------------- ***/

    public static function isCLI(): bool
    {
        return php_sapi_name() == 'cli';
    }

    public static function denyCLI()
    {
        if (self::isCLI()) die('CLI access to this script is not allowed.');
    }
}
