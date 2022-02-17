<?php

namespace APIFunctions;

use GameCourse\API;

$MODULE = 'core';

/**
 * Upload image to server.
 *
 * @param $image
 * @param string $folder
 * @param string $name
 */
API::registerFunction($MODULE, 'uploadImage', function () {
    API::requireValues('image', 'folder', 'name');

    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', API::getValue('image')));
    $path = API::getValue('folder') . "/" . API::getValue('name') . ".png";
    file_put_contents($path, $img);

    API::response(['path' => $path]);
});
