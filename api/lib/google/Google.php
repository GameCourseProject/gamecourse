<?php

use Google\Service\Sheets;

require_once("google.config.php");

#[\AllowDynamicProperties]
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


    /*** --------------------------------------------- ***/
    /*** ------------------ Account ------------------ ***/
    /*** --------------------------------------------- ***/

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

        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        return (object) array("username" => $google_account_info->email, "name" => $google_account_info->name, "email" => $google_account_info->email, "pictureUrl" => $google_account_info->picture);
    }

    public function downloadPhoto(string $pictureUrl, int $userId)
    {
        $pictureUrl = str_replace("\\", "", $pictureUrl);
        $img = file_get_contents($pictureUrl);
        $path = USER_DATA_FOLDER . '/' . $userId . '/profile.png';
        file_put_contents($path, $img);
    }


    /*** --------------------------------------------- ***/
    /*** --------------- Google Sheets --------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws \Google\Exception
     */
    public static function getGoogleSheetsClient(int $courseId, array $credentials, array $accessToken = null): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('spreadsheets');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentials);
        if ($accessToken) $client->setAccessToken($accessToken);
        $client->setState($courseId);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }

    public static function getGoogleSheetsService(Google_Client $client): Sheets
    {
        return new Sheets($client);
    }

    public static function createAccessToken(Google_Client $client, string $authCode): array
    {
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);
        return $accessToken;
    }
}