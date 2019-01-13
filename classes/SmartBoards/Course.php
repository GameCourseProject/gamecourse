<?php
namespace SmartBoards;

use MagicDB\MagicDB;
use MagicDB\MagicWrapper;

class Course {
    public static $coursesDb;
    private static function loadCoursesDb() {
        static::$coursesDb = new MagicWrapper(new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'courses'));
    }

    public function __construct($cid, $create = false) {
        if (static::$coursesDb == null)
            static::loadCoursesDb();
        $this->cid = $cid;
        if ((static::$coursesDb->isNull($cid) || !array_key_exists($cid, Core::getCourses())) && !$create)
            throw new \RuntimeException('Unknown Course');
        $this->db = static::$coursesDb->getWrapped($cid);
    }

    public function getId() {
        return $this->cid;
    }

    public function getName() {
        return $this->db->get('name');
    }

    public function getUsers() {
        return $this->db->getWrapped('users');
    }

    public function getUsersWithRole($role) {
        return self::getUsers()->filter(function($key, $valueWrapped) use ($role) {
            return (new \SmartBoards\CourseUser($key, $valueWrapped, $this))->hasRole($role);
        });
    }

    public function getUsersIds() {
        return $this->db->getWrapped('users')->getKeys();
    }

    public function setUsers($users) {
        $this->db->set('users', $users);
    }

    public function getUser($istid) {
        $users = self::getUsers();
        if ($users->hasKey($istid))
            return new CourseUser($istid, $users->getWrapped($istid), $this);
        return new NullCourseUser($istid, $this);
    }

    public function getUserData($istid) {
        return $this->db->getWrapped('users')->getWrapped($istid)->getWrapped('data');
    }

    public function getLoggedUser() {
        $user = Core::getLoggedUser();
        if ($user == null)
            return new NullCourseUser(-1, $this);
        return self::getUser($user->getId());
    }

    public function getHeaderLink() {
        return $this->db->get('headerLink');
    }

    public function setHeaderLink($link) {
        return $this->db->set('headerLink', $link);
    }

    public function getRoles() {
        return $this->db->get('roles');
    }

    public function setRoles($roles) {
        return $this->db->set('roles', $roles);
    }

    public function getRolesHierarchy() {
        return $this->db->get('rolesHierarchy');
    }

    public function setRolesHierarchy($rolesHierarchy) {
        return $this->db->set('rolesHierarchy', $rolesHierarchy);
    }

    public function getRolesSettings() {
        return $this->getWrapped('rolesSettings');
    }

    public function getRoleSettings($role) {
        return $this->getWrapped('rolesSettings')->getWrapped($role);
    }

    public function getEnabledModules() {
        return $this->db->get('modules');
    }

    public function addModule($module) {
        return $this->loadedModules[$module->getId()] = $module;
    }


    public function getModules() {
        return $this->loadedModules;
    }

    public function getModule($module) {
        if (array_key_exists($module, $this->loadedModules))
            return $this->loadedModules[$module];
        return null;
    }

    public function getModulesResources() {
        $modules = $this->getModules();
        $resources = array();
        foreach ($modules as $id => $module) {
            $moduleResources = $module->getResources();
            $resources[] = array(
                'name' => 'module.' . $id,
                'files' => $moduleResources
            );
        }
        return $resources;
    }

    public function getModuleData($module) {
        if ($module == null)
            return null;
        else if (is_string($module))
            $moduleId = $module;
        else if (is_object($module))
            $moduleId = $module->getId();
        else
            return null;
        return $this->db->getWrapped('moduleData')->getWrapped($moduleId);
    }

    public function getWrapper() {
        return $this->db;
    }

    public function getWrapped($key) {
        return $this->db->getWrapped($key);
    }

    public function getAll() {
        return $this->db->getValue();
    }

    public function setModuleEnabled($moduleId, $enabled) {
        $modules = self::getEnabledModules();
        if (!$enabled) {
            $key = array_search($moduleId, $modules);
            if ($key !== false) {
                unset($modules[$key]);
                $this->db->set('modules', array_values($modules));
            }
        } else if ($enabled && !in_array($moduleId, $modules)) {
            $modules[] = $moduleId;
            $this->db->set('modules', $modules);
        }
    }

    public function goThroughRoles($func, &...$data) {
        \Utils::goThroughRoles($this->getRolesHierarchy(), $func, ...$data);
    }

    public static function getCourse($cid, $initModules = true) {
        if (!array_key_exists($cid, static::$courses)) {
            static::$courses[$cid] = new Course($cid);
            if ($initModules)
                ModuleLoader::initModules(static::$courses[$cid]);
        }
        return static::$courses[$cid];
    }

    public static function newCourse($courseName, $copyFrom = null) {
        if (static::$coursesDb == null)
            static::loadCoursesDb();

        $courses = Core::getCourses();
        end($courses);
        $end = key($courses);
        $newCourse = 0;
        if ($end !== NULL)
            $newCourse = $end + 1;

        if (static::$coursesDb->get($newCourse) !== null) // Its in the Course graveyard
            static::$coursesDb->delete($newCourse);

        $course = new Course($newCourse, true);

        $courseWrapper = $course->getWrapper();

        $courseWrapper->set('name', $courseName);
        $courseWrapper->set('users', array());

        $courseExists = false;
        $copyFromCourse = null;
        if ($copyFrom !== null) {
            try {
                $copyFromCourse = Course::getCourse($copyFrom);
                if ($copyFromCourse != $course) // make sure its not the same as the new one..
                    $courseExists = true;
            } catch (\RuntimeException $e) {
            }
        }

        if ($copyFrom !== null && $courseExists) {
            $copyFromWrapper = $copyFromCourse->getWrapper();
            $keys = array('headerLink', 'defaultRoleSettings', 'modules', 'roles', 'rolesSettings', 'rolesHierarchy', 'moduleData');
            foreach ($keys as $key)
                $courseWrapper->set($key, $copyFromWrapper->get($key));

            foreach ($course->getModules() as $module) {
                $module->cleanModuleData();
            }
        } else {
            $courseWrapper->set('headerLink', '');
            $courseWrapper->set('defaultRoleSettings', array('landingPage' => ''));

            $courseWrapper->set('modules', array());

            $courseWrapper->set('roles', array('Teacher', 'Student'));
            $courseWrapper->set('rolesSettings', array(
                    'Teacher' => array('landingPage' => '/'),
                    'Student' => array('landingPage' => '/'))
            );
            $courseWrapper->set('rolesHierarchy', array(
                array('name' => 'Teacher'),
                array('name' => 'Student')));
        }

        Core::getCoursesWrapped()->set($newCourse, $courseName);
        return $course;
    }

    private $loadedModules = array();
    private $db;
    private static $courses = array();
}
