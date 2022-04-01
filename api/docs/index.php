<?php
/**
 * Exposes GameCourse's RESTful API in a JSON format,
 * which can be used by Swagger UI to generate an API
 * documentation page.
 */

require __DIR__ . "/../inc/bootstrap.php";

$openapi = \OpenApi\Generator::scan([ROOT_PATH . "controllers"]);
header('Content-Type: application/json');
echo $openapi->toJSON();