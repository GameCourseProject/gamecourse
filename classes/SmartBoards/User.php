<?php
namespace SmartBoards;

use MagicDB\MagicDB;
use MagicDB\MagicWrapper;

class User {
    public function __construct($id, $userWrapper) {
        $this->id = $id;
        $this->userWrapper = $userWrapper;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->userWrapper->get('name');
    }

    public function setName($name) {
        $this->userWrapper->set('name', $name);
    }

    public function getEmail() {
        return $this->userWrapper->get('email');
    }

    public function setEmail($email) {
        $this->userWrapper->set('email', $email);
    }

    public function getUsername() {
        return $this->userWrapper->get('username');
    }

    public function setUsername($username) {

        $this->userWrapper->set('username', $username);
    }

    public function isAdmin() {
        return $this->userWrapper->get('isAdmin', false);
    }

    public function setAdmin($isAdmin) {
        $this->userWrapper->set('isAdmin', $isAdmin);
    }

    public function exists() {
        return !$this->userWrapper->isNull();
    }

    public function initialize($name, $email) {
        if (!self::exists()) {
            $this->userWrapper->setValue(array(
                'name' => $name,
                'email' => $email
            ));
        }
        return $this;
    }

    public function getWrapper() {
        return $this->userWrapper;
    }

    private $id = null;
    private $userWrapper = null;

    public static function getUser($id) {
        if (static::$usersDB == null)
            static::initDB();
        return new User($id, static::$usersDB->getWrapped((string)$id));
    }

    public static function getUserByUsername($username) {
        if (static::$usersDB == null)
            static::initDB();
        $allUsers = static::$usersDB->getValue();
        foreach ($allUsers as $id => $user) {
            if (array_key_exists('username', $user) && $user['username'] == $username)
                return new User($id, static::$usersDB->getWrapped((string)$id));
        }
        return null;
    }

    public static function getAll() {
        if (static::$usersDB == null)
            static::initDB();
        return static::$baseDB->getKeys();//array_values(array_keys(static::$baseDB->getValue()));
    }

    public static function getAllInfo() {
        if (static::$usersDB == null)
            static::initDB();
        return static::$baseDB->getValue();
    }

    public static function getUserDbWrapper() {
        if (static::$usersDB == null)
            static::initDB();
        return static::$usersDB;
    }

    private static function initDB() {
        static::$baseDB = new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'users');
        static::$usersDB = new MagicWrapper(static::$baseDB);
    }

    private static $baseDB = null;
    private static $usersDB = null;
}
