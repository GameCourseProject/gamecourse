<?php
namespace API;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;
use GameCourse\Role\Role;
use GameCourse\Views\Page\Page;
use GameCourse\Views\ViewHandler;

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

    /*** --------------------------------------------- ***/
    /*** ------------------- Views ------------------- ***/
    /*** --------------------------------------------- ***/

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

    // TODO: renderPageInEditor (mocked or not)

    // TODO: preview page


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

    // TODO: export
}
