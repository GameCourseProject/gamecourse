<?php

namespace GameCourse\Views;

use GameCourse\API;
use GameCourse\Core;
use GameCourse\Course;

/**
 * This class has functions that manage pages and templates.
 * It also has some utility functions about views and roles.
 */
class Views
{

    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Pages ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets page by ID
     *
     * @param int $courseId
     * @param int $pageId
     * @return mixed
     */
    public static function getPage(int $courseId, int $pageId)
    {
        return Core::$systemDB->select("page", ["id" => $pageId, "course" => $courseId]);
    }

    /**
     * Creates a new page on course.
     *
     * @param int $courseId
     * @param string $name
     * @param int $viewId
     * @param int $isEnabled
     */
    public static function createPage(int $courseId, string $name, int $viewId, int $isEnabled)
    {
        $newView = ["name" => $name, "course" => $courseId];
        $numberOfPages = count(Core::$systemDB->selectMultiple("page", ["course" => $courseId]));

        $newView["viewId"] = $viewId;
        $newView["isEnabled"] = $isEnabled;
        $newView["seqId"] = $numberOfPages + 1;
        Core::$systemDB->insert("page", $newView);
    }

    /**
     * Edits an existing page on course.
     *
     * @param int $courseId
     * @param int $pageId
     * @param string $name
     * @param int $viewId
     * @param int $isEnabled
     */
    public static function editPage(int $courseId, int $pageId, string $name, int $viewId, int $isEnabled)
    {
        $newView = ["name" => $name, "course" => $courseId];
        $newView["viewId"] = $viewId;
        $newView["isEnabled"] = $isEnabled;
        Core::$systemDB->update("page", $newView, ['id' => $pageId]);
    }

    /**
     * Deletes an existing page on course.
     *
     * @param int $courseId
     * @param int $pageId
     */
    public static function deletePage(int $courseId, int $pageId)
    {
        Core::$systemDB->delete("page", ["course" => $courseId, "id" => $pageId]);
    }

