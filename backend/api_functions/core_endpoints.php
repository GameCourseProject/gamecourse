<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Module;
use GameCourse\ModuleLoader;

$MODULE = 'core';


/*** --------------------------------------------- ***/
/*** ------------------- Themes ------------------ ***/
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



/*** --------------------------------------------- ***/
/*** ------------------ Modules ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get all available modules in the system.
 */
API::registerFunction($MODULE, 'getModules', function() {
    API::requireAdminPermission();

    $allModules = ModuleLoader::getModules();

    $modulesArr = [];
    foreach ($allModules as $module) {
        $mod = array(
            'id' => $module['id'],
            'name' => $module['name'],
            'dir' => $module['dir'],
            'version' => $module['version'],
            'dependencies' => $module['dependencies'],
            'description' => $module['description']
        );
        $modulesArr[] = $mod;
    }

    API::response($modulesArr);

});

/**
 * Import modules into the system.
 * FIXME: check if working
 *
 * @param $file
 * @param string $fileName
 */
API::registerFunction($MODULE, 'importModule', function () {
    API::requireAdminPermission();
    API::requireValues('file');
    API::requireValues('fileName');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    Module::importModules($fileContents, API::getValue("fileName"));
    API::response(array());
});

/**
 * Export modules of the system.
 * FIXME: not working.
 */
API::registerFunction($MODULE, 'exportModule', function () {
    API::requireAdminPermission();
    $zipFile = Module::exportModules();
    API::response(array("file"=> $zipFile));
    unlink($zipFile);
});
