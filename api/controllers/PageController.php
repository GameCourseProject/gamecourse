<?php
namespace API;

use Event\Event;
use Event\EventType;
use Exception;
use GameCourse\Core\Core;

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

        $viewerId = Core::getLoggedUser()->getId();
        $userId = API::getValue("userId", "int");

        // Verify page is visible for current user
        $course = $page->getCourse();
        $courseUser = API::verifyCourseUserExists($course, $viewerId);
        if (!$courseUser->isTeacher() && !$page->isVisible())
            throw new Exception("Page with ID = " . $pageId . " is not visible for current user.");

        // Trigger page viewed event
        // FIXME: handle on profiling module
        Event::trigger(EventType::PAGE_VIEWED, $pageId, $viewerId, $userId ?? $viewerId);

        API::response($page->renderPage($viewerId, $userId));
    }

    // TODO: renderPageInEditor (mocked or not)

    // TODO: preview page
}
