<?php

require_once("facebook.config.php");


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
        $this->callbackUrl = $config["callback_url"] ?? null;
        $this->accessToken = $config["access_token"] ?? null;
        $this->refreshToken = isset($config["refresh_token"]) ? $config["access_token"] : null;

        if (isset($_SESSION['accessToken'])) {
            $this->accessToken = $_SESSION['accessToken'];
            $this->refreshToken = $_SESSION['accessToken'];
            $this->expirationTime = $_SESSION['expires'];
        }
    }

    public static function getSingleton(): Facebook
    {
        if (self::$INSTANCE == null) self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function getAuthUrl(): string
    {
        return "https://www.facebook.com/v8.0/dialog/oauth?client_id=" . $this->accessKey .
            "&redirect_uri=" . $this->callbackUrl . "&scope=email";
    }

    public function getAccessTokenFromCode($code)
    {
        $url = "https://graph.facebook.com/v8.0/oauth/access_token?client_id=" . $this->accessKey .
            "&redirect_uri=" . $this->callbackUrl . "&client_secret=" . $this->secretKey . "&code=" . $code;

        $response = Facebook::curlRequests($url);

        $token = json_decode($response);
        $this->accessToken = $_SESSION['accessToken'] = $token->access_token;
        $this->expirationTime = $_SESSION['expires'] = time() + $token->expires_in;
        return $this->accessToken;
    }

    public function getPerson(): object
    {
        $url = "https://graph.facebook.com/me?access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $infoPersonId = json_decode($response);
        $personId = $infoPersonId->id;

        $url = "https://graph.facebook.com/v8.0/" . $personId . "?fields=id,name,email&access_token=" . $this->accessToken;
        $response = Facebook::curlRequests($url);
        $infoPerson = json_decode($response);

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $pic = Facebook::curlRequests("https://graph.facebook.com/v8.0/" . $personId . "/picture?type=normal", $headers);
        return (object) array("username" => $infoPerson->email, "name" => $infoPerson->name, "email" => $infoPerson->email, "pictureUrl" => $pic);
    }

    public function downloadPhoto(string $img, int $userId)
    {
        $path = ROOT_PATH . '/' . $userId . '/profile.png';
        file_put_contents($path, $img);
    }

    public static function curlRequests($url, $headers = null)
    {
        $ch = curl_init($url);
        if ($headers) {
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => $headers
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
