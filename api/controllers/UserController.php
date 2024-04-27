<?php
namespace API;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\User\User;

/**
 * This is the User controller, which holds API endpoints for
 * user related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="User",
 *     description="API endpoints for user related actions"
 * )
 */
class UserController
{
    /*** --------------------------------------------- ***/
    /*** ---------------- Logged User ---------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Get logged user information.
     *
     * @OA\Get(
     *     path="/?module=user&request=getLoggedUser",
     *     summary="Receive logged user information",
     *     description="Endpoint allows to receive current logged user information",
     *     tags={"User"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="The user ID"
     *                     ),
     *                     @OA\Property(
     *                         property="username",
     *                         type="string",
     *                         description="The user username"
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         description="The user name"
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         description="The user e-mail"
     *                     ),
     *                     @OA\Property(
     *                         property="major",
     *                         type="string",
     *                         description="The user major"
     *                     ),
     *                     @OA\Property(
     *                         property="nickname",
     *                         type="string",
     *                         description="The user nickname"
     *                     ),
     *                     @OA\Property(
     *                         property="studentNumber",
     *                         type="integer",
     *                         description="The user student number"
     *                     ),
     *                     @OA\Property(
     *                         property="auth_service",
     *                         type="string",
     *                         enum={"fenix", "google", "facebook", "linkedin"}
     *                         description="The user chosen authentication service"
     *                     ),
     *                     @OA\Property(
     *                         property="image",
     *                         type="string",
     *                         description="The user image URL"
     *                     ),
     *                     @OA\Property(
     *                         property="isAdmin",
     *                         type="boolean",
     *                         description="Whether user is an admin"
     *                     ),
     *                     @OA\Property(
     *                         property="isActive",
     *                         type="boolean",
     *                         description="Whether user is active"
     *                     ),
     *                     example={
     *                         "data"={
     *                              "id"=1,
     *                              "name"="John Smith Doe",
     *                              "email"="johndoe@email.com",
     *                              "major"="MEIC-A",
     *                              "nickname"="John Doe",
     *                              "studentNumber"=12345,
     *                              "isAdmin"=false,
     *                              "isActive"=true,
     *                              "username"="ist12345",
     *                              "auth_service"="fenix",
     *                              "image"="{API_URL}/{USER_DATA_FOLDER}/1/profile.png"
     *                         }
     *                     }
     *                 )
     *             )
     *         }
     *     )
     * )
     */
    public function getLoggedUser()
    {
        $user = Core::getLoggedUser();
        $userInfo = $user->getData();
        API::response($userInfo);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets a user by its ID.
     *
     * @param int $userId
     */
    public function getUserById()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $loggedUser = Core::getLoggedUser();
        if ($loggedUser->getId() != $user->getId() && !$loggedUser->isAdmin()) {
            $userInfo = $user->getData("name, major, nickname, studentNumber, username, auth_service");
            $userInfo["image"] = $user->getImage();
            $userInfo["avatar"] = $user->getAvatar();
        } else $userInfo = $user->getData();

        API::response($userInfo);
    }

    /**
     * Get users on the system.
     * Option for 'active'.
     *
     * @param bool $isActive (optional)
     * @param bool $isAdmin (optional)
     */
    public function getUsers()
    {
        API::requireAdminPermission();
        $isActive = API::getValue("isActive", "bool");
        $isAdmin = API::getValue("isAdmin", "bool");

        $users = User::getUsers($isActive, $isAdmin);
        foreach ($users as &$userInfo) {
            $user = User::getUserById($userInfo["id"]);
            $userInfo["nrCourses"] = count($user->getCourses());
        }

        API::response($users);
    }

    /**
     * Get list of courses for a given user.
     * Option for 'active' and/or 'visible'.
     */
    public function getUserCourses()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Only admins can access other users' courses
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireAdminPermission();

        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");

        $userCourses = $user->getCourses($isActive, $isVisible);

