<?php
namespace SmartBoards;

//use MagicDB\MagicDB;
//use MagicDB\MagicWrapper;

class User {
    //private static $baseDB = null;
    //private static $usersDB = null;
    protected $id = null;
    //private $userWrapper = null;
    
    public function __construct($id){//, $userWrapper) {
        $this->id = $id;
        //$this->userWrapper = $userWrapper;
    }
    public function create($name){
        Core::$sistemDB->insert("user",["name"=>$name,"id"=>$this->id]);        
    }
    
    public function getId() {
        return $this->id;
    }

    public function getName() {
        //return $this->userWrapper->get('name');
        return Core::$sistemDB->select("user","name",["id"=>$this->id]);
    }

    public function setName($name) {
        //$this->userWrapper->set('name', $name);
        Core::$sistemDB->update("user",["name"=>$name],["id"=>$this->id]);
    }

    public function getEmail() {
        //return $this->userWrapper->get('email');
        return Core::$sistemDB->select("user","email",["id"=>$this->id]);
    }

    public function setEmail($email) {
        //$this->userWrapper->set('email', $email);
        Core::$sistemDB->update("user",["email"=>$email],["id"=>$this->id]);
        
    }

    public function getUsername() {
        //return $this->userWrapper->get('username');
        return Core::$sistemDB->select("user","username",["id"=>$this->id]);
    }

    public function setUsername($username) {
        Core::$sistemDB->update("user",["username" => $username],["id"=>$this->id]);
        //$this->userWrapper->set('username', $username);
    }

    public function isAdmin() {
        //return $this->userWrapper->get('isAdmin', false);
        return Core::$sistemDB->select("user","isAdmin",["id"=>$this->id]);
    }

    public function setAdmin($isAdmin) {
        //$this->userWrapper->set('isAdmin', $isAdmin);
        Core::$sistemDB->update("user",["isAdmin" => $isAdmin],["id"=>$this->id]);
    }

    public function exists() {
        //return !$this->userWrapper->isNull();
        return (Core::$sistemDB->select("user","*",["id"=>$this->id])!=null);
    }

    public function initialize($name, $email) {
        //if (!self::exists()) {
            Core::$sistemDB->update("user",["name" => $name,"email" => $email],["id"=>$this->id]);
        return $this;
    }

    //public function getWrapper() {
    //    return $this->userWrapper;
    //}

    public static function getUser($id) {
        //if (static::$usersDB == null)
        //    static::initDB();
        //return new User($id, static::$usersDB->getWrapped((string)$id));
        return new User($id);
    }

    public static function getUserByUsername($username) {
        //if (static::$usersDB == null)
        //    static::initDB();
        $userId=Core::$sistemDB->select("user","id",["username"=>$username]);
        if ($userId==null)
            return null;
        else
            return new User($userId);
    }

   // public static function getAll() {//ToDo
   //     if (static::$usersDB == null)
   //         static::initDB();
   //     return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
   // }

   // public static function getAllInfo() {
   //     if (static::$usersDB == null)
   //         static::initDB();
    //    return static::$baseDB->getValue();
   // }

    //public static function getUserDbWrapper() {
    //    if (static::$usersDB == null)
    //        static::initDB();
    //    return static::$usersDB;
    //}

    private static function initDB() {
        //static::$baseDB = new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'users');
        //static::$usersDB = new MagicWrapper(static::$baseDB);
        
    }
}
