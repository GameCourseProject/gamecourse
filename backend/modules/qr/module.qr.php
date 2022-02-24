<?php
namespace Modules\QR;

include "lib/phpqrcode.php";

use PDOException;
use QRcode;
use GameCourse\API;
use GameCourse\Core;
use GameCourse\Module;
use GameCourse\ModuleLoader;

class QR extends Module
{
    const ID = 'qr';

    const TABLE = 'qr_code';
    const TABLE_ERROR = 'qr_error';

    const QR_FILE = 'qr-code.png';

    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init() {
        $this->setupData();
    }

    public function initAPIEndpoints()
    {
        /**
         * Generates an X number of QR codes.
         *
         * @param int $courseId
         * @param int $nrCodes
         */
        API::registerFunction(self::ID, 'generateQRCodes', function () {
            API::requireCourseAdminPermission();
            API::requireValues('courseId', 'nrCodes');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $nrCodes = intval(API::getValue('nrCodes'));
            $datagen = date('YmdHis');

            // Generate QR codes
            $QRCodes = [];
            for ($i = 1; $i <= $nrCodes; $i++) {
                // Generate unique key
                $uid = uniqid();
                $separator = '-';
                $key = $datagen . $separator . $uid;

                // Insert in database
                Core::$systemDB->insert(QR::TABLE, ["qrkey" => $key, "course" => $courseId]);

                // Get URL
                $url = URL . "/#/courses/" . $courseId . "/participation/" . $key;
                $tinyUrl = $this->getTinyURL($url); // FIXME: not working well

                // Generate QR Code with URL
                QRcode::png($url, self::QR_FILE);
                $data = file_get_contents(self::QR_FILE);
                $base64 = "data:image/png;base64," . base64_encode($data);
                $QRCodes[] = ['qr' => $base64, 'url' => $tinyUrl];
            }

            unlink(self::QR_FILE);
            API::response(['QRCodes' => $QRCodes]);
        });

        /**
         * Submists a lecture participation through a QR code, for the logged user.
         *
         * @param int $courseId
         * @param string $key
         * @param int $lectureNr
         * @param string $typeOfClass
         */
        API::registerFunction(self::ID, 'submitQRParticipation', function () {
            API::requireCoursePermission();
            API::requireValues('courseId', 'lectureNr', 'typeOfClass');

            $courseId = API::getValue('courseId');
            $course = API::verifyCourseExists($courseId);

            $user = Core::getLoggedUser();
            $key = API::getValue('key');
            $lectureNr = API::getValue('lectureNr');
            $typeOfClass = API::getValue('typeOfClass');

            try {
                $check = Core::$systemDB->select(QR::TABLE, ["qrkey" => $key, "course" => $courseId], "*");

                if ($check) { // Code exists
                    if (!($check["user"])) { // Code never used
                        Core::$systemDB->update(QR::TABLE,
                            ["user" => $user->getId(), "classNumber" =>  $lectureNr, "classType" => $typeOfClass],
                            ["qrkey" => $key]
                        );

                        $type = "";
                        if ($typeOfClass == "Lecture") $type = "participated in lecture";
                        else if ($typeOfClass == "Invited Lecture") $type = "participated in lecture (invited)";

                        Core::$systemDB->insert("participation", ["user" => $user->getId(), "course" => $courseId, "description" => $lectureNr, "type" => $type]);

                    } else { // Code has already been redeemed
                        Core::$systemDB->insert(QR::TABLE_ERROR, [
                            "user" => $user->getId(),
                            "course" => $courseId,
                            "ip" => $_SERVER['REMOTE_ADDR'],
                            "qrkey" => $key,
                            "msg" => "Code has already been redeemed."
                        ]);
                        API::error("Sorry. This code has already been redeemed.<br />The participation was not registered.");
                    }

                } else { // Code doesn't exist
                    Core::$systemDB->insert(QR::TABLE_ERROR, [
                        "user" => $user->getId(),
                        "course" => $courseId,
                        "ip" => $_SERVER['REMOTE_ADDR'],
                        "qrkey" => $key,
                        "msg" => "Code not found for this course."
                    ]);
                    API::error("Sorry. This code does not exist.<br />The participation was not registered. ");
                }

            } catch (PDOException $e) {
                Core::$systemDB->insert(QR::TABLE_ERROR, [
                    "user" => $user->getId(),
                    "course" => $courseId,
                    "ip" => $_SERVER['REMOTE_ADDR'],
                    "qrkey" => $key,
                    "msg" => $e->getMessage()
                ]);
                API::error("Sorry. An error occured. Contact your class professor with your QRCode and this message. Your student ID and IP number was registered.");
            }
        });
    }

    public function setupData(){
        $this->addTables(self::ID, self::TABLE);
    }

    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Module Config ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function is_configurable(): bool {
        return true;
    }

    public function has_personalized_config(): bool
    {
        return true;
    }

    public function get_personalized_function(): string
    {
        return self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------ Database Manipulation ------------ ***/
    /*** ----------------------------------------------- ***/

    public function deleteDataRows(int $courseId){
        Core::$systemDB->delete(self::TABLE, ["course" => $courseId]);
        Core::$systemDB->delete(self::TABLE_ERROR, ["course" => $courseId]);
    }


    /*** ----------------------------------------------- ***/
    /*** -------------------- Utils -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function getTinyURL(string $url): string
    {
        $ch = curl_init();
        $timeout = 5;
        $content = json_encode(array('url' => $url));

        // Make a POST request to create tiny url
        curl_setopt($ch,CURLOPT_URL,TINY_API_URL . '/create?api_token=' . TINY_API_TOKEN);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);

        $data = json_decode(curl_exec($ch), true)['data'];
        curl_close($ch);

        return $data['tiny_url'];
    }
}

ModuleLoader::registerModule(array(
    'id' => QR::ID,
    'name' => 'QR',
    'description' => 'Generates a QR code to be used for student participation in class.',
    'type' => 'GameElement',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(),
    'factory' => function() {
        return new QR();
    }
));
