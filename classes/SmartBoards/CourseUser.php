<?php
namespace SmartBoards;

class CourseUser extends User{
    
    //private $id;
    private $course;
    
    function __construct($id, $course) {
       // $this->id = $id;
        parent::__construct($id);
        $this->course = $course;
    }
    public function create($id,$role=null){
        //User must already exist in database
        Core::$sistemDB->insert("course_user",["course"=>$this->course->getId(),"id"=>$id]);
        if ($role){
            Core::$sistemDB->update("course_user",["roles"=>"'".$role."'"],["course"=>$this->course->getId(),"id"=>$id]);
        }
    }
    public function exists() {
        return (Core::$sistemDB->select("course_user","*",["id"=>$this->id,"course"=>$this->course->getId()])!=null);
    }
    //function refreshActivity() {
    //    $this->userWrapper->set('previousActivity', $this->userWrapper->get('lastActivity'));
    //    $this->userWrapper->set('lastActivity', time());
    //}

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

    /*function getData($field = null, $wrapped = false) {
        $data = $this->course->getUserData($this->id);
        if ($data == null)
            return null;
        if ($field == null)
            return $data;
        if ($wrapped)
            return $data->getWrapped($field, null);
        return $data->get($field, null);
    }*/

    function getRoles() {
        //return $this->userWrapper->get('roles');
        return explode(",",Core::$sistemDB->select("course_user",'roles',["course"=>$this->course->getId(),
                                                                          "id"=>$this->id,]));
    }

    function setRoles($roles) {
        //return $this->userWrapper->set('roles', $roles);
        Core::$sistemDB->update("course_user",["roles"=>"'".$roles."'"],["course"=>$this->course->getId(),
                                                                          "id"=>$this->id,]);
    }

    function hasRole($role) {
        return (!empty(Core::$sistemDB->selectMultiple("course_user",'*',["course"=>$this->course->getId(),
                                                            "id"=>$this->id,
                                                             "roles"=>"'".$role."'"])));
    }

    function isTeacher() {
        return $this->hasRole('Teacher');
    }

    function isStudent() {
        return $this->hasRole('Student');
    }

    //function getBasicInfo() {
    //    return $this->userWrapper->getValue();
    //}

    //function getWrapper() {
    //    return $this->userWrapper;
    //}

    //function getWrappedComplex($complexKey) {
    //    return $this->userWrapper->getWrappedComplex($complexKey);
    //}

    function getLandingPage() {
        $userRoles = $this->getRoles();//array w names
        $landingPage = Core::$sistemDB->select("course","defaultLandingPage",["id"=> $this->course->getId()]);
        $roles=$this->course->getRolesHierarchy();
        $this->course->goThroughRoles($roles,function($role, $hasChildren, $continue) use (&$landingPage, $userRoles) {
            if (in_array($role['name'], $userRoles) && $role['landingPage'] != '') {
                $landingPage = $role['landingPage'];
                //break?
            }
            $continue();
        });
        return $landingPage;
    }
}
