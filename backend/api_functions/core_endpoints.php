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
    $folder = API::getValue('folder');
    $path = $folder . "/" . API::getValue('name') . ".png";

    if (!is_dir($folder))
        mkdir($folder, 0777, true);
    file_put_contents($path, $img);

    API::response(['path' => $path]);
});
