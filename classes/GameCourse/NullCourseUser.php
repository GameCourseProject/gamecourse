<?php
namespace GameCourse;

class NullCourseUser extends CourseUser {
    function __construct($id, $course) {
       /* $userWrapper = new \ValueWrapper(array(
            'id' => $id,
            'name' => 'UserWithNoName',
            'email' => 'User@WithNo@Email',
            'previousActivity' => 0,
            'lastActivity' => 0,
            'roles' => array()
        ));*/
        parent::__construct($id, $course);
    }

    function refreshActivity() {
    }

    // inherit getId()
    // inherit getCourse()
    // inherit getPreviousActivity()

    public function getUsername() {
        return null;
    }

    function getData($field = null, $wrapped = false) {
        return null;
    }

    // inherit getRoles()
    // inherit setRoles($roles)
    // inherit hasRole($role)

    // inherit isTeacher()
    // inherit isStudent()

    // inherit getBasicInfo()

    // inherit getWrapper()

    // inherit getWrappedComplex

    // inherit getLandingPage

    function exists() {
        return false;
    }
}
