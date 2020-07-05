<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\API;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;

class ClassCheck
{
    private $code;
    private $courseId;

    public function __construct($courseId)
    {
        // $this->courseId = $courseId;
        // $this->getDBConfigValues();
        // $this->readAttendance();
    }

    public function getDBConfigValues()
    {
        $classCheckVarsDB = Core::$systemDB->select("config_class_check", ["course" => $this->courseId], "*");

        $this->code = $classCheckVarsDB["tsvCode"];
    }

    public function readAttendance()
    {
        $url = "https://classcheck.tk/tsv/course?s=" . $this->code;
        $fp = fopen($url, 'r');


        while (!feof($fp)) {
            $line = fgets($fp);
            $data = str_getcsv($line, "\t");
            $profNumber = $data[0];
            $studentNumber = $data[2];
            $studentId = substr($studentNumber, 4, strlen($studentNumber) - 1);
            $studentName = $data[3];
            $action = $data[4];
            $att_type = $data[5];
            $classNumber = $data[6];
            $shift = $data[7];

            Core::$systemDB->insert(
                "attendance",
                [
                    "course" => $this->courseId,
                    "studentId" => $studentId,
                    "action" => $action,
                    "class" => $classNumber
                ]
            );
        }
    }
}
