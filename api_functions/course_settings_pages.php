<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;
use GameCourse\Settings;
use GameCourse\CronJob;


//get tabs for course settings
API::registerFunction('settings', 'courseTabs', function () {
    API::requireCourseAdminPermission();
    API::response(Settings::getTabs());
});


//change user roles or role hierarchy
API::registerFunction('settings', 'roles', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        if (API::hasKey('updateRoleHierarchy')) {

            API::requireValues('hierarchy');
            API::requireValues('roles');

            $hierarchy = API::getValue('hierarchy');
            $newRoles = API::getValue('roles');

            $course->setRoles($newRoles);
            $course->setRolesHierarchy($hierarchy);
            http_response_code(201);
        } else {
            $globalInfo = array(
                'pages' => $course->getAvailablePages(),
                'roles' => array_column($course->getRoles("name"), "name"),
                'roles_obj' => $course->getRoles('id, name, landingPage'), //
                'rolesHierarchy' => $course->getRolesHierarchy(),
            );
            API::response($globalInfo);
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

//course main setting page
API::registerFunction('settings', 'courseGlobal', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        $globalInfo = array(
            'name' => $course->getName(),
            'theme' => $GLOBALS['theme'],
            'activeUsers' => count($course->getUsers()),
            'awards' => $course->getNumAwards(),
            'participations' => $course->getNumParticipations()
        );
        API::response($globalInfo);
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});


//gets the data from a database table
API::registerFunction('settings', 'getTableData', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $tableName = API::getValue('table');

    if ($tableName != null) {
        $data = Core::$systemDB->selectMultiple("game_course_user g join " . $tableName . " t on g.id=t.user", ["course" => $courseId], "t.*, g.name, g.studentNumber");
        foreach ($data as &$d) {
            $exploded =  explode(' ', $d["name"]);
            $nickname = $exploded[0] . ' ' . end($exploded);
            $d["name"] = $nickname;
        }

        $orderedColumns = null;
        // get columns in order: id , name, studentNumber, (...)
        if($data){
            $columns = array_keys($data[0]);
            $lastHalf = array_slice($columns, 1, -2);
            $lastTwo = array_slice($columns, -2);
            $orderedColumns = array_merge(array_merge(["id"], $lastTwo), $lastHalf);
        }


        API::response(array("entries" => $data, "columns" => $orderedColumns));
    }
});

//deletes a row from a database table
API::registerFunction('settings', 'deleteTableEntry', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $tableName = API::getValue('table');

    if ($tableName != null) {
        $row = API::getValue('rowData');
        // only keep keys that are columns on the target table
        unset($row['name']);
        unset($row['studentNumber']);

        Core::$systemDB->delete($tableName, $row);
    }
});

//edits or creates a row in a database table
API::registerFunction('settings', 'submitTableEntry', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $tableName = API::getValue('table');

    if ($tableName != null) {
        $update = API::getValue('update');
        $newData = API::getValue('newData');
        $newData['course'] = $courseId;
        $newStudentNumber = $newData['studentNumber'];

        $newStudent = Core::$systemDB->select("course_user c join game_course_user g on c.id=g.id", ["course" => $courseId, "studentNumber" => $newStudentNumber], "g.id, name");
        if (!$newStudent) {
            API::error('There are no students in this course with student number ' . $newStudentNumber, 400);
        }
        // only keep keys that are columns on the target table
        unset($newData['name']);
        unset($newData['studentNumber']);

        $exploded =  explode(' ', $newStudent["name"]);
        $nickname = $exploded[0] . ' ' . end($exploded);

        $newData['user'] = $newStudent['id'];

        if ($update) {
            $where = API::getValue('rowData');
            if ($newData != null and $where != null) {
                // only keep keys that are columns on the target table
                unset($where['name']);
                unset($where['studentNumber']);

                Core::$systemDB->update($tableName, $newData, $where);
                $newData['name'] = $nickname;
                $newData['studentNumber'] = $newStudentNumber;

                API::response(array("newRecord" => $newData));
            }
        } else {
            $id = Core::$systemDB->insert($tableName, $newData);
            $newRecord = Core::$systemDB->select($tableName, ["id" => $id]);
            $newRecord['name'] = $nickname;
            $newRecord['studentNumber'] = $newStudentNumber;

            API::response(array("newRecord" => $newRecord));
        }
    } else {
        API::error('Table name missing!', 400);
    }
});

