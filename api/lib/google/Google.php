<?php

require_once("google.config.php");

class GoogleHandler
{
    private static $INSTANCE;

    private $accessKey;
    private $secretKey;

    private $callbackUrl;

    protected function __construct()
    {
        global $_GOOGLE;
        global $_SESSION;
        if (php_sapi_name() != 'cli' && !session_id()) {
            session_start();
        }
        $config = $_GOOGLE;
        $this->accessKey = $config["access_key"];
        $this->secretKey = $config["secret_key"];
        $this->callbackUrl = $config["callback_url"] ?? null;
        $this->accessToken = $config["access_token"] ?? null;
        $this->refreshToken = isset($config["refresh_token"]) ? $config["access_token"] : null;

        if (isset($_SESSION['accessToken'])) {
            $this->accessToken = $_SESSION['accessToken'];
            $this->refreshToken = $_SESSION['accessToken'];
            $this->expirationTime = $_SESSION['expires'];
        }
    }

    public static function getSingleton(): GoogleHandler
    {
        if (self::$INSTANCE == null) self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function getAuthUrl(): string
    {
        $client = new Google_Client();
        $client->setClientId($this->accessKey);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($this->callbackUrl);
        $client->addScope("email");
        $client->addScope("profile");

        return $client->createAuthUrl();
    }

    public function getAccessTokenFromCode($code)
    {
        $client = new Google_Client();
        $client->setClientId($this->accessKey);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($this->callbackUrl);
        $client->addScope("email");
        $client->addScope("profile");

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if ($token['access_token']) {
            $this->accessToken = $_SESSION['accessToken'] = $token['access_token'];
            $this->expirationTime = $_SESSION['expires'] = time() + $token['expires_in'];
            $client->setAccessToken($token['access_token']);
            return $client;
        } else {
            return false;
        }
    }

    public function getPerson(): object
    {
        $client = new Google_Client();
        $client->setClientId($this->accessKey);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($this->callbackUrl);
        $client->addScope("email");
        $client->addScope("profile");
        $client->setAccessToken($this->accessToken);

        $google_oauth = new \Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        return (object) array("username" => $google_account_info->email, "name" => $google_account_info->name, "email" => $google_account_info->email, "pictureUrl" => $google_account_info->picture);
    }

    public function downloadPhoto($pictureUrl, $userId)
    {
        $pictureUrl = str_replace("\\", "", $pictureUrl);
        $pic = file_get_contents($pictureUrl);
        $path = ROOT_PATH . 'photos/' . $userId . '.png';
        file_put_contents($path, $pic);
    }

    public static function setCredentials($credentials, $course = 0): Google_Client
    {
        $client = new \Google_Client();
        $client->setApplicationName('spreadsheets');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentials, false);
        $client->setState($course);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }

    public static function checkToken($credentials, $token, $authCode, $course)
    {
        $client = GoogleHandler::setCredentials($credentials, $course);
        if ($token) {
            $accessToken = $token;
            $client->setAccessToken($accessToken);
            return array("access_token" => $client->getAccessToken(), "auth_url" => null, "client" => $client);
        }

        if ($authCode) {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);
            return array("access_token" => $client->getAccessToken(), "auth_url" => null, "client" => $client);
        }

        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                return array("access_token" => $client->getAccessToken(), "auth_url" => null, "client" => $client);
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                return array("access_token" => null, "auth_url" => $authUrl, "client" => $client);
            }
        }
    }

    public static function getGoogleSheets($credentials, $token, $authCode, $course): Google_Service_Sheets
    {
        $result = GoogleHandler::checkToken($credentials, $token, $authCode, $course);
        return new \Google_Service_Sheets($result["client"]);
    }
}