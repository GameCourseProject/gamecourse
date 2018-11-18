<?php

$modulesToLoad = array('leaderboard', 'views', 'overview', 'charts', 'skills', 'badges', 'xp-levels');

$modules = array(
    'leaderboard' => array(
        'id' => 'leaderboard',
        'dependencies' => array(
            array('id' => 'views', 'mode' => 'hard')
        )
    ),
    'views' => array(
        'id' => 'views',
        'dependencies' => array(
        )
    ),
    'overview' => array(
        'id' => 'overview',
        'dependencies' => array(
            array('id' => 'views', 'mode' => 'optional'),
        )
    ),
    'charts' => array(
        'id' => 'charts',
        'dependencies' => array(
            array('id' => 'views', 'mode' => 'hard'),
            array('id' => 'xp-levels', 'mode' => 'soft')
        )
    ),
    'skills' => array(
        'id' => 'skills',
        'dependencies' => array(
            array('id' => 'xp-levels', 'mode' => 'soft')
        )
    ),
    'badges' => array(
        'id' => 'badges',
        'dependencies' => array(
            array('id' => 'xp-levels', 'mode' => 'hard')
        )
    ),
    'xp-levels' => array(
        'id' => 'xp-levels',
        'dependencies' => array(
        )
    )
);

$numModulesToLoad = count($modules);
$loadedModules = array();
$softDependencies = array();

echo '<pre>';

$loadedNow = 1;
while($numModulesToLoad > 0) {
    if ($loadedNow == 0) {
        die('Circular hard dependency!');
        break;
    }

    $loadedNow = 0;
    for ($m = 0; $m < $numModulesToLoad; ++$m) {
        $moduleId = $modulesToLoad[$m];
        $module = $modules[$moduleId];

        $canLoad = true;
        $notFound = array();
        foreach ($module['dependencies'] as $dependency) {
            if ($dependency['mode'] == 'hard') {
                if (!array_key_exists($dependency['id'], $modules)) {
                    $notFound[] = $dependency['id'];
                    $canLoad = false;
                } else if (!array_key_exists($dependency['id'], $loadedModules)) {
                    $canLoad = false;
                    break;
                }
            }
        }

        if (count($notFound) > 0) {
            echo 'Missing hard dependencies ' . json_encode($notFound) . ' for ' . $moduleId;
            die();
        }

        if ($canLoad) {
            $loadedModules[$module['id']] = $module;
            array_splice($modulesToLoad, $m, 1);

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

if (count($notFound) > 0) {
    echo 'Missing soft dependencies: ' . json_encode($notFound);
} else
    print_r($loadedModules);
?>