    /**
     * Renders a page.
     *
     * @param int $courseId
     * @param int $pageId
     * @param int|null $userId (optional)
     * @return mixed
     */
    public static function renderPage(int $courseId, int $pageId, int $userId = null)
    {
        $viewId = Core::$systemDB->select("page", ["course" => $courseId, "id" => $pageId], 'viewId');
        $view = self::getViewByViewId($viewId);

        $course = Course::getCourse($courseId, false);
        $viewer = $course->getLoggedUser();
        $viewer->refreshActivity();

        $user = null;
        if ($userId) $user = $course->getUser($userId);

        // Get viewer roles hierarchy
        $viewerRolesHierarchy = $viewer->getUserRolesByHierarchy(); // [0]=>role more specific, [1]=>role less specific...
        array_push($viewerRolesHierarchy, "Default"); // add Default as the last choice

        $roleType = self::getRoleType($view[0]["role"]);
        $rolesHierarchy = [];

        $viewParams = null;
        if ($roleType == 'ROLE_SINGLE') {
            $rolesHierarchy = $viewerRolesHierarchy;
            $viewParams = ["course" => $courseId, "viewer" => $viewer->getId()];

        } else if ($roleType == 'ROLE_INTERACTION') {
            if (!$user) API::error('Missing user to render view with role type = \'ROLE_INTERACTION\'');
            $viewParams = ["course" => $courseId, "viewer" => $viewer->getId(), "user" => $userId];

            // Get user roles hierarchy
            $userRolesHierarchy = $user->getUserRolesByHierarchy();   // [0]=>role more specific, [1]=>role less specific...
            array_push($userRolesHierarchy, "Default"); // add Default as the last choice

            foreach ($viewerRolesHierarchy as $viewerRole) {
                foreach ($userRolesHierarchy as $userRole) {
                    $rolesHierarchy[] = $userRole . '>' . $viewerRole;
                }
            }
        }

        ViewHandler::renderView($view, $rolesHierarchy, $viewParams);
        return $view;
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Templates -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets template by ID
     *
     * @param int|null $templateId
     * @param string|null $name
     * @return mixed
     */
    public static function getTemplate(int $templateId = null, string $name = null)
    {
        $where = [];
        if ($templateId) $where["t.id"] = $templateId;
        else $where["name"] = $name;
        return Core::$systemDB->select(
            "view_template vt join template t on templateId=id",
            $where,
            "t.id, name, roleType, course, isGlobal, vt.viewId"
        );
    }

    /**
     * Gets templates of course.
     *s
     * @param int $courseId
     * @param bool $includeGlobals
     * @return array
     */
    public static function getTemplates(int $courseId, bool $includeGlobals = false): array
    {
        $temps = Core::$systemDB->selectMultiple(
            'template t join view_template vt on templateId=id',
            ['course' => $courseId],
            "t.id,name,course,isGlobal,roleType,vt.viewId",
            null,
            [],
            [],
            "t.id"
        );

        // Get template roles
        foreach ($temps as &$template) {
            $template["roles"] = self::getTemplateRoles($template["id"]);
        }

        if ($includeGlobals) {
            $globalTemp = Core::$systemDB->selectMultiple("template", ["isGlobal" => true]);
            return [$temps, $globalTemp];
        }
        return $temps;
    }

    /**
     * Gets template roles.
     * Option to get roles parsed (e.g. 'Default' or 'Default>Default'),
     * or unparsed (e.g. 'role.Default' or 'role.Default>role.Default')
     *
     * @param int $templateId
     * @param bool $parse
     * @return array
     */
    public static function getTemplateRoles(int $templateId, bool $parse = true): array
    {
        $roleType = Core::$systemDB->select("template", ["id" => $templateId], "roleType");
        return array_map(function ($item) use ($parse, $roleType) {
            if ($parse) {
                $viewerRole = self::splitRole($item["role"])["viewerRole"];
                $role = $viewerRole;
                if ($roleType == "ROLE_INTERACTION") {
                    $userRole = self::splitRole($item["role"])["userRole"];
                    $role = $userRole . '>' . $viewerRole;
                }
                return $role;

            } else return $item["role"];
        }, Core::$systemDB->selectMultiple("template_role", ["templateId" => $templateId], "role"));
    }

    /**
     * Sets a new template from a .txt file.
     * The contents of the file need to be in a correct format (see docs).
     * Option for when template comes from a module specification.
     *
     * @param string $name
     * @param string $contents
     * @param int $courseId
     * @param string|null $moduleId (optional)
     */
    public static function createTemplateFromFile(string $name, string $contents, int $courseId, string $moduleId = null): void
    {
        $view = json_decode($contents, true); // format: [aspect1, aspect2, ...], where aspect = view object
        $roleType = self::getRoleType($view[0]["role"]); // role type must be the same for all aspects
        $templateId = Views::createTemplate($view, $courseId, $name, $roleType)[0];

        // If template comes from a module, insert in 'template_module' table
        if ($moduleId)
            Core::$systemDB->insert("template_module", ["templateId" => $templateId, "moduleId" => $moduleId]);
    }

    /**
     * Sets a new template and all its views.
     *
     * @param $view
     * @param int $courseId
     * @param string $name
     * @param string $roleType
     * @return array
     */
    public static function createTemplate($view, int $courseId, string $name, string $roleType): array
    {
        // Add entry on 'template' table
        Core::$systemDB->insert("template", ["name" => $name, "roleType" => $roleType, "course" => $courseId]);
        $templateId = Core::$systemDB->getLastId();

        // Add view to database (including all aspects and children)
        $templateRoles = ViewHandler::updateView($view);

        // Add template roles to 'template_role' table
        foreach ($templateRoles as $role) {
            Core::$systemDB->insert("template_role", ["templateId" => $templateId, "role" => $role]);
        }

        // Add entry on 'view_template'
        $viewId = $view[0]["viewId"]; // viewId is the same for all aspects
        Core::$systemDB->insert("view_template", ["viewId" => $viewId, "templateId" => $templateId]);

        return array($templateId, $viewId);
    }

    /**
     * Edits a template and all its views.
     *
     * @param $view
     * @param int $templateId
     */
    public static function editTemplate($view, int $templateId)
    {
        // Update view in database (including all aspects and children)
        $templateRoles = ViewHandler::updateView($view);

        // Update template roles in 'template_role' table
        // Clean and insert again
        Core::$systemDB->delete("template_role", ["templateId" => $templateId]);
        foreach ($templateRoles as $role) {
            Core::$systemDB->insert("template_role", ["templateId" => $templateId, "role" => $role]);
        }

        // Update entry on 'view_template'
        $viewId = $view[0]["viewId"]; // viewId is the same for all aspects
        Core::$systemDB->update("view_template", ["viewId" => $viewId], ["templateId" => $templateId]);
    }

    /**
     * Deletes a template and all its views (only if they are not referenced
     * in other views).
     *
     * @param int $courseId
     * @param int $templateId
     */
    public static function deleteTemplate(int $courseId, int $templateId): void
    {
        Core::$systemDB->setForeignKeyChecks(false);

        $viewId = Core::$systemDB->select("view_template", ["templateId" => $templateId], 'viewId');
        $view = self::getViewByViewId($viewId);

        // Delete entry on 'view_template'
        Core::$systemDB->delete('view_template', ["viewId" => $viewId, "templateId" => $templateId]);

        // Delete view from database (including all aspects and children), if only usage
        self::deleteViewIfNotUsed($view);

        // Delete entry on 'template' table
        Core::$systemDB->delete('template', ["course" => $courseId, "id" => $templateId]);

        // Delete entries on 'template_role' table
        Core::$systemDB->delete('template_role', ["templateId" => $templateId]);

        // Delete from 'template_module' if module template
        Core::$systemDB->delete('template_module', ["templateId" => $templateId]);

        Core::$systemDB->setForeignKeyChecks(true);
    }

    /**
     * Builds a complete template view with all aspects.
     *
     * @param int $templateId
     * @return mixed
     */
    public static function buildTemplate(int $templateId)
    {
        $template = self::getTemplate($templateId);
        $view = self::getViewByViewId($template["viewId"]);

        ViewHandler::buildView($view, false, null, true);
        return $view;
    }

    /**
     * Renders a template based on viewer and user roles.
     *
     * @param int $courseId
     * @param int $templateId
     * @param string $viewerRole
     * @param string|null $userRole
     * @param bool $edit
     * @return mixed
     */
    public static function renderTemplateByAspect(int $courseId, int $templateId, string $viewerRole, string $userRole = null, bool $edit = true)
    {
        $course = Course::getCourse($courseId, false);
        $template = self::getTemplate($templateId);
        $view = self::getViewByViewId($template["viewId"]);

        $roleType = self::getRoleType($view[0]["role"]);
        $rolesHierarchy = [];

        // Get viewer rolesHierarchy
        $viewerRolesHierarchy = array_merge([$viewerRole], self::getRoleParents($course, $viewerRole));
        if (!in_array("Default", $viewerRolesHierarchy)) $viewerRolesHierarchy[] = "Default";

        if ($roleType == 'ROLE_SINGLE') {
            $rolesHierarchy = $viewerRolesHierarchy;

        } else if ($roleType == 'ROLE_INTERACTION') {
            if (!$userRole) API::error('Missing user role to render view with role type = \'ROLE_INTERACTION\'');

            // Get user rolesHierarchy
            $userRolesHierarchy = array_merge([$userRole], self::getRoleParents($course, $userRole));
            if (!in_array("Default", $userRolesHierarchy)) $userRolesHierarchy[] = "Default";

            foreach ($viewerRolesHierarchy as $viewerRole) {
                foreach ($userRolesHierarchy as $userRole) {
                    $rolesHierarchy[] = $userRole . '>' . $viewerRole;
                }
            }
        }

        ViewHandler::renderView($view, $rolesHierarchy, array('course' => $courseId), $edit);
        return $view;
    }

    /**
     * Checks if a template with a given id or name exists in the database.
     *
     * @param string|null $name
     * @param int|null $id
     * @param int $courseId
     * @return bool
     */
    public static function templateExists(int $courseId, string $name = null, int $id = null): bool
    {
        if ($name)
            return !empty(Core::$systemDB->select('template', ['name' => $name, 'course' => $courseId]));
        else if ($id)
            return !empty(Core::$systemDB->select('template', ['id' => $id, 'course' => $courseId]));
        return false;
    }

    /**
     * Exports a template from the system.
     *
     * @param int $templateId
     * @return mixed
     */
    public static function exportTemplate(int $templateId)
    {
        $view = Core::$systemDB->selectMultiple(
            "view_template vt join view v on vt.viewId=v.viewId",
            ["templateId" => $templateId],
            "v.*"
        );

        ViewHandler::buildView($view, true);
        return $view;
    }



    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Roles ---------------------- ***/
    /*** ---------------------------------------------------- ***/
    // FIXME: should be in its own class

    /**
     * Receives a role string and returns the role type.
     * Input format: 'role.Default', 'Default' or 'role.Default>role.Default', 'Default>Default'
     * Output options: ROLE_SINGLE & ROLE_INTERACTION
     *
     * @param string $role
     * @return string
     */
    public static function getRoleType(string $role): string
    {
        if (strpos($role, '>') !== false) return "ROLE_INTERACTION";
        else return "ROLE_SINGLE";
    }

    /**
     * Receives a role string and returns the viewer and user roles.
     * Input format: 'role.Default', 'Default' or 'role.Default>role.Default', 'Default>Default'
     * Output: ["viewerRole" => ____, "userRole" => ____]
     *
     * @param string $role
     * @return array
     */
    public static function splitRole(string $role): ?array
    {
        $roleType = self::getRoleType($role);
        if ($roleType == "ROLE_SINGLE") {
            if (strpos($role, 'role.') !== false) return ["viewerRole" => explode('.', $role)[1]];
            return ["viewerRole" => $role];

        } else if ($roleType == "ROLE_INTERACTION") {
            if (strpos($role, 'role.') !== false) return [
                "viewerRole" => explode('.', explode('>', $role)[1])[1],
                "userRole" => explode('.', explode('>', $role)[0])[1]
            ];
            return [
                "viewerRole" => explode('>', $role)[1],
                "userRole" => explode('>', $role)[0]
            ];
        }
        return null;
    }

    private static function getRoleParents(Course $course, string $role): array
    {
        $parents = [];
        self::traverseRoles($course->getRolesHierarchy(), $role, $parents);
        return $parents;
    }

    private static function traverseRoles(array $roles, string $roleName, &$parents): bool
    {
        foreach ($roles as $role) {
            if ($role["name"] == $roleName) return true;

            if (isset($role["children"])) {
                $foundRole = self::traverseRoles($role["children"], $roleName, $parents);
                if ($foundRole) {
                    $parents[] = $role["name"];
                    return true;
                }
            }
        }
        return false;
    }



    /*** ---------------------------------------------------- ***/
    /*** --------------------- Utilities -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets view by its unique database id.
     * Doesn't build the view, just returns entry from 'view' table.
     *
     * @param int $id
     * @return mixed
     */
    public static function getViewById(int $id)
    {
        return Core::$systemDB->select("view", ["id" => $id]);
    }

    /**
     * Gets view by viewId.
     * Doesn't build the view, just returns entries from 'view' table.
     *
     * @param int $viewId
     * @return mixed
     */
    public static function getViewByViewId(int $viewId)
    {
        return Core::$systemDB->selectMultiple("view", ["viewId" => $viewId]);
    }

    /**
     * Delete view from database (including all aspects and children),
     * if it is not being used anywhere else.
     *
     * @param $view
     * @return void
     */
    public static function deleteViewIfNotUsed($view)
    {
        $viewId = $view[0]["viewId"];
        $onlyUsage = empty(Core::$systemDB->select("view_template", ["viewId" => $viewId])) &&
            empty(Core::$systemDB->select("view_parent", ["childId" => $viewId]));
        if ($onlyUsage) ViewHandler::deleteView($view);
    }



    // TODO: refactor to new structure (everything underneath)

    //tests view parsing and processing
    function testView($course, $courseId, &$testDone, &$view, $viewerRole, $userRole = null)
    {
        try { //ToDo: for preview viewer should be the current user if they have the role
            $viewerId = $this->getUserIdWithRole($course, $viewerRole);
            $params = ['course' => (string)$courseId];
            //print_r("test");

            if ($userRole !== null) { //if view has role interaction
                $userId = $this->getUserIdWithRole($course, $userRole);
                if ($userId == -1) {
                    return;
                }
                $params["user"] = (string)$userId;
            }
            if ($viewerId != -1) {
                $params['viewer'] = $viewerId;
                ViewHandler::processView($view, $params);
                $testDone = true;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    //receives roles like 'role.Default','role.1',etc and get a user of that role
    function getUserIdWithRole($course, $role)
    {
        $uid = -1;
        if (strpos($role, 'role.') === 0) {
            $role = substr($role, 5);
            if ($role == 'Default')
                return $course->getUsersIds()[0];
            $loggedUserId = Core::getLoggedUser()->getId();
            $loggedUser = new \GameCourse\CourseUser($loggedUserId, $course);
            if (in_array($role, $loggedUser->getRolesNames()))
                return $loggedUserId;
            $users = $course->getUsersWithRole($role, false);

            if (count($users) != 0)
                $uid = $users[0]['id'];
        } else if (strpos($role, 'user.') === 0) {
            $uid = substr($role, 5);
        }
        return $uid;
    }

}
