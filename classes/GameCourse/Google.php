<?php

namespace GameCourse;

include 'google-api-php-client/vendor/autoload.php';

class Google
{
    private $client;
    public static function getGoogleSheets()
    {
        $client = new \Google_Client();
        $client->setApplicationName('spreadsheets');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig('google-api-php-client/testeC.json');

        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // // Load previously authorized token from a file, if it exists.
        // // The file token.json stores the user's access and refresh tokens, and is
        // // created automatically when the authorization flow completes for the first
        // // time.
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                // printf("Open the following link in your browser:\n%s\n", $authUrl);
                // print 'Enter verification code: ';
                $authCode = "4/1QGhh1Bezr3Dn_R0oWvn6jGLiocjGkRNl1DByzlxsKzFHi9q4ZFUBh0";


                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return new \Google_Service_Sheets($client);
    }

    public function get2($authCode)
    {
        $tokenPath = 'token.json';
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        $this->client->setAccessToken($accessToken);
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        return new \Google_Service_Sheets($this->client);
    }
    public function getSheet()
    {
        $result = true;
        $this->client  = new \Google_Client();
        $this->client->setApplicationName('spreadsheets');
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig('google-api-php-client/testeC.json');

        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else {
                $authUrl = $this->client->createAuthUrl();
                file_put_contents("tttt.txt", $authUrl);

                return;
            }
            // Save the token to a file.
            if ($result) {

                if (!file_exists(dirname($tokenPath))) {
                    mkdir(dirname($tokenPath), 0700, true);
                }
                file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
                return new \Google_Service_Sheets($this->client);
            }
        }
        return $result;
    }
}
