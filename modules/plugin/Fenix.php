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

            //Diana foi aqui que a class User mudou
            //tens que ir buscar o user por studentNumber (getUserByStudentNumber)
            //se returnar null, user nao existe ainda e chamas apenas User::addUserToDB <- funcao passou a ser estatica e tem novos campos por isso vai la ver
            //no else em vez de chamares initialize (que já não existe) chamas editUser, mudei o nome, achei que assim fica mais explicito
            $user = User::getUser($id);
            if (!$user->exists()) {
                $user->addUserToDB($name, $username, $email);
                //depois chama novamente getUserByStudentNumber para ficar com $user definido
            } else {
                $user->initialize($name, $username, $email);
            }
            //nesta apenas tens de mudar $id por $user->getId()
            $courseUser = new CourseUser($id, new Course($courseId));
            if (!$courseUser->exists()) {
                $courseUser->addCourseUserToDB($roleId, $campus);
            } else {
                $courseUser->setCampus($campus);
            }
        }
    }
}
