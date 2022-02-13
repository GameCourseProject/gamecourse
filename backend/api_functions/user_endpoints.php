<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\User;
use GameCourse\Course;

$MODULE = 'user';


/*** --------------------------------------------- ***/
/*** ---------------- Logged User ---------------- ***/
/*** --------------------------------------------- ***/

/**
 * Get logged user information.
 */
API::registerFunction($MODULE, 'getLoggedUserInfo', function() {
    $user = Core::getLoggedUser();
    $userInfo = $user->getData();
    $userInfo['username'] = $user->getUsername();
    $userInfo['authenticationService'] = User::getUserAuthenticationService($userInfo['username']);
    $userInfo['hasImage'] = User::hasImage($user->getUsername());
    API::response(array('userInfo' => $userInfo));
});

/**
 * Get list of active courses for logged user.
 */
API::registerFunction($MODULE, 'getLoggedUserActiveCourses', function() {
    $user = Core::getLoggedUser();
    $coursesId = $user->getCourses();

    $courses = [];
    foreach($coursesId as $cid){
        $course = Core::getCourse($cid);
        if ($course["isActive"]) {
            $courses[] = $course;
        }
    }

    API::response(array('userActiveCourses' => $courses));
});



/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get all users on the system.
 */
API::registerFunction($MODULE, 'getUsers', function () {
    API::requireAdminPermission();

    $users = User::getAllInfo(); // Get all users
    foreach ($users as &$user) {
        $uOb = User::getUser($user['id']);
        $coursesIds = $uOb->getCourses();
        $courses = [];
        foreach ($coursesIds as $id) {
            $cOb = Course::getCourse($id, false);
            $c = $cOb->getData();
            $courses[] = $c;
        }
        $lastLogins = $uOb->getSystemLastLogin();

        $user['courses'] = $courses;
        $user['lastLogin'] = $lastLogins;
        $user['username'] = $uOb->getUsername();
        $user['authenticationService'] = User::getUserAuthenticationService($user['username']);
        $user['hasImage'] = User::hasImage($uOb->getUsername());
    }

    API::response(array('users' => $users));
});

/**
 * Get a list of courses that logged user is allowed to see.
 */
API::registerFunction($MODULE, 'getUserCourses', function() {
    $user = Core::getLoggedUser();

    if ($user->isAdmin()) {
        $courses = Core::getCourses(); // admins see all courses of the system

        // Get number of students per course
        foreach($courses as &$course){
            $cOb = Course::getCourse($course['id'], false);
            $course['nrStudents'] = sizeof($cOb->getUsersWithRole("Student"));
        }

    } else {
        $coursesId = $user->getCourses();

        $courses = [];
        foreach($coursesId as $cid){
            $course = Core::getCourse($cid);
            if ($course["isVisible"]) $courses[] = $course;
        }
    }
    API::response(array('courses' => $courses));
});

/**
 * Import users into the system.
 *
 * @param $file
 * @param bool $replace (optional)
 */
API::registerFunction($MODULE, 'importUsers', function () {
    API::requireAdminPermission();
    API::requireValues('file');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $nrUsers = User::importUsers($fileContents, $replace);
    API::response(array('nrUsers' => $nrUsers));
});

/**
 * Export users from the system.
 */
API::registerFunction($MODULE, 'exportUsers', function () {
    API::requireAdminPermission();
    $users = User::exportUsers();
    API::response(array('users' => $users));
});



/*** --------------------------------------------- ***/
/*** ------------- User Manipulation ------------- ***/
/*** --------------------------------------------- ***/

/**
 * Create a new user on the system.
 *
 * @param string $userName
 * @param int $userStudentNumber
 * @param string $userUsername
 * @param string $userEmail
 * @param string $userMajor
 * @param int $userIsActive
 * @param int $userIsAdmin
 * @param string $userAuthService
 * @param bool $userHasImage
 * @param string $userNickname (optional)
 * @param $userImage (optional)
 */
