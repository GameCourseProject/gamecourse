<?php
namespace Api;

use GameCourse\User\User;

/**
 * This is the User controller, which holds API endpoints for
 * user related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class UserController
{
    /**
     * @OA\Get(
     *     path="/api/?module=docs&request=getAPIDocs",
     *     tags={"Documentation"},
     *     @OA\Response(response="200", description="GameCourse API documentation")
     * )
     */
    public function getAPIDocs()
    {
        $openapi = \OpenApi\Generator::scan([ROOT_PATH . "controllers"]);
        API::response(json_decode($openapi->toJSON()));
    }
}


