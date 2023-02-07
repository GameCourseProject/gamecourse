<?php
namespace GameCourse\Module\QR;

use Exception;
use GameCourse\AutoGame\AutoGame;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Module;
use GameCourse\Module\ModuleType;
use GameCourse\User\User;
use QRcode;

require __DIR__ . "/lib/phpqrcode.php";

/**
 * This is the QR module, which serves as a compartimentalized
 * plugin that adds functionality to the system.
 */
class QR extends Module
{
    const TABLE_QR_CODE = "qr_code";
    const TABLE_QR_ERROR = "qr_error";

    const QR_FILE = '/qr-code.png';

    public function __construct(?Course $course)
    {
        parent::__construct($course);
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "QR";  // NOTE: must match the name of the class
    const NAME = "QR";
    const DESCRIPTION = "Generates QR codes to be used for student participation in class.";
    const TYPE = ModuleType::UTILITY;

    const VERSION = "2.2.0";                                     // Current module version
    const PROJECT_VERSION = ["min" => "2.2", "max" => null];     // Min/max versions of project for module to work
    const API_VERSION = ["min" => "2.2.0", "max" => null];       // Min/max versions of API for module to work
    // NOTE: versions should be updated on code changes

    const DEPENDENCIES = [];
    // NOTE: dependencies should be updated on code changes

    const RESOURCES = [];


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();
    }

    public function copyTo(Course $copyTo)
    {
        // Nothing to do here
    }

    public function disable()
    {
        $this->cleanDatabase();
    }


    /*** ----------------------------------------------- ***/
    /*** ---------------- Configuration ---------------- ***/
    /*** ----------------------------------------------- ***/

    public function isConfigurable(): bool
    {
        return true;
    }

    public function getPersonalizedConfig(): ?array
    {
        return ["position" => "before"];
    }


    /*** ----------------------------------------------- ***/
    /*** --------------- Module Specific --------------- ***/
    /*** ----------------------------------------------- ***/

    /*** --------- QR Codes --------- ***/

    /**
     * Gets QR codes that haven't been used yet.
     *
     * @return array
     */
    public function getUnusedQRCodes(): array
    {
        $qrCodes = Core::database()->selectMultiple(self::TABLE_QR_CODE, ["course" => $this->getCourse()->getId(), "user" => null], "qrkey, qrcode, qrURL");
        return $qrCodes;
    }

    /**
     * Deletes a given QR code.
     *
     * @param string $qrKey
     * @return void
     */
    public function deleteQRCode(string $qrKey)
    {
        Core::database()->delete(self::TABLE_QR_CODE, ["qrkey" => $qrKey]);
    }


    /**
     * Gets all in-class participations.
     *
     * @return array
     */
    public function getQRParticipations(): array
    {
        $table = self::TABLE_QR_CODE . " qr JOIN " . AutoGame::TABLE_PARTICIPATION . " p on qr.participation=p.id";
        $participations = Core::database()->selectMultiple($table, ["qr.course" => $this->course->getId()], "qr.*, p.id, p.date");
        foreach ($participations as &$participation) {
            unset($participation["course"]);
            unset($participation["participation"]);
            $user = new User($participation["user"]);
            $participation["user"] = $user->getData();
            $participation["user"]["image"] = $user->getImage();
            $participation["classNumber"] = intval($participation["classNumber"]);
        }
        return $participations;
    }

    /**
     * Gets in-class participations for a given user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserQRParticipations(int $userId): array
    {
        $table = self::TABLE_QR_CODE . " qr JOIN " . AutoGame::TABLE_PARTICIPATION . " p on qr.participation=p.id";
        $participations = Core::database()->selectMultiple($table, ["qr.course" => $this->course->getId(), "qr.user" => $userId], "qr.*, p.id, p.date");
        foreach ($participations as &$participation) {
            unset($participation["course"]);
            unset($participation["user"]);
            unset($participation["participation"]);
            $participation["classNumber"] = intval($participation["classNumber"]);
        }
        return $participations;
    }

    /**
     * Adds a class participation for a given user.
     * If no QR key is passed, it will generate a new QR code to link.
     *
     * @param int $userId
     * @param int $classNumber
     * @param string $classType
     * @param string|null $qrKey
     * @return void
     * @throws Exception
     */
    public function addQRParticipation(int $userId, int $classNumber, string $classType, string $qrKey = null)
    {
        if (is_null($qrKey)) {
            // Generate a QR code
            $QRCode = $this->generateQRCodes()[0];
            $qrKey = $QRCode["key"];
        }

        if (!$this->QRExists($qrKey)) { // QR code is not registered
            Core::database()->insert(self::TABLE_QR_ERROR, [
                "course" => $this->course->getId(),
                "user" => $userId,
                "qrkey" => $qrKey,
                "msg" => "QR code not registered"
            ]);
            throw new Exception("Participation not submitted: this QR code is not registered on the system.");
        }

        if ($this->QRHasBeenUsed($qrKey)) { // QR code has already been used
            Core::database()->insert(self::TABLE_QR_ERROR, [
                "course" => $this->course->getId(),
                "user" => $userId,
                "qrkey" => $qrKey,
                "msg" => "QR code has already been redeemed"
            ]);
            throw new Exception("Participation not submitted: this QR code has already been redeemed.");
        }

        // Add participation
        $id = AutoGame::addParticipation($this->course->getId(), $userId, strval($classNumber), "participated in lecture", $this->id);
        Core::database()->update(self::TABLE_QR_CODE,
            ["user" => $userId, "classNumber" => $classNumber, "classType" => $classType, "participation" => $id],
            ["qrkey" => $qrKey]
        );
    }

