<?php
namespace SmartBoards;

use MagicDB\MagicDB;
use MagicDB\MagicWrapper;

class Course {
    private $loadedModules = array();
    private static $courses = array();
    private $cid;

    public function __construct($cid, $create = false) {
        $this->cid = $cid;
        //if ((Core::getCourse($cid)==null) && !$create)
        //    throw new \RuntimeException('Unknown Course');
    }

    public function getId() {
        return $this->cid;
    }
    
    public function getData($field='*'){
        return Core::$sistemDB->select("course",$field,["id"=>$this->cid]);
    }
    public function getName() {
        return $this->getData("name");
    }
    public function getNumBadges(){
        return $this->getData("numBadges");
    }
    public function getHeaderLink() {
        return $this->getData("headerLink");
    }
    public function getActive(){
        return $this->getData("active");
    }
    public function getLandingPage(){
        return $this->getData("defaultLandingPage");
    }
    
    public function setData($field, $value){
        Core::$sistemDB->update("course",[$field=>$value],["id"=>$this->cid]);
    }
    public function setHeaderLink($link) {
        $this->setData("headerLink",$link);
    }
    public function setActiveState($active){
        $this->setData("active",$active);
    }
    public function setLandingPage($page){
        $this->setData("defaultLandingPage",$page);
    }

    public function getUsers() {
        return Core::$sistemDB->selectMultiple("course_user",'*',["course"=>$this->cid]);
    }

    public function getUsersWithRole($role) {
        //return self::getUsers()->filter(function($key, $valueWrapped) use ($role) {
        //    return (new \SmartBoards\CourseUser($key, $valueWrapped, $this))->hasRole($role);
        //});
        return Core::$sistemDB->selectMultiple("course_user natural join user_role",
                                        '*',["course"=>$this->cid,"role"=>$role]);
    }

    public function getUsersIds() { 
        return array_column(Core::$sistemDB->selectMultiple("course_user",'id',["course"=>$this->cid]),'id');
    }

    //public function setUsers($users) {
    //    $this->db->set('users', $users);
    //}

