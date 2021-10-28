<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Views\Views;
use GameCourse\Views\ViewHandler;
use GameCourse\Views\Expression\EvaluateVisitor;

$MODULE = 'views';


/*** --------------------------------------------- ***/
/*** ------------------ General ------------------ ***/
/*** --------------------------------------------- ***/

/**
 * Get pages and templates of course.
 *
 * @param int $courseId
 */
API::registerFunction($MODULE, 'listViews', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');

    $courseId = API::getValue('courseId');
    $course = Course::getCourse($courseId, false);

    if (!$course->exists())
        API::error('There is no course with id = ' . $courseId);

    $templates = Views::getTemplates($courseId, true);
    $response = [
        'pages' => $course->getPages(),
        'templates' => $templates[0], "globals" => $templates[1]
    ];
    $response['types'] = array(
        ['id' => "ROLE_SINGLE", 'name' => 'Role - Single'],
        ['id' => "ROLE_INTERACTION", 'name' => 'Role - Interaction']
    );
    API::response($response);
});



/*** ---------------------------------------------------- ***/
/*** ----------------------- Pages ---------------------- ***/
/*** ---------------------------------------------------- ***/

/**
 * Get a parsed and processed view to show on page.
 *
 * @param int $courseId
 * @param int $pageId
 * @param int $userId (optional)
 */
API::registerFunction($MODULE, 'renderPage', function () {
    API::requireCoursePermission();
    API::requireValues('courseId', 'pageId');

    $courseId = API::getValue('courseId');
    $pageId = API::getValue('pageId');
    $userId = API::getValue('userId');

    API::response(['view' => Views::renderPage($courseId, $pageId, $userId)]);
});

/**
 * Create new page in course.
 *
 * @param int $courseId
 * @param string $pageName
 * @param int $viewId
 * @param int $isEnabled
 */
API::registerFunction($MODULE, 'createPage', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'pageName', 'viewId', 'isEnabled');

    Views::createPage(API::getValue('courseId'), API::getValue('pageName'), API::getValue('viewId'), API::getValue('isEnabled'));
});

/**
 * Edit existing page of course.
 *
 * @param int $courseId
 * @param int $pageId
 */
API::registerFunction($MODULE, 'editPage', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'pageId', 'pageName', 'viewId', 'isEnabled');

    Views::editPage(API::getValue('courseId'), API::getValue('pageId'), API::getValue('pageName'), API::getValue('viewId'), API::getValue('isEnabled'));
});

/**
 * Delete existing page of course.
 *
 * @param int $courseId
 * @param int $pageId
 */
API::registerFunction($MODULE, 'deletePage', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'pageId');

    Views::deletePage(API::getValue('courseId'), API::getValue('pageId'));
});



/*** ---------------------------------------------------- ***/
/*** -------------------- Templates --------------------- ***/
/*** ---------------------------------------------------- ***/

/**
 * Get a template by id.
 *
 * @param int $courseId
 * @param int $templateId
 */
API::registerFunction($MODULE, 'getTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateId');

    $template = Views::getTemplate(API::getValue("courseId"), API::getValue("templateId"));
    API::response(array('template' => $template));
});

/**
 * Create new template in course.
 *
 * @param int $courseId
 * @param string $templateName
 * @param string $roleType
 */
API::registerFunction($MODULE, 'createTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateName', 'roleType');

    // Set default role
    $roleType = API::getValue('roleType');
    if ($roleType == "ROLE_INTERACTION") $defaultRole = "role.Default>role.Default";
    else $defaultRole = "role.Default";

    // Set default view (an empty block w/ default role)
    $view = [["type" => "block", "role" => $defaultRole]];

    // Set template
    Views::setTemplate($view, API::getValue('courseId'), API::getValue('templateName'), $roleType);
});

/**
 * Edit existing template basic info.
 *
 * @param int $courseId
 * @param int $templateId
 * @param string $templateName
 * @param string $roleType
 */
