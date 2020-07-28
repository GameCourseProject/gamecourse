<?php
namespace GameCourse;

use \GameCourse as GameCourse;

class ModuleLoader {
    
    private static $loadingModuleDir = null;
    private static $modules = array();
    private static $firstScan = false;
    
    private static function requireProp($module, $prop) {
        if (!array_key_exists($prop, $module))
            die('Missing ' . $prop . ' in module' . (array_key_exists('id', $module) ? ' ' . $module['id'] : ' from directory ' . static::$loadingModuleDir));
    }

    public static function registerModule($module) {
        if (static::$loadingModuleDir == null)
            die('Not expecting to load a module.');

        static::requireProp($module, 'id');
        static::requireProp($module, 'name');
        static::requireProp($module, 'version');
        if (!array_key_exists('dependencies', $module))
            $module['dependencies'] = array();
        static::requireProp($module, 'factory');
        $module['dir'] = static::$loadingModuleDir;

        if (array_key_exists($module['id'], static::$modules))
            die('Module conflict, two modules with same id: ' . $module['id'] . ' at ' . $module['dir'] . ' and ' . static::$modules[$module['id']]['dir']);
        static::$modules[$module['id']] = $module;
        static::$loadingModuleDir = null;
        
        if (empty(Core::$systemDB->select("module",['moduleId' => $module['id']]))) {
            Core::$systemDB->insert("module", ['moduleId' => $module['id'], 'name' => $module['name']]);
            $courses= array_column(Core::$systemDB->selectMultiple("course",null,"id"),"id");
            foreach ($courses as $course) {
                Core::$systemDB->insert("course_module",["course"=>$course,"moduleId"=>$module['id']]);
            }
        }
    }

    public static function loadModuleFromDir($moduleDir) {
        $moduleDirHandle = dir($moduleDir);
        while (($file_name = $moduleDirHandle->read()) !== false) {
            $file = $moduleDir . '/' . $file_name;
            if ($file_name == '..' || $file_name == '.' || filetype($file) != 'file')
                continue;
            if (strpos($file_name, 'module.') === 0) {
                static::$loadingModuleDir = $moduleDir . '/' ;
                require_once($file);
            }
        }
        $moduleDirHandle->close();
    }

    public static function scanModules() {
        $modulesDir = dir(MODULES_FOLDER);
        while (($moduleDirName = $modulesDir->read()) !== false) {
            $moduleDir = preg_replace('#/+#','/', MODULES_FOLDER . '/') . $moduleDirName;
            if ($moduleDirName == '.' || $moduleDirName == '..' || filetype($moduleDir) != 'dir')
                continue;
            static::loadModuleFromDir($moduleDir);
        }
        $modulesDir->close();
        static::$firstScan = true;
    }

    public static function initModules($course) {
        if (!static::$firstScan)
            static::scanModules(); 
            
        $modulesToLoad = $course->getEnabledModules();
        $numModulesToLoad = count($modulesToLoad);
        $loadedModules = array();
        $softDependencies = array();

        $loadedNow = 1;
        while($numModulesToLoad > 0) {
            if ($loadedNow == 0) {
                API::error('Circular hard dependency!', 500);
            }

            $loadedNow = 0;
            for ($m = 0; $m < $numModulesToLoad; ++$m) {
                $moduleId = $modulesToLoad[$m];
                if (!array_key_exists($moduleId, static::$modules)) {
                    API::error("Module $moduleId is enabled but was not found in the system.", 500);
                }

                $module = static::$modules[$moduleId];

                $canLoad = true;
                $notFound = array();
                $notEnabled = array();
                foreach ($module['dependencies'] as $dependency) {
                    if ($dependency['mode'] == 'hard') {
                        if (!array_key_exists($dependency['id'], static::$modules)) {
                            $notFound[] = $dependency['id'];
                            $canLoad = false;
                        } else if (!array_key_exists($dependency['id'], $loadedModules)) {
                            if (!in_array($dependency['id'], $modulesToLoad))
                                $notEnabled[] = $dependency['id'];
                            $canLoad = false;
                        }
                    }
                }

                ob_start();
                if (count($notFound) > 0)
                    echo 'Missing hard dependencies ' . json_encode($notFound) . ' for ' . $moduleId . "\n";
                if (count($notEnabled) > 0)
                    echo 'Hard dependencies ' . json_encode($notEnabled) . ' for ' . $moduleId . " are not enabled!\n";
                if (count($notFound) > 0 || count($notEnabled) > 0) {
                    API::error(ob_get_clean());
                } else
                    ob_clean();

                if ($canLoad) {
                    $loadedModules[$moduleId] = $module;
                    //array_splice($modulesToLoad, $m, 1);

                    foreach($module['dependencies'] as $dependency) {
                        if ($dependency['mode'] == 'soft')
                            $softDependencies[] = $dependency['id'];
                    }

                    $numModulesToLoad--;
                    $m--;
                    $loadedNow++;
                }
            }
        }

        $notFound = array();
        foreach ($softDependencies as $dependency) {
            if (!array_key_exists($dependency, $loadedModules))
                $notFound[] = $dependency;
        }

        foreach ($loadedModules as $moduleId => $module) {
            $moduleInfo = static::$modules[$moduleId];
            $module = $moduleInfo['factory']();
            $module->parent = $course;

            $module->id = $moduleInfo['id'];
            $module->dir = $moduleInfo['dir'];
            $module->name = $moduleInfo['name'];
            $module->version = $moduleInfo['version'];
            $module->dependencies = $moduleInfo['dependencies'];

            $module->init($course->getId());
            $module->setupResources();
            $course->addModule($module);
        }
    }

    public static function getModule($moduleId) {
        if (array_key_exists($moduleId, static::$modules))
            return static::$modules[$moduleId];
        return null;
    }

    public static function getModules() {
        return static::$modules;
    }
}
?>
