<?php
namespace SmartBoards;

class CourseUser {
    function __construct($id, $userWrapper, $course) {
        $this->id = $id;
        $this->userWrapper = $userWrapper;
        $this->course = $course;
    }

    function refreshActivity() {
        $this->userWrapper->set('previousActivity', $this->userWrapper->get('lastActivity'));
        $this->userWrapper->set('lastActivity', time());
    }

    function getId() {
        return $this->id;
    }

    public function getCourse() {
        return $this->course;
    }

    public function getPreviousActivity() {
        return $this->userWrapper->get('previousActivity');
    }

    public function getUsername() {
        return User::getUser($this->id)->getUsername();
    }

    function getData($field = null, $wrapped = false) {
        $data = $this->course->getUserData($this->id);
        if ($data == null)
            return null;
        if ($field == null)
            return $data;
        if ($wrapped)
            return $data->getWrapped($field, null);
        return $data->get($field, null);
    }

    function getRoles() {
        return $this->userWrapper->get('roles');
    }

    function setRoles($roles) {
        return $this->userWrapper->set('roles', $roles);
    }

    function hasRole($role) {
        $roles = $this->userWrapper->getWrapped('roles');
        if ($roles == null)
            return false;
        return $roles->has($role);
    }

    function isTeacher() {
        return $this->hasRole('Teacher');
    }

    function isStudent() {
        return $this->hasRole('Student');
    }

    function getBasicInfo() {
        return $this->userWrapper->getValue();
    }

    function getWrapper() {
        return $this->userWrapper;
    }

    function getWrappedComplex($complexKey) {
        return $this->userWrapper->getWrappedComplex($complexKey);
    }

    function getLandingPage() {
        $userRoles = $this->getRoles();
        $landingPage = $this->course->getWrapped('defaultRoleSettings')->get('landingPage');
        $rolesSettings = $this->course->getRolesSettings()->getValue();
        $this->course->goThroughRoles(function($roleName, $hasChildren, $continue) use (&$landingPage, $userRoles, $rolesSettings) {
            if (in_array($roleName, $userRoles) && $rolesSettings[$roleName]['landingPage'] != '')
                $landingPage = $rolesSettings[$roleName]['landingPage'];
            $continue();
        });
        return $landingPage;
    }

    function exists() {
        return true;
    }

    private $id;
    private $userWrapper;
    private $course;
}
