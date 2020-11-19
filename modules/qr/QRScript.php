<?php

namespace Modules\QR;

chdir('/var/www/html/gamecourse');
include 'classes/ClassLoader.class.php';
include 'classes/GameCourse/Core.php';
include 'classes/GameCourse/User.php';
include 'classes/GameCourse/Course.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\User;

Core::init();
$course = $argv[1];
$inserted = 0;
$qr_codes = Core::$systemDB->selectMultiple("qr_code", ["course" => $course]);
foreach ($qr_codes as $row) {
    $type = "";
    if ($row["classType"] == "Lecture") {
        $type = "participated in lecture";
    } else if ($row["classType"] == "Invited Lecture") {
        $type = "participated in lecture (invited)";
    }
    if ($row["studentNumber"] && $type) {
        if (!Core::$systemDB->select("participation", [
            "user" => $row["studentNumber"],
            "course" => $course,
            "description" => $row["classNumber"],
            "type" => $type
        ])) {
            Core::$systemDB->insert("participation", [
                "user" => $row["studentNumber"],
                "course" => $course,
                "description" => $row["classNumber"],
                "type" => $type
            ]);
            $inserted++;
        } else {
            $qr_repetidos_qr_code = Core::$systemDB->selectMultiple("qr_code", [
                "studentNumber" => $row["studentNumber"],
                "course" => $course,
                "classNumber" => $row["classNumber"],
                "classType" => $type
            ]);

            $qr_repetidos_participation = Core::$systemDB->selectMultiple("participation", [
                "user" => $row["studentNumber"],
                "course" => $course,
                "description" => $row["classNumber"],
                "type" => $type
            ]);
            if (count($qr_repetidos_participation) < count($qr_repetidos_qr_code)) {
                Core::$systemDB->insert("participation", [
                    "user" => $row["studentNumber"],
                    "course" => $course,
                    "description" => $row["classNumber"],
                    "type" => $type
                ]);
                $inserted++;
            }
        }
    }
}
if ($inserted > 0) {
    Course::newExternalData();
}
