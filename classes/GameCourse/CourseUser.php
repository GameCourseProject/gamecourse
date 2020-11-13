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

    public static function addCourseUser($courseId, $id, $roleId = null, $campus = "")
    {
        $id = Core::$systemDB->insert("course_user", ["course" => $courseId, "id" => $id, "campus" => $campus]);
        if ($roleId) {
            Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $id, "role" => $roleId]);
        }
        return $id;
    }

    public static function editCourseUser($id, $course, $campus, $role){
        Core::$systemDB->update(
            "course_user",
            [
                "course" => $course,
                "campus" => $campus,
            ],
            [
                "id" => $id
            ]
        );
        if ($role) {
            if (!Core::$systemDB->select("user_role", ["id" => $id])) {

                Core::$systemDB->insert(
                    "user_role",
                    [
                        "id" => $id,
                        "course" => $course,
                        "role" => $role
                    ]
                );
            } else {
                Core::$systemDB->update(
                    "user_role",
                    [
                        "course" => $course,
                        "role" => $role
                    ],
                    [
                        "id" => $id
                    ]

                );
            }
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
        $file .= "course,name,nickname,email,campus,studentNumber,isAdmin,isActive,roles,username,auth\n";
        foreach ($listOfUsers as $courseUser) {
            $user = Core::$systemDB->select("game_course_user", ["id" => $courseUser["id"]]);
            $auth = Core::$systemDB->select("auth", ["game_course_user_id" => $user["id"]]);
            $roles = Core::$systemDB->selectMultiple("user_role", ["id" => $user["id"], "course" => $courseUser["course"]]);
            $roleId = "";
            if ($roles) {
                $lenRoles = count($roles);
                $j = 0;
                foreach ($roles as $value) {
                    $roleName = Core::$systemDB->select("role", ["id" => $value["role"]]);
                    $roleId .= $roleName["name"]; 
                    if ($j != $lenRoles - 1) {
                        $roleId .= "-";
                    }
                    $j++;
                }
            }
            $file .= $courseUser["course"] . "," .  $user["name"] . "," . $user["nickname"] . "," . $user["email"] . "," .
                $courseUser["campus"] . "," . $user["studentNumber"] . "," . $user["isAdmin"] . "," .  $user["isActive"] . "," . 
                $roleId . "," . $auth["username"] . "," . $auth["authentication_service"];
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return $file;
    }

    public static function importCourseUsers($fileData, $replace=true)
    {
        $newUsersNr = 0;
        $lines = explode("\n", $fileData);

        $has1stLine = false;
        $courseIndex = "";
        $nameIndex = "";
        $nicknameIndex = "";
        $emailIndex = "";
        $campusIndex = "";
        $studentNumberIndex = "";
        $isAdminIndex = "";
        $isActiveIndex = "";
        $rolesIndex = "";
        $usernameIndex = "";
        $authIndex = "";
        //se tiver 1ª linha com nomes
        if ($lines[0]) {
            $lines[0] = trim($lines[0]);
            $firstLine = explode(",", $lines[0]);
            $firstLine = array_map('trim', $firstLine);
                if (in_array("course", $firstLine) && in_array("name", $firstLine) 
                && in_array("email", $firstLine) && in_array("nickname", $firstLine) 
                && in_array("campus", $firstLine) && in_array("studentNumber", $firstLine)
                && in_array("isAdmin", $firstLine) && in_array("isActive", $firstLine) 
                && in_array("roles", $firstLine) && in_array("username", $firstLine) && in_array("auth", $firstLine) 
            ) {
                $has1stLine = true;
                $courseIndex = array_search("course", $firstLine);
                $nameIndex = array_search("name", $firstLine);
                $nicknameIndex = array_search("nickname", $firstLine);
                $emailIndex = array_search("email", $firstLine);
                $campusIndex = array_search("campus", $firstLine);
                $studentNumberIndex = array_search("studentNumber", $firstLine);
                $isAdminIndex = array_search("isAdmin", $firstLine);
                $isActiveIndex = array_search("isActive", $firstLine);
                $rolesIndex = array_search("roles", $firstLine);
                $usernameIndex = array_search("username", $firstLine);
                $authIndex = array_search("auth", $firstLine);
            }
        }
        $i = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            $user = explode(",", $line);
            $user = array_map('trim', $user);
            if (count($user) == 11) {
                if (!$has1stLine) {
                    $courseIndex = 0;
                    $nameIndex = 1;
                    $nicknameIndex = 2;
                    $emailIndex = 3;
                    $campusIndex = 4;
                    $studentNumberIndex = 5;
                    $isAdminIndex = 6;
                    $isActiveIndex = 7;
                    $rolesIndex = 8;
                    $usernameIndex = 9;
                    $authIndex = 10;
                }

                if (!$has1stLine || ($i != 0 && $has1stLine)) {
                    //.csv tem studentNumber e email a branco
                    if ($user[$studentNumberIndex] == "" && $user[$emailIndex] == "") {
                        $user[$studentNumberIndex] = null;
                        $user[$emailIndex] = null;
                        $id = User::addUserToDB($user[$nameIndex],$user[$usernameIndex],$user[$authIndex],$user[$emailIndex],$user[$studentNumberIndex],$user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                        CourseUser::addCourseUser($user[$courseIndex], $id, null, $user[$campusIndex]);
                        $courseUserObj = new CourseUser($id, new Course($user[$courseIndex]));
                        if (!$user[$rolesIndex] == "") {
                            $userRolesArr =  explode("-", $user[$rolesIndex]);
                            $userRolesArr = array_map('trim', $userRolesArr);
                            foreach ($userRolesArr as $valueRole) {
                                $courseUserObj->addRole($valueRole);
                            }
                        }
                        $newUsersNr++;
                    //.csv tem studentNumber 
                    } else if ($user[$studentNumberIndex] != "") {
                        //esse studentNumber não existe
                        if (!User::getUserByStudentNumber($user[$studentNumberIndex])) {
                            //email não existe
                            if (!User::getUserByEmail($user[$emailIndex]) && $user[$emailIndex] != "") {
                                $id = User::addUserToDB($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                                CourseUser::addCourseUser($user[$courseIndex], $id, null, $user[$campusIndex]);
                                $courseUserObj = new CourseUser($id, new Course($user[$courseIndex]));
                                if (!$user[$rolesIndex] == "") {
                                    $userRolesArr =  explode("-", $user[$rolesIndex]);
                                    $userRolesArr = array_map('trim', $userRolesArr);
                                    foreach ($userRolesArr as $valueRole) {
                                        $courseUserObj->addRole($valueRole);
                                    }
                                }
                                $newUsersNr++;
                            } else {
                                //email existe
                                if($user[$emailIndex] && User::getUserByEmail($user[$emailIndex])){
                                    $userUpdated = User::getUserByEmail($user[$emailIndex]);
                                    if ($replace) {
                                        $userToUpdate = User::getUserByEmail($user[$emailIndex]);
                                        $userToUpdate->editUser($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                                        
                                        //se nao tem course user
                                        if (!Core::$systemDB->select("course_user", ["id" => $userUpdated->getId()])) {
                                            if ($user[$rolesIndex] == "") {
                                                $user[$rolesIndex] = null;
                                            }
                                            CourseUser::addCourseUser($user[$courseIndex], $userUpdated->getId(), null, $user[$campusIndex]);
                                            $courseUserObj = new CourseUser($userUpdated->getId(), new Course($user[$courseIndex]));
                                            if (!$user[$rolesIndex] == "") {
                                                $userRolesArr =  explode("-", $user[$rolesIndex]);
                                                $userRolesArr = array_map('trim', $userRolesArr);
                                                foreach ($userRolesArr as $valueRole) {
                                                    $courseUserObj->addRole($valueRole);
                                                }
                                            }
                                            $newUsersNr++;
                                        }else{
                                            Core::$systemDB->delete("user_role", ["course" => $user[$courseIndex], "id" => $userToUpdate->getId()]);
                                            $courseUserObj = new CourseUser(
                                                $userToUpdate->getId(),
                                                new Course($user[$courseIndex])
                                            );
                                            if (!$user[$rolesIndex] == "") {
                                                $userRolesArr =  explode("-", $user[$rolesIndex]);
                                                $userRolesArr = array_map('trim', $userRolesArr);
                                                foreach ($userRolesArr as $valueRole) {
                                                    $courseUserObj->addRole($valueRole);
                                                }
                                            }
                                            CourseUser::editCourseUser($userToUpdate->getId(), $user[$courseIndex], $user[$campusIndex], null); 
                                        }
                                    }
                                }else{
                                    $id = User::addUserToDB($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                                    CourseUser::addCourseUser($user[$courseIndex], $id, null, $user[$campusIndex]);
                                    $courseUserObj = new CourseUser($id, new Course($user[$courseIndex]));
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    $newUsersNr++;
                                }
                            }
                        } else {
                            //esse studentNumber existe
                            if ($replace) {
                                $userToUpdate = User::getUserByStudentNumber($user[$studentNumberIndex]);
                                $userToUpdate->editUser($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                              
                                //se tem course user
                                if (!Core::$systemDB->select("course_user", ["id" => $userToUpdate->getId()])) {
                                    CourseUser::addCourseUser($user[$courseIndex], $userToUpdate->getId(), null, $user[$campusIndex]);
                                    $courseUserObj = new CourseUser($userToUpdate->getId(), new Course($user[$courseIndex]));
                                    
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    $newUsersNr++;
                                } else {
                                    Core::$systemDB->delete("user_role", ["course" => $user[$courseIndex], "id" => $userToUpdate->getId()]);
                                    $courseUserObj = new CourseUser(
                                        $userToUpdate->getId(),
                                        new Course($user[$courseIndex])
                                    );
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    CourseUser::editCourseUser($userToUpdate->getId(), $user[$courseIndex], $user[$campusIndex], null); 
                                }
                            }else{                                
                                $userToUpdate = User::getUserByStudentNumber($user[$studentNumberIndex]);
                                 //se nao tiver course user
                                if (!Core::$systemDB->select("course_user", ["id" => $userToUpdate->getId()])) {
                                    CourseUser::addCourseUser($user[$courseIndex], $userToUpdate->getId(), null, $user[$campusIndex]);
                                    $courseUserObj = new CourseUser($userToUpdate->getId(), new Course($user[$courseIndex]));
                                    
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    $newUsersNr++;
                                }
                            }
                        }
                    } else {
                        $user[$studentNumberIndex] = null;
                        if (!User::getUserByEmail($user[$emailIndex])) {
                            $id = User::addUserToDB($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);

                            CourseUser::addCourseUser($user[$courseIndex], $id, null, $user[$campusIndex]);
                            $courseUserObj = new CourseUser($id, new Course($user[$courseIndex]));
                            if (!$user[$rolesIndex] == "") {
                                $userRolesArr =  explode("-", $user[$rolesIndex]);
                                $userRolesArr = array_map('trim', $userRolesArr);
                                foreach ($userRolesArr as $valueRole) {
                                    $courseUserObj->addRole($valueRole);
                                }
                            }
                            $newUsersNr++;
                        } else {
                            if ($replace) {
                                $userToUpdate = User::getUserByEmail($user[$emailIndex]);
                                $userToUpdate->editUser($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                              
                                //se tem course user
                                if (!Core::$systemDB->select("course_user", ["id" => $userToUpdate->getId()])) {

                                    CourseUser::addCourseUser($user[$courseIndex], $userToUpdate->getId(), null, $user[$campusIndex]);
                                    $courseUserObj = new CourseUser(
                                        $userToUpdate->getId(),
                                        new Course($user[$courseIndex])
                                    );
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    $newUsersNr++;
                                
                                } else {
                                    Core::$systemDB->delete("user_role", ["course" => $user[$courseIndex], "id" => $userToUpdate->getId()]);
                                    $courseUserObj = new CourseUser(
                                        $userToUpdate->getId(),
                                        new Course($user[$courseIndex])
                                    );
                                    if (!$user[$rolesIndex] == "") {
                                        $userRolesArr =  explode("-", $user[$rolesIndex]);
                                        $userRolesArr = array_map('trim', $userRolesArr);
                                        foreach ($userRolesArr as $valueRole) {
                                            $courseUserObj->addRole($valueRole);
                                        }
                                    }
                                    CourseUser::editCourseUser($userToUpdate->getId(), $user[$courseIndex], $user[$campusIndex], null);
                                }
                            }
                        }
                    }
                }
            }
            $i++;
        }
        return $newUsersNr;
    }
}

