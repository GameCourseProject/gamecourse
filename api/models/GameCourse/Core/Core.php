<?php
namespace GameCourse\Core;

use API\API;
use Database\Database;
use Exception;
use Facebook;
use Faker\Factory;
use Faker\Generator;
use FenixEdu;
use FenixEduException;
use GameCourse\User\User;
use GameCourse\Views\Dictionary\Dictionary;
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

    /**
     * Get an instance of the database.
     *
     * @return Database
     */
    public static function database(): Database
    {
        return Database::get();
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Checks whether the system stills needs to be set up.
     * If $performSetup is true, throws an error if setup is
     * not yet done.
     *
     * @param bool $performSetup
     * @return bool
     */
    public static function requireSetup(bool $performSetup = true): bool
    {
        $needsSetup = !file_exists(ROOT_PATH . 'setup/setup.done');
        if ($needsSetup && $performSetup)
            API::error('GameCourse is not yet setup.', 409);
        return $needsSetup;
    }

    /**
     * Resets the system to a clean state.
     *
     * @return void
     * @throws Exception
     */
    public static function resetGameCourse()
    {
        Core::database()->cleanDatabase(true);
        if (file_exists(LOGS_FOLDER)) Utils::deleteDirectory(LOGS_FOLDER);
        if (file_exists(COURSE_DATA_FOLDER)) Utils::deleteDirectory(COURSE_DATA_FOLDER);
        if (file_exists(USER_DATA_FOLDER)) Utils::deleteDirectory(USER_DATA_FOLDER);
        if (file_exists(CACHE_FOLDER)) Utils::deleteDirectory(CACHE_FOLDER);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/imported-functions", false, ["defaults.py"]);
        Utils::deleteDirectory(AUTOGAME_FOLDER . "/config", false, ["samples"]);
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Authentication ---------------- ***/
    /*** ----------------------------------------------- ***/

    // FIXME: logic should move to Auth.php but keep function here
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

            if ($loginType == AuthService::GOOGLE) {
                $client = GoogleHandler::getSingleton();
            } else if ($loginType == AuthService::FENIX) {
                $client = FenixEdu::getSingleton();
            } else if ($loginType == AuthService::FACEBOOK) {
                $client = Facebook::getSingleton();
            } else if ($loginType == AuthService::LINKEDIN) {
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
     * FIXME: logic should move to Auth.php but keep function here
     * @throws FenixEduException
     * @throws Exception
     */
    public static function performLogin(string $loginType)
    {
        if ($loginType == AuthService::FENIX) {
            $client = FenixEdu::getSingleton();
        } else if ($loginType == AuthService::GOOGLE) {
            $client = GoogleHandler::getSingleton();
        } else if ($loginType == AuthService::FACEBOOK) {
            $client = Facebook::getSingleton();
        } else if ($loginType == AuthService::LINKEDIN) {
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
                $_SESSION['pictureUrl'] = $loginType == AuthService::FENIX ? $person->photo->data : $person->pictureUrl;
                $_SESSION['loginDone'] = $loginType;

                $user = User::getUserByUsername($_SESSION['username'], $loginType);
                if ($user) $user->refreshLastLogin();
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function checkAccess(bool $redirect = true): bool
    {
        if (array_key_exists('user', $_SESSION)) {
            static::$loggedUser = User::getUserByUsername($_SESSION['username']);
            $_SESSION['user'] = static::$loggedUser->getId();
            if (static::$loggedUser && !static::$loggedUser->isActive()) self::denyAccess();
            return true;
        }

        if (array_key_exists("loginDone", $_SESSION)) {
            $username = $_SESSION['username'];
            $user = User::getUserByUsername($username, $_SESSION['type']);
            if ($user) static::$loggedUser = $user;
            if (!static::$loggedUser->isActive()) self::denyAccess();

            if (static::$loggedUser) {
                $_SESSION['user'] = static::$loggedUser->getId();

                // User doesn't have a photo yet
                if (!static::$loggedUser->hasImage()) {
                    if (array_key_exists('type', $_SESSION) && array_key_exists('pictureUrl', $_SESSION)) {
                        if ($_SESSION['type'] == AuthService::GOOGLE) {
                            $client = GoogleHandler::getSingleton();
                        } else if ($_SESSION['type'] == AuthService::FACEBOOK) {
                            $client = Facebook::getSingleton();
                        } else if ($_SESSION['type'] == AuthService::LINKEDIN) {
                            $client = Linkedin::getSingleton();
                        } else {
                            $client = FenixEdu::getSingleton();
                        }
                        $client->downloadPhoto($_SESSION['pictureUrl'], static::$loggedUser->getId());
                    }
                }
                return true;

            } else if ($redirect) self::denyAccess();
        }

        return false;
    }

    private static function denyAccess()
    {
        $_SESSION = [];
        API::error("Access denied.", 403);
    }

    public static function getLoggedUser(): ?User
    {
        return static::$loggedUser;
    }

    public static function setLoggedUser(?User $user)
    {
        self::$loggedUser = $user;
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];
        session_destroy();
        self::setLoggedUser(null);

        echo json_encode(['isLoggedIn' => false]);
        exit();
    }


    /*** ----------------------------------------------- ***/
    /*** ----------------- Dictionary ------------------ ***/
    /*** ----------------------------------------------- ***/

    /**
     * Get an instance of the dictionary.
     *
     * @return Dictionary
     */
    public static function dictionary(): Dictionary
    {
        return Dictionary::get();
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Mocks -------------------- ***/
    /*** ----------------------------------------------- ***/

    /**
     * Get an instance of a faker generator to mock data.
     * @see https://fakerphp.github.io/
     *
     * @return Generator
     */
    public static function mock(): Generator
    {
        return Factory::create();
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
