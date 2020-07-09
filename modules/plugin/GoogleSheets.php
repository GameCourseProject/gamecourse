<?php

namespace Modules\Plugin;

use GameCourse\Google;
use GameCourse\Core;
use GameCourse\Module;

class GoogleSheets
{
    private $courseId;
    private $spreadsheetId;
    private $sheetName;
    private $range;
    private $service;
    private $parent;
    private $gsUser;

    public function __construct($parent, $courseId)
    {
        $this->parent = $parent;
        $this->courseId = $courseId;
        // $this->getDBConfigValues();
        // $this->service = Google::getGoogleSheets();
        // $this->readGoogleSheets();
    }

    public function getCredentialsFromDB()
    {
        $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");

        $uris = explode(";", $credentialDB["redirectUris"]);

        $arrayKey[$credentialDB['key_']] = array(
            'client_id' => $credentialDB['clientId'], "project_id" => $credentialDB["projectId"],
            'auth_uri' => $credentialDB['authUri'], "token_uri" => $credentialDB["tokenUri"], "auth_provider_x509_cert_url" => $credentialDB["authProvider"],
            'client_secret' => $credentialDB["clientSecret"], "redirect_uris" => $uris
        );
        return $arrayKey;
    }

    public function getTokenFromDB()
    {
        $accessExists = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "accessToken");
        if ($accessExists) {
            $credentialDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");

            $arrayToken = array(
                'access_token' => $credentialDB['accessToken'], "expires_in" => $credentialDB["expiresIn"],
                'scope' => $credentialDB['scope'], "token_type" => $credentialDB["tokenType"],
                "created" => $credentialDB["created"], 'refresh_token' => $credentialDB["refreshToken"]
            );
            return json_encode($arrayToken);
        } else {
            return null;
        }
    }

    public function setCredentials()
    {
        $credentials = $this->getCredentialsFromDB();
        Google::setCredentials(json_encode($credentials));
    }

    public function setAuthCode()
    {
        $response = $this->handleToken();
        if ($response["auth_url"]) {
            Core::$systemDB->update(
                "config_google_sheets",
                ["authUrl" => $response["auth_url"]]
            );
        }
    }

    public function handleToken()
    {
        $credentials = $this->getCredentialsFromDB();
        $token = $this->getTokenFromDB();
        $authCode = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "authCode");
        return Google::checkToken($credentials, $token, $authCode);
    }

    public function saveTokenToDB()
    {
        $response = $this->handleToken();
        $token = $response["access_token"];
        if ($token) {

            $arrayToDB = array(
                "course" => $this->courseId,
                "accessToken" => $token["access_token"],
                "expiresIn" => $token["expires_in"],
                "scope" => $token["scope"],
                "tokenType" => $token["token_type"],
                "created" => $token["created"],
                "refreshToken" => $token["refresh_token"]
            );
            Core::$systemDB->update("config_google_sheets", $arrayToDB);
        }
    }

    public function getDBConfigValues()
    {
        $googleSheetsVarsDB = Core::$systemDB->select("config_google_sheets", ["course" => $this->courseId], "*");
        $this->spreadsheetId = $googleSheetsVarsDB["spreadsheetId"];
        $this->sheetName = $googleSheetsVarsDB["sheetName"];
        $this->range = $googleSheetsVarsDB["sheetRange"];
    }

    public function readGoogleSheets()
    {
        $tableName = $this->service->spreadsheets->get($this->spreadsheetId)->properties->title;

        if ($this->range) {
            $responseColumns = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->sheetName . "!" . $this->range, ["majorDimension" => "COLUMNS"]);
            $responseRows = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->sheetName . "!" . $this->range);
        } else {
            $responseColumns = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->sheetName, ["majorDimension" => "COLUMNS"]);
            $responseRows = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->sheetName);
        }
        $valuesColumns = $responseColumns->getValues();
        $valuesRows = $responseRows->getValues();

        //get the array of columns
        $columnNames = array();
        $mergedColumns = array();
        $allColumns = array();
        $counter = 0;
        foreach ($valuesColumns as $column) {
            if (empty($column[0]) && !empty($column)) {
                array_push($mergedColumns, $counter);
                $allColumns[$counter] = "";
            }
            if (!empty($column[0])) {
                $column[0] = preg_replace('/[^a-zA-Z0-9]/', '', $column[0]);
                array_push($columnNames, $column[0]);
                $allColumns[$counter] = $column[0];
            }
            $counter++;
        }
        $this->parent->addTablesByQuery($tableName, $columnNames);

        //handle rows to be at the same size
        $rowsWithValues = array();
        for ($row = 1; $row < sizeof($valuesRows) - 1; $row++) {
            $arrayTemp = array();
            $counter = 0;
            foreach ($valuesRows[$row] as $cell) {
                if (array_key_exists($counter, $allColumns)) {
                    if (!empty($cell)) {
                        if (in_array($counter, $mergedColumns)) { //caso a cell seja merged
                            $lastCell = end($arrayTemp);
                            $lastKey = array_key_last($arrayTemp);
                            $arrayTemp[$lastKey] = $lastCell . ";" . $cell;
                        } else {
                            $arrayTemp[$allColumns[$counter]] = $cell;
                        }
                    } else {
                        if (!in_array($counter, $mergedColumns)) {
                            $arrayTemp[$allColumns[$counter]] = $cell;
                        }
                    }
                }
                $counter++;
            }
            array_push($rowsWithValues, $arrayTemp);
        }

        foreach ($rowsWithValues as $row) {
            $row["course"] = $this->courseId;
            Core::$systemDB->insert(
                $tableName,
                $row
            );
        }
    }


    // for ($i = 1; $i < sizeof($values) - 1; $i++) {
    //     Core::$systemDB->insert(
    //         $tableName,
    //         [
    //         ]
    //     );
    // }



    //     foreach ($values as $row) {
    //     }
    // }

    public function read2($authcode)
    {

        return $this->google->get2($authcode);
    }

    public function aaaa()
    {
        $this->google = new Google;
        $this->service = $this->google->getSheet();
        $response = $this->service->spreadsheets_values->get("19nAT-76e-YViXk-l-BOig9Wm0knVtwaH2_pxm4mrd7U", "Daniel" . "!" . "A1:E18");
        $values = $response->getValues();
        if (empty($values)) {
            print "No data found.\n";
        } else {
            print "Name, Major:\n";
            foreach ($values as $row) {
                // Print columns A and E, which correspond to indices 0 and 4.
                printf("%s, %s\n", $row[0], $row[1]);
            }
        }
    }
}




