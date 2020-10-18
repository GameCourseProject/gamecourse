<?php

namespace GameCourse;

class User
{
    protected $id = null;

    public function __construct($id)
    {
        $this->id = $id;
    }

    //identificador interno - externo passou a ser student number
    //id ainda existe mas nao tem referencia fora do sistema

    //previously was not static
    public static function addUserToDB($name, $username, $authenticationService, $email, $studentNumber, $nickname, $isAdmin, $isActive)
    {
        $id = Core::$systemDB->insert("game_course_user", [
            "name" => $name,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "isAdmin" => $isAdmin,
            "isActive" => $isActive
        ]);
        Core::$systemDB->insert("auth", [
            "game_course_user_id" => $id,
            "username" => $username,
            "authentication_service" => $authenticationService
        ]);
        return $id;
    }

    public function exists()
    {
        return (!empty($this->getData("id")));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getData($field = '*')
    {
        return Core::$systemDB->select("game_course_user", ["id" => $this->id], $field);
    }

    public function setData($fieldValues)
    {
        Core::$systemDB->update("game_course_user", $fieldValues, ["id" => $this->id]);
    }

    public function getName()
    {
        return $this->getData("name");
    }
    public function setName($name)
    {
        $this->setData(["name" => $name]);
    }

    public function getEmail()
    {
        return $this->getData("email");
    }
    public function setEmail($email)
    {
        $this->setData(["email" => $email]);
    }

    public function getUsername()
    {
        return Core::$systemDB->select("auth", ["game_course_user_id" => $this->id], "username");
    }
    public function setUsername($username)
    {
        Core::$systemDB->update("auth", ["username" => $username], ["game_course_user_id" => $this->id]);
    }
    public function setAuthenticationService($authenticationService)
    {
        Core::$systemDB->update("auth", [ "authentication_service" => $authenticationService], ["game_course_user_id" => $this->id]);
    }
    public function getNickname()
    {
        return $this->getData("nickname");
    }
    public function setNickname($nickname)
    {
        $this->setData(["nickname" => $nickname]);
    }
    public function getStudentNumber()
    {
        return $this->getData("studentNumber");
    }
    public function setStudentNumber($studentNumber)
    {
        $this->setData(["studentNumber" => $studentNumber]);
    }


    public function isAdmin()
    {
        return $this->getData("isAdmin");
    }
    public function setAdmin($isAdmin)
    {
        $this->setData(["isAdmin" => $isAdmin]);
    }
    public function isActive()
    {
        return $this->getData("isActive");
    }
    public function setActive($isActive)
    {
        $this->setData(["isActive" => $isActive]);
    }

    public static function getAdmins()
    {
        return array_column(Core::$systemDB->selectMultiple("game_course_user", ["isAdmin" => true], 'id'), 'id');
    }

    public function editUser($name, $username, $authenticationService,  $email, $studentNumber, $nickname, $isAdmin, $isActive)
    {
        Core::$systemDB->update("game_course_user", [
            "name" => $name,
            "email" => $email,
            "studentNumber" => $studentNumber,
            "nickname" => $nickname,
            "isAdmin" => $isAdmin,
            "isActive" => $isActive
        ], ["id" => $this->id]);

        Core::$systemDB->update("auth", [
            "username" => $username,
            "authentication_service" => $authenticationService
        ], ["game_course_user_id" => $this->id]);
        return $this;
    }

    public static function getUser($id)
    {
        return new User($id);
    }

    public static function getUserByUsername($username)
    {
        $userId = Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
        // $userId = Core::$systemDB->select("game_course_user", ["username" => $username], "id");
        if ($userId == null) {
            return null;
        } else {
            return new User($userId);
        }
    }

    public static function getUserIdByUsername($username)
    {
        return Core::$systemDB->select("auth", ["username" => $username], "game_course_user_id");
    }
    public static function getUserAuthenticationService($username)
    {
        return Core::$systemDB->select("auth", ["username" => $username], "authentication_service");
    }

    public static function getUserByStudentNumber($studentNumber)
    {
        $userId = Core::$systemDB->select("game_course_user", ["studentNumber" => $studentNumber], "id");
        // user is not on DB yet
        if ($userId == null)
            return null;
        else
            return new User($userId);
    }

    public function getCourses()
    {
        return array_column(Core::$systemDB->selectMultiple("course_user", ["id" => $this->id], "course"), "course");
    }

    public function lastLoginTimeTostring($lastLogin)
    {
        if ($lastLogin == "0000-00-00 00:00:00")
            return "never";
        else {
            $lastTime = new \DateTime($lastLogin);
            $currentTime = new \DateTime(date("Y-m-d H:i:s"));
            $interval = $currentTime->diff($lastTime);

            $years = $interval->format('%y');
            $months = $interval->format('%m');
            $days = $interval->format('%d');
            $hours = $interval->format('%h');
            $minutes = $interval->format('%i');
            if ($years != "0")
                return $years == "1" ? $years . " year ago" : $years . " years ago";
            if ($months != "0")
                return $months == "1" ? $months . " month ago" : $months . " months ago";
            if ($days != "0")
                return $days == "1" ? $days . " day ago" : $days . " days ago";
            if ($hours != "0")
                return $hours == "1" ? $hours . " hour ago" : $hours . " hours ago";
            if ($minutes != "0")
                return $minutes == "1" ? $minutes . " minute ago" : $minutes . " minutes ago";
            else
                return "now";
        }
    }

    public function getSystemLastLogin()
    {
        $allLastLogins = array_column(Core::$systemDB->selectMultiple("course_user", ["id" => $this->id], "lastActivity"), "lastActivity");
        $lastLogin = "";
        if (!empty($allLastLogins)) {
            $lastLogin = max($allLastLogins);
            return $this->lastLoginTimeTostring($lastLogin);
        } else {
            return "never";
        }
    }

    // public static function getAll() {//ToDo
    //     if (static::$usersDB == null)
    //         static::initDB();
    //     return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
    // }

    public static function getAllInfo()
    {
        return Core::$systemDB->selectMultiple("game_course_user");
    }

    public static function deleteUser($userId)
    {
        Core::$systemDB->delete("game_course_user", ["id" => $userId]);
    }

    public static function saveImage($img, $userId)
    {
        file_put_contents("photos/". $userId . ".png", $img);
    }

    public static function getImage($userId)
    {
        if(file_exists("photos/" . $userId . ".png")){
            return file_get_contents("photos/" . $userId . ".png");
        }else{
            return null;
        }
    }
    public static function exportUsers()
    {
        $listOfUsers = User::getAllInfo();
        $file = "";
        $i = 0;
        $len = count($listOfUsers);
        foreach ($listOfUsers as $user) {
            $file .= $user["name"] . "," . $user["email"] . "," . $user["nickname"] . "," . $user["studentNumber"] . "," . $user["isAdmin"] . "," . $user["isActive"];
            if ($i != $len - 1) {
                $file .= "\n";
            }
            $i++;
        }
        return $file;
    }


    public static function importUsers($file)
    {
        $file = fopen($file, "r");
        while (!feof($file)) {
            $user = fgetcsv($file);
            if (!User::getUserByStudentNumber($user[3])) {
                Core::$systemDB->insert(
                    "game_course_user",
                    [
                        "name" => $user[0],
                        "email" => $user[1],
                        "nickname" => $user[2],
                        "studentNumber" => $user[3],
                        "isAdmin" => $user[4],
                        "isActive" => $user[5]
                    ]
                );
            }
        }
        fclose($file);
    }
}
