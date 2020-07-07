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
        $this->courseId = $courseId;
        $this->getDBConfigValues();
        $this->readAttendance();
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

            $prof = User::getUserByUsername($profUsername);
            if($prof){
                $courseUserProf = new CourseUser($prof->getId(), $course);
            }

            $student = User::getUserByUsername($studentUsername);
            if($student){
                $courseUserStudent = new CourseUser($student->getId(), $course);
            }
            
            if ($courseUserStudent->getData("id") && $courseUserProf->getData("id")) {
                Core::$systemDB->insert(
                    "participation",
                    [
                        "user" => $courseUserStudent->getData("id"),
                        "course" => $this->courseId,
                        "description" => $classNumber,
                        "type" => $action,
                        "moduleInstance" => "plugin",
                        "rating" => 0,
                        "evaluator" => $courseUserProf->getData("id")
                    ]
                );
            }
        }
    }
}
