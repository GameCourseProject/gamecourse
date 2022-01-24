<?php

namespace Modules\Fenix;

use GameCourse\Module;
use GameCourse\ModuleLoader;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\CourseUser;
use GameCourse\User;
use GameCourse\CronJob;

class Fenix extends Module
{

    private function setFenixVars($courseId, $fenix)
    {
        $course = new Course($courseId);
        $year = $course->getData("year");
        for ($line = 1; $line < sizeof($fenix) - 1; $line++) {
            $fields = explode(";", $fenix[$line]);
            if(count($fields) < 10){
                return "The number of columns is incorrect, please check the template";
            }

            $username = $fields[0];
            $studentNumber = $fields[1];
            $studentName = $fields[2];
            $email = $fields[3];
            $courseName = $fields[10];
            $major = "";

            if (strpos($courseName, 'Alameda')) {
                $major = "MEIC-A";
            } else if (strpos($courseName, 'Taguspark')) {
                $major = "MEIC-T";
            } else {
                $endpoint = "degrees";
                if($year){
                    $year = str_replace("-", "/", $year);
                    $endpoint = "degrees?academicTerm=".$year;
                }
                $listOfCourses = Core::getFenixInfo($endpoint);
                $courseFound = false;
                if($listOfCourses){
                    foreach ($listOfCourses as $courseFenix) {
                        if ($courseFound) {
                            break;
                        } else {
                            if (strpos($courseName, $courseFenix->name)) {
                                $courseFound = true;
                                foreach ($courseFenix->campus as $campusfenix) {
                                    $major = $campusfenix->name[0];
                                }
                            }
                        }
                    }
                }
            }
            $roleId = Core::$systemDB->select("role", ["name"=>"Student", "course"=>$courseId], "id");
            if($studentNumber){
                if (!User::getUserByStudentNumber($studentNumber)) {
                    User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $user = User::getUserByStudentNumber($studentNumber);
                    $courseUser = new CourseUser($user->getId(), $course);
                    $courseUser->addCourseUserToDB($roleId);
                } else {
                    $existentUser = User::getUserByStudentNumber($studentNumber);
                    $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", 0, 1);
                    $courseUser = new CourseUser($existentUser->getId(), $course);
                    if(!Core::$systemDB->select("course_user", ["id" => $existentUser->getId(), "course" => $courseId])){
                        $courseUser->addCourseUserToDB($roleId);
                    }else{
                        $courseUser->editCourseUser($existentUser->getId(), $course->getId(), $major, null);
                    }
                }
            }else{
                if (!User::getUserByUsername($username)) {
                    User::addUserToDB($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $user = User::getUserByUsername($username);
                    $courseUser = new CourseUser($user->getId(), $course);
                    $courseUser->addCourseUserToDB($roleId);
                } else {
                    $existentUser = User::getUserByUsername($username);
                    $existentUser->editUser($studentName, $username, "fenix", $email, $studentNumber, "", $major, 0, 1);
                    $courseUser = new CourseUser($existentUser->getId(), $course);
                    if (!Core::$systemDB->select("course_user", ["id" => $existentUser->getId(), "course" => $courseId])) {
                        $courseUser->addCourseUserToDB($roleId);
                    } else {
                        $courseUser->editCourseUser($existentUser->getId(), $course->getId(), $major, null);
                    }
                }
            }
        }
        return "";
    }

    // public function setCourseCronJobs();

    public function setupResources()
    {
        parent::addResources('js/');
        parent::addResources('css/');
    }

    // public function readConfigJson();

    public function init()
    {
        API::registerFunction('settings', 'courseFenix', function () {
            API::requireCourseAdminPermission();
            $courseId = API::getValue('course');

            if (API::hasKey('fenix')) {
                $fenix = API::getValue('fenix');
                $lastFileUploaded = count($fenix) - 1;
                if(count($fenix) == 0){
                    API::error("Please fill the mandatory fields");
                }
                //place to verify input values
                $resultFenix = $this->setFenixVars($courseId, $fenix[$lastFileUploaded]);
                if (!$resultFenix) {
                    API::response(["updatedData" => ["Variables for fenix saved"]]);
                } else {
                    API::error($resultFenix);
                }

                return;
            }

            API::response(array());

        });

    }


    public function update_module($compatibleVersions)
    {
        //verificar compatibilidade
    }

    public function is_configurable()
    {
        return true;
    }

    public function has_personalized_config()
    {
        return true;
    }

    public function get_personalized_function()
    {
        return "fenixPersonalizedConfig";
    }

    public function has_general_inputs()
    {
        return false;
    }

    public function has_listing_items()
    {
        return false;
    }
}

ModuleLoader::registerModule(array(
    'id' => 'fenix',
    'name' => 'Fenix',
    'description' => 'Allows Fenix to be automaticaly included on gamecourse.',
    'type' => 'DataSource',
    'version' => '0.1',
    'compatibleVersions' => array(),
    'dependencies' => array(
        array('id' => 'views', 'mode' => 'hard')
    ),
    'factory' => function () {
        return new Fenix();
    }
));

