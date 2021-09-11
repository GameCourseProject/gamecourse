<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Course;
use GameCourse\User;
use GameCourse\Core;

//system users (manage admins)

//get list of users on the system
API::registerFunction('core', 'users', function () {
    API::requireAdminPermission();

    $users = User::getAllInfo(); //get all users
    foreach ($users as &$user) {
        $uOb = User::getUser($user['id']);
        $coursesIds = $uOb->getCourses();
        $courses = [];
        foreach ($coursesIds as $id) {
            $cOb = Course::getCourse($id, false);
            $c = ["id" => $id, "name" => $cOb->getName()];
            $courses[] = $c;
        }
        $lastLogins = $uOb->getSystemLastLogin();

        $user['ncourses'] = sizeof($courses);
        $user['courses'] = $courses;
        $user['lastLogin'] = $lastLogins;
        $user['username'] = $uOb->getUsername();
        $user['authenticationService'] = User::getUserAuthenticationService($user['username']);
    }

    API::response(array('users' => $users));
});

//change user admin permissions
API::registerFunction('core', 'setUserAdmin', function () {
    API::requireAdminPermission();
    API::requireValues('user_id');
    API::requireValues('isAdmin');

    $user_id = API::getValue('user_id');
    $isAdmin = API::getValue('isAdmin');

    $uOb = User::getUser($user_id);
    if ($uOb != null) {
        $uOb->setAdmin($isAdmin);
    } else {
        API::error("There is no user with that id: " . API::getValue('user_id'));
    }
});

//change user access
API::registerFunction('core', 'setUserActive', function () {
    API::requireAdminPermission();
    API::requireValues('user_id');
    API::requireValues('isActive');

    $user_id = API::getValue('user_id');
    $active = API::getValue('isActive');

    $uOb = User::getUser($user_id);
    if ($uOb != null) {
        $uOb->setActive($active);
    } else {
        API::error("There is no user with that id: " . API::getValue('user_id'));
    }
});

//delete user from system
API::registerFunction('core', 'deleteUser', function () {
    API::requireAdminPermission();
    API::requireValues('user_id');

    $user = API::getValue('user_id');
    $ubj = User::getUser($user);
    if ($ubj->exists()) {
        User::deleteUser($user);
    } else {
        API::error("There is no user with that id: " . $user);
    }
});

//create new user on the system
API::registerFunction('core', 'createUser', function () {
    API::requireAdminPermission();
    API::requireValues('userHasImage', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userUsername', 'userMajor', 'userIsActive', 'userIsAdmin');
    $user = User::getUserByStudentNumber(API::getValue('userStudentNumber'));
    if ($user == null) {
        $id = User::addUserToDB(API::getValue('userName'), API::getValue('userUsername'), API::getValue('userAuthService'), API::getValue('userEmail'), API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userMajor'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));
        if (API::getValue('userHasImage') == 'true') {
            API::requireValues('userImage');
            $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
            User::saveImage($img, API::getValue('userUsername'));
        }
    } else {
        API::error("There is already a student registered with the student number: " . API::getValue('userStudentNumber'));
    }
});

//edit user on the system
API::registerFunction('core', 'editUser', function () {
    API::requireAdminPermission();
    API::requireValues('userHasImage', 'userId', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userMajor', 'userUsername', 'userIsActive', 'userIsAdmin');

    $user = new User(API::getValue('userId'));
    if ($user->exists()) {
        //verify if new student number(if changed) is taken
        $userStudentNumber = API::getValue('userStudentNumber');
        if ($user->getStudentNumber() != $userStudentNumber) {
            $otheruser = User::getUserByStudentNumber($userStudentNumber);
            if ($otheruser != null) {
                API::error("There is already a student registered with the student number: " . $userStudentNumber);
            }
        }

        $user->editUser(API::getValue('userName'), API::getValue('userUsername'), API::getValue('userAuthService'), API::getValue('userEmail'), API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userMajor'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));

        if (API::getValue('userHasImage') == 'true') {
            API::requireValues('userImage');
            $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
            User::saveImage($img, API::getValue('userUsername'));
        }
    } else {
        API::error("There is no user with that id: " . API::getValue('userId'));
    }
});

//edit user on the system
API::registerFunction('core', 'editSelfInfo', function () {
    API::requireValues('userHasImage', 'userId', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userUsername');

    $user = Core::getLoggedUser();
    $major = $user->getMajor();
    $isActive = $user->isActive();
    $isAdmin = $user->isAdmin();

    $user->editUser(API::getValue('userName'), API::getValue('userUsername'), API::getValue('userAuthService'), API::getValue('userEmail'), API::getValue('userStudentNumber'), API::getValue('userNickname'), $major, $isAdmin, $isActive);

    if (API::getValue('userHasImage') == 'true') {
        API::requireValues('userImage');
        $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
        User::saveImage($img, API::getValue('userUsername'));
    }
});

//import users to the system
API::registerFunction('core', 'importUser', function () {
    API::requireAdminPermission();
    API::requireValues('file');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nUsers = User::importUsers($fileContents, $replace);
    API::response(array('nUsers' => $nUsers));
});

//export users from the system
API::registerFunction('core', 'exportUsers', function () {
    API::requireAdminPermission();
    $users = User::exportUsers();
    API::response(array('users' => $users));
});