API::registerFunction($MODULE, 'editTemplateBasicInfo', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateId', 'templateName', 'roleType');

    $newView = ["name" => API::getValue('templateName'), "course" => API::getValue('courseId')];
    $newView["roleType"] = API::getValue('roleType');
    Core::$systemDB->update("template", $newView, ['id' => API::getValue('templateId')]);
});

/**
 * Delete existing template of course.
 *
 * @param int $courseId
 * @param int $templateId
 */
API::registerFunction($MODULE, 'deleteTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateId');

    Views::deleteTemplate(API::getValue('courseId'), API::getValue('templateId'));
});

/**
 * Set global state of template.
 *
 * @param int $courseId
 * @param int $templateId
 * @param bool $isGlobal
 */
API::registerFunction($MODULE, "setGlobalState", function () {
    API::requireCourseAdminPermission();
    API::requireValues('templateId', 'isGlobal');

    $isGlobal = API::getValue("isGlobal") ? 0 : 1;
    Core::$systemDB->update("template", ["isGlobal" => $isGlobal], ["id" => API::getValue("templateId")]);
});

/**
 * Import template from a .txt file.
 * It needs to be well formatted.
 *
 * @param int $courseId
 * @param $file
 */
API::registerFunction($MODULE, 'importTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'file');

    $courseId = API::getValue('courseId');
    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);

    Views::setTemplateFromFile('Imported Template', $fileContents, $courseId);
});

/**
 * Export template to a .txt file.
 * It needs to either be imported, or manually moved to a module folder and
 * call setTemplateFromFile() on init function of module.
 *
 * @param int $courseId
 * @param int $templateId
 */
API::registerFunction($MODULE, 'exportTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateId');

    $templateView = Views::exportTemplate(API::getValue('templateId'));
    API::response(array('template' => json_encode($templateView)));
});




// TODO: refactor

//creates a new aspect for the page/template, copies content of closest aspect
API::registerFunction($MODULE, 'createAspectView', function () {
    $data = $this->getViewSettings();
    API::requireValues('info', 'copyOrNew');
    $this->viewHandler->createAspect(
        $data["viewSettings"]["roleType"],
        $data["viewSettings"]["id"],
        $data["course"],
        API::getValue('info'),
        API::getValue('copyOrNew')
    );

    http_response_code(201);
    return;
});
//Delete an aspect view of a page or template
API::registerFunction($MODULE, 'deleteAspectView', function () {
    $data = $this->getViewSettings();
    $viewSettings = $data["viewSettings"];
    $type = $viewSettings['roleType'];
    API::requireValues('info');
    $info = API::getValue('info');

    if (!array_key_exists('roleOne', $info)) {
        API::error('Missing roleOne in info');
    }
    $aspects = $this->viewHandler->getAspects($viewSettings["viewId"]);

    $isTemplate = $data["pageOrTemp"] == "template";
    if ($type == "ROLE_INTERACTION" && !array_key_exists('roleTwo', $info)) {
        $role = ["role" => $info['roleOne'] . '>%'];
        $this->deleteTemplateRefs($isTemplate, $data["id"], $info['roleOne'] . '>%', false);
        //This is assuming that there is always an undeletable default aspect
        Core::$systemDB->delete("view", ["partType" => "block", "parent" => null], $role);
    } else {
        $aspectsByRole = array_combine(array_column($aspects, "role"), $aspects);
        if ($type == "ROLE_SINGLE") {
            $role = $info["roleOne"];
            $this->deleteTemplateRefs($isTemplate, $data["id"], "%>" . $role, false);
        } else if ($type == "ROLE_INTERACTION") {
            $role = $info['roleOne'] . '>' . $info['roleTwo'];
            $this->deleteTemplateRefs($isTemplate, $data["id"], $info['roleTwo'], true);
        }
        $aspect = $aspectsByRole[$role];
        $this->deleteTemplateRefs($isTemplate, $data["id"], $role, true);

        Core::$systemDB->delete("view", ["id" => $aspect["id"]]);
    }
    // if(sizeof($aspects)==2){//only 1 aspect after deletion -> aspectClass becomes null
    //     Core::$systemDB->delete("aspect_class",["aspectClass"=>$aspects[0]["aspectClass"]]);
    // }
    http_response_code(200);
    return;
});

