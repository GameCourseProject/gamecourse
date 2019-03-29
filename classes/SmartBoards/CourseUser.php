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
    public function create($role=null,$campus=""){
        Core::$sistemDB->insert("course_user",["course"=>$this->course->getId(),"id"=>$this->id, "campus"=>$campus]);
        if ($role){
            Core::$sistemDB->update("course_user",["roles"=>$role],["course"=>$this->course->getId(),"id"=>$this->id]);
        }
    }
    
    public function exists() {
        return (!empty(Core::$sistemDB->select("course_user","*",["id"=>$this->id,"course"=>$this->course->getId()])));
    }
    
    public function delete(){
        Core::$sistemDB->delete("course_user",["id"=>$this->id,"course"=>$this->course->getId()]);
    }
    
    function refreshActivity() {
        $prev = $this->getData("lastActivity");
        Core::$sistemDB->update("course_user",["prevActivity"=>$prev],["course"=>$this->course->getId(),"id"=>$this->id]);
        Core::$sistemDB->update("course_user",["lastActivity"=> date("Y-m-d H:i:s",time())],["course"=>$this->course->getId(),"id"=>$this->id]);
    }

    public function getId() {
        return $this->id;
    }

    public function getCourse() {
        return $this->course;
    }

    //public function getPreviousActivity() {
    //    return $this->userWrapper->get('previousActivity');
    //}

    public function getUsername() {
        return User::getUser($this->id)->getUsername();
    }
    
    function  getData($field="*"){
        return Core::$sistemDB->select("course_user",$field,["course"=>$this->course->getId(),
                                                                          "id"=>$this->id,]);
    }
    function getRoles() {
        return explode(",",$this->getData("roles"));
    }
    
    function setRoles($roles) {
        Core::$sistemDB->update("course_user",["roles"=>$roles],["course"=>$this->course->getId(),
                                                                          "id"=>$this->id,]);
    }
    
    //adds Role (instead of replacing) only if it isn't already in user's roles
    function addRole($role){
        $currRoles=$this->getRoles();
        if (!in_array($role, $currRoles)){
            if (empty($currRoles))
                $this->setRoles($role);
            else
                $this->setRoles(implode(',', $currRoles).",".$role); 
        }
    }

    function hasRole($role) {
        return (!empty(Core::$sistemDB->selectMultiple("course_user",'*',["course"=>$this->course->getId(),
                                                            "id"=>$this->id,
                                                             "roles"=>$role])));
    }

    function isTeacher() {
        return $this->hasRole('Teacher');
    }

    function isStudent() {
        return $this->hasRole('Student');
    }

    function getLandingPage() {
        $userRoles = $this->getRoles();//array w names
        $landingPage = Core::$sistemDB->select("course","defaultLandingPage",["id"=> $this->course->getId()]);
        $this->course->goThroughRoles(function($role, $hasChildren, $continue) use (&$landingPage, $userRoles) {
            if (in_array($role['name'], $userRoles) && $role['landingPage'] != '') {
                $landingPage = $role['landingPage'];
            }
            $continue();
        });
        return $landingPage;
    }   
}
