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
}
