<?php

namespace APIFunctions;

use GameCourse\API;

include('api_functions/core_endpoints.php');
include('api_functions/course_endpoints.php');
include('api_functions/module_endpoints.php');
include('api_functions/themes_endpoints.php');
include('api_functions/user_endpoints.php');
include('api_functions/views_endpoints.php');


$MODULE = 'api';

/**
 * Get all API endpoints available.
 */
API::registerFunction($MODULE, 'getAPIEndpoints', function () {
    // FIXME: return functions args and description as well
    API::response(array('endpoints' => API::getAllFunctions()));
});
