<?php
namespace GameCourse\Views\Page;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\ViewHandler;
use Utils\CronJob;
use Utils\Utils;

/**
 * This is the Page model, which implements the necessary methods
 * to interact with pages in the MySQL database.
 */
class Page
{
    const TABLE_PAGE = "page";
    const TABLE_PAGE_HISTORY = "user_page_history";

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getId(): int
    {
        return $this->id;
    }

    public function getCourse(): Course
    {
        return Course::getCourseById($this->getData("course"));
    }

    public function getName(): string
    {
        return $this->getData("name");
    }

    public function getViewRoot(): int
    {
        return $this->getData("viewRoot");
    }

    public function getCreationTimestamp(): string
    {
        return $this->getData("creationTimestamp");
    }

    public function getUpdateTimestamp(): string
    {
        return $this->getData("updateTimestamp");
    }

    public function getVisibleFrom(): ?string
    {
        return $this->getData("visibleFrom");
    }

    public function getVisibleUntil(): ?string
    {
        return $this->getData("visibleUntil");
    }

    public function getPosition(): ?int
    {
        return $this->getData("position");
    }

    public function isVisible(): bool
    {
        return $this->getData("isVisible");
    }

    /**
     * Gets page data from the database.
     *
     * @example getData() --> gets all page data
     * @example getData("name") --> gets page name
     * @example getData("name, position") --> gets page name & position
     *
     * @param string $field
     * @return array|int|null
     */
    public function getData(string $field = "*")
    {
        $data = Core::database()->select(self::TABLE_PAGE, ["id" => $this->id], $field);
        return is_array($data) ? self::parse($data) : self::parse(null, $data, $field);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Setters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function setName(string $name)
    {
        $this->setData(["name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function setCourse(Course $course)
    {
        $this->setData(["course" => $course->getId()]);
    }

    /**
     * @throws Exception
     */
    public function setVisible(bool $isVisible)
    {
        $this->setData(["isVisible" => +$isVisible]);
    }

    /**
     * @throws Exception
     */
    public function setViewRoot(int $viewRoot)
    {
        $this->setData(["viewRoot" => $viewRoot]);
    }

    /**
     * @throws Exception
     */
    public function setCreationTimestamp(string $timestamp)
    {
        $this->setData(["creationTimestamp" => $timestamp]);
    }

    /**
     * @throws Exception
     */
    public function setUpdateTimestamp(string $timestamp)
    {
        $this->setData(["updateTimestamp" => $timestamp]);
    }

    /**
     * @throws Exception
     */
    public function setVisibleFrom(?string $timestamp)
    {
        $this->setData(["visibleFrom" => $timestamp]);
    }

    /**
     * @throws Exception
     */
    public function setVisibleUntil(?string $timestamp)
    {
        $this->setData(["visibleUntil" => $timestamp]);
    }

    /**
     * @throws Exception
     */
    public function setPosition(int $position)
    {
        $this->setData(["position" => $position]);
    }

    /**
     * Sets page data on the database.
     *
     * @param array $fieldValues
     * @return void
     * @throws Exception
     * @example setData(["name" => "New name", "course" => 1])
     *
     * @example setData(["name" => "New name"])
     */
    public function setData(array $fieldValues)
    {
        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($fieldValues["name"]);
        if (key_exists("visibleFrom", $fieldValues)) {
            self::validateDateTime($fieldValues["visibleFrom"]);
            $visibleUntil = key_exists("visibleUntil", $fieldValues) ? $fieldValues["visibleUntil"] : $this->getVisibleUntil();
            if ($visibleUntil) self::validateVisibleFromAndUntilDates($fieldValues["visibleFrom"], $visibleUntil);
        }
        if (key_exists("visibleUntil", $fieldValues)) {
            self::validateDateTime($fieldValues["visibleUntil"]);
            $visibleFrom = key_exists("visibleFrom", $fieldValues) ? $fieldValues["visibleFrom"] : $this->getVisibleFrom();
            if ($visibleFrom) self::validateVisibleFromAndUntilDates($visibleFrom, $fieldValues["visibleUntil"]);
        }

        // Update data
        if (count($fieldValues) != 0)
            Core::database()->update(self::TABLE_PAGE, $fieldValues, ["id" => $this->id]);

        // Additional actions
        if (key_exists("visibleFrom", $fieldValues)) {
            $this->setAutomation("AutoEnabling", $fieldValues["visibleFrom"]);
        }
        if (key_exists("visibleUntil", $fieldValues)) {
            $this->setAutomation("AutoDisabling", $fieldValues["visibleUntil"]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a page by its ID.
     * Returns null if page doesn't exist.
     *
     * @param int $pageId
     * @return Page|null
     */
    public static function getPageById(int $pageId): ?Page
    {
        $page = new Page($pageId);
        if ($page->exists()) return $page;
        else return null;
    }

    /**
     * Gets a page by its name.
     * Returns null if page doesn't exist.
     *
     * @param int $courseId
     * @param string $name
     * @return Page|null
     */
    public static function getPageByName(int $courseId, string $name): ?Page
    {
        $pageId = intval(Core::database()->select(self::TABLE_PAGE, ["course" => $courseId, "name" => $name], "id"));
        if (!$pageId) return null;
        else return new Page($pageId);
    }

    /**
     * Gets a page by its position.
     * Returns null if page doesn't exist.
     *
     * @param int $courseId
     * @param int $position
     * @return Page|null
     */
    public static function getPageByPosition(int $courseId, int $position): ?Page
    {
        $pageId = intval(Core::database()->select(self::TABLE_PAGE, ["course" => $courseId, "position" => $position], "id"));
        if (!$pageId) return null;
        else return new Page($pageId);
    }

    /**
     * Gets pages of a given course.
     * Option for 'visible'.
     *
     * @param int $courseId
     * @param bool|null $visible
     * @return array
     */
    public static function getCoursePages(int $courseId, ?bool $visible = null): array
    {
        $where = ["course" => $courseId];
        if ($visible !== null) $where["isVisible"] = $visible;
        $pages = Core::database()->selectMultiple(self::TABLE_PAGE, $where, "*", "id");
        foreach ($pages as &$page) { $page = self::parse($page); }
        return $pages;
    }

    /**
     * Gets course pages available for a given user according to their roles.
     * Option for 'visible'.
     *
     * @param int $courseId
     * @param int $userid
     * @param bool|null $visible
     * @return array
     * @throws Exception
     */
    public static function getUserPages(int $courseId, int $userid, ?bool $visible = null): array
    {
        // Get course pages
        $coursePages = self::getCoursePages($courseId, $visible);

        // Filter pages based on user roles and aspects defined for each page
        $availablePagesForUser = [];
        foreach ($coursePages as $page) {
            // Try to build page for user
            $viewTree = ViewHandler::buildView($page["viewRoot"], Aspect::getAspects($courseId, $userid));
            if (!empty($viewTree)) $availablePagesForUser[] = $page;
        }
        return $availablePagesForUser;
    }

    /**
     * Updates page's updateTimestamp to current time.
     *
     * @return void
     * @throws Exception
     */
    public function refreshUpdateTimestamp()
    {
        $this->setUpdateTimestamp(date("Y-m-d H:i:s", time()));
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Page Manipulation ---------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a page to the database.
     * Returns the newly created page.
     *
     * @param int $courseId
     * @param string $name
     * @param array|null $viewTree
     * @param string|null $visibleFrom
     * @param string|null $visibleUntil
     * @return Page
     * @throws Exception
     */
    public static function addPage(int $courseId, string $name, array $viewTree = null, ?string $visibleFrom = null,
                                   ?string $visibleUntil = null): Page
    {
        self::trim($name, $visibleFrom, $visibleUntil);
        self::validatePage($name, false, $visibleFrom, $visibleUntil);

        // Add view tree of page
        $viewRoot = ViewHandler::insertViewTree($viewTree ?? ViewHandler::ROOT_VIEW, $courseId);

        // Insert in database
        $id = Core::database()->insert(self::TABLE_PAGE, [
            "course" => $courseId,
            "name" => $name,
            "viewRoot" => $viewRoot,
            "visibleFrom" => $visibleFrom,
            "visibleUntil" => $visibleUntil
        ]);

        // Set automations
        $page = new Page($id);
        $page->setAutomation("AutoEnabling", $visibleFrom);
        $page->setAutomation("AutoDisabling", $visibleUntil);

        return $page;
    }

    /**
     * Adds a page to the database by copying from another
     * existing page.
     *
     * @param int $copyFrom
     * @return Page
     * @throws Exception
     */
    public static function copyPage(int $copyFrom): Page
    {
        $pageToCopy = Page::getPageById($copyFrom);
        if (!$pageToCopy) throw new Exception("Page to copy from with ID = " . $copyFrom . " doesn't exist.");
        $pageInfo = $pageToCopy->getData();

        // Create copy
        $name = $pageInfo["name"] . " (Copy)";
        $viewTree = ViewHandler::buildView($pageInfo["viewRoot"]);
        return self::addPage($pageInfo["course"], $name, $viewTree, $pageInfo["visibleFrom"], $pageInfo["visibleUntil"]);
    }

    /**
     * Edits an existing page in database.
     * Returns the edited page.
     *
     * @param string $name
     * @param bool $isVisible
     * @param string|null $visibleFrom
     * @param string|null $visibleUntil
     * @param int|null $position
     * @return Page
     * @throws Exception
     */
    public function editPage(string $name, bool $isVisible, ?string $visibleFrom = null, ?string $visibleUntil = null,
                             ?int $position = null): Page
    {
        $this->setData([
            "name" => $name,
            "isVisible" => +$isVisible,
            "visibleFrom" => $visibleFrom,
            "visibleUntil" => $visibleUntil,
            "position" => $position
        ]);
        $this->refreshUpdateTimestamp();

        // Update automations
        $this->setAutomation("AutoEnabling", $visibleFrom);
        $this->setAutomation("AutoDisabling", $visibleUntil);

        return $this;
    }

    /**
     * Deletes a page from the database and removes all its views.
     *
     * @param int $pageId
     * @return void
     */
    public static function deletePage(int $pageId)
    {
        $page = Page::getPageById($pageId);
        ViewHandler::deleteViewTree($page->getViewRoot());
    }

    /**
     * Checks whether page exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->getData("id"));
    }

    /**
     * Checks whether a view root is a page.
     *
     * @param int $viewRoot
     * @return bool
     */
    public static function isPage(int $viewRoot): bool
    {
        return !empty(Core::database()->select(self::TABLE_PAGE, ["viewRoot" => $viewRoot]));
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Rendering -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Renders a page for a given viewer and user.
     *
     * @param int $viewerId
     * @param int|null $userId
     * @return array
     * @throws Exception
     */
    public function renderPage(int $viewerId, int $userId = null): array
    {
        $userId = $userId ?? $viewerId;
        $pageInfo = $this->getData("course, viewRoot");
        $paramsToPopulate = ["course" => $pageInfo["course"], "viewer" => $viewerId, "user" => $userId];
        $sortedAspects = Aspect::getAspectsByViewerAndUser($pageInfo["course"], $viewerId, $userId, true);
        return ViewHandler::renderView($pageInfo["viewRoot"], $sortedAspects, $paramsToPopulate);
    }

    public function renderPageForEditor()
    {
        // TODO
    }

    /**
     * Previews a page either for a specific viewer and user
     * or a specific aspect.
     *
     * @example previewPage(1) --> previews page for viewer and user with ID = 1
     * @example previewPage(1, 2) --> previews page for viewer with ID = 1 and user with ID = 2
     * @example previewPage(null, null, <Aspect>) --> previews page for a given aspect
     * @example previewPage(1, null, <Aspect>) --> same as 1st example
     *
     * @param int|null $viewerId
     * @param int|null $userId
     * @param Aspect|null $aspect
     * @return array
     * @throws Exception
     */
    public function previewPage(int $viewerId = null, int $userId = null, Aspect $aspect = null): array
    {
        if ($viewerId === null && $aspect === null)
            throw new Exception("Need either viewer ID or an aspect to preview a page.");

        // Render for a specific viewer and user
        if ($viewerId) return $this->renderPage($viewerId, $userId);

        // Render for a specific aspect
        $pageInfo = $this->getData("course, viewRoot");
        $defaultAspect = Aspect::getAspectBySpecs($pageInfo["course"], null, null);
        return ViewHandler::renderView($pageInfo["viewRoot"], [$aspect, $defaultAspect], true);
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Automation -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Sets automation for some page processes.
     *
     * @param string $script
     * @param ...$data
     * @return void
     * @throws Exception
     */
    private function setAutomation(string $script, ...$data)
    {
        switch ($script) {
            case "AutoEnabling":
                $this->setAutoEnabling($data[0]);
                break;

            case "AutoDisabling":
                $this->setAutoDisabling($data[0]);
                break;

            default:
                throw new Exception("Automation script '" . $script . "' not found for page.");
        }
    }

    /**
     * Enables auto enabling for a given page on a specific date.
     * If date is null, it will disable it.
     *
     * @param string|null $startDate
     * @return void
     * @throws Exception
     */
    private function setAutoEnabling(?string $startDate)
    {
        $script = ROOT_PATH . "models/GameCourse/Views/Page/AutoEnablingScript.php";
        if ($startDate) new CronJob($script, CronJob::dateToExpression($startDate), $this->id);
        else CronJob::removeCronJob($script, $this->id);
    }

    /**
     * Enables auto disabling for a given page on a specific date.
     * If date is null, it will disable it.
     *
     * @param string|null $endDate
     * @return void
     * @throws Exception
     */
    private function setAutoDisabling(?string $endDate)
    {
        $script = ROOT_PATH . "models/GameCourse/Views/Page/AutoDisablingScript.php";
        if ($endDate) new CronJob($script, CronJob::dateToExpression($endDate), $this->id);
        else CronJob::removeCronJob($script, $this->id);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates page parameters.
     *
     * @param $name
     * @param $isVisible
     * @param $visibleFrom
     * @param $visibleUntil
     * @return void
     * @throws Exception
     */
    private static function validatePage($name, $isVisible, $visibleFrom, $visibleUntil)
    {
        self::validateName($name);
        self::validateDateTime($visibleFrom);
        self::validateDateTime($visibleUntil);
        self::validateVisibleFromAndUntilDates($visibleFrom, $visibleUntil);

        if (!is_bool($isVisible)) throw new Exception("'isVisible' must be either true or false.");

    }

    /**
     * Validates page name.
     *
     * @throws Exception
     */
    private static function validateName($name)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Page name can't be null neither empty.");

        if (iconv_strlen($name) > 25)
            throw new Exception("Page name is too long: maximum of 25 characters.");
    }

    /**
     * Validate datetime.
     *
     * @param $dateTime
     * @return void
     * @throws Exception
     */
    private static function validateDateTime($dateTime)
    {
        if (is_null($dateTime)) return;
        if (!is_string($dateTime) || !Utils::isValidDate($dateTime, "Y-m-d H:i:s"))
            throw new Exception("Datetime '" . $dateTime . "' should be in format 'yyyy-mm-dd HH:mm:ss'");
    }

    /**
     * Validates page visible from and until dates.
     *
     * @throws Exception
     */
    private static function validateVisibleFromAndUntilDates($visibleFrom, $visibleUntil)
    {
        if ($visibleFrom && $visibleUntil && strtotime($visibleFrom) >= strtotime($visibleUntil))
            throw new Exception("Page visible until date must come later than visible from date.");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a page coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $page
     * @param $field
     * @param string|null $fieldName
     * @return array|bool|int|mixed|null
     */
    private static function parse(array $page = null, $field = null, string $fieldName = null)
    {
        $intValues = ["id", "course", "position", "viewRoot", "position"];
        $boolValues = ["isVisible"];

        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $page, $field, $fieldName);
    }

    /**
     * Trims page parameters' whitespace at start/end.
     *
     * @param mixed ...$values
     * @return void
     */
    private static function trim(&...$values)
    {
        $params = ["name", "creationTimestamp", "updateTimestamp", "visibleFrom", "visibleUntil"];
        Utils::trim($params, ...$values);
    }
}