//gets course module's information
API::registerFunction('settings', 'courseModules', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        if (API::hasKey('module') && API::hasKey('enabled')) {
            $moduleId = API::getValue('module');
            $modules = ModuleLoader::getModules();
            $module = ModuleLoader::getModule($moduleId);
            if ($module == null) {
                API::error('Unknown module!', 400);
                http_response_code(400);
            } else {
                $moduleObject = $module['factory']();
                $moduleEnabled = (in_array($module["id"], $course->getEnabledModules()));
                if ($moduleEnabled && !API::getValue('enabled')) { //disabling module
                    $modules = $course->getModules();
                    foreach ($modules as $mod) {
                        $dependencies = $mod->getDependencies();
                        foreach ($dependencies as $dependency) {
                            if ($dependency['id'] == $moduleId && $dependency['mode'] != 'optional')
                                API::error('Must disable all modules that depend on this one first.');
                        }
                    }

                    if (Core::$systemDB->select("course_module", ["moduleId" => $moduleId, "isEnabled" => 1], "count(*)") == 1) {
                        //only drop the tables of the module data if this is the last course where it is enabled
                        $moduleObject->dropTables($moduleId); //deletes tables associated with the module
                    } else {
                        $moduleObject->deleteDataRows(API::getValue('course'));
                    }
                } else if (!$moduleEnabled && API::getValue('enabled')) { //enabling module
                    foreach ($module['dependencies'] as $dependency) {
                        if ($dependency['mode'] != 'optional' && ModuleLoader::getModules($dependency['id']) == null)
                            API::error('Must enable all dependencies first.');
                    }
                }
                if ($moduleEnabled != API::getValue('enabled')) {
                    $course->setModuleEnabled($moduleId, !$moduleEnabled);
                }
                http_response_code(201);
            }
        } else {
            $allModules = ModuleLoader::getModules();
            $enabledModules = $course->getEnabledModules();

            $modulesArr = [];
            foreach ($allModules as $module) {

                if (in_array($module['id'], $enabledModules)) {
                    $moduleInfo = ModuleLoader::getModule($module['id']);
                    $moduleObj = $moduleInfo['factory']();
                    $module['hasConfiguration'] = $moduleObj->is_configurable();
                    $module['enabled'] = true;
                } else {
                    $module['hasConfiguration'] = false;
                    $module['enabled'] = false;
                }

                $dependencies = [];
                $canBeEnabled = true;
                foreach ($module['dependencies'] as $dependency) {
                    if ($dependency['mode'] != 'optional') {
                        if (in_array($dependency['id'], $enabledModules)) {
                            $dependencies[] = array('id' => $dependency['id'], 'enabled' => true);
                        } else {
                            $dependencies[] = array('id' => $dependency['id'], 'enabled' => false);
                            $canBeEnabled = false;
                        }
                    }
                }

                $mod = array(
                    'id' => $module['id'],
                    'name' => $module['name'],
                    'dir' => $module['dir'],
                    'version' => $module['version'],
                    'enabled' => $module['enabled'],
                    'canBeEnabled' => $canBeEnabled,
                    'dependencies' => $dependencies,
                    'description' => $module['description'],
                    'hasConfiguration' => $module['hasConfiguration']
                );
                $modulesArr[] = $mod;
            }
            API::response($modulesArr);
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

//gets module information for the configuration page
API::registerFunction('settings', 'getModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false);
    if($course != null){
        $module = $course->getModule(API::getValue('module'));
        $folder = Course::getCourseDataFolder($courseId);

        if ($module != null) {
            $moduleInfo = array(
                'id' => $module->getId(),
                'name' => $module->getName(),
                'description' => $module->getDescription()
            );

            $generalInputs = [];
            if ($module->has_general_inputs()) {
                $generalInputs = $module->get_general_inputs($course->getId());
            }

            $personalizedConfig = [];
            if ($module->has_personalized_config()) {
                $personalizedConfig = $module->get_personalized_function();
            }

            $listingItems = [];
            if ($module->has_listing_items()) {
                $listingItems = $module->get_listing_items($course->getId());
            }

            $tiers = [];
            if ($moduleInfo["name"] == "Skills") {
                $tiers = $module->get_tiers_items($course->getId());
            }

            $info = array(
                'generalInputs' => $generalInputs,
                'listingItems' => $listingItems,
                'personalizedConfig' => $personalizedConfig,
                'tiers' => $tiers,
                'module' => $moduleInfo,
                'courseFolder' => $folder,
            );
            API::response($info);
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

//request to change the item's active status
API::registerFunction('settings', 'activeItem', function() {
    API::requireCourseAdminPermission();
    $courseId = API::getValue('course');
    $course = Course::getCourse($courseId, false);
    if($course != null){
        $module = $course->getModule(API::getValue('module'));
        if($module != null){
            $itemId = API::getValue('itemId');
            $module->activeItem($itemId);
        }
    }
});

//request to save user input on the module configuration page
API::registerFunction('settings', 'saveModuleConfigInfo', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        $module = $course->getModule(API::getValue('module'));

        if ($module != null) {
            if (API::hasKey('generalInputs')) {
                $generalInputs = API::getValue('generalInputs');
                $module->save_general_inputs($generalInputs, $course->getId());
            }

            //personalized configuration should create its own API request
            //inside the currespondent module 

            if (API::hasKey('listingItems')) {
                $listingItems = API::getValue('listingItems');
                $action_type = API::getValue('action_type'); //new, edit, delete
                if ($module->getName() != "Skills")
                    $module->save_listing_item($action_type, $listingItems, $course->getId());
                else {
                    if (array_key_exists("reward", $listingItems))
                        $module->save_tiers($action_type, $listingItems, $course->getId());
                    else
                        $module->save_listing_item($action_type, $listingItems, $course->getId());
                }
            }
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

API::registerFunction('settings', 'importItem', function () {
    API::requireAdminPermission();
    API::requireValues('file');

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);
    $replace = API::getValue('replace');
    $module = API::getValue('module');
    $course = API::getValue('course');

    $courseObject = Course::getCourse($course, false);
    $moduleObject = $courseObject->getModule($module);
    $nItems = $moduleObject->importItems($course, $fileContents, $replace);
    API::response(array('nItems' => $nItems));
});

API::registerFunction('settings', 'exportItem', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = API::getValue('course');
    $module = API::getValue('module');

    $courseObject = Course::getCourse($course, false);
    $moduleObject = $courseObject->getModule($module);
    [$fileName, $courseItems] = $moduleObject->exportItems($course);
    API::response(array('courseItems' => $courseItems, 'fileName' => $fileName));
});

API::registerFunction('settings', 'saveNewSequence', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'), false);
    if($course != null){
        $module = $course->getModule(API::getValue('module'));

        if ($module != null) {
            if (API::hasKey('oldSeq') && API::hasKey('nextSeq') && API::hasKey('itemId') && API::hasKey('table')) {
                $oldSeq = API::getValue('oldSeq');
                $nextSeq = API::getValue('nextSeq');
                $itemId = API::getValue('itemId');
                $table =  API::getValue('table');
                $courseId = API::getValue('course');
                $module->changeSeqId($courseId, $itemId, $oldSeq, $nextSeq, $table);
            }
        } else {
            API::error("There is no module with that id: " . API::getValue('module'));
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

API::registerFunction('settings', 'saveNewNavigationOrder', function () {
    API::requireCourseAdminPermission();
    $course = Course::getCourse(API::getValue('course'));
    if ($course != null) {
        Core::setNavigation(API::getValue('nav'));
        //TODO 
        //call function to save in the DB
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});

API::registerFunction('settings', 'upload', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = API::getValue('course');
    $module = API::getValue('module');
    $file = API::getValue('newFile');
    $fileName = API::getValue('fileName');
    $subfolder = API::getValue('subfolder');

    $courseObject = Course::getCourse($course, false);
    $result = $courseObject->upload($file, $fileName, $module, $subfolder);
    API::response(array('url' => $result));
});

API::registerFunction('settings', 'deleteFile', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course', 'path');
    $course = API::getValue('course');
    $path =  API::getValue('path');
    $courseObject = Course::getCourse($course, false);
    $courseObject->deleteFile($path);
    http_response_code(201);
    return;
});
