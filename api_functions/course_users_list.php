<?php
namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\User;
use GameCourse\CourseUser;

//get name of all course roles
API::registerFunction('course', 'courseRoles', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $roles = $course->getRoles("name");
        API::response(["courseRoles"=> $roles ]);
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
    
});

//request to remove a user from the course
API::registerFunction('course', 'removeUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $user_id = API::getValue('user_id');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUser = new CourseUser($user_id,$course);
        if ($courseUser->exists()) {
            Core::$systemDB->delete("course_user",["id"=>$user_id, "course"=>$courseId]);
        }
        else{
            API::error("There is no user with that id: ". API::getValue('user_id'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
   
});

//request to edit user information
API::registerFunction('course', 'editUser', function() {
    API::requireAdminPermission();
    API::requireValues('userHasImage','userId','userName', 'userStudentNumber', 'userEmail', 'course', 'userRoles', 'userCampus');

    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUser = new CourseUser(API::getValue('userId'), $course);
        $user = new User(API::getValue('userId'));
        if ($courseUser->exists()) {
            $userStudentNumber = API::getValue('userStudentNumber');
            
            //verify if new student number(if changed) is taken
            if($user->getStudentNumber() != $userStudentNumber){
                $otheruser = User::getUserByStudentNumber($userStudentNumber);
                if ($otheruser != null){
                    API::error("There is already a student registered with the student number: ". $userStudentNumber);
                }
            }

            $user->setName(API::getValue('userName'));
            $user->setEmail(API::getValue('userEmail'));
            $user->setStudentNumber(API::getValue('userStudentNumber'));
            $user->setNickname(API::getValue('userNickname'));
            $user->setUsername(API::getValue('userUsername'));
            $user->setCampus(API::getValue('userCampus'));
            $user->setAuthenticationService(API::getValue('userAuthService'));

            //$courseUser->setCampus(API::getValue('userCampus'));
            $courseUser->setRoles(API::getValue('userRoles'));

            if(API::getValue('userHasImage') == 'true'){
                API::requireValues('userImage');
                $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
                User::saveImage($img, API::getValue('userId'));
            }
        }
        else{
            API::error("There is no user with that id: ". API::getValue('user_id') . "on the course:" . API::getValue('course'));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }

});

//request to create a new user and add it to the course users list
API::registerFunction('course', 'createUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        API::requireValues('userHasImage', 'userCampus', 'userUsername', 'userAuthService','userName', 'userStudentNumber', 'userEmail', 'userRoles');
        $userName = API::getValue('userName');
        $userEmail = API::getValue('userEmail');
        $userStudentNumber = API::getValue('userStudentNumber');
        $userNickname = API::getValue('userNickname');
        $userRoles = API::getValue('userRoles');
        $userCampus = API::getValue('userCampus');
        $userUsername = API::getValue('userUsername');
        $userAuthService = API::getValue('userAuthService');

        //verifies if user exits on the system
        $user = User::getUserByStudentNumber($userStudentNumber);
        if ($user == null) {
            User::addUserToDB($userName,$userUsername,$userAuthService,$userEmail,$userStudentNumber, $userNickname, $userCampus, 0, 1);
            $user = User::getUserByStudentNumber($userStudentNumber);
            $courseUser = new CourseUser($user->getId(),$course);
            $courseUser->addCourseUserToDB(null);
            $courseUser->setRoles(API::getValue('userRoles'));
            if(API::getValue('userHasImage') == 'true'){
                API::requireValues('userImage');
                $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
                User::saveImage($img, $user->getId());
            }

        } else {
            API::error("There is already a student registered with the student number: ". $userStudentNumber);
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

//add existing user to course
API::registerFunction('course', 'addUser', function(){
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        API::requireValues('users', 'role');
        $users = API::getValue('users');
        $role = API::getValue('role');
        foreach ($users as $userData) {
            //is user valid?
            $user = User::getUserByStudentNumber($userData['studentNumber']);
            if($user->getId() == $userData['id']){
                $courseUser = new CourseUser($userData['id'],$course);
                if (!$courseUser->exists()) {
                    $courseUser->addCourseUserToDB();
                    $courseUser->addRole($role);
                }
            }
            else{
                echo("not a valid user");
            }
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});


//get users not registered on the course
API::registerFunction('course', 'notCourseUsers', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    if($course != null){
        $courseUsers = $course->getUsers();
        $systemUsers = User::getAllInfo();

        $courseUsersInfo = [];
        foreach ($courseUsers as $userData) {
            $courseUsersInfo[] = array(
                'id' => $userData['id'], 
                'name' => $userData['name'], 
                'studentNumber' => $userData['studentNumber']);
        }
        $systemUsersInfo = [];
        foreach ($systemUsers as $userData) {
            $systemUsersInfo[] = array(
                'id' => $userData['id'], 
                'name' => $userData['name'], 
                'studentNumber' => $userData['studentNumber']);
        }
        
        //udiffCompare declared on the info.php file
        $notCourseUsers = array_udiff($systemUsersInfo, $courseUsersInfo, 'udiffCompare');
        
        API::response(array('notCourseUsers'=> $notCourseUsers)); 
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
});

//get users registered on the course
API::registerFunction('course', 'courseUsers', function() {
    API::requireCourseAdminPermission();
    $courseId=API::getValue('course');
    $course = Course::getCourse($courseId);
    $role=API::getValue('role');
    if($course != null){
        if (API::hasKey('role')){
            if ($role == "allRoles") {
                $users = $course->getUsers();
            }
            else{
                $users = $course->getUsersWithRole($role);
            }
            
            $usersInfo = [];
            //for security measures we send only what is needed
            foreach ($users as $userData) {
                $id = $userData['id'];
                $user = new \GameCourse\CourseUser($id,$course);
                $usersInfo[] = array(
                    'id' => $id, 
                    'name' => $user->getName(), 
                    'nickname' => $user->getNickname(),
                    'studentNumber' => $user->getStudentNumber(),
                    'roles' => $user->getRolesNames(),
                    'campus' => $user->getCampus(),
                    'email' => $user->getEmail(),
                    'lastLogin' => $user->getLastLogin(),
                    'username' => $user->getUsername(),
                    'authenticationService' => User::getUserAuthenticationService($user->getUsername())
                );
            }
            
            API::response(array('userList' => $usersInfo));
        }
    }
    else{
        API::error("There is no course with that id: ". API::getValue('course'));
    }
 
});

