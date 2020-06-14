<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\API;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;

class Fenix
{

    public function __construct($fenix)
    {
        $this->fenix = $fenix;
    }

    public function getStudents($courseIdUrl)
    {
        $endpoint = "courses/" . $courseIdUrl . "/students";
        $listOfStudents = Core::getStudents($endpoint);
        // $listOfStudents = json_encode($students);

        return $listOfStudents->students;
    }

    public function writeUsersToDB($listOfStudents)
    {
        $courseId = API::getValue('course');
        $course = Core::getCourse($courseId);
        $role = "Student";
        $roleId = Course::getRoleId($role, $courseId);

        foreach ($listOfStudents as $student) {
            $username = $student->username;
            $id = substr($username, 4, strlen($username) - 1);
            $name = "name";
            $email = "email";
            $campus = "";

            if (strpos($student->degree->name, "Alameda") == true) {
                $campus = "A";
            } else if (strpos($student->degree->name, "Taguspark") == true) {
                $campus = "T";
            }

            $user = User::getUser($id);
            if (!$user->exists()) {
                $user->addUserToDB($name, $username, $email);
            } else {
                $user->initialize($name, $username, $email);
            }

            $courseUser = new CourseUser($id, new Course($courseId));
            if (!$courseUser->exists()) {
                $courseUser->addCourseUserToDB($roleId, $campus);
            } else {
                $courseUser->setCampus($campus);
            }
        }
    }
}
