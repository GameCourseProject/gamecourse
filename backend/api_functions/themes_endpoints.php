<?php

namespace APIFunctions;

use GameCourse\API;

$MODULE = 'themes';


/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get theme settings
 * FIXME: not doing much now; needs refactor
 */
API::registerFunction($MODULE, 'getThemeSettings', function() {
    API::requireAdminPermission();

    $themes = array();

    $themesDir = dir('themes/');
    while (($themeDirName = $themesDir->read()) !== false) {
        $themeDir = 'themes/' . $themeDirName;
        if ($themeDirName == '.' || $themeDirName == '..' || filetype($themeDir) != 'dir')
            continue;
        $themes[] = array('name' => $themeDirName, 'preview' => file_exists($themeDir . '/preview.png'));
    }
    $themesDir->close();

    API::response(array('theme' => $GLOBALS['theme'], 'themes' => $themes));
});