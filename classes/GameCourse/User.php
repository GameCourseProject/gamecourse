<?php
namespace GameCourse;

class User {
    protected $id = null;
    
    public function __construct($id){
        $this->id = $id;
    }
    
    public function addUserToDB($name, $username, $email){
        Core::$systemDB->insert("game_course_user",["name"=>$name,"id"=>$this->id,"username"=>$username, "email"=>$email]);       
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

    public function getLastLogin(){
        return $this->getData("lastActivity");
    }

    public function isAdmin() {
        return $this->getData("isAdmin");
    }
    public function setAdmin($isAdmin) {
        $this->setData(["isAdmin"=>$isAdmin]);
    }
    public static function getAdmins(){
        return array_column(Core::$systemDB->selectMultiple("game_course_user",["isAdmin"=>true],'id'),'id');
    }

    public function initialize($name, $username,$email) {
        Core::$systemDB->update("game_course_user",["name" => $name,"email" => $email, "username"=>$username],["id"=>$this->id]);
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
   // public static function getAll() {//ToDo
   //     if (static::$usersDB == null)
   //         static::initDB();
   //     return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
   // }

    public static function getAllInfo() {
        return Core::$systemDB->selectMultiple("game_course_user");
    }
}
