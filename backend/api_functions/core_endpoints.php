<?php

namespace APIFunctions;

use GameCourse\API;

$MODULE = 'core';

/**
 * Upload file to server.
 *
 * @param $file
 * @param string $folder
 * @param string $name
 */
API::registerFunction($MODULE, 'uploadFile', function () {
    API::requireValues('file', 'folder', 'name');

    $file = base64_decode(preg_replace('#^data:\w+/\w+;base64,#i', '', API::getValue('file')));
    $folder = API::getValue('folder');

    $matches = null;
    preg_match('#^data:\w+/(\w+);base64,#i', API::getValue('file'), $matches);
    $extension = $matches[1];

    $path = $folder . "/" . API::getValue('name') . "." . $extension;

    if (!is_dir($folder))
        mkdir($folder, 0777, true);
    file_put_contents($path, $file);

    API::response(['path' => $path]);
});
