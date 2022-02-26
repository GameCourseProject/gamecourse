<?php
namespace Modules\QR;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

Core::init();

$courseId = $argv[1];
$inserted = 0;
$qr_codes = Core::$systemDB->selectMultiple(QR::TABLE, ["course" => $courseId]);
foreach ($qr_codes as $row) {
    $type = "";
    if ($row["classType"] == "Lecture") {
        $type = "participated in lecture";
    } else if ($row["classType"] == "Invited Lecture") {
        $type = "participated in lecture (invited)";
    }
    if ($row["user"] && $type) {
        if (!Core::$systemDB->select("participation", [
            "user" => $row["user"],
            "course" => $courseId,
            "description" => $row["classNumber"],
            "type" => $type
        ])) {
            Core::$systemDB->insert("participation", [
                "user" => $row["user"],
                "course" => $courseId,
                "description" => $row["classNumber"],
                "type" => $type
            ]);
            $inserted++;

        } else {
            $qr_repetidos_qr_code = Core::$systemDB->selectMultiple(QR::TABLE, [
                "user" => $row["user"],
                "course" => $courseId,
                "classNumber" => $row["classNumber"],
                "classType" => $row["classType"]
            ]);

            $qr_repetidos_participation = Core::$systemDB->selectMultiple("participation", [
                "user" => $row["user"],
                "course" => $courseId,
                "description" => $row["classNumber"],
                "type" => $type
            ]);
            if (count($qr_repetidos_participation) < count($qr_repetidos_qr_code)) {
                Core::$systemDB->insert("participation", [
                    "user" => $row["user"],
                    "course" => $courseId,
                    "description" => $row["classNumber"],
                    "type" => $type
                ]);
                $inserted++;
            }
        }
    }
}
if ($inserted > 0) {
    return true;
} else return false;
