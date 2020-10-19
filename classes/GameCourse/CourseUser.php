<?php

namespace GameCourse;

class CourseUser extends User
{
    //$id is in User
    private $course; //course object

    function __construct($id, $course)
    {
        parent::__construct($id);
        $this->course = $course;
    }
    //adds course_user to DB, User must already exist in DB
    public function addCourseUserToDB($roleId = null, $campus = "")
    {
        Core::$systemDB->insert("course_user", ["course" => $this->course->getId(), "id" => $this->id, "campus" => $campus]);
        if ($roleId) {
            Core::$systemDB->insert("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $roleId]);
        }
    }

    public function exists()
    {
        return (!empty($this->getData('id')));
    }

    public function delete()
    {
        Core::$systemDB->delete("course_user", ["id" => $this->id, "course" => $this->course->getId()]);
    }

    //updates the lastActivity of user to current time, and prevActivity to previous val of activity
    function refreshActivity()
    {
        $lastlast = Core::$systemDB->select("course_user", ["course" => $this->course->getId(), "id" => $this->id], "lastActivity");
        //this is updating the prevActivity (used to decide what notification to show)
        //if you whish to only show notification on the profile page, then you should only refresh activity in that page
        Core::$systemDB->update("course_user", ["previousActivity" => $lastlast], ["course" => $this->course->getId(), "id" => $this->id]);

        Core::$systemDB->update("course_user", ["lastActivity" => date("Y-m-d H:i:s", time())], ["course" => $this->course->getId(), "id" => $this->id]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCourse()
    {
        return $this->course;
    }

    public function getName()
    {
        return parent::getData("name");
    }
    public function getUsername()
    {
        return parent::getUsername();
    }
    public function getEmail()
    {
        return parent::getData("email");
    }
    public function getNickname()
    {
        return parent::getData("nickname");
    }
    public function getStudentNumber()
    {
        return parent::getData("studentNumber");
    }
    public function getLastLogin()
    {
        $lastLogin = $this->getData("lastActivity");
        return parent::lastLoginTimeTostring($lastLogin);
    }
    //gets data from course_user table
    function  getData($field = "*")
    {
        return Core::$systemDB->select("course_user", ["course" => $this->course->getId(), "id" => $this->id], $field);
    }
    //gets data from course_user e game_course_user tables
    function  getAllData($field = "*")
    {
        return Core::$systemDB->select(
            "course_user natural join game_course_user",
            ["course" => $this->course->getId(), "id" => $this->id],
            $field
        );
    }
    function getXP()
    {
        $xpMod = $this->course->getModule("xp");
        if ($xpMod !== null) {
            return $xpMod->calculateXPComponents($this->id, $this->course->getId());
        }
        throw new \Exception("Tried to get XP, but XP is disabled");
    }
    function setCampus($campus)
    {
        return Core::$systemDB->update(
            "course_user",
            ["campus" => $campus],
            ["course" => $this->course->getId(), "id" => $this->id]
        );
    }
    function getCampus()
    {
        return $this->getData("campus");
    }

    function getRolesNames()
    {
        return array_column(Core::$systemDB->selectMultiple(
            "user_role u join role r on role=r.id",
            ["u.course" => $this->course->getId(), "u.id" => $this->id],
            "name"
        ), "name");
    }
    function getRolesIds()
    {
        return array_column(Core::$systemDB->selectMultiple(
            "user_role",
            ["course" => $this->course->getId(), "id" => $this->id],
            "role"
        ), "role");
    }

    //receives array of roles and replaces them in the database
    function setRoles($roles)
    {
        $oldRoles = $this->getRolesNames();
        $courseRoles = $this->course->getRolesData();
        $rolesByName = array_combine(array_column($courseRoles, "name"), $courseRoles);
        foreach ($roles as $role) {
            $found = array_search($role, $oldRoles);
            if ($found === false) {
                $id = Course::getRoleId($role, $this->course->getId());
                Core::$systemDB->insert("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $rolesByName[$role]["id"]]);
            } else {
                unset($oldRoles[$found]);
            }
        }
        //delete the remaining roles
        foreach ($oldRoles as $role) {
            $id = Course::getRoleId($role, $this->course->getId());
            Core::$systemDB->delete("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $id]);
        }
    }

    //adds Role (instead of replacing) only if it isn't already in user's roles
    function addRole($role)
    {
        $currRoles = $this->getRolesNames();
        $courseRoles = $this->course->getRolesData();
        $rolesByName = array_combine(array_column($courseRoles, "name"), $courseRoles);
        if (!in_array($role, $currRoles)) {
            Core::$systemDB->insert("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $rolesByName[$role]["id"]]);
            return true;
        }
        return false;
    }

    function hasRole($role)
    {
        $roleId = Course::getRoleId($role, $this->course->getId());
        return (!empty(Core::$systemDB->selectMultiple(
            "user_role",
            ["course" => $this->course->getId(), "id" => $this->id, "role" => $roleId]
        )));
    }
    function isTeacher()
    {
        return $this->hasRole('Teacher');
    }
    function isStudent()
    {
        return $this->hasRole('Student');
    }

    function getLandingPage()
    {
        $userRoles = $this->getRolesNames(); //array w names
        $landingPage = $this->course->getLandingPage();
        $this->course->goThroughRoles(function ($role, $hasChildren, $continue) use (&$landingPage, $userRoles) {
            if (in_array($role["name"], $userRoles)) {
                $land = $this->course->getRoleByName($role["name"], "landingPage");
                if ($land != '') {
                    $landingPage = $land;
                }
            }
            $continue();
        });
        return $landingPage;
    }

    public static function exportCourseUsers($courseId)
    {
        $listOfUsers = Core::$systemDB->selectMultiple("course_user", ["course" => $courseId], "*");
        $file = "";
        $i = 0;
        $len = count($listOfUsers);
        foreach ($listOfUsers as $courseUser) {
            $user = Core::$systemDB->select("game_course_user", ["id" => $courseUser["id"]]);
            $role = Core::$systemDB->select("user_role", ["id" => $user["id"], "course" => $courseUser["id"]]);
            if (!$role) {
                $roleId = "";
            } else {
                $roleId = $role["id"];
            }
            $file .= $courseUser["course"] . "," .  $user["name"] . "," . $user["nickname"] . "," . $user["email"] . "," .
                $courseUser["campus"] . "," . $user["studentNumber"] . "," . $user["isAdmin"] . "," .  $user["isActive"] . "," . $roleId;
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return $file;
    }

    public static function importCourseUsers($file)
    {
        //$file is a string gotten from reading an .csv or .txt file
        //return number of new courses added pls
        $file = fopen($file, "r");
        while (!feof($file)) {
            $courseUser = fgetcsv($file);
            if (!User::getUserByStudentNumber($courseUser[5])) {
                Core::$systemDB->insert(
                    "game_course_user",
                    [
                        "name" => $courseUser[1],
                        "nickname" => $courseUser[2],
                        "email" => $courseUser[3],
                        "studentNumber" => $courseUser[5],
                        "isAdmin" => $courseUser[6],
                        "isActive" => $courseUser[7]
                    ]
                );
            }
            $newUser = User::getUserByStudentNumber($courseUser[5]);
            if (!Core::$systemDB->select("course_user", ["id" => $newUser->getId(), "course" => $courseUser[0]])) {

                Core::$systemDB->insert(
                    "course_user",
                    [
                        "id" => $newUser->getId(),
                        "course" => $courseUser[0],
                        "campus" => $courseUser[4]
                    ]
                );
                if ($courseUser[8]) {

                    Core::$systemDB->insert(
                        "user_role",
                        [
                            "id" => $newUser->getId(),
                            "course" => $courseUser[0],
                            "role" => $courseUser[8]
                        ]
                    );
                }
            }
        }
    }
}
