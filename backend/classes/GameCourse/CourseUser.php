<?php

namespace GameCourse;

use Modules\XP\XPLevels;

class CourseUser extends User
{
    //$id is in User
    private $course; //course object

    function __construct($id, Course $course)
    {
        parent::__construct($id);
        $this->course = $course;
    }
    //adds course_user to DB, User must already exist in DB
    public function addCourseUserToDB($roleId = null)
    {
        Core::$systemDB->insert("course_user", ["course" => $this->course->getId(), "id" => $this->id]);
        if ($roleId) {
            Core::$systemDB->insert("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $roleId]);
        }
    }

    public static function addCourseUser($courseId, $id, $roleId = null)
    {
        Core::$systemDB->insert("course_user", ["course" => $courseId, "id" => $id]);
        if ($roleId) {
            Core::$systemDB->insert("user_role", ["course" => $courseId, "id" => $id, "role" => $roleId]);
        }
        return $id;
    }

    public static function createMockCourseUser(int $courseId, string $name, string $username, string $auth, string $email, string $studentNumber, string $nickname, string $major, int $isAdmin, int $isActive, string $role)
    {
        // Add to system
        if (is_null($user = User::getUserByUsername($username)))
            $userId = User::addUserToDB($name, $username, $auth, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
        else $userId = $user->getId();

        // Add to course
        $courseUser = new CourseUser($userId, Course::getCourse($courseId, false));
        if (!self::userExists($courseId, $userId))
            $courseUser->addCourseUserToDB(Course::getRoleId($role, $courseId));

        return $userId;
    }

    public static function deleteMockCourseUser(int $courseId, int $userId) {
        $courseUser = new CourseUser($userId, Course::getCourse($courseId, false));
        $courseUser->delete();
        User::deleteUserFromDB($userId);
    }

    public static function editCourseUser($id, $course, $role){
        Core::$systemDB->update(
            "course_user",
            [
                "course" => $course,
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

    public static function userExists(int $courseId, int $userId): bool
    {
        return !empty(Core::$systemDB->select("course_user", ["id" => $userId, "course" => $courseId]));
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
        return $lastLogin;
    }
    public function isActive()
    {
        $isActive = $this->getData("isActive");
        return $isActive;
    }
    //gets data from course_user table
    function  getData($field = "*")
    {
        return Core::$systemDB->select("course_user", ["course" => $this->course->getId(), "id" => $this->id], $field);
    }
    //gets data from course_user and game_course_user tables
    function  getAllData($field = "*")
    {
        return Core::$systemDB->select(
            "course_user cu left join game_course_user u on cu.id=u.id join auth a on a.game_course_user_id=u.id",
            ["course" => $this->course->getId(), "game_course_user_id" => $this->id],
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
    /*
    function setCampus($campus)
    {
        return Core::$systemDB->update(
            "course_user",
            ["campus" => $campus],
            ["course" => $this->course->getId(), "id" => $this->id]
        );
    }
    */
    function getMajor()
    {
        return parent::getData("major");
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

    function setIsActive($isActive)
    {
        Core::$systemDB->update("course_user", ["isActive" => $isActive], ["course" => $this->course->getId(), "id" => $this->id]);
    }

    function getUserRolesByHierarchy()
    {
        $courseRoles = $this->course->getRolesHierarchy();
        $userRoles = $this->getRolesNames();
        $hierarchyRoles = array();
        foreach ($courseRoles as $courseRole) {
            $this->setHierarchy($courseRole, $userRoles, $hierarchyRoles);
        }
        return $hierarchyRoles;
    }

    public function setHierarchy($role, $userRoles, &$hierarchyRoles)
    {
        if (array_key_exists("children", $role)) {
            foreach ($role["children"] as $child) {
                $this->setHierarchy($child, $userRoles, $hierarchyRoles);
            }
        }
        if (in_array($role["name"], $userRoles))
            $hierarchyRoles[] = $role["name"];
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

        //if xp module is enabled and course user is no longer a student, remove from user_xp
        $xp = Core::$systemDB->select("course_module", ["course" => $this->course->getId(), "moduleId" => "xp"], "isEnabled");
        if($xp){
            if(!in_array("Student", $rolesByName) and in_array("Student", $oldRoles)) {
                Core::$systemDB->delete(XPLevels::TABLE_XP, ["course" => $this->course->getId(), "user" => $this->id]);
            }
        }

    }

    //adds Role (instead of replacing) only if it isn't already in user's roles
    function addRole($role)
    {
        $currRoles = $this->getRolesNames();
        $courseRoles = $this->course->getRolesData();
        $rolesByName = array_combine(array_column($courseRoles, "name"), $courseRoles);
        if (!in_array($role, $currRoles) and array_key_exists($role, $rolesByName)) {

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
        $courseInfo = Core::$systemDB->select("course", ["id"=>$courseId]);
        $file = "";
        $i = 0;
        $len = count($listOfUsers);
        $file .= "name,email,nickname,studentNumber,isAdmin,isActive,major,roles,username,auth\n";
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

            $file .= $user["name"] . "," . $user["email"] . "," .  $user["nickname"] . "," .
                $user["studentNumber"] . "," . $user["isAdmin"] . "," .  $user["isActive"] . "," .
                $user["major"] . "," . $roleId . "," . $auth["username"] . "," . $auth["authentication_service"];
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return ["Users - ".$courseInfo["name"] . " " . $courseInfo["year"], $file];
    }

    public static function importCourseUsers($fileData, $courseId, $replace=true)
    {
        $newUsersNr = 0;
        $lines = explode("\n", $fileData);

        $has1stLine = false;
        $nameIndex = "";
        $nicknameIndex = "";
        $emailIndex = "";
        $majorIndex = "";
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
            foreach ($firstLine as $item) {
                if (strval($item) !== 'name' && strval($item) !== 'email' && strval($item) !== 'nickname'
                    && strval($item) !== 'studentNumber' && strval($item) !== 'isAdmin' && strval($item) !== 'isActive'
                    && strval($item) !== 'major' && strval($item) !== 'username' && strval($item) !== 'auth' && strval($item) !== 'roles') {

                    $has1stLine = true;
                    $nameIndex = array_search("name", $firstLine);
                    $nicknameIndex = array_search("nickname", $firstLine);
                    $emailIndex = array_search("email", $firstLine);
                    $majorIndex = array_search("major", $firstLine);
                    $studentNumberIndex = array_search("studentNumber", $firstLine);
                    $isAdminIndex = array_search("isAdmin", $firstLine);
                    $isActiveIndex = array_search("isActive", $firstLine);
                    $rolesIndex = array_search("roles", $firstLine);
                    $usernameIndex = array_search("username", $firstLine);
                    $authIndex = array_search("auth", $firstLine);
                }
            }
        }
        $i = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            $user = explode(",", $line);
            $user = array_map('trim', $user);
            if (count($user) == 10) {
                if (!$has1stLine) {
                    $nameIndex = 0;
                    $emailIndex = 1;
                    $nicknameIndex = 2;
                    $studentNumberIndex = 3;
                    $isAdminIndex = 4;
                    $isActiveIndex = 5;
                    $majorIndex = 6;
                    $rolesIndex = 7;
                    $usernameIndex = 8;
                    $authIndex = 9;
                }

                if (!$has1stLine || ($i != 0 && $has1stLine)) {
                    $userId = Core::$systemDB->select("auth", ["username" => $user[$usernameIndex], "authentication_service" => $user[$authIndex]], "game_course_user_id");
                    if ($userId) {
                        $courseUserId = Core::$systemDB->select("course_user", ["id" => $userId, "course" => $courseId]);
                        if(!$courseUserId){
                            CourseUser::addCourseUser($courseId, $userId, null);
                            $courseUserObj = new CourseUser($userId, new Course($courseId));
                            if (!$user[$rolesIndex] == "") {
                                $userRolesArr =  explode("-", $user[$rolesIndex]);
                                $userRolesArr = array_map('trim', $userRolesArr);
                                foreach ($userRolesArr as $valueRole) {
                                    $courseUserObj->addRole($valueRole);
                                }
                            }
                            $newUsersNr++;
                        }else{
                            if ($replace) {
                                $userToUpdate = User::getUserByUsername($user[$usernameIndex]);
                                $userToUpdate->editUser($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$majorIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                            }
                        }
                    } else {
                        $id = User::addUserToDB($user[$nameIndex], $user[$usernameIndex], $user[$authIndex], $user[$emailIndex], $user[$studentNumberIndex], $user[$nicknameIndex], $user[$majorIndex], $user[$isAdminIndex], $user[$isActiveIndex]);
                        CourseUser::addCourseUser($courseId, $id, null);
                        $courseUserObj = new CourseUser($id, new Course($courseId));
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
            $i++;
        }
        return $newUsersNr;
    }
}

