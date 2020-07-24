<?php

namespace GameCourse;


class Facebook
{
    private static $INSTANCE;
    private $accessKey;
    private $secretKey;
    private $callbackUrl;

    protected function __construct()
    {
        global $_FACEBOOK;
        global $_SESSION;
        if (php_sapi_name() != 'cli' && !session_id()) {
            session_start();
        }
        $config = $_FACEBOOK;
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
    public function getAuthUrl()
    {
        return "https://www.facebook.com/v7.0/dialog/oauth?client_id=" . $this->accessKey . "&redirect_uri=" . $this->callbackUrl . "&scope=email";
    }

    // public function askPermission()
    // {
    //     $url = "https://www.facebook.com/v7.0/dialog/oauth?client_id=" . $this->accessKey . "&redirect_uri=" . $this->callbackUrl . "&auth_type=rerequest&scope=email";
    // }

    public function getAccessTokenFromCode($code)
    {
        $url = "https://graph.facebook.com/v7.0/oauth/access_token?client_id=" . $this->accessKey .
            "&redirect_uri=" . $this->callbackUrl . "&client_secret=" . $this->secretKey . "&code=" . $code;

        $response = Facebook::curlRequests($url);

        $token = json_decode($response);
        $this->accessToken = $_SESSION['accessToken'] = $token->access_token;
        $this->expirationTime = $_SESSION['expires'] = time() + $token->expires_in;
        return $this->accessToken;
    }

    public function getPersonId()
    {
        $url = "https://graph.facebook.com/me?access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $info = json_decode($response);
        return $info->id;
    }

    public function getPerson()
    {
        $url = "https://graph.facebook.com/me?access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $info = json_decode($response);
        $personId = $info->id;

        $url = "https://graph.facebook.com/v7.0/" . $personId . "?fields=id,name,email&access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $info = json_decode($response);
        return $info;
        //atençao pq o email nao le bem o @!!    
    }

    public function logout()
    {
        $url = "https://m.facebook.com/logout.php?confirm=1next=" . $this->callbackUrl . "&access_token=" . $this->accessToken;
        file_put_contents("uuuuuuuuuuuuu.txt", $url);
        $response = Facebook::curlRequests($url);
    }

    public static function curlRequests($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
}
