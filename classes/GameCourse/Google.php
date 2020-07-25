<?php

namespace GameCourse;

include 'google-api-php-client/vendor/autoload.php';

class Google
{
    private $client;

    public function setCredentials($credentials)
    {
        $client = new \Google_Client();
        $client->setApplicationName('spreadsheets');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentials, false);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        return $client;
    }

    public function checkToken($credentials, $token, $authCode)
    {
        $this->client = $this->setCredentials($credentials);
        if ($token) {
            $accessToken = $token;
            $this->client->setAccessToken($accessToken);
            return array("access_token" => $this->client->getAccessToken(), "auth_url" => null);
        }

        if ($authCode) {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            $this->client->setAccessToken($accessToken);
            return array("access_token" => $this->client->getAccessToken(), "auth_url" => null);
        }

        if ($this->client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                return array("access_token" => $this->client->getAccessToken(), "auth_url" => null);
                //VER MELHOR!!!!!
            } else {
                // Request authorization from the user.
                $authUrl = $this->client->createAuthUrl();
                return array("access_token" => null, "auth_url" => $authUrl);
                // 4/1gGIdDZ1KwcItpRm6FoytbT08e82mSs6nw6Zy5EWR4AYUTpPd5WmrpE

            }
        }
    }

    public function getGoogleSheets($credentials, $token, $authCode)
    {
        $this->checkToken($credentials, $token, $authCode);
        return new \Google_Service_Sheets($this->client);
    }



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
        $this->callbackUrl = isset($config["callback_url"]) ? $config["callback_url"] : null;

        $this->accessToken = isset($config["access_token"]) ? $config["access_token"] : null;
        $this->refreshToken = isset($config["refresh_token"]) ? $config["access_token"] : null;

        if (isset($_SESSION['accessToken'])) {
            $this->accessToken = $_SESSION['accessToken'];
            $this->refreshToken = $_SESSION['accessToken'];
            $this->expirationTime = $_SESSION['expires'];
        }
    }
    public static function getSingleton()
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    function getAuthUrl()
    {
        $client = new \Google_Client();
        $client->setClientId($this->accessKey);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($this->callbackUrl);
        $client->addScope("email");
        $client->addScope("profile");

        return $client->createAuthUrl();
    }

    function getAccessTokenFromCode($code)
    {

        $client = new \Google_Client();
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

    function getPerson()
    {
        $client = new \Google_Client();
        $client->setClientId($this->accessKey);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($this->callbackUrl);
        $client->addScope("email");
        $client->addScope("profile");
        $client->setAccessToken($this->accessToken);

        $google_oauth = new \Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $info = (object) array("username" => $google_account_info->email, "name" => $google_account_info->name, "email" => $google_account_info->email);
        return $info;
    }



    // public function getAuthCodeLogin()
    // {
    //     $this->client->setClientId("370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com");
    //     $this->client->setClientSecret("hC4zsuwH1fVIWi5k0C4zjOub");
    //     $this->client->setRedirectUri("http://localhost/gamecourse/auth");
    //     $this->client->addScope("email");
    //     $this->client->addScope("profile");
    //     return $this->client->createAuthUrl();
    // }

    public function getClient()
    {
        $client = new \Google_Client();
        $client->setClientId("370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com");
        $client->setClientSecret("hC4zsuwH1fVIWi5k0C4zjOub");
        $client->setRedirectUri("http://localhost/gamecourse/auth");
        $client->addScope("email");
        $client->addScope("profile");
    }



    // public function getAccessTokenFromCode($code)
    // {
    //     // $client = new \Google_Client();
    //     // $client->setClientId("370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com");
    //     // $client->setClientSecret("hC4zsuwH1fVIWi5k0C4zjOub");
    //     // $client->setRedirectUri("http://localhost/gamecourse/auth");
    //     // $client->addScope("email");
    //     // $client->addScope("profile");
    //     $token = $this->client->fetchAccessTokenWithAuthCode($code);
    //     $this->client->setAccessToken($token['access_token']);
    //     return $token['access_token'];
    // }

    // public function getPerson($code)
    // {
    //     // $client = new \Google_Client();
    //     // $client->setClientId("370984617561-lf04il2ejv9e92d86b62lrts65oae80r.apps.googleusercontent.com");
    //     // $client->setClientSecret("hC4zsuwH1fVIWi5k0C4zjOub");
    //     // $client->setRedirectUri("http://localhost/gamecourse/auth");
    //     // $client->addScope("email");
    //     // $client->addScope("profile");
    //     // get profile info
    //     $google_oauth = new \Google_Service_Oauth2($this->client);
    //     $google_account_info = $google_oauth->userinfo->get();
    //     return $google_account_info;
    // }
}
