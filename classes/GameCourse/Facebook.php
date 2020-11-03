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
        return "https://www.facebook.com/v7.0/dialog/oauth?client_id=" . $this->accessKey .
            "&redirect_uri=" . $this->callbackUrl . "&scope=email";
    }

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

    public function getPerson()
    {
        $url = "https://graph.facebook.com/me?access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $infoPersonId = json_decode($response);
        $personId = $infoPersonId->id;

        $url = "https://graph.facebook.com/v7.0/" . $personId . "?fields=id,name,email&access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $infoPerson = json_decode($response);

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $pic = Facebook::curlRequests("https://graph.facebook.com/v7.0/" . $personId . "/picture?type=normal", $headers);
        $info = (object) array("username" => $infoPerson->email, "name" => $infoPerson->name, "email" => $infoPerson->email, "pictureUrl" => $pic);
        return $info;
    }

    public function downloadPhoto($pic, $userId)
    {
        $path = 'photos/' . $userId . '.png';
        file_put_contents($path, $pic);
    }

    public static function curlRequests($url, $headers = null)
    {   
        $ch = curl_init($url);
        if ($headers) {
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer EAAhue1T99zoBAK1kajoCi8SYYi3rAdDZAtn6u8ampX1FmpeNlBmAxLa6SR24L7WX9krVAfZC2vJ48DqLFxKFJnQN5WufOHHpnaylrhxZAcTuRU3LZAlCXCKng1otZBSDk9FWVB3iNFF5T1gGXH8Phl8EHbrxZCDDgwgswzNSVZBSEgAUf7icQxO"
                ),
            ));
        }
            
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_COOKIESESSION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return  $response;
    }
}