    public function getUser($istid) {
       if (!empty(Core::$sistemDB->select("course_user",'id',["course"=>$this->cid,"id"=>$istid])))
               return new CourseUser($istid,$this);
       else
           return new NullCourseUser($istid, $this);
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
    
    public function setRoleData($name, $field, $value){
        return Core::$sistemDB->update("role",[$field=>$value],["course"=>$this->cid,"role"=>$name]);
    }
    
    public function getRoleData($name, $field='*'){
        return Core::$sistemDB->select("role",$field,["course"=>$this->cid,"role"=>$name]);
    }
    public function getRolesData($field='*') {
        return Core::$sistemDB->selectMultiple("role",$field,["course"=>$this->cid]);
    }
    //return an array with role names
    public function getRoles() {
        return array_column($this->getRolesData(),"role");
    }

    public function setRoles($newroles) {
        $oldRoles=$this->getRoles();
        foreach ($newroles as $role){
            $inOldRoles=array_search($role, $oldRoles);
            if ($inOldRoles===false){
                Core::$sistemDB->insert("role",["role"=>$role,"course"=>$this->cid]);
            }else{
                unset($oldRoles[$inOldRoles]);
            }
        }
        foreach($oldRoles as $role){
            Core::$sistemDB->delete("role",["role"=>$role,"course"=>$this->cid]);
        }
    }
    
    //returns array with all the roles ordered by hierarchy
    public function getRolesHierarchy() {
        return json_decode( Core::$sistemDB->select("role_hierarchy","hierarchy",["course"=>$this->cid]),true);
    }

    public function setRolesHierarchy($rolesHierarchy) {
        Core::$sistemDB->update("role_hierarchy",["hierarchy"=>json_encode($rolesHierarchy)],["course"=>$this->cid]);
    }

    //public function getRolesSettings() {
    //    return $this->getWrapped('rolesSettings');
    //}

    //public function getRoleSettings($role) {
    //    return $this->getWrapped('rolesSettings')->getWrapped($role);
    //}
    
    //returns array w module names
    public function getEnabledModules() {
        return array_column(Core::$sistemDB->selectMultiple("enabled_module","moduleId",["course"=>$this->cid],"moduleId"),'moduleId'); 
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

//goes from higher in the hierarchy to lower (eg: Teacher > Student), maybe shoud add option to user reverse order
    public function goThroughRoles( $func, &...$data) {
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
    
    public static function deleteCourse($courseId){
        unset(static::$courses[$courseId]);
        Core::$sistemDB->delete("course",["id"=>$courseId]);
    }
    
    //insert data to tiers and roles tables 
    //FixMe, this has a lot of hard coded info
    public static function insertBasicCourseData($db, $courseId){
        
        $db->insert("role",["role"=>"Teacher","course" =>$courseId]);
        $db->insert("role",["role"=>"Student","course" =>$courseId]);
        $db->insert("role",["role"=>"Watcher","course" =>$courseId]);
        
        $roles = [["name"=>"Teacher"],["name"=>"Student"],["name"=>"Watcher"]];
        $db->insert("role_hierarchy",["course"=>$courseId,"hierarchy"=> json_encode($roles)]);
        
        $db->insert("skill_tier",["tier"=>1,"reward"=>150,"course"=>$courseId]);
        $db->insert("skill_tier",["tier"=>2,"reward"=>400,"course"=>$courseId]);
        $db->insert("skill_tier",["tier"=>3,"reward"=>750,"course"=>$courseId]);
        $db->insert("skill_tier",["tier"=>4,"reward"=>1150,"course"=>$courseId]); 
        
        
    }
    
    //copies content of a specified table in DB to new rows for the new course
    private static function copyCourseContent($content,$fromCourseId,$newCourseId){
        $fromData = Core::$sistemDB->selectMultiple($content,'*',["course"=>$fromCourseId]);
        foreach ($fromData as $data) {
            $data['course'] = $newCourseId;
            Core::$sistemDB->insert($content, $data);
        }
    }
    
    public static function newCourse($courseName, $copyFrom = null) {
        //if (static::$coursesDb->get($newCourse) !== null) // Its in the Course graveyard
        //    static::$coursesDb->delete($newCourse);
        Core::$sistemDB->insert("course",["name"=>$courseName]);
        $courseId=Core::$sistemDB->select("course","id",["name"=>$courseName]);
        $course = new Course($courseId);
        static::$courses[$courseId] = $course;
        
        //course_user table (add current user)
        $currentUserId=Core::getLoggedUser()->getId();
        Core::$sistemDB->insert("course_user",["id" => $currentUserId,"course" => $courseId]);
        
        if ($copyFrom !== null){//&& $courseExists) {
            $copyFromCourse = Course::getCourse($copyFrom);
            $fromId=$copyFromCourse->getId();
            
            //course table
            $keys = ['headerLink','defaultLandingPage'];
            $fromCourseData = $copyFromCourse->getData();//Core::$sistemDB->select("course",'*',["id"=>$fromId]);
            $newData=[];
            foreach ($keys as $key)
                $newData[$key] = $fromCourseData[$key];
            Core::$sistemDB->update("course",$newData,["id"=>$courseId]);
            
            //role table;
            Course::copyCourseContent("role",$fromId,$courseId);
            
            //copy content of other tables to new course
            Course::copyCourseContent("skill_tier",$fromId,$courseId);
            Course::copyCourseContent("enabled_module",$fromId,$courseId);
            Course::copyCourseContent("view_template",$fromId,$courseId);
            Course::copyCourseContent("view",$fromId,$courseId);
            Course::copyCourseContent("view_role",$fromId,$courseId);
            Course::copyCourseContent("view_part",$fromId,$courseId);
            Course::copyCourseContent("role_hierarchy",$fromId,$courseId);
            
        } else {
            Course::insertBasicCourseData(Core::$sistemDB, $courseId);
            Core::$sistemDB->insert("user_role",["id" => $currentUserId,"course" => $courseId, "role"=>"Teacher"]);
        }
        return $course;
    }
}
