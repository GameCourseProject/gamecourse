<?php
namespace API;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;
use GameCourse\Views\CreationMode;
use GameCourse\Views\Component\CustomComponent;
use GameCourse\Views\Component\CoreComponent;
use GameCourse\Views\Category\Category;
use GameCourse\Views\Template\CoreTemplate;
use GameCourse\Views\Template\CustomTemplate;

/**
 * This is the Page controller, which holds API endpoints for
 * page related actions.
 *
 * NOTE: use annotations to automatically generate OpenAPI
 *      documentation for GameCourse's RESTful API
 */
class PageController
{
    /*** --------------------------------------------- ***/
    /*** ------------------ General ------------------ ***/
    /*** --------------------------------------------- ***/

    /**
     * Get page by its ID.
     *
     * @throws Exception
     */
    public function getPageById()
    {
        API::requireValues("pageId");

        $pageId = API::getValue("pageId", "int");
        $page = API::verifyPageExists($pageId);

        $course = $page->getCourse();
        API::requireCoursePermission($course);

        API::response($page->getData());
    }

    public function getUserLandingPage()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $courseUser = API::verifyCourseUserExists($course, $userId);

        // Only course admins can access other users' pages
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireCourseAdminPermission($course);

        // Get user roles by most specific
        $userRoleNames = Role::getUserRoles($userId, $courseId, true, true);
        foreach ($userRoleNames as $roleName) {
            $page = Role::getRoleLandingPage(Role::getRoleId($roleName, $courseId));
            if ($page) API::response($page->getData());
        }

