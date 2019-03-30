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
        return (!empty($this->getData('id')));
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
    public function getName(){
        return parent::getData("name");
    }
    public function getUsername() {
        return parent::getData("username");
    }
    
    function  getData($field="*"){
        return Core::$sistemDB->select("course_user",$field,["course"=>$this->course->getId(),
                                                                          "id"=>$this->id]);
    }
    function setCampus($campus) {
        return Core::$sistemDB->update("course_user",["campus"=>$campus],
                ["course"=>$this->course->getId(),"id"=>$this->id]);
    }
    function getRoles() {
        return explode(",",$this->getData("roles"));
    }
    
    function setRoles($roles) {
        $updatedRoles = implode(',', $roles);
        Core::$sistemDB->update("course_user",["roles"=>$updatedRoles],["course"=>$this->course->getId(),
                                                                          "id"=>$this->id]);
    }
    
    //adds Role (instead of replacing) only if it isn't already in user's roles
    function addRole($role){
        $currRoles=$this->getRoles();
        if (!in_array($role, $currRoles)){
            if (empty($currRoles))
                $this->setRoles($role);
            else
                $this->setRoles(array_merge($currRoles, [$role]));
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
