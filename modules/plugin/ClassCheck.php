<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;


class ClassCheck
{
    private $courseId;

    public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }

    public function getDBConfigValues()
    {
        $classCheckVarsDB = Core::$systemDB->select("config_class_check", ["course" => $this->courseId], "*");
        if ($classCheckVarsDB) {
            return $classCheckVarsDB["tsvCode"];
        } else {
            return null;
        }
    }

    public function readAttendance($code)
    {
        $inserted = false;
        $this->getDBConfigValues();
        $url = "https://classcheck.tk/tsv/course?s=" . $code;
        $fp = fopen($url, 'r');

        $course = new Course($this->courseId);
        while (!feof($fp)) {
            $line = fgets($fp);
            $data = str_getcsv($line, "\t");
            $profUsername = $data[0];
            $studentUsername = $data[2];
            $studentName = $data[3];
            $action = $data[4];
            $att_type = $data[5];
            $classNumber = $data[6];
            $shift = $data[7];

            $prof = User::getUserIdByUsername($profUsername);
            if ($prof) {
                $courseUserProf = new CourseUser($prof, $course);
            }

            $student = User::getUserIdByUsername($studentUsername);
            if ($student) {
                $courseUserStudent = new CourseUser($student, $course);
            }

            if ($courseUserStudent && $courseUserProf) {
                $count = Core::$systemDB->select("participation", ["user" => $courseUserStudent->getData("id"), "description" => $classNumber]);
                if (!$count) {
                    $inserted  = true;
                    Core::$systemDB->insert(
                        "participation",
                        [
                            "user" => $courseUserStudent->getData("id"),
                            "course" => $this->courseId,
                            "description" => $classNumber,
                            "type" => $action,
                            "rating" => 0,
                            "evaluator" => $courseUserProf->getData("id")
                        ]
                    );
                }
            }
        }
        return $inserted;
    }
}