        API::response(null);
    }

    /**
     * Gets pages of a given course.
     * Option for 'visible'.
     *
     * @throws Exception
     */
    public function getCoursePages()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $isVisible = API::getValue("isVisible", "bool");
        $coursePages = Page::getPages($courseId, $isVisible);

        // Only course admins can access invisible pages
        if (!$isVisible && !Core::getLoggedUser()->isAdmin()) {
            $filteredPages = [];
            $courseUser = $course->getCourseUserById(Core::getLoggedUser()->getId());
            foreach ($coursePages as $pageInfo) {
                $page = Page::getPageById($pageInfo["id"]);
                if ($courseUser->isTeacher() || $page->isVisible())
                    $filteredPages[] = $pageInfo;
            }
            $coursePages = $filteredPages;
        }

        API::response($coursePages);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function getPublicPages()
    {
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $outsideCourse = API::getValue("outsideCourse", "bool") ?? false;
        $publicPages = Page::getPublicPages($courseId, $outsideCourse);

        API::response($publicPages);
    }

    /**
     * Gets course pages available for a given user according to their roles.
     * Option for 'visible'.
     *
     * @throws Exception
     */
    public function getUserPages()
    {
        API::requireValues("courseId", "userId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");

        // Only course admins can access other users' pages
        if (Core::getLoggedUser()->getId() != $userId)
            API::requireCourseAdminPermission($course);

        $isVisible = API::getValue("isVisible", "bool");
        $userPages = Page::getUserPages($courseId, $userId, $isVisible);

        // Only course admins can access invisible pages
        if (!$isVisible && !Core::getLoggedUser()->isAdmin()) {
            $filteredPages = [];
            $courseUser = $course->getCourseUserById(Core::getLoggedUser()->getId());
            foreach ($userPages as $pageInfo) {
                $page = Page::getPageById($pageInfo["id"]);
                if ($courseUser->isTeacher() || $page->isVisible())
                    $filteredPages[] = $pageInfo;
            }
            $userPages = $filteredPages;
        }

        API::response($userPages);
    }

    /**
     * Creates page in the DB
     * @throws Exception
     */
    public function createPage()
    {
        API::requireValues("courseId", "name", "viewTree");
        
        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        
        API::requireCourseAdminPermission($course);
        
        $name = API::getValue("name");
        $viewTree = API::getValue("viewTree", "array");
        
        Page::addPage($courseId, CreationMode::BY_VALUE, $name, $viewTree);
    }

    /**
     * Edits the view of a page in the DB
     * @throws Exception
     */
    public function savePage()
    {
        API::requireValues("courseId", "pageId", "viewTree", "viewsDeleted");
        
        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        
        API::requireCourseAdminPermission($course);

        $pageId = API::getValue("pageId", "int");
        $page = Page::getPageById($pageId);
        
        $viewTree = API::getValue("viewTree", "array");
        $viewIdsDeleted = API::getValue("viewsDeleted", "array");
        
        // Translate tree into logs
        $translatedTree = ViewHandler::translateViewTree($viewTree, ViewHandler::getViewById($page->getViewRoot()), $viewIdsDeleted);

        $page->editPage($page->getName(), $page->isVisible(), $page->isPublic(), $page->getVisibleFrom(), $page->getVisibleUntil(),
                $page->getPosition(), $translatedTree);
    }

    /**
     * Updates page in the DB
     * @return void
     * @throws Exception
     */
    public function editPage(){
        API::requireValues("courseId", "pageId", "name", "isVisible", "viewRoot", "visibleFrom",
            "visibleUntil", "position", "isPublic");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCoursePermission($course);

        $pageId = API::getValue("pageId", "int");
        $page = Page::getPageById($pageId);

        // Get rest of the values
        $name = API::getValue("name");
        $isVisible = API::getValue("isVisible", "bool");
        $viewRoot = API::getValue("viewRoot", "int"); // FIXME --> is it needed?
        $visibleFrom = API::getValue("visibleFrom") ?? null;
        $visibleUntil = API::getValue("visibleUntil") ?? null;
        $position = API::getValue("position", "int");
        $isPublic = API::getValue("isPublic", "bool");

        $pageInfo = $page->editPage($name, $isVisible, $isPublic, $visibleFrom, $visibleUntil, $position)->getData();

        API::response($pageInfo);
    }

    /**
     * Removes page from DB given its ID and course
     *
     * @return void
     * @throws Exception
     */
    public function deletePage(){
        API::requireValues("courseId", "pageId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCoursePermission($course);

        $pageId = API::getValue("pageId", "int");

        Page::deletePage($pageId);
    }

    /**
     * Creates a copy of a given page in a specific course
     * @return void
     * @throws Exception
     */
    public function copyPage(){
        API::requireValues("courseId", "pageId", "creationMode");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);
        API::requireCoursePermission($course);

        // Get rest of values
        $pageId = API::getValue("pageId", "int");
        $creationMode = API::getValue("creationMode");

        $page = new Page($pageId);
        $copy = $page->copyPage($creationMode)->getData();

        API::response($copy);
    }

    /*** --------------------------------------------- ***/
    /*** ------------------- Views ------------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Gets all existing viewTypes from DB
     * @return void
     * @throws Exception
     */
    public function getViewTypes(){
        API::requireValues("courseId", "idsOnly");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);
        $idsOnly = API::getValue("idsOnly", "bool") ?? false;

        $viewTypes = ViewHandler::getViewTypes($idsOnly);
        API::response($viewTypes);

    }

    /**
     * Gets all View Templates in the course
     *
     * @return void
     * @throws Exception
     */
    public function getViews(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);
        $views = ViewHandler::getViews();
        API::response($views);
    }

    /**
     * Gets all Core Components grouped by category
     *
     * @return void
     * @throws Exception
     */
    public function getCoreComponents(){
        $coreComponents = [];

        foreach (CoreComponent::getComponents() as $component) {
            $category = Category::getCategoryById($component["category"])->getName();
            $view = ViewHandler::renderView($component["viewRoot"])[0];
            $type = $view['type'];

            if (!isset($coreComponents[$type][$category])) {
                $coreComponents[$type][$category] = [];
            }

            $coreComponents[$type][$category][] = $view;
        }
        
        API::response($coreComponents);
    }

    /**
     * Gets all Custom Components of a course
     *
     * @return void
     * @throws Exception
     */
    public function getCustomComponents(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);
        
        $fun = function($component) {
            $pair = (object)[];
            $pair->id = $component["id"];
            $pair->view = ViewHandler::renderView($component["viewRoot"])[0];
            return $pair;
        };
        
        $customComponents = array_map($fun, CustomComponent::getComponents($courseId));
        API::response($customComponents);
    }

    /**
     * Saves a View as a Custom Component
     *
     * @return void
     * 
     * @throws Exception
     */
    public function createCustomComponent(){
        API::requireValues("courseId", "name", "viewTree");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $name = API::getValue("name");
        $viewTree = API::getValue("viewTree", "array");
        
        $componentInfo = CustomComponent::addComponent($courseId, CreationMode::BY_VALUE, $name, $viewTree)->getData();
        API::response($componentInfo);
    }

    /**
     * Deletes a Custom Component
     *
     * @return void
     * 
     * @throws Exception
     */
    public function deleteCustomComponent(){
        API::requireValues("courseId", "componentId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $componentId = API::getValue("componentId", "int");
        
        CustomComponent::deleteComponent($componentId);
    }

    /**
     * Turns a Custom Component into a Shared Component
     *
     * @return void
     * 
     * @throws Exception
     */
    public function makeComponentShared(){
        API::requireValues("componentId", "courseId", "userId", "description");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $componentId = API::getValue("componentId", "int");
        $description = API::getValue("description", "string");

        CustomComponent::shareComponent($componentId, $userId, $description);
    }

    /**
     * Stops sharing a Component
     *
     * @return void
     * 
     * @throws Exception
     */
    public function makeComponentPrivate(){
        API::requireValues("componentId", "userId");

        $userId = API::getValue("userId", "int");
        $componentId = API::getValue("componentId", "int");

        CustomComponent::stopShareComponent($componentId, $userId);
    }

    /**
     * Gets all Shared Components
     *
     * @return void
     * @throws Exception
     */
    public function getSharedComponents(){
        
        $fun = function($component) {
            $pair = (object)[];
            $pair->id = $component["id"];
            $pair->user = $component["sharedBy"];
            $pair->view = ViewHandler::renderView($component["viewRoot"])[0];
            $pair->timestamp = $component["sharedTimestamp"];
            return $pair;
        };
        
        $customComponents = array_map($fun, CustomComponent::getSharedComponents());
        API::response($customComponents);
    }

    /**
     * Gets all Core Templates
     *
     * @return void
     * @throws Exception
     */
    public function getCoreTemplates(){
        $fun = function($template) {
            $pair = (object)[];
            $pair->id = $template["id"];
            $pair->name = $template["name"];
            $pair->view = ViewHandler::renderView($template["viewRoot"])[0];
            return $pair;
        };
        
        $coreTemplates = array_map($fun, CoreTemplate::getTemplates());
        API::response($coreTemplates);
    }

    /**
     * Gets all Custom Templates of a course
     *
     * @return void
     * @throws Exception
     */
    public function getCustomTemplates(){
        API::requireValues("courseId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);
        
        $fun = function($template) {
            $pair = (object)[];
            $pair->id = $template["id"];
            $pair->name = $template["name"];
            $pair->view = ViewHandler::renderView($template["viewRoot"])[0];
            return $pair;
        };
        
        $customTemplates = array_map($fun, CustomTemplate::getTemplates($courseId));
        API::response($customTemplates);
    }

    /**
     * Saves a View as a Custom Template
     *
     * @return void
     * 
     * @throws Exception
     */
    public function createCustomTemplate(){
        API::requireValues("courseId", "name", "viewTree");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $name = API::getValue("name");
        $viewTree = API::getValue("viewTree", "array");
        
        $templateInfo = CustomTemplate::addTemplate($courseId, CreationMode::BY_VALUE, $name, $viewTree)->getData();
        API::response($templateInfo);
    }

    /**
     * Deletes a Custom Template
     *
     * @return void
     * 
     * @throws Exception
     */
    public function deleteCustomTemplate(){
        API::requireValues("courseId", "templateId");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $templateId = API::getValue("templateId", "int");
        
        CustomTemplate::deleteTemplate($templateId);
    }

    /**
     * Turns a Custom Template into a Shared Template
     *
     * @return void
     * 
     * @throws Exception
     */
    public function makeTemplateShared(){
        API::requireValues("templateId", "courseId", "userId", "description");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCoursePermission($course);

        $userId = API::getValue("userId", "int");
        $templateId = API::getValue("templateId", "int");
        $description = API::getValue("description", "string");

        CustomTemplate::shareTemplate($templateId, $userId, $description);
    }

    /**
     * Stops sharing a Template
     *
     * @return void
     * 
     * @throws Exception
     */
    public function makeTemplatePrivate(){
        API::requireValues("templateId", "userId");

        $userId = API::getValue("userId", "int");
        $templateId = API::getValue("templateId", "int");

        CustomTemplate::stopShareTemplate($templateId, $userId);
    }

    /**
     * Gets all Shared Components
     *
     * @return void
     * @throws Exception
     */
    public function getSharedTemplates(){
        
        $fun = function($template) {
            $pair = (object)[];
            $pair->id = $template["id"];
            $pair->name = $template["name"];
            $pair->user = $template["sharedBy"];
            $pair->view = ViewHandler::renderView($template["viewRoot"])[0];
            $pair->timestamp = $template["sharedTimestamp"];
            return $pair;
        };
        
        $customTemplates = array_map($fun, CustomTemplate::getSharedTemplates());
        API::response($customTemplates);
    }


    /*** --------------------------------------------- ***/
    /*** ----------------- Rendering ----------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Renders a given page with data.
     *
     * @param int $pageId
     * @param int $userId (optional)
     * @throws Exception
     */
    public function renderPage()
    {
        API::requireValues("pageId");

        $pageId = API::getValue("pageId", "int");
        $page = API::verifyPageExists($pageId);

        $course = $page->getCourse();
        API::requireCoursePermission($course);

        $viewerId = Core::getLoggedUser()->getId();
        $userId = API::getValue("userId", "int");

        // Verify page is visible for current user
        if (!Core::getLoggedUser()->isAdmin()) {
            $courseUser = API::verifyCourseUserExists($course, $viewerId);
            if (!$courseUser->isTeacher() && !$page->isVisible())
                API::error("Page with ID = " . $pageId . " is not visible for current user.", 403);
        }

        // Trigger page viewed event
        Event::trigger(EventType::PAGE_VIEWED, $pageId, $viewerId, $userId);

        API::response($page->renderPage($viewerId, $userId));
    }

    /**
     * Renders a given page for editing.
     *
     * @param int $pageId
     * @param int $userId (optional)
     * @throws Exception
     */
    public function renderPageInEditor()
    {
        API::requireValues("pageId");

        $pageId = API::getValue("pageId", "int");
        $page = API::verifyPageExists($pageId);

        $course = $page->getCourse();
        API::requireCoursePermission($course);

        API::response($page->renderPageForEditor());
    }

    /**
     * Renders a given page with mock data.
     *
     * @param int $pageId
     * @throws Exception
     */
    public function renderPageWithMockData()
    {
        API::requireValues("pageId");

        $pageId = API::getValue("pageId", "int");
        $page = API::verifyPageExists($pageId);

        $course = $page->getCourse();
        API::requireCoursePermission($course);

        $viewerId = Core::getLoggedUser()->getId();
        $userId = API::getValue("userId", "int");

        API::response($page->renderPage($viewerId, $userId, true));
    }

    /**
     * Renders a given page for previewing.
     *
     * @param int $pageId
     * @param int $userId (optional)
     * @throws Exception
     */
    public function previewPage()
    {
        API::requireValues("pageId", "userRole", "viewerRole");

        $pageId = API::getValue("pageId", "int");
        $page = API::verifyPageExists($pageId);
        
        $course = $page->getCourse();
        API::requireCoursePermission($course);

        $userRole = API::getValue("userRole", "string");
        $viewerRole = API::getValue("viewerRole", "string");

        API::response($page->previewPage(null, null, Aspect::getAspectBySpecs($course->getId(), $userRole, $viewerRole)));
    }


    /*** --------------------------------------------- ***/
    /*** -------------- Import / Export -------------- ***/
    /*** --------------------------------------------- ***/

    /**
     * Import pages into a given course.
     *
     * @param $courseId
     * @param $file
     * @param bool $replace
     * @throws Exception
     */
    public function importPages()
    {
        API::requireValues("courseId", "file", "replace");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $file = API::getValue("file");
        $replace = API::getValue("replace", "bool");

        $nrPagesImported = Page::importPages($courseId, $file, $replace);
        API::response($nrPagesImported);
    }

    /**
     * Export page(s) from a given course.
     * @throws Exception
     */
    public function exportPages(){
        API::requireValues("courseId", "pagesIds");

        $courseId = API::getValue("courseId", "int");
        $course = API::verifyCourseExists($courseId);

        API::requireCourseAdminPermission($course);

        $pagesIds = API::getValue("pagesIds", "array");

        API::response(Page::exportPages($courseId, $pagesIds));
    }
}