<?php

namespace APIFunctions;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Views\Views;
use GameCourse\Views\Expression\EvaluateVisitor;
use Modules\Profiling\Profiling;

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
    $course = API::verifyCourseExists($courseId);

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
 * @param int $viewerId
 * @param int $userId (optional)
 */
API::registerFunction($MODULE, 'renderPage', function () {
    API::requireCoursePermission();
    API::requireValues('courseId', 'pageId', 'viewerId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $pageId = API::getValue('pageId');
    $page = API::verifyPageExists($courseId, $pageId);
    if (!$page["isEnabled"])
        API::error('Page \'' . $page["name"] . '\' (id = ' . $pageId . ') is not enabled.');

    if ($page["roleType"] === "ROLE_INTERACTION") {
        $userId = API::getValue('userId');
        if (!is_null($userId)) API::verifyCourseUserExists($courseId, $userId);
    }

    // If module profiling is enabled, save user page history
    if (Core::$systemDB->select("course_module", ["course" => $courseId, "moduleId" => Profiling::ID], "isEnabled")) {
        $viewer = API::getValue('viewerId');
        Core::$systemDB->insert("user_page_history", ["user" => $viewer, "page" => $pageId]);
    }

    API::response(['view' => Views::renderPage($courseId, $pageId, $userId ?? null)]);
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    Views::createPage($courseId, API::getValue('pageName'), API::getValue('viewId'), API::getValue('isEnabled'));
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $pageId = API::getValue('pageId');
    $page = API::verifyPageExists($courseId, $pageId);

    Views::editPage($courseId, $pageId, API::getValue('pageName'), API::getValue('viewId'), API::getValue('isEnabled'));
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $pageId = API::getValue('pageId');
    $page = API::verifyPageExists($courseId, $pageId);

    Views::deletePage((int)API::getValue('courseId'), (int)API::getValue('pageId'));
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    // Set default role
    $roleType = API::getValue('roleType');
    if ($roleType == "ROLE_INTERACTION") $defaultRole = "role.Default>role.Default";
    else $defaultRole = "role.Default";

    // Set default view (an empty block w/ default role)
    $view = [["type" => "block", "role" => $defaultRole]];

    // Set template
    Views::createTemplate($view, $courseId, API::getValue('templateName'), $roleType);
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

    $newTemplate = ["name" => API::getValue('templateName'), "course" => $courseId, "roleType" => API::getValue('roleType')];
    Core::$systemDB->update("template", $newTemplate, ['id' => $templateId]);
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

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

    Views::deleteTemplate($courseId, $templateId);
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
    API::requireValues('courseId', 'templateId', 'isGlobal');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

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
    $course = API::verifyCourseExists($courseId);

    $file = explode(",", API::getValue('file'));
    $fileContents = base64_decode($file[1]);

    Views::createTemplateFromFile('Imported Template', $fileContents, $courseId);
});

/**
 * Export template to a .txt file.
 * It needs to either be imported, or manually moved to a module folder and
 * call createTemplateFromFile() on init function of module.
 *
 * @param int $courseId
 * @param int $templateId
 */
API::registerFunction($MODULE, 'exportTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateId');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

    $templateView = Views::exportTemplate($templateId);
    API::response(array('template' => json_encode($templateView)));
});



/*** ---------------------------------------------------- ***/
/*** ---------------------- Editor ---------------------- ***/
/*** ---------------------------------------------------- ***/

/**
 * Get essential info for editor like course roles, roles hierarchy,
 * template roles, template views by aspect, template view tree and
 * enabled modules in course.
 *
 * @param int $courseId
 * @param int $templateId
 */
API::registerFunction($MODULE, 'getTemplateEditInfo', function () {
    API::requireCourseAdminPermission();
    API::requireValues("courseId", "templateId");

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

    $templateRoles = Views::getTemplateRoles($templateId);
    $roleType = Views::getRoleType($templateRoles[0]);
    $templateViewsByAspect = array();

    // Get template views by aspect
    foreach ($templateRoles as $role) {
        $viewerRole = Views::splitRole($role)["viewerRole"];
        $userRole = null;
        if ($roleType == "ROLE_INTERACTION") $userRole = Views::splitRole($role)["userRole"];
        $templateViewsByAspect[$role] = Views::renderTemplateByAspect($courseId, $templateId, $viewerRole, $userRole);
    }

    API::response(array(
        'courseRoles' => $course->getRoles('id, name, landingPage'),
        'rolesHierarchy' => $course->getRolesHierarchy(),
        'templateRoles' => $templateRoles,
        'templateViewsByAspect' => $templateViewsByAspect,
        'enabledModules' => $course->getEnabledModules()
    ));
});

/**
 * Get a parsed and processed template to show on editor preview.
 *
 * @param int $courseId
 * @param int $templateId
 * @param string $viewerRole
 * @param string $userRole (optional)
 */
API::registerFunction($MODULE, 'previewTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues("courseId", "templateId", "viewerRole");

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    $template = API::verifyTemplateExists($courseId, $templateId);

    $viewerRole = API::getValue("viewerRole");
    $userRole = API::getValue("userRole");

    API::response(array('view' => Views::renderTemplateByAspect($courseId, $templateId, $viewerRole, $userRole, false)));
});

/**
 * Save template to database.
 *
 * @param int $courseId
 * @param int $templateId
 * @param $template
 * @param array $viewsDeleted (optional)
 */
API::registerFunction($MODULE, 'saveTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues("courseId", "templateId", "template");

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    $templateId = API::getValue("templateId");
    API::verifyTemplateExists($courseId, $templateId);
    $template = API::getValue("template");

    // Save template
    Views::editTemplate($template, $templateId);

    // Delete views not being used from database
    if (API::hasKey("viewsDeleted")) {
        $viewIdsDeleted = API::getValue("viewsDeleted");
        foreach ($viewIdsDeleted as $viewId) {
            $view = Views::getViewByViewId($viewId);
            Views::deleteViewIfNotUsed($view);
        }
    }
});

/**
 * Save a specific view as a new template.
 *
 * @param int $courseId
 * @param string $templateName
 * @param $view
 * @param string $roleType
 * @param bool isRef
 */
API::registerFunction($MODULE, 'saveViewAsTemplate', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId', 'templateName', 'view', 'roleType', 'isRef');

    $courseId = API::getValue('courseId');
    $course = API::verifyCourseExists($courseId);

    // Set template
    Views::createTemplate(API::getValue("view"), $courseId, API::getValue('templateName'), API::getValue('roleType'));
});





// TODO: refactor to new structure (everything underneath)

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

//getDictionary
API::registerFunction($MODULE, 'getDictionary', function () {
    API::requireCourseAdminPermission();
    API::requireValues('courseId');
    $courseId = API::getValue('courseId');
    //get course libraries
    $course = new Course($courseId);
    //API::response([$course->getEnabledLibraries(), $course->getEnabledVariables()]);
    API::response(array('libraries' => $course->getEnabledLibrariesInfo(), 'functions' => $course->getAllFunctionsForEditor(), 'variables' => $course->getEnabledVariables()));
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
