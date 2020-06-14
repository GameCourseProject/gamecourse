<?php

namespace Modules\Plugin;

// include BASE . '/google-api-php-client/vendor/autoload.php';
use GameCourse\Google;

class GoogleSheets
{
    public function __construct($sheets)
    {
        $this->sheets = $sheets;
    }

    public function readGoogleSheets($spreadsheetId, $sheetName, $range)
    {
        $service = Google::getGoogleSheets();
        $response = $service->spreadsheets_values->get($spreadsheetId, $sheetName . "!" . $range);
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