        // Only course admins can access invisible courses
        if (!$isVisible && !Core::getLoggedUser()->isAdmin()) {
            $filteredCourses = [];
            foreach ($userCourses as $courseInfo) {
                $course = Course::getCourseById($courseInfo["id"]);
                $courseUser = $course->getCourseUserById(Core::getLoggedUser()->getId());
                if ($courseUser->isTeacher() || $course->isVisible())
                    $filteredCourses[] = $courseInfo;
            }
            $userCourses = $filteredCourses;
        }

        API::response($userCourses);
    }


    /*** --------------------------------------------- ***/
    /*** ------------- User Manipulation ------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function createUser()
    {
        API::requireAdminPermission();
        API::requireValues('name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'image');

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $image = API::getValue("image");

        // Add new user
        $user = User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, false, true);
        if ($image) $user->setImage($image);

        $userInfo = $user->getData();
        $userInfo["nrCourses"] = count($user->getCourses());
        API::response($userInfo);
    }

    /**
     * @throws Exception
     */
    public function editUser()
    {
        API::requireValues('userId', 'name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'image');

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Only admins can access other users' information
        if (Core::getLoggedUser()->getId() !== $userId)
            API::requireAdminPermission();

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $isAdmin = $user->isAdmin();
        $isActive = $user->isActive();
        $image = API::getValue("image");

        // Edit user
        $user->editUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
        if ($image) $user->setImage($image);

        $userInfo = $user->getData();
        $userInfo["nrCourses"] = count($user->getCourses());
        API::response($userInfo);
    }

    /**
     * @throws Exception
     */
    public function deleteUser()
    {
        API::requireAdminPermission();
        API::requireValues('userId');

        $userId = API::getValue("userId", "int");
        API::verifyUserExists($userId);

        User::deleteUser($userId);
    }

    /**
     * @throws Exception
     */
    public function setAdmin()
    {
        API::requireAdminPermission();
        API::requireValues('userId', 'isAdmin');

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $isAdmin = API::getValue("isAdmin", "bool");
        $user->setAdmin($isAdmin);
    }

    /**
     * @throws Exception
     */
    public function setActive()
    {
        API::requireAdminPermission();
        API::requireValues('userId', 'isActive');

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $isActive = API::getValue("isActive", "bool");
        $user->setActive($isActive);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Courses ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Checks whether a user is a teacher of any course.
     *
     * @return void
     * @throws Exception
     */
    public function isATeacher()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Only admins can access other users' information
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireAdminPermission();

        API::response($user->isATeacher());
    }

    /**
     * Checks whether a user is a student of any course.
     *
     * @return void
     * @throws Exception
     */
    public function isAStudent()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Only admins can access other users' information
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireAdminPermission();

        API::response($user->isAStudent());
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ Avatars ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets a user's avatar settings.
     *
     * @return void
     * @throws Exception
     */
    public function getUserAvatarSettings()
    {
        API::requireValues("userId");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        API::response($user->getAvatarSettings());
    }

    /**
     * Saves a user's avatar.
     *
     * @return void
     * @throws Exception
     */
    public function saveUserAvatar()
    {
        API::requireValues("userId", "selected", "colors", "image");

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        $selected = API::getValue("selected", "array");
        $colors = API::getValue("colors", "array");
        $png = API::getValue("image");

        $user->saveAvatar($selected, $colors, $png);
    }

    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Import users into the system.
     *
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importUsers()
    {
        API::requireAdminPermission();
        API::requireValues("file", "replace");

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrUsersImported = User::importUsers($file, $replace);
        API::response($nrUsersImported);
    }

    /**
     * Export users from the system into a .csv file.
     *
     * @param $userIds
     */
    public function exportUsers()
    {
        API::requireValues("userIds");
        $userIds = API::getValue("userIds", "array");

        API::requireAdminPermission();
        $csv = User::exportUsers($userIds);

        API::response($csv);
    }
}