//gets page/template info, show aspects (for the page/template settings page)
API::registerFunction($MODULE, 'getInfo', function () {
    $data = $this->getViewSettings();
    $viewSettings = $data["viewSettings"];
    $course = $data["course"];
    $response = ['viewSettings' => $viewSettings];
    $type = $viewSettings['roleType'];
    $aspects = $this->viewHandler->getAspects($viewSettings["viewId"]);
    $result = [];

    //function to get role details from the role in aspect
    $parseRoleName = function ($aspectRole) {
        $roleInfo = explode(".", $aspectRole); //e.g: role.Default
        $roleSpecification = $roleInfo[1];
        return ["id" => $aspectRole, "name" => $roleSpecification];
    };

    $doubleRoles = []; //for views w role interaction
    foreach ($aspects as $aspects) {
        $aspectRole = $aspects['role']; //string like 'role.Default'
        if ($type == "ROLE_INTERACTION") {
            $roleTwo = substr($aspectRole, strpos($aspectRole, '>') + 1, strlen($aspectRole));
            $roleOne = substr($aspectRole, 0, strpos($aspectRole, '>'));
            $doubleRoles[$roleOne][] = $roleTwo;
        } else {
            $result[] = $parseRoleName($aspectRole);
        }
    }

    if ($type == "ROLE_INTERACTION") {
        foreach ($doubleRoles as $roleOne => $rolesTwo) {
            $viewedBy = [];
            foreach ($rolesTwo as $roleTwo) {
                $viewedBy[] = $parseRoleName($roleTwo);
            }
            $result[] = array_merge($parseRoleName($roleOne), ['viewedBy' => $viewedBy]);
        }
    }

    $response['viewSpecializations'] = $result;
    $response['allIds'] = array();
    $roles = array_merge([["name" => 'Default', "id" => "Default"]], $course->getRolesData());
    $users = $course->getUsersNames();
    $response['allIds'][] = array('id' => 'special.Own', 'name' => 'Own (special)');
    foreach ($roles as $role) {
        $response['allIds'][] = array('id' => 'role.' . $role["name"], 'name' => $role["name"]);
    }
    foreach ($users as $user) {
        $response['allIds'][] = array('id' => 'user.' . $user, 'name' => $user);
    }
    $response["pageOrTemp"] = $data["pageOrTemp"];
    API::response($response);
});

//gets contents of template to put it in the view being edited
API::registerFunction($MODULE, 'getTemplateContent', function () {
    API::requireCourseAdminPermission();
    API::requireValues('id');
    $templateView = $this->getTemplateContents(API::getValue("id"));
    // if (sizeOf($templateView["children"]) > 1) {
    //     $block = $templateView;
    //     $block["partType"] = "block";
    //     unset($block["id"]);
    // } else {
    //     $block = $templateView["children"][0];
    // }
    API::response(array('template' => $templateView));
});
//gets
API::registerFunction($MODULE, 'getTemplateReference', function () {
    API::requireCourseAdminPermission(); //course/id/isglobal/name/role/roletype/viewid
    API::requireValues('viewId', 'id');

    $templateView = $this->getTemplateContents(API::getValue("viewId"));

    //$view = Core::$systemDB->select("view", ["id" => $referenceId], "parent,role");
    //$parentId = Core::$systemDB->select("view", ["viewId" => $view["parent"], "role" => $view["role"]], "id");
    //$templateView =  $this->getTemplateContents($parentId, true, API::getValue("role"));

    //$templateView = $this->getTemplateContents(API::getValue("role"), $templateId, API::getValue("course"), API::getValue("roleType"));
    $templateView["partType"] = "templateRef";
    $templateView["templateId"] = API::getValue("id");
    //is this needed??? aspectId
    //$templateView["aspectId"] = $templateView["id"];
    API::response(array('template' => $templateView));
});


