<?php

namespace Modules\ClassCheck;

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
        $classCheckVarsDB = Core::$systemDB->select(ClassCheckModule::TABLE_CONFIG, ["course" => $this->courseId], "*");
        if ($classCheckVarsDB) {
            return $classCheckVarsDB["tsvCode"];
        } else {
            return null;
        }
    }

    public static function checkConnection($code){
        try {
            $result = fopen($code, 'r');
            return $result;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public function readAttendance($code)
    {
        $inserted = false;
        $this->getDBConfigValues();
        $fp = fopen($code, 'r');

        $course = new Course($this->courseId);
        while (!feof($fp)) {
            $line = fgets($fp);
            $data = str_getcsv($line, "\t");
            if (count($data) < 8) {
                // to do : file returns extra info, source should be fixed
                // if len of line is too small, do not parse
                break;
            }
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
                $count = Core::$systemDB->selectMultiple("participation", ["user" => $courseUserStudent->getData("id"), "description" => $classNumber, "course" => $this->courseId], "*", null, [] , [], null, ["type" => "attended lecture%"] );
                if (Core::$systemDB->select("course_user", ["course" => $this->courseId, "id" => $courseUserStudent->getData("id")])) {
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
        }
        return $inserted;
    }
}
