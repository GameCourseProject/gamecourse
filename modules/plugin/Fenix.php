<?php

namespace Modules\Plugin;

use GameCourse\Core;
use GameCourse\API;
use GameCourse\User;
use GameCourse\CourseUser;
use GameCourse\Course;

class Fenix
{

    public function __construct()
    {
        // $bodyContent = $this->getStudentsInfo("https://fenix.tecnico.ulisboa.pt/disciplinas/PCM26/2018-2019/2-semestre/notas");
        // $listOfStudents = $this->getStudents($this->fenixCourseId);
        $parsedHTML = $this->parseHTML(); //for now it's a file, later it will access a given url
        $this->writeUsersToDB($parsedHTML);
    }

    public function parseHTML()
    {
        $body = file_get_contents("C:/xampp/htdocs/gamecourse/modules/plugin/fenixAlunos.txt");
        $dom = new \DOMDocument(5, $encoding = 'UTF-8');
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $body);

        $studentsTable = $dom->getElementsByTagName('table')[0];
        $studentInfo = array();
        if ($studentsTable != null) {
            $elements = $studentsTable->getElementsByTagName('tr');
            for ($i = 1; $i < $elements->length; $i++) {
                $username = $elements[$i]->childNodes[1]->nodeValue;
                $number = $elements[$i]->childNodes[3]->nodeValue;
                $name = $elements[$i]->childNodes[5]->nodeValue;
                $course = $elements[$i]->childNodes[7]->nodeValue;
                array_push($studentInfo, ["username" => $username, "number" => $number, "name" => $name, "course" => $course]);
            }
        }
        return $studentInfo;
    }

    public function writeUsersToDB($parsedHtml)
    {
        $courseId = API::getValue('course');
        $course = Core::getCourse($courseId);
        $role = "Student";
        $roleId = Course::getRoleId($role, $courseId);

        foreach ($parsedHtml as $student) {
            $id = $student["number"];
            $username = $student["username"];
            $name = $student["name"];
            $campus = "";
            $email = "";

            if (strpos($student["course"], "-A") == true) {
                $campus = "A";
            } else if (strpos($student["course"], "-T") == true) {
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

    //access to fenix api, not used for now
    public function getStudents($courseIdUrl)
    {
        $endpoint = "courses/" . $courseIdUrl . "/students";
        $listOfStudents = Core::getStudents($endpoint);
        // $listOfStudents = json_encode($students);

        return $listOfStudents->students;
    }

    //with url
    public function getStudentsInfo($courseUrl)
    {
        $isCLI = Core::isCLI();

        if (!Core::requireSetup(false))
            die('Please perform setup first!');

        if ($isCLI) {
            echo "aaaaaaaa";
            // $courseId = (array_key_exists(1, $argv) ? $argv[1] : 0);
            // $id = 2;
            // while (array_key_exists($id, $argv)) {
            //     $courseUrls[] = $argv[$id];
            //     $id++;
            // }
        } else {
            echo "bbbbbbb";
            $courseId = (array_key_exists('course', $_GET) ? $_GET['course'] : 0);
            $id = 0;
            while (array_key_exists('courseurl' . $id, $_GET)) {
                $courseUrls[] = $_GET['courseurl' . $id];
                $id++;
            }
            if (array_key_exists('backendid', $_GET))
                $BACKENDID = $_GET['backendid'];
            if (array_key_exists('jsessionid', $_GET))
                $JSESSIONID = $_GET['jsessionid'];
        }

        $course = Course::getCourse($courseId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: JSESSIONID=' . $JSESSIONID . ';BACKENDID=' . $BACKENDID));

        curl_setopt($ch, CURLOPT_URL, $courseUrl);
        $response = curl_exec($ch);

        if ($response === false) {
            die(curl_error($ch));
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        return substr($response, $header_size);
    }
}