//save a part of the view as a template or templateRef while editing the view
API::registerFunction($MODULE, 'saveTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course', 'name', 'parts', 'isRef');
    $templateName = API::getValue('name');
    $content = API::getValue('parts');
    $courseId = API::getValue("course");
    $isRef = API::getValue("isRef");

    $roleType = $this->viewHandler->getRoleType($content[0]["role"]);
    if ($roleType == "ROLE_INTERACTION") {
        $defaultRole = "role.Default>role.Default";
    } else {
        $defaultRole = "role.Default";
    }
    //$aspects = [];
    //$aspects[] = ["role" => "role.Default", "partType" => "block", "parent" => null];

    //these lines were moved to setTemplate
    // Core::$systemDB->insert("aspect_class");
    // $aspectClass = Core::$systemDB->getLastId();
    //'container' is always Default
    // Core::$systemDB->insert("view", ["aspectClass" => $aspectClass, "partType" => "block", "parent" => null, "role" => $defaultRole]);
    // $viewId = Core::$systemDB->getLastId();
    // Core::$systemDB->update("view", ["viewId" => $viewId], ['id' => $viewId]);
    if ($isRef) {
        $viewId = API::getValue('viewId');
        $role = API::getValue('role');
        Core::$systemDB->insert("template", ["course" => $courseId, "name" => $templateName, "roleType" => $roleType]);
        $templateId = Core::$systemDB->getLastId();


        $view = Core::$systemDB->select("view", ["viewId" => $viewId, "role" => $role]);
        $existsTemplateWithViewId = Core::$systemDB->select("view_template", ["viewId" => $viewId]) != null; //templateRef
        // print_r($view);
        // print_r($existsTemplateWithViewId);
        if ($view == null || $existsTemplateWithViewId) {
            // print_r("aqui");
            foreach ($content as $aspect) {
                // print_r($aspect);
                $aspect["parentId"] = null;
                if (!isset($aspect["isTemplateRef"])) // (not) used as ref
                    $aspect["viewId"] = null;
                ViewHandler::updateViewAndChildren($aspect, $courseId, false, false, $templateName);
            }
        } else {
            Core::$systemDB->insert("view_template", ["viewId" => $viewId, "templateId" => $templateId]);
        }

        $finalViewId = Core::$systemDB->select("view_template", ["templateId" => $templateId], "viewId");

        API::response(array('templateId' => $templateId, 'idView' => $finalViewId));
    } else {
        //FIXME: setTemplate changed
        [$templateId, $viewId] = $this->setTemplate($content, $defaultRole, $courseId, $templateName, $roleType);
        API::response(array('templateId' => $templateId, 'idView' => $viewId));
    }
});

//make copy of global template for the current course
API::registerFunction($MODULE, "copyGlobalTemplate", function () {
    API::requireCourseAdminPermission();
    API::requireValues('template', 'course', 'roles');
    $template = API::getValue("template");
    $roles = API::getValue("roles");
    $aspect = Core::$systemDB->select(
        "view_template vt join view on vt.viewId=id",
        ["templateId" => $template["id"]]
    );
    //$aspect["aspectClass"] = null;
    $views = $this->viewHandler->getViewWithParts($aspect["id"], $roles);

    if ($template["roleType"] == "ROLE_INTERACTION") {
        $defaultRole = "role.Default>role.Default";
    } else {
        $defaultRole = "role.Default";
    }

    //just coppying the default aspect because we don't know if the other course has the same roles
    //$aspectClass = null;
    //$views = [$views[0]];
    //FIXME: setTemplate changed
    $this->setTemplate($views, $defaultRole, API::getValue("course"), $template["name"], $template["roleType"]);
    http_response_code(201);
    return;
});

