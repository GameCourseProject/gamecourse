<?php
namespace API;

use OpenApi\Generator;

/**
 * This is the Docs controller, which holds API endpoints for
 * documentation related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class DocsController
{
    /**
     * @OA\Get(
     *     path="/?module=docs&request=getAPIDocs",
     *     tags={"Documentation"},
     *     @OA\Response(response="200", description="GameCourse API documentation")
     * )
     */
    public function getAPIDocs()
    {
        API::requireAdminPermission();
        $openAPI = Generator::scan([ROOT_PATH . "controllers"]);
        API::response(json_decode($openAPI->toJSON()));
    }
}
