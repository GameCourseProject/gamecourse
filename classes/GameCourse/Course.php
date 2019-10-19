<?php
namespace GameCourse;

class Course {
    private $loadedModules = array();
    private static $courses = array();
    private $cid;

    public function __construct($cid, $create = false) {
        $this->cid = $cid;
    }

    public function getId() {
        return $this->cid;
    }
    
    public function getData($field='*'){
        return Core::$systemDB->select("course",["id"=>$this->cid],$field);
    }
    public function getName() {
        return $this->getData("name");
    }
    public function getNumBadges(){
        return $this->getData("numBadges");
    }
    public function getActive(){
        return $this->getData("active");
    }
    public function getLandingPage(){
        return $this->getData("defaultLandingPage");
    }
    
    public function setData($field, $value){
        Core::$systemDB->update("course",[$field=>$value],["id"=>$this->cid]);
    }
    public function setActiveState($active){
        $this->setData("isActive",$active);
    }
    public function setLandingPage($page){
        $this->setData("defaultLandingPage",$page);
    }

    public function getUsers() {
        return Core::$systemDB->selectMultiple("course_user natural join game_course_user",
                ["course"=>$this->cid]);
    }
    
    //receives name of role and gets all the course_users w that role
    public function getUsersWithRole($role) {
        return Core::$systemDB->selectMultiple(
                "game_course_user u natural join course_user cu natural join user_role ur join role r on r.id=ur.role",
                ["r.course"=>$this->cid,"r.name"=>$role],
                "u.*,cu.*,r.name as role");
    }
    //receives id of role and gets all the course_users w that role
    public function getUsersWithRoleId($role) {
        return Core::$systemDB->selectMultiple(
                "game_course_user natural join course_user natural join user_role",
                ["course"=>$this->cid,"role"=>$role]);
    }

    public function getUsersIds() { 
        return array_column(Core::$systemDB->selectMultiple("course_user",["course"=>$this->cid],'id'),'id');
    }

    public function getUser($istid) {
       if (!empty(Core::$systemDB->select("course_user",["course"=>$this->cid,"id"=>$istid],'id')))
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
        return Core::$systemDB->update("role",[$field=>$value],["course"=>$this->cid,"name"=>$name]);
    }
    public function setRoleDataById($id, $field, $value){
        return Core::$systemDB->update("role",[$field=>$value],["id"=>$id]);
    }
    public function getRoleById($id, $field='*'){
        return Core::$systemDB->select("role",["course"=>$this->cid,"id"=>$id],$field);
    }
    public function getRoleByName($name, $field='*'){
        return Core::$systemDB->select("role",["course"=>$this->cid,"name"=>$name],$field);
    }
    public function getRolesData($field='*') {
        return Core::$systemDB->selectMultiple("role",["course"=>$this->cid],$field);
    }
    //return an array with role names
    public function getRoles() {
        return array_column($this->getRolesData("name"),"name");
    }
    public static function getRoleId($role,$courseId){
        return Core::$systemDB->select("role",["course"=>$courseId,"name"=>$role],"id");
    }
    //receives array of roles to replace in the DB,
    public function setRoles($newroles) {
        //ToDo:If this is suposed to work with repeated roles, then there should be hiearchy info in role table in DB
        $oldRoles=$this->getRoles();
        foreach ($newroles as $role){
            $inOldRoles=array_search($role, $oldRoles);
            if ($inOldRoles===false){
                Core::$systemDB->insert("role",["name"=>$role,"course"=>$this->cid]);
            }else{
                unset($oldRoles[$inOldRoles]);
            }
        }
        foreach($oldRoles as $role){
            Core::$systemDB->delete("role",["name"=>$role,"course"=>$this->cid]);
        }
    }
    
    public function setHierarchyId(&$role,$rolesByName){
        $role["id"]=$rolesByName[$role["name"]]["id"];
        if (array_key_exists("children", $role)){
            foreach($role["children"] as &$child){
                $this->setHierarchyId($child, $rolesByName);
            }
        }
    }
    //returns array with all the roles ordered by hierarchy
    public function getRolesHierarchy() {
        //ToDO, if we want to allow repeated role names,the hierarchy will work diferently
        $roles = Core::$systemDB->selectMultiple("role",["course"=>$this->cid]);
        $rolesByName = array_combine(array_column($roles, "name"), $roles);
        
        $hierarchy = json_decode( Core::$systemDB->select("course",["id"=>$this->cid],"roleHierarchy"),true);
        foreach ($hierarchy as &$role){
            $this->setHierarchyId($role,$rolesByName);
        }
        return $hierarchy;
    }
    public function setRolesHierarchy($rolesHierarchy) {
        Core::$systemDB->update("course",["roleHierarchy"=>json_encode($rolesHierarchy)],["id"=>$this->cid]);
    }
    
