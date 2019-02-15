<?php
namespace SmartBoards;

use MagicDB\MagicDB;
use MagicDB\MagicWrapper;

class Course {
    private $loadedModules = array();
    //private $db;
    private static $courses = array();
    private $cid;
    //public static $coursesDb;
    //private static function loadCoursesDb() {
    //    static::$coursesDb = new MagicWrapper(new MagicDB(CONNECTION_STRING, CONNECTION_USERNAME, CONNECTION_PASSWORD, 'courses'));
    //}


    public function __construct($cid, $create = false) {
        //if (static::$coursesDb == null)
        //    static::loadCoursesDb();
        $this->cid = $cid;
        if ((Core::getCourse($cid)==null) && !$create)
            throw new \RuntimeException('Unknown Course');
        //$this->db = static::$coursesDb->getWrapped($cid);
    }

    public function getId() {
        return $this->cid;
    }

    public function getName() {
        //return $this->db->get('name');
        return Core::$sistemDB->select("course","name",["id"=>$this->cid])[0];
    }

    public function getUsers() {
        //return $this->db->getWrapped('users');
        return Core::$sistemDB->selectMultiple("course_user",'*',["course"=>$this->cid]);
    }

    public function getUsersWithRole($role) {
        //return self::getUsers()->filter(function($key, $valueWrapped) use ($role) {
        //    return (new \SmartBoards\CourseUser($key, $valueWrapped, $this))->hasRole($role);
        //});
        return Core::$sistemDB->selectMultiple("course_user",'*',["course"=>$this->cid,"roles"=>$role]);
    }

    public function getUsersIds() {
        //return $this->db->getWrapped('users')->getKeys();     
        return array_column(Core::$sistemDB->selectMultiple("course_user",'id',["course"=>$this->cid]),'id');
    }

    //public function setUsers($users) {
    //    $this->db->set('users', $users);
    //}

    public function getUser($istid) {
       if (!empty(Core::$sistemDB->select("course_user",'*',["course"=>$this->cid,"id"=>$istid])))
               return new CourseUser($istid,$this);
       else
           return new NullCourseUser($istid, $this);
        //$users = self::getUsers();
        //if ($users->hasKey($istid))
        //    return new CourseUser($istid, $users->getWrapped($istid), $this);
        //return new NullCourseUser($istid, $this);
    }

    //public function getUserData($istid) {
    //    return $this->db->getWrapped('users')->getWrapped($istid)->getWrapped('data');
    //}

    public function getLoggedUser() {
        $user = Core::getLoggedUser();
        if ($user == null)
            return new NullCourseUser(-1, $this);

        return self::getUser($user->getId());
    }

    public function getHeaderLink() {
        //return $this->db->get('headerLink');
        return Core::$sistemDB->select("course","headerLink",["id"=>$this->cid])[0];
    }

    public function setHeaderLink($link) {
        //return $this->db->set('headerLink', $link);
        Core::$sistemDB->update("course",["headerLink"=>$link],["id" =>$this->cid]);
    }

    public function getRoles() {
        //return $this->db->get('roles');
        return Core::$sistemDB->selectMultiple("role","*",["course"=>$this->cid]);
    }

    //public function setRoles($roles) {
        //return $this->db->set('roles', $roles);
    //    Core::$sistemDB->update("course",["roles"=>$roles],["id"=>$this->cid]);
    //}

    public function getRolesHierarchy() {//same as getRoles, but ordered
        //return $this->db->get('rolesHierarchy');
        $roles = Core::$sistemDB->selectMultiple("role","*",["course"=>$this->cid]);
        usort($roles, function($a, $b) { 
            return $a['hierarchy'] < $b['hierarchy'] ? -1 : 1;
        });
        return $roles;
    }

    //public function setRolesHierarchy($rolesHierarchy) {
    //    return $this->db->set('rolesHierarchy', $rolesHierarchy);
    //}

    //public function getRolesSettings() {
    //    return $this->getWrapped('rolesSettings');
    //}

    //public function getRoleSettings($role) {
    //    return $this->getWrapped('rolesSettings')->getWrapped($role);
    //}

    public function getEnabledModules() {
        return Core::$sistemDB->selectMultiple("enabled_module","moduleId",["course"=>$this->cid]);
        //array w module names
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
        return Core::$sistemDB->select("module","*",["moduleId"=>$moduleId]);
    }

   // public function getWrapper() {
    //    return $this->db;
    //}

    //public function getWrapped($key) {
    //    return $this->db->getWrapped($key);
    //}

    //public function getAll() {
    //    return $this->db->getValue();
    //}

    public function setModuleEnabled($moduleId, $enabled) {
        $modules = self::getEnabledModules();
        if (!$enabled) {
            $key = array_search($moduleId, $modules);
            if ($key !== false) {
                Core::$sistemDB->delete("enabled_module",["moduleId"=>$moduleId,"course"=>$this->cid]);
            }
        } else if ($enabled && !in_array($moduleId, $modules)) {
            $modules[] = $moduleId;
            Core::$sistemDB->insert("enabled_module",["moduleId"=>$moduleId,"course"=>$this->cid]);
        }
    }

    public function goThroughRoles($roles, $func, &...$data) {
        \Utils::goThroughRoles($roles, $func, ...$data);
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
        //if (static::$coursesDb == null)
        //    static::loadCoursesDb();

        //$courses = Core::getCourses();
        //end($courses);
        //$end = key($courses);
        //$newCourse = 0;
        //if ($end !== NULL)
        //    $newCourse = $end + 1;

        //if (static::$coursesDb->get($newCourse) !== null) // Its in the Course graveyard
        //    static::$coursesDb->delete($newCourse);
        Core::$sistemDB->insert("course",["name"=>$courseName]);
        $newCourse=Core::$sistemDB->select("course","id",["name"=>$courseName])[0];
        $course = new Course($newCourse, true);

        //$courseWrapper = $course->getWrapper();

        //$courseWrapper->set('name', $courseName);
        //$courseWrapper->set('users', array());

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
            //$copyFromWrapper = $copyFromCourse->getWrapper();
            
            //ToDo
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
}