API::registerFunction($MODULE, 'createUser', function () {
    API::requireAdminPermission();
    API::requireValues('userHasImage', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userUsername', 'userMajor', 'userIsActive', 'userIsAdmin');

    $studentNumber = API::getValue('userStudentNumber');
    $user = User::getUserByStudentNumber($studentNumber);

    if ($user != null)
        API::error('There is already a student registered with studentNumber = ' . $studentNumber);

    $id = User::addUserToDB(API::getValue('userName'), API::getValue('userUsername'), API::getValue('userAuthService'), API::getValue('userEmail'), API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userMajor'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));
    if (API::getValue('userHasImage') == 'true') {
        API::requireValues('userImage');
        $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
        User::saveImage($img, API::getValue('userUsername'));
    }

    $uOb = User::getUser($id);
    $user = $uOb->getData();
    $coursesIds = $uOb->getCourses();
    $courses = [];
    foreach ($coursesIds as $id) {
        $cOb = Course::getCourse($id, false);
        $c = $cOb->getData();
        $courses[] = $c;
    }
    $lastLogins = $uOb->getSystemLastLogin();

    $user['courses'] = $courses;
    $user['lastLogin'] = $lastLogins;
    $user['username'] = $uOb->getUsername();
    $user['authenticationService'] = User::getUserAuthenticationService($user['username']);
    $user['hasImage'] = User::hasImage($uOb->getUsername());

    API::response(array('user' => $user));
});

/**
 * Edit an existing user.
 *
 * @param int $userId
 * @param string $userName
 * @param int $userStudentNumber
 * @param string $userUsername
 * @param string $userEmail
 * @param string $userMajor
 * @param int $userIsActive
 * @param int $userIsAdmin
 * @param string $userAuthService
 * @param bool $userHasImage
 * @param string $userNickname (optional)
 * @param $userImage (optional)
 */
API::registerFunction($MODULE, 'editUser', function () {
    API::requireAdminPermission();
    API::requireValues('userHasImage', 'userId', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userMajor', 'userUsername', 'userIsActive', 'userIsAdmin');

    $userId = API::getValue('userId');
    $user = API::verifyUserExists($userId);

    // Verify if new student number (if changed) is taken
    $studentNumber = API::getValue('userStudentNumber');
    if ($user->getStudentNumber() != $studentNumber) {
        $otherUser = User::getUserByStudentNumber($studentNumber);
        if ($otherUser != null)
            API::error('There is already a student registered with studentNumber = ' . $studentNumber);
    }

    $user->editUser(API::getValue('userName'), API::getValue('userUsername'), API::getValue('userAuthService'), API::getValue('userEmail'), API::getValue('userStudentNumber'), API::getValue('userNickname'), API::getValue('userMajor'), API::getValue('userIsAdmin'), API::getValue('userIsActive'));

    if (API::getValue('userHasImage') == 'true') {
        API::requireValues('userImage');
        $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('userImage')));
        User::saveImage($img, API::getValue('userUsername'));
    }
});

/**
 * Edit self user info.
 *
 * @param string $userName
 * @param int $userStudentNumber
 * @param string $userUsername
 * @param string $userEmail
 * @param string $userAuthService
 * @param bool $userHasImage
 * @param string $userNickname (optional)
 * @param $userImage (optional)
 */
API::registerFunction($MODULE, 'editSelfInfo', function () {
    API::requireValues('userHasImage', 'userName', 'userAuthService', 'userStudentNumber', 'userEmail', 'userUsername');

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

/**
 * Delete an existing user.
 *
 * @param int $userId
 */
API::registerFunction($MODULE, 'deleteUser', function () {
    API::requireAdminPermission();
    API::requireValues('userId');

    $userId = API::getValue('userId');
    $user = API::verifyUserExists($userId);

    User::deleteUser($userId);
});

/**
 * Change user admin permission.
 *
 * @param int $userId
 * @param int $isAdmin
 */
API::registerFunction($MODULE, 'setUserAdminPermission', function () {
    API::requireAdminPermission();
    API::requireValues('userId', 'isAdmin');

    $userId = API::getValue('userId');
    $user = API::verifyUserExists($userId);
    $isAdmin = API::getValue('isAdmin');

    $user->setAdmin($isAdmin);
});

/**
 * Change user access.
 *
 * @param int $userId
 * @param int $isActive
 */
API::registerFunction($MODULE, 'setUserActiveState', function () {
    API::requireAdminPermission();
    API::requireValues('userId', 'isActive');

    $userId = API::getValue('userId');
    $user = API::verifyUserExists($userId);
    $isActive = API::getValue('isActive');

    $user->setActive($isActive);
});