    //returns array w module names
    public function getEnabledModules() {
        return array_column(Core::$systemDB->selectMultiple("course_module",["course"=>$this->cid,"isEnabled"=>true],"moduleId","moduleId"),'moduleId'); 
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
        return Core::$systemDB->select("module",["moduleId"=>$moduleId]);
    }

    public function setModuleEnabled($moduleId, $enabled) {
        Core::$systemDB->update("course_module",["isEnabled"=>$enabled],["course"=>$this->cid,"moduleId"=>$moduleId]);
        if (!$enabled){
            //ToDo:do something about views that use this module?
            //  Core::$systemDB->delete("page",["module"=>$moduleId, "course"=>$this->cid]);
        }
          
    }

    //goes from higher in the hierarchy to lower (eg: Teacher > Student), maybe shoud add option to use reverse order
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
        Core::$systemDB->delete("course",["id"=>$courseId]);
    }

    //insert data to tiers and roles tables 
    //FixMe, this has hard coded info
    public static function insertBasicCourseData($db, $courseId){
        $db->insert("role",["name"=>"Teacher","course" =>$courseId]);
        $teacherId=$db->getLastId();
        $db->insert("role",["name"=>"Student","course" =>$courseId]);
        $db->insert("role",["name"=>"Watcher","course" =>$courseId]);

        $roles = [["name"=>"Teacher"],["name"=>"Student"],["name"=>"Watcher"]];
        $db->update("course",["roleHierarchy"=> json_encode($roles)],["id"=>$courseId]);
        
        return $teacherId;
    }
    
    //copies content of a specified table in DB to new rows for the new course
    private static function copyCourseContent($content,$fromCourseId,$newCourseId, $ignoreID=false){
        $fromData = Core::$systemDB->selectMultiple($content,["course"=>$fromCourseId]);
        foreach ($fromData as $data) {
            $data['course'] = $newCourseId;
            if ($ignoreID)
                unset($data['id']);
            Core::$systemDB->insert($content, $data);
        }
    }
    public static function getCourseLegacyFolder($courseId,$courseName=null){
        if ($courseName===null){
            $courseName = Course::getCourse($courseId)->getName();
        }
        $courseName= preg_replace("/[^a-zA-Z0-9_ ]/","",$courseName);
        $folder = LEGACY_DATA_FOLDER . '/'.$courseId.'-'.$courseName;
        return $folder;
    }

    public static function createCourseLegacyFolder($courseId,$courseName){
        $folder = Course::getCourseLegacyFolder($courseId,$courseName);
        if (!file_exists($folder))
            mkdir($folder);
        if (!file_exists($folder."/tree"))
            mkdir($folder."/tree");
        return $folder;
    }
    
    public static function newCourse($courseName, $copyFrom = null) {
        //if (static::$coursesDb->get($newCourse) !== null) // Its in the Course graveyard
        //    static::$coursesDb->delete($newCourse);
        Core::$systemDB->insert("course",["name"=>$courseName]);
        $courseId=Core::$systemDB->getLastId();
        $course = new Course($courseId);
        static::$courses[$courseId] = $course;
        $legacyFolder = Course::createCourseLegacyFolder($courseId,$courseName);
        
        //course_user table (add current user)
        $currentUserId=Core::getLoggedUser()->getId();
        Core::$systemDB->insert("course_user",["id" => $currentUserId,"course" => $courseId]);
        
        if ($copyFrom !== null){//&& $courseExists) {
            $copyFromCourse = Course::getCourse($copyFrom);
            //$fromId=$copyFromCourse->getId();
            
            //course table
            $keys = ['defaultLandingPage',"roleHierarchy","theme"];
            $fromCourseData = $copyFromCourse->getData();//Core::$systemDB->select("course",["id"=>$fromId]);
            $newData=[];
            foreach ($keys as $key)
                $newData[$key] = $fromCourseData[$key];
            Core::$systemDB->update("course",$newData,["id"=>$courseId]);
            
            //copy content of tables to new course
            Course::copyCourseContent("role",$copyFrom,$courseId,true);
            Course::copyCourseContent("course_module",$copyFrom,$courseId);
            
            //view data
       
            //Course::copyCourseContent("page",$copyFrom,$courseId,true);
            //Course::copyCourseContent("view",$copyFrom,$courseId);
            //Course::copyCourseContent("view_role",$copyFrom,$courseId);
            //Course::copyCourseContent("view_part",$copyFrom,$courseId);
            
            //badges/skills/levels data
            /*Course::copyCourseContent("skill_tree",$copyFrom,$courseId,true);
            $tree=Core::$systemDB->getLastId();
            $treeData = Core::$systemDB->selectMultiple("skill_tree",["course"=>$copyFrom]);
            foreach ($treeData as $tree) {
                $tree['course'] = $courseId;
                $oldTreeId=$tree['id'];
                unset($tree['id']);
                Core::$systemDB->insert("skill_tree", $tree);
                $treeId=Core::$systemDB->getLastId();
                $tierData = Core::$system->selectMultiple("skill_tier",["treeId"=>$oldTreeId]);
                foreach ($tierData as $tier){
                    $tier["treeId"]= $treeId;
                    $tierNum = $tier["tier"];
                    Core::$systemDB->insert("skill_tier",$tier);
                    $skillsData=Core::$systemDB->selectMultiple("skill",["tier"=>$tierNum,"treeId"=>$oldTreeId]);
                    foreach($skillsData as $skill){
                        $oldSkillId=$skill['id'];
                        unset($skill['id']);
                        $skill['treeId']=$treeId;
                        Core::$systemDB->insert("skill",$skill);
                        $skillId=Core::$systemDB->getLastId();
                        $dependencyData = Core::$systemDB->select("dependency",["superSkill"=>$oldSkillId]);
                        foreach($dependencyData as $dep){
                            $oldDepId=$dep['id'];
                            unset($dep['id']);
                            $dep["superSkill"]=$skillId;
                            Core::$systemDB->insert("dependency",$dep);
                            $depId=Core::$systemDB->getLastId();
                            $dependencyElementData=Core::$systemDB->select("skill_dependency",["dependencyId"=>$oldDepId]);
                            foreach ($dependencyElementData as $element){
                                $element["dependencyId"]=$depId;
                                $element["normalSkill"]
                                Core::$systemDB->insert("skill_dependency", $element);
                            }
                        }
                    }
                          
                }
            }*/
            //Course::copyCourseContent("skill_tier",$copyFrom,$courseId);
            //Course::copyCourseContent("skill",$copyFrom,$courseId);
            //Course::copyCourseContent("skill_dependency",$copyFrom,$courseId);
            
            Course::copyCourseContent("badge",$copyFrom,$courseId,true);
            //Course::copyCourseContent("badge_has_level",$copyFrom,$courseId);
            //Course::copyCourseContent("level",$copyFrom,$courseId,true);
            
            $fromFolder = Course::getCourseLegacyFolder($copyFrom);
            
            $fromTree = file_get_contents($fromFolder . "/tree.txt");  
            file_put_contents($legacyFolder."/tree.txt",$fromTree);
            $fromBagdes = file_get_contents($fromFolder . "/achievements.txt");  
            file_put_contents($legacyFolder."/achievements.txt",$fromBagdes);
            $fromLevels = file_get_contents($fromFolder . "/levels.txt");  
            file_put_contents($legacyFolder."/levels.txt",$fromLevels);

            \Utils::copyFolder($fromFolder ."/tree",$legacyFolder ."/tree");
        } else {
            $teacherRoleId= Course::insertBasicCourseData(Core::$systemDB, $courseId);
            Core::$systemDB->insert("user_role",["id" => $currentUserId,"course" => $courseId, "role"=>$teacherRoleId]);
            $modules = Core::$systemDB->selectMultiple("module");
            foreach($modules as $mod){
                Core::$systemDB->insert("course_module",["course"=>$courseId,"moduleId"=>$mod["moduleId"]]);
            }
        }
        
        return $course;
    }
}
