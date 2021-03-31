<?php
namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\ModuleLoader;


//system settings (theme settings)
API::registerFunction('settings', 'global', function() {
    API::requireAdminPermission();

    if (API::hasKey('setTheme')) {
        if (file_exists('themes/' . API::getValue('setTheme')))
            Core::setTheme(API::getValue('setTheme'));
    } else {
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
    }
});

//system settings (courses installed)
API::registerFunction('settings', 'modules', function() {
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

// ------------------- Module List

API::registerFunction('core', 'importModule', function () {
    API::requireAdminPermission();
    API::requireValues('file');
    API::requireValues('fileName');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    Module::importModules($fileContents, API::getValue("fileName"));
    API::response(array());
});

API::registerFunction('core', 'exportModule', function () {
    API::requireAdminPermission();
    $zipFile = Module::exportModules();
    API::response(array("file"=> $zipFile));
    unlink($zipFile);
});
