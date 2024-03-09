<?php
namespace GameCourse\Module\Avatars\controllers;

use API\API;
use API\Exception;

/**
 * This is the Avatar controller, which holds API endpoints for
 * saving students avatars.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 *
 * @OA\Tag(
 *     name="Avatar",
 *     description="API endpoints for saving students avatars"
 * )
 */
class AvatarController
{
    /**
     * @throws Exception
     */
    public function saveAvatarOptions()
    {
        API::requireValues('userId', 'colors', 'types');

        $userId = API::getValue("userId", "int");
        $user = API::verifyUserExists($userId);

        // Get values
        $colors = API::getValue("name");
        $types = API::getValue("username");
    }
}