//get contents of a view with a specific aspect, for the edit page
API::registerFunction($MODULE, 'getEdit', function () {
    API::requireCourseAdminPermission();
    $data = $this->getViewSettings();
    $course = $data["course"];
    $courseId = $course->getId();
    $viewSettings = $data["viewSettings"];
    $viewType = $viewSettings['roleType'];
    API::requireValues('roles');
    $roles = API::getValue('roles');
    $rolesHierarchy = $course->getRolesHierarchy(); // more specific --> less specific
    // although Default is not the more specific, we need to include it, so it goes as the 'role 0' in the first position
    array_unshift($rolesHierarchy, ["name" => "Default", "id" => "0"]);
    if ($viewType == "ROLE_SINGLE") {
        //print_r($viewSettings);
        // if (!array_key_exists('role', $info)) {
        //     API::error('Missing role');
        // }
        //When entering the view editor, starts always with Default
        $view = $this->viewHandler->getViewWithParts($viewSettings["viewId"], $roles['viewerRole'], true);
    } else if ($viewType == "ROLE_INTERACTION") {
        // if (!array_key_exists('roleOne', $roles) || !array_key_exists('roleTwo', $roles)) {
        //     API::error('Missing roleOne and/or roleTwo in info');
        // }
        $view = $this->viewHandler->getViewWithParts($viewSettings["viewId"], $roles['userRole'] . '>' . $roles['viewerRole'], true);
    }

    $templates = $this->getTemplates();
    if ($viewType == 'ROLE_SINGLE') {
        $templates = array_filter($templates, function ($var, $key) use ($viewType) {
            // returns whether the input integer is even
            return $var["roleType"] == $viewType;
        }, ARRAY_FILTER_USE_BOTH);
    }
    $templates = array_filter($templates, function ($var, $key) use ($viewType) {
        // returns whether the input integer is even
        return $var["roleType"] == $viewType;
    }, ARRAY_FILTER_USE_BOTH);
    $templates = array_values($templates);
    //removes the template itself
    if (($key = array_search($viewSettings["viewId"], array_column($templates, 'viewId'))) !== FALSE) {
        unset($templates[$key]);
    }
    $courseRoles = $course->getRolesData("id,name");
    //include Default as a role
    array_unshift($courseRoles, ["id" => "0", "name" => "Default"]);
    $viewRoles = array_values($this->viewHandler->getViewRoles($viewSettings["viewId"], $courseRoles, $viewType));
    API::response(array('view' => $view, 'courseId' => $courseId, 'fields' => [], 'templates' => $templates, 'courseRoles' => $courseRoles, 'viewRoles' => $viewRoles, 'rolesHierarchy' => $rolesHierarchy));
});
//getDictionary
API::registerFunction($MODULE, 'getDictionary', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $courseId = API::getValue('course');
    //get course libraries
    $course = new Course($courseId);
    //API::response([$course->getEnabledLibraries(), $course->getEnabledVariables()]);
    API::response(array('libraries' => $course->getEnabledLibrariesInfo(), 'functions' => $course->getAllFunctionsForEditor(), 'variables' => $course->getEnabledVariables()));
});
//save the view being edited
API::registerFunction($MODULE, 'saveEdit', function () {
    $this->saveOrPreview(true);
});
//gets data to show preview of the view being edited
API::registerFunction($MODULE, 'previewEdit', function () {
    $this->saveOrPreview(false);
});

API::registerFunction($MODULE, 'testExpression', function () {
    API::requireCourseAdminPermission();
    API::requireValues('course');
    $course = Course::getCourse(API::getValue('course'));
    if ($course != null) {
        if (API::hasKey('expression')) {
            $expression = API::getValue('expression');
            $views = $course->getModule('views');
            $res = null;

            if ($views != null) {
                $viewHandler = $views->getViewHandler();

                $viewHandler->parseSelf($expression);
                $visitor = new EvaluateVisitor(['course' => (string)API::getValue('course')], $viewHandler);
                $expression = $expression->accept($visitor)->getValue();
                $objtype = getType($expression);

                if ($objtype == "bool") {
                    $res = $expression;
                } else if ($objtype == "string") {
                    $res = $expression;
                } else if ($objtype == "object") {
                    $res = $expression;
                } else if ($objtype == "integer") {
                    $res = $expression;
                } else if ($objtype == "array") {
                    if ($expression["type"] == "collection") {
                        $res = json_encode($expression["value"]);
                    }
                } else {
                    $res = get_object_vars($expression);
                }
                API::response($res);
            }
        }
    } else {
        API::error("There is no course with that id: " . API::getValue('course'));
    }
});
