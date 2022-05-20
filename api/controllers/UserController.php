<?php
namespace API;

use Exception;
use GameCourse\Core\Core;
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
     *                         property="authentication_service",
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
     *                              "authentication_service"="fenix",
     *                              "image"="{API_URL}/user_data/1/profile.png"
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
        $userInfo["image"] = $user->getImage();
        API::response($userInfo);
    }


    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

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

        if (Core::getLoggedUser()->getId() != $userId)
            API::requireAdminPermission();

        $isActive = API::getValue("isActive", "bool");
        $isVisible = API::getValue("isVisible", "bool");
        $userCourses = $user->getCourses($isActive, $isVisible);
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
        API::requireValues('name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'isActive', 'isAdmin', 'image');

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $isAdmin = API::getValue("isAdmin", "bool");
        $isActive = API::getValue("isActive", "bool");
        $image = API::getValue("image");

        // Add new user
        $user = User::addUser($name, $username, $authService, $email, $studentNumber, $nickname, $major, $isAdmin, $isActive);
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
        API::requireAdminPermission();
        API::requireValues('userId', 'name', 'authService', 'studentNumber', 'email', 'nickname', 'username', 'major', 'isActive', 'isAdmin', 'image');

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Get values
        $name = API::getValue("name");
        $username = API::getValue("username");
        $authService = API::getValue("authService");
        $email = API::getValue("email");
        $studentNumber = API::getValue("studentNumber", "int");
        $nickname = API::getValue("nickname");
        $major = API::getValue("major");
        $isAdmin = API::getValue("isAdmin", "bool");
        $isActive = API::getValue("isActive", "bool");
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
     */
    public function exportUsers()
    {
        API::requireAdminPermission();
        $csv = User::exportUsers();
        API::response($csv);
    }
}


