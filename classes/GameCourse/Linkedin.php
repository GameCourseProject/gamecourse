<?php

namespace GameCourse;


class Linkedin
{
    private static $INSTANCE;
    private $accessKey;
    private $secretKey;
    private $callbackUrl;

    protected function __construct()
    {
        global $_LINKEDIN;
        global $_SESSION;
        if (php_sapi_name() != 'cli' && !session_id()) {
            session_start();
        }
        $config = $_LINKEDIN;
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
        return "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=" . $this->accessKey .
            "&redirect_uri=" . $this->callbackUrl . "&scope=r_liteprofile%20r_emailaddress";
    }

    public function getAccessTokenFromCode($code)
    {
        $url = "https://www.linkedin.com/oauth/v2/accessToken?grant_type=authorization_code&code=" . $code .
            "&redirect_uri=" . $this->callbackUrl . "&client_id=" . $this->accessKey . "&client_secret=" . $this->secretKey;

        $response = Linkedin::curlRequests($url);

        $token = json_decode($response);
        $this->accessToken = $_SESSION['accessToken'] = $token->access_token;
        $this->expirationTime = $_SESSION['expires'] = time() + $token->expires_in;
        return $this->accessToken;
    }

    public function getPerson()
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $url = "https://api.linkedin.com/v2/me";
        $response = Linkedin::curlRequests($url, $headers);
        file_put_contents("person.txt", $response);
        $infoName = json_decode($response);
        $name = $infoName->localizedFirstName . " " . $infoName->localizedLastName;

        $url = "https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))";
        $response = Linkedin::curlRequests($url, $headers);
        $response = str_replace("~", "_", $response);
        file_put_contents("eeeee.txt", $response);
        $infoEmail = json_decode($response);
        $email = $infoEmail->elements[0]->handle_->emailAddress;

        $info = (object) array("username" => $email, "name" => $name, "email" => $email);

        return $info;
    }

    public static function curlRequests($url, $headers = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
}
