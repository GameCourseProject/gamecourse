<?php
namespace SmartBoards;

class User {
    protected $id = null;
    
    public function __construct($id){
        $this->id = $id;
    }
    public function create($name){
        Core::$sistemDB->insert("user",["name"=>$name,"id"=>$this->id]);        
    }
    
    public function exists() {
        return (!empty($this->getData("id")));
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getData($field='*'){
        return Core::$sistemDB->select("user",$field,["id"=>$this->id]);
    }
    
    public function setData($fieldValues){
        Core::$sistemDB->update("user",$fieldValues,["id"=>$this->id]);
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

    public function isAdmin() {
        return $this->getData("isAdmin");
    }
    public function setAdmin($isAdmin) {
        $this->setData(["isAdmin"=>$isAdmin]);
    }
    public static function getAdmins(){
        return array_column(Core::$sistemDB->selectMultiple("user",'id',["isAdmin"=>true]),'id');
    }

    public function initialize($name, $email) {
        Core::$sistemDB->update("user",["name" => $name,"email" => $email],["id"=>$this->id]);
        return $this;
    }

    public static function getUser($id) {
        return new User($id);
    }

    public static function getUserByUsername($username) {
        $userId=Core::$sistemDB->select("user","id",["username"=>$username]);
        if ($userId==null)
            return null;
        else
            return new User($userId);
    }
    
    public function getCourses(){
        return array_column(Core::$sistemDB->selectMultiple("course_user","course",["id"=>$this->id]),"course");
    }
   // public static function getAll() {//ToDo
   //     if (static::$usersDB == null)
   //         static::initDB();
   //     return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
   // }

    public static function getAllInfo() {
        return Core::$sistemDB->selectMultiple("user");
    }

    //private static function initDB() {
        //static::$baseDB = new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'users');
        //static::$usersDB = new MagicWrapper(static::$baseDB);     
    //}
}
