<?php
namespace GameCourse;

class User {
    protected $id = null;
    
    public function __construct($id){
        $this->id = $id;
    }
    

    public static function addUserToDB($name, $username, $email, $studentNumber, $nickname, $isAdmin, $isActive ){
        Core::$systemDB->insert("game_course_user",[
            "name"=>$name,
            "username"=>$username,
            "email"=>$email,
            "studentNumber"=>$studentNumber,
            "nickname"=>$nickname,
            "isAdmin"=>$isAdmin,
            "isActive"=>$isActive
        ]);       
    }
    
    public function exists() {
        return (!empty($this->getData("id")));
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getData($field='*'){
        return Core::$systemDB->select("game_course_user",["id"=>$this->id],$field);
    }
    
    public function setData($fieldValues){
        Core::$systemDB->update("game_course_user",$fieldValues,["id"=>$this->id]);
    }
    
    public function getName() {
        return $this->getData("name");
    }
    public function setName($name) {
        $this->setData(["name"=>$name]);
    }

    public function getEmail() {
        return $this->getData("email");
    }
    public function setEmail($email) {
        $this->setData(["email"=>$email]);
    }

    public function getUsername() {
        return $this->getData("username");
    }
    public function setUsername($username) {
        $this->setData(["username"=>$username]);
    }
    public function getNickname() {
        return $this->getData("nickname");
    }
    public function setNickname($nickname) {
        $this->setData(["nickname"=>$nickname]);
    }
    public function getStudentNumber() {
        return $this->getData("studentNumber");
    }
    public function setStudentNumber($studentNumber) {
        $this->setData(["studentNumber"=>$studentNumber]);
    }


    public function isAdmin() {
        return $this->getData("isAdmin");
    }
    public function setAdmin($isAdmin) {
        $this->setData(["isAdmin"=>$isAdmin]);
    }
    public function isActive() {
        return $this->getData("isActive");
    }
    public function setActive($isActive) {
        $this->setData(["isActive"=>$isActive]);
    }

    public static function getAdmins(){
        return array_column(Core::$systemDB->selectMultiple("game_course_user",["isAdmin"=>true],'id'),'id');
    }

    public function editUser($name, $username, $email, $studentNumber, $nickname, $isAdmin, $isActive ) {
        Core::$systemDB->update("game_course_user",[
            "name"=>$name,
            "username"=>$username,
            "email"=>$email,
            "studentNumber"=>$studentNumber,
            "nickname"=>$nickname,
            "isAdmin"=>$isAdmin,
            "isActive"=>$isActive
        ],["id"=>$this->id]);
        return $this;
    }

    public static function getUser($id) {
        return new User($id);
    }

    public static function getUserByUsername($username) {
        $userId=Core::$systemDB->select("game_course_user",["username"=>$username],"id");
        if ($userId==null)
            return null;
        else
            return new User($userId);
    }
    
    public function getCourses(){
        return array_column(Core::$systemDB->selectMultiple("course_user",["id"=>$this->id],"course"),"course");
    }

    public function getSystemLastLogin(){
        $allLastLogins = array_column(Core::$systemDB->selectMultiple("course_user",["id"=>$this->id],"lastActivity"),"lastActivity");
        $lastLogin = "";
        if(!empty($allLastLogins))
            $lastLogin = max($allLastLogins);
            if ($lastLogin == "0000-00-00 00:00:00")
                return "never";
            else
                $lastTime = new \DateTime($lastLogin);
                $currentTime = new \DateTime(date("Y-m-d H:i:s"));
                $interval = $currentTime->diff($lastTime);

                $years = $interval->format('%y');
                $months = $interval->format('%m');
                $days = $interval->format('%d');
                $hours = $interval->format('%h');
                $minutes = $interval->format('%i');
                if ($years != "0")
                    return $years=="1" ? $years . " year ago" : $years . " years ago";
                if ($months != "0")
                    return $months=="1" ? $months . " month ago" : $months . " months ago";
                if ($days != "0")
                    return $days=="1" ? $days . " day ago" : $days . " days ago";
                if ($hours != "0")
                    return $hours=="1" ? $hours . " hour ago" : $hours . " hours ago";
                if ($minutes != "0")
                    return $minutes=="1" ? $minutes . " minute ago" : $minutes . " minutes ago";
                else
                    return "now";
    }

   // public static function getAll() {//ToDo
   //     if (static::$usersDB == null)
   //         static::initDB();
   //     return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
   // }

    public static function getAllInfo() {
        return Core::$systemDB->selectMultiple("game_course_user");
    }

    public static function deleteUser($userId){
        Core::$systemDB->delete("game_course_user",["id"=>$userId]);
    }
}