    /**
     * Edits a class participation.
     *
     * @param int $classNumber
     * @param string $classType
     * @param string|null $qrKey
     * @return void
     * @throws Exception
     */
    public function editQRParticipation(string $qrKey, int $classNumber, string $classType)
    {
        $id = intval(Core::database()->select(self::TABLE_QR_CODE, ["qrkey" => $qrKey], "participation"));
        AutoGame::updateParticipation($id, strval($classNumber), "participated in lecture", date("Y-m-d H:i:s", time()), $this->id);
        Core::database()->update(self::TABLE_QR_CODE, [
            "classNumber" => $classNumber,
            "classType" => $classType
        ], ["qrkey" => $qrKey, "participation" => $id]);
    }

    /**
     * Deletes a given class participation.
     *
     * @param string $qrKey
     * @return void
     */
    public function deleteQRParticipation(string $qrKey)
    {
        $id = intval(Core::database()->select(self::TABLE_QR_CODE, ["qrkey" => $qrKey], "participation"));
        AutoGame::removeParticipation($id);
        Core::database()->delete(self::TABLE_QR_CODE, ["qrkey" => $qrKey]);
    }


    /**
     * Gets all QR code errors.
     *
     * @return array
     */
    public function getQRErrors(): array
    {
        $errors = Core::database()->selectMultiple(self::TABLE_QR_ERROR, ["course" => $this->course->getId()]);
        foreach ($errors as &$error) {
            unset($error["course"]);
            $user = new User($error["user"]);
            $error["user"] = $user->getData();
            $error["user"]["image"] = $user->getImage();
        }
        return $errors;
    }

    /**
     * Gets QR code errors for a given user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserQRErrors(int $userId): array
    {
        $errors = Core::database()->selectMultiple(self::TABLE_QR_ERROR, ["course" => $this->course->getId(), "user" => $userId]);
        foreach ($errors as &$error) {
            unset($error["course"]);
            unset($error["user"]);
        }
        return $errors;
    }


    /**
     * Generates a given number of QR codes.
     *
     * @param int $amount
     * @return array
     * @throws Exception
     */
    public function generateQRCodes(int $amount = 1): array
    {
        if ($amount < 0)
            throw new Exception("Amount of QR codes to generate must be a positive number.");

        $QRCodes = [];
        $datagen = date("YmdHis");
        $courseId = $this->course->getId();

        for ($i = 1; $i <= $amount; $i++) {
            // Generate unique key
            $uid = uniqid();
            $key = "$datagen-$uid";

            // Get URL
            $url = URL . "/#/courses/" . $courseId . "/participation/" . $key;
            $tinyUrl = $this->getTinyURL($url);

            // Generate QR Code with URL
            QRcode::png($url, __DIR__ . self::QR_FILE);
            $data = file_get_contents(__DIR__ . self::QR_FILE);
            $base64 = "data:image/png;base64," . base64_encode($data);
            $QRCodes[] = ["key" => $key, "qr" => $base64, "url" => $tinyUrl];

            // Insert in database
            Core::database()->insert(self::TABLE_QR_CODE, ["qrkey" => $key, "qrcode" => $base64, "qrURL" => $tinyUrl, "course" => $courseId]);
        }

        unlink(__DIR__ . self::QR_FILE);
        return $QRCodes;
    }


    /**
     * Checks whether a given QR code exists in the course context.
     *
     * @param string $qrKey
     * @return bool
     */
    private function QRExists(string $qrKey): bool
    {
        return !empty(Core::database()->select(self::TABLE_QR_CODE, ["course" => $this->course->getId(), "qrkey" => $qrKey]));
    }

    /**
     * Checks whether a given QR code has already been used.
     *
     * @param string $qrKey
     * @return bool
     */
    private function QRHasBeenUsed(string $qrKey): bool
    {
        return !is_null(Core::database()->select(self::TABLE_QR_CODE, ["course" => $this->course->getId(), "qrkey" => $qrKey], "user"));
    }


    /*** --------- Tiny URL --------- ***/

    /**
     * Creates a Tiny URL from a given URL.
     * @see https://tinyurl.com/app/dev
     *
     * @param string $url
     * @return string
     */
    private function getTinyURL(string $url): string
    {
        $ch = curl_init();
        $timeout = 5;
        $content = json_encode(["url" => $url]);

        // Make a POST request to create tiny url
        curl_setopt($ch,CURLOPT_URL,TINY_API_URL . "/create?api_token=" . TINY_API_TOKEN);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);

        $data = json_decode(curl_exec($ch), true)["data"];
        curl_close($ch);

        return $data["tiny_url"];
    }
}
