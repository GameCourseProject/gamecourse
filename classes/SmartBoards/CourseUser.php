<?php
namespace SmartBoards;

class CourseUser extends User{
    //$id is in User
    private $course;
    
    function __construct($id, $course) {
        parent::__construct($id);
        $this->course = $course;
    }
    //adds course_user to DB, User must already exist in DB
    public function addCourseUserToDB($roleId=null,$campus=""){
        Core::$systemDB->insert("course_user",["course"=>$this->course->getId(),"id"=>$this->id, "campus"=>$campus]);
        if ($roleId){
            Core::$systemDB->insert("user_role",["course"=>$this->course->getId(),"id"=>$this->id,"role"=>$roleId]);
        }
    }
    
    public function exists() {
        return (!empty($this->getData('id')));
    }
    
    public function delete(){
        Core::$systemDB->delete("course_user",["id"=>$this->id,"course"=>$this->course->getId()]);
    }
    
    //updates the lastActivity of user to current time, and prevActivity to previous val of activity
    function refreshActivity() {
        $lastlast = Core::$systemDB->select("course_user","lastActivity",["course"=>$this->course->getId(),"id"=>$this->id]);
        //this is updating the prevActivity (used to decide what notification to show)
        //if you whish to only show notification on the profile page, then you should only refresh activity in that page
        Core::$systemDB->update("course_user",["prevActivity"=> $lastlast],["course"=>$this->course->getId(),"id"=>$this->id]);
        
        Core::$systemDB->update("course_user",["lastActivity"=> date("Y-m-d H:i:s",time())],["course"=>$this->course->getId(),"id"=>$this->id]);
    }

    public function getId() {
        return $this->id;
    }

    public function getCourse() {
        return $this->course;
    }

    public function getName(){
        return parent::getData("name");
    }
    public function getUsername() {
        return parent::getData("username");
    }
    
    function  getData($field="*"){
        return Core::$systemDB->select("course_user",$field,["course"=>$this->course->getId(),"id"=>$this->id]);
    }
    
    function setCampus($campus) {
        return Core::$systemDB->update("course_user",["campus"=>$campus],
                ["course"=>$this->course->getId(),"id"=>$this->id]);
    }
    
    function getRoles() {
        return array_column(Core::$systemDB->selectMultiple("user_role","role",
                ["course"=>$this->course->getId(),"id"=>$this->id]),"role");
    }
    
    //receives array of roles and replaces them in the database
    function setRoles($roles) {
        $oldRoles = $this->getRoles();
        foreach($roles as $role){
            $found = array_search($role, $oldRoles);
            if ($found === false) {
                Core::$systemDB->insert("user_role", ["course" => $this->course->getId(), "id" => $this->id, "role" => $role]);
            } else {
                unset($oldRoles[$found]);
            }
        }
        //delete the remaining roles
        foreach ($oldRoles as $role){
            Core::$systemDB->delete("user_role",["course"=>$this->course->getId(),"id"=>$this->id,"role"=>$role]);
        }
    }
    
    //adds Role (instead of replacing) only if it isn't already in user's roles
    function addRole($roleId){
        $currRoles=$this->getRoles();
        if (!in_array($roleId, $currRoles)){
            Core::$systemDB->insert("user_role",["course"=>$this->course->getId(),"id"=>$this->id,"role"=>$roleId]);  
            return true;
        }
        return false;
    }

    function hasRole($role) {
        $roleId = Course::getRoleId($role, $this->course->getId());
        return (!empty(Core::$systemDB->selectMultiple("user_role",'*',
            ["course"=>$this->course->getId(),"id"=>$this->id,"role"=>$roleId])));
    }
    function isTeacher() {
        return $this->hasRole('Teacher');
    }
    function isStudent() {
        return $this->hasRole('Student');
    }

    function getLandingPage() {
        $userRoles = $this->getRoles();//array w names
        $landingPage = $this->course->getLandingPage();
        $this->course->goThroughRoles(function($roleName, $hasChildren, $continue) use (&$landingPage, $userRoles) {
            if (in_array($roleName, $userRoles) ) {
                $land = $this->course->getRoleData($roleName, "landingPage");
                if ($land != ''){
                    $landingPage= $land;
                }
            }
            $continue();
        });
        return $landingPage;
    }   
}
