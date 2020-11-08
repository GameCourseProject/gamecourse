<?php
include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;

$username = $argv[1];
$course = $argv[2];
Core::init();

echo "\n-----LOGIN PICTURE-----";
checkPhoto($username);

echo "\n";
echo "\n-----FENIX PLUGIN-----";
$fenix = array();
array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
array_push($fenix, "ist112345;12345;João Silva;js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

$usersInfo = checkFenix($fenix, $course);

if ($usersInfo[0] == 2 && $usersInfo[1] == 0) {
    $gcu1 = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
    $gcu2 = Core::$systemDB->select("game_course_user", ["studentNumber" => "99999"]);
    if ($gcu1 && $gcu2) {
        echo "\nSuccess: Users uploaded";

        $courseUser1 = Core::$systemDB->select("course_user", ["id" => $gcu1["id"]]);
        $courseUser2 = Core::$systemDB->select("course_user", ["id" => $gcu2["id"]]);
        if ($courseUser1 && $courseUser2) {
            echo "\nSuccess: CourseUsers uploaded";
        } else {
            echo "\nFailed: CourseUsers failed to upload";
        }
    } else {
        echo "\nFailed: Users failed to upload";
    }

    $auth1 = Core::$systemDB->select("auth", ["username" => "ist112345"]);
    $auth2 = Core::$systemDB->select("auth", ["username" => "ist199999"]);
    if ($auth1 && $auth2) {
        echo "\nSuccess: Users' authentication uploaded";
    } else {
        echo "\nFailed: Users' authentication failed to upload";
    }
} else {
    echo "\nFailed: The users where not created correctly";
}

$fenix = array();
$updatedName = "João Silvestre";
array_push($fenix, "Username;Número;Nome;Email;Agrupamento PCM Labs;Turno Teórica;Turno Laboratorial;Total de Inscrições;Tipo de Inscrição;Estado Matrícula;Curso");
array_push($fenix, "ist112345;12345;" . $updatedName . ";js@tecnico.ulisboa.pt; 33 - PCM264L05; PCM264T02; ;1; Normal; Matriculado; Licenciatura Bolonha em Engenharia Informática e de Computadores - Alameda - LEIC-A 2006");
array_push($fenix, "ist199999;99999;Ana Alves;ft@tecnico.ulisboa.pt; 34 - PCM264L06; PCM264T01; ;1; Normal; Matriculado; Mestrado Bolonha em Engenharia Informática e de Computadores - Taguspark - MEIC-T 2015");

$usersInfo = checkFenix($fenix, $course);
if ($usersInfo[0] == 0 && $usersInfo[1] == 2) {
    $gcu = Core::$systemDB->select("game_course_user", ["studentNumber" => "12345"]);
    if ($gcu) {
        if ($gcu["name"] == $updatedName) {
            echo "\nSuccess: User updated";
        } else {
            echo "\nFailed: User failed to update";
        }
    }
} else {
    echo "\nFailed: The users where not updated correctly";
}


//Check if a photo is created when logging in (by username)
function checkPhoto($username)
{
    if ($username) {
        $id = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
        if (!$id) {
            echo "\nFailed: " . $username . " does not exist";
        } else {

            if (file_exists("photos/" . $id . ".png")) {
                echo "\nSuccess: Photo created";
            } else {
                echo "\nFailed: Photo was not created";
            }
        }
    } else {
        echo "\nFailed: " . $username . " does not exist";
    }
}

//Check if users where created/updated
function checkFenix($fenix, $course)
{
    $newUsers = 0;
    $updatedUsers = 0;
    $course = new Course($course);
    for ($line = 1; $line < sizeof($fenix); $line++) {
        $fields = explode(";", $fenix[$line]);
        $username = $fields[0];
        $studentNumber = $fields[1];
        $studentName = $fields[2];
        $email = $fields[3];
        $courseName = $fields[10];
        $campus = "";
        if (strpos($courseName, 'Alameda')) {
            $campus = "A";
        } else if (strpos($courseName, 'Taguspark')) {
            $campus = "T";
        } else {
            $endpoint = "degrees?academicTerm=2019/2020";
            $listOfCourses = Core::getFenixInfo($endpoint);
            $courseFound = false;
            foreach ($listOfCourses as $courseFenix) {
                if ($courseFound) {
                    break;
                } else {
                    if (strpos($courseName, $courseFenix->name)) {
                        $courseFound = true;
                        foreach ($courseFenix->campus as $campusfenix) {
                            $campus = $campusfenix->name[0];
                        }
                    }
                }
            }
        }
        if ($studentNumber) {
            if (!User::getUserByStudentNumber($studentNumber)) {
                User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $user = User::getUserByStudentNumber($studentNumber);
                $courseUser = new CourseUser($user->getId(), $course);
                $courseUser->addCourseUserToDB(2, $campus);
                $newUsers++;
            } else {
                $existentUser = User::getUserByStudentNumber($studentNumber);
                $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $updatedUsers++;
            }
        } else {
            if (!User::getUserByEmail($email)) {
                User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $user = User::getUserByEmail($email);
                $courseUser = new CourseUser($user->getId(), $course);
                $courseUser->addCourseUserToDB(2, $campus);
                $newUsers++;
            } else {
                $existentUser = User::getUserByEmail($email);
                $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                $updatedUsers++;
            }
        }
    }

    return [$newUsers, $updatedUsers];
}