// class PCMSpreadsheetParser:
//   def __init__(self):
//     self._Authorize()
//     self.curr_key = ''
//     self.curr_wksht_id = ''
//   def _Authorize(self):
//     token = None
//     if not(os.path.exists(auth_file)):
//       token = gdata.gauth.OAuth2Token(
//         client_id=CLIENT_ID,
//         client_secret=CLIENT_SECRET,
//         scope=SCOPE,
//         user_agent=application_name);

//       url = token.generate_authorize_url()
//       print 'Use this url to authorize the application: \n'
//       print url;
//       code = raw_input('What is the verification code? ').strip()
//       token.get_access_token(code)

//       with open(auth_file, 'w') as file:
//         file.write(token.refresh_token + '\n')
//         file.write(token.access_token + '\n')
//     else:
//       refresh_token = ''
//       access_token = ''
//       with open(auth_file, 'r') as file:
//         refresh_token = file.readline().strip()
//         access_token = file.readline().strip()

//       token = gdata.gauth.OAuth2Token(
//         client_id=CLIENT_ID,
//         client_secret=CLIENT_SECRET,
//         scope=SCOPE,
//         user_agent=application_name,
//         refresh_token=refresh_token,
//         access_token=access_token);

//     self.gd_client = gdata.spreadsheets.client.SpreadsheetsClient()
//     token.authorize(self.gd_client)
//   def _FindSpreadsheet(self):
//     # Find the spreadsheet
//     feed = self.gd_client.GetSpreadsheets()
//     for f in feed.entry:
//         if f.title.text=="PCMLogs":
//             entry=f
//             #break
//     id_parts = entry.id.text.split('/')
//     self.curr_key = id_parts[len(id_parts) - 1]
// #    print self.curr_key

//   def _FindWorksheet(self, name):
//     # Get the list of worksheets
//     feed = self.gd_client.GetWorksheets(self.curr_key)
//     for f in feed.entry:
//         if f.title.text==name:
//             entry=f
//             break
//     id_parts = entry.id.text.split('/')
//     self.curr_wksht_id = id_parts[len(id_parts) - 1]
// #    print self.curr_wksht_id


//   def _ReadWorksheet(self):
//     res=[]
//     feed = self.gd_client.GetListFeed(self.curr_key, self.curr_wksht_id)
//     for f in feed.entry:
//         d = f.to_dict() #d = dict(map(lambda e: (e[0],e[1].text), f.custom.items()))
//         if d["num"]:
//             res = res+[ LogLine(d["num"],d["name"].encode("latin1"),time.time(), d["action"],
//                                 d["xp"], d["info"])]
//     return res
