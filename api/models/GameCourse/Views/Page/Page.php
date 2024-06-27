<?php
namespace GameCourse\Views\Page;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\CreationMode;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\Logging\Logging;
use GameCourse\Views\ViewHandler;
use GameCourse\Views\ViewType\ViewType;
use Utils\Cache;
use Utils\CronJob;
use Utils\Time;
use Utils\Utils;
use ZipArchive;

/**
 * This is the Page model, which implements the necessary methods
 * to interact with pages in the MySQL database.
 */
class Page
{
    const TABLE_PAGE = "page";
    const TABLE_PAGE_HISTORY = "user_page_history";

    const HEADERS = [   // headers for import/export functionality
        "name", "viewRoot"
    ];

    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getImage(): ?string
    {
        return $this->hasImage() ? API_URL . "/" . $this->getDataFolder(false) . "/screenshot.png" : null;
    }

    public function hasImage(): bool
    {
        return file_exists($this->getDataFolder() . "/screenshot.png");
    }

    /**
     * Gets page data folder path.
     * Option to retrieve full server path or the short version.
     *
     * @param bool $fullPath
     * @return string
     */
    public function getDataFolder(bool $fullPath = true): string
    {
        if ($fullPath) return PAGES_DATA_FOLDER . "/" . $this->getId();
        else return Utils::getDirectoryName(PAGES_DATA_FOLDER) . "/" . $this->getId();
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

    public function isPublic(): bool{
        return $this->getData("isPublic");
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
        if ($field == "*" || str_contains($field, "image")) $data["image"] = $this->getImage();
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
     * @param bool $isPublic
     * @return void
     * @throws Exception
     */
    public function setPublic(bool $isPublic){
        $this->setData(["isPublic" => +$isPublic]);
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
        $courseId = $this->getCourse()->getId();

        // Trim values
        self::trim($fieldValues);

        // Validate data
        if (key_exists("name", $fieldValues)) self::validateName($courseId, $fieldValues["name"], $this->id);
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
        if (key_exists("position", $fieldValues)) {
            $newPosition = $fieldValues["position"];
            $oldPosition = $this->getPosition();
            Utils::updateItemPosition($oldPosition, $newPosition, self::TABLE_PAGE, "position", $this->id, self::getPages($courseId));
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

    /**
     * @throws Exception
     */
    public function setImage(string $base64)
    {
        Utils::uploadFile($this->getDataFolder(), $base64, "screenshot.png");
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
    public static function getPages(int $courseId, ?bool $visible = null): array
    {
        $where = ["course" => $courseId];
        if ($visible !== null) $where["isVisible"] = $visible;

        $pages = Core::database()->selectMultiple(self::TABLE_PAGE, $where, "*", "position");

        foreach ($pages as &$page) { 
            $page = self::parse($page); 
            // Get image
            $pageForImage = new Page($page["id"]);
            $page["image"] = $pageForImage->getImage();
        }

        return $pages;
    }

    /**
     * Gets all public
     *
     * @param int $courseId
     * @return array
     */
    public static function getPublicPages(int $courseId, ?bool $outsideCourse = false): array
    {
        // Add public pages from other courses
        $publicPages = Core::database()->selectMultiple(self::TABLE_PAGE, ["isPublic" => true]);

        $filteredPages = $publicPages;
        if ($outsideCourse){
            // Removes public pages from the current course (those are already in the $pages array)
            $filteredPages = [];
            foreach ($publicPages as $publicPage) {
                if (intval($publicPage["course"]) !== $courseId) {
                    $filteredPages[] = $publicPage;
                }
            }
        }

        foreach ($filteredPages as &$page) { $page = self::parse($page); }
        return $filteredPages;
    }

    /**
     * Gets pages by a given view root.
     *
     * @param int $viewRoot
     * @return array
     */
    public static function getPagesByViewRoot(int $viewRoot): array
    {
        $pages = Core::database()->selectMultiple(self::TABLE_PAGE, ["viewRoot" => $viewRoot], "*", "id");
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
        $coursePages = self::getPages($courseId, $visible);

        // Filter pages based on user roles and aspects defined for each page
        $availablePagesForUser = [];
        foreach ($coursePages as $page) {
            // Try to build page for user
            $viewTree = ViewHandler::buildView($page["viewRoot"], Aspect::getAspects($courseId, $userid, true));
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
     * @param string $creationMode
     * @param string $name
     * @param array|null $viewTree
     * @param int|null $viewRoot
     * @param string|null $visibleFrom
     * @param string|null $visibleUntil
     * @return Page
     * @throws Exception
     */
    public static function addPage(int $courseId, string $creationMode, string $name, array $viewTree = null,
                                   int $viewRoot = null, ?string $visibleFrom = null, ?string $visibleUntil = null): Page
    {
        self::trim($name, $visibleFrom, $visibleUntil);
        self::validatePage($courseId, $name, false, $visibleFrom, $visibleUntil);

        if ($creationMode == CreationMode::BY_VALUE) {
            if (!$viewTree) $viewTree = ViewHandler::ROOT_VIEW;
            $viewRoot = ViewHandler::insertViewTree($viewTree, $courseId);

        } else if ($creationMode == CreationMode::BY_REFERENCE) {
            if (is_null($viewRoot))
                throw new Exception("Can't add page by reference: no view root given.");
        }


        // Insert in database
        $now = date("Y-m-d H:i:s", time());
        $isVisible = ($visibleFrom && Time::isBefore($visibleFrom, $now)) && (!$visibleUntil || Time::isAfter($visibleUntil, $now));
        $id = Core::database()->insert(self::TABLE_PAGE, [
            "course" => $courseId,
            "name" => $name,
            "isVisible" => +$isVisible,
            "viewRoot" => $viewRoot,
            "visibleFrom" => $visibleFrom,
            "visibleUntil" => $visibleUntil
        ]);

        // Set position
        if ($isVisible) {
            $coursePages = self::getPages($courseId);
            Utils::updateItemPosition(null, count($coursePages), self::TABLE_PAGE, "position", $id, $coursePages);
        }

        // Set automations
        $page = new Page($id);
        $page->setAutomation("AutoEnabling", $visibleFrom);
        $page->setAutomation("AutoDisabling", $visibleUntil);

        return $page;
    }

    /**
     * Copies an existing page.
     *
     * @param string $creationMode
     * @return Page
     * @throws Exception
     */
    public function copyPage(string $creationMode): Page
    {
        $pageInfo = $this->getData();

        // Copy page
        $name = $pageInfo["name"] . " (Copy)";
        $viewTree = $creationMode === CreationMode::BY_VALUE ? ViewHandler::buildView($pageInfo["viewRoot"], null, true) : null;
        $viewRoot = $creationMode === CreationMode::BY_REFERENCE ? $pageInfo["viewRoot"] : null;
        return self::addPage($pageInfo["course"], $creationMode, $name, $viewTree, $viewRoot);
    }

    /**
     * Edits an existing page in the database.
     * Returns the edited page.
     *
     * @param string $name
     * @param bool $isVisible
     * @param string|null $visibleFrom
     * @param string|null $visibleUntil
     * @param int|null $position
     * @param array|null $viewTreeChanges
     * @return Page
     * @throws Exception
     */
    public function editPage(string $name, bool $isVisible, bool $isPublic, ?string $visibleFrom = null, ?string $visibleUntil = null,
                             ?int $position = null, ?array $viewTreeChanges = null): Page
    {
        $this->setData([
            "name" => $name,
            "isVisible" => +$isVisible,
            "visibleFrom" => $visibleFrom,
            "visibleUntil" => $visibleUntil,
            "position" => $position,
            "isPublic" => +$isPublic
        ]);

        // Update view tree, if changes were made
        if ($viewTreeChanges) {
            $logs = $viewTreeChanges["logs"];
            $views = $viewTreeChanges["views"];
            Logging::processLogs($logs, $views, $this->getCourse()->getId());
        }

        // Update automations
        $this->setAutomation("AutoEnabling", $visibleFrom);
        $this->setAutomation("AutoDisabling", $visibleUntil);

        $this->refreshUpdateTimestamp();
        return $this;
    }

    
    /**
     * Clear positions of all pages received as input
     *
     * @throws Exception
     */
    public static function clearPositions(array $ids)
    {
        $sql = "UPDATE page SET position = null WHERE id IN (" . implode(',', $ids) . ")";
        Core::database()->executeQuery($sql);
    }

    /**
     * Sets positions of all pages received as input
     *
     * @throws Exception
     */
    public static function setPositions(array $array)
    {
        $sql = "UPDATE page SET position = CASE ";

        foreach($array as $pair) {
            $sql .= "WHEN id = " . $pair["id"] . " THEN " . $pair["position"] . " ";
        }
        $sql .= "ELSE position END WHERE id IN (" . implode(',', array_map(function($el) {return $el["id"];}, $array)) . ")";

        Core::database()->executeQuery($sql);
    }

    /**
     * Deletes a page from the database and removes all its views.
     * Option to keep views linked to page (created by reference)
     * intact or to replace them by a placeholder view.
     *
     * @param int $pageId
     * @param bool $keepLinked
     * @return void
     * @throws Exception
     */
    public static function deletePage(int $pageId, bool $keepLinked = true)
    {
        $page = Page::getPageById($pageId);
        if ($page) {
            // Delete page view tree
            ViewHandler::deleteViewTree($pageId, $page->getViewRoot(), $keepLinked);

            // Remove automations
            $page->setAutomation("AutoEnabling", null);
            $page->setAutomation("AutoDisabling", null);

            // Delete from database
            Core::database()->delete(self::TABLE_PAGE, ["id" => $pageId]);
        }
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
     * Option to render page with mocked data instead.
     *
     * @param int $viewerId
     * @param int|null $userId
     * @param array $mockedData
     * @return array
     * @throws Exception
     */
    public function renderPage(int $viewerId, int $userId = null, array $mockedData = null): array
    {
        $pageInfo = $this->getData("course, viewRoot, type");
        if (isset($pageInfo["type"])) Cache::loadFromDatabase($this->id, $pageInfo["type"], $userId);

        // NOTE: user defaults as viewer if no user directly passed
        $userId = $userId ?? $viewerId;

        $sortedAspects = Aspect::getAspectsByViewerAndUser($pageInfo["course"], $viewerId, $userId, true);

        if ($mockedData) {
            return ViewHandler::renderView($pageInfo["viewRoot"], null, true, ["course" => $pageInfo["course"], "viewerRole" => $mockedData["viewerRole"], "userRole" => $mockedData["userRole"]]);
        }
        else {
            return ViewHandler::renderView($pageInfo["viewRoot"], $sortedAspects, ["course" => $pageInfo["course"], "viewer" => $viewerId, "user" => $userId]);
        }
    }

    /**
     * Renders a page for editing.
     *
     * @return array
     * @throws Exception
     */
    public function renderPageForEditor()
    {
        $pageInfo = $this->getData("course, viewRoot");
        $viewRoot = $pageInfo["viewRoot"];
        $courseId = $pageInfo["course"];
        return ViewHandler::buildViewComplete($viewRoot, $courseId);
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
        $aspectParams = "id, viewerRole, userRole";
        $defaultAspect = Aspect::getAspectBySpecs($pageInfo["course"], null, null);
        $sortedAspects = [$aspect->getData($aspectParams), $defaultAspect->getData($aspectParams)];
        return ViewHandler::renderView($pageInfo["viewRoot"], $sortedAspects, true);
    }

    /*** ---------------------------------------------------- ***/
    /*** ------------------- Editor Tools ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Previews an expression of the Language Expression as Text.
     *
     * @throws Exception
     */
    public static function previewExpressionLanguage(string $expression, int $courseId, int $viewerId, array $tree)
    {
        $visitor = new EvaluateVisitor(["course" => $courseId, "viewer" => $viewerId, "user" => $viewerId]);
        Core::dictionary()->setVisitor($visitor);

        // Process the tree to obtain knowledge of the variables
        ViewHandler::compileReducedView($tree);
        ViewHandler::evaluateReducedView($tree, $visitor);

        // Compile and evaluate the desired expression
        $viewType = ViewType::getViewTypeById("text");
        $view = ["text" => $expression];
        $viewType->compile($view);
        $viewType->evaluate($view, $visitor);

        if (is_object($view["text"])) {
            $className = explode('\\', get_class($view["text"]));
            return array_merge(["itemType" => end($className)], $view["text"]->getData());
        }
        else if (is_array($view["text"])) {
            foreach ($view["text"] as &$el) {
                if (is_object($el)) {
                    return array_merge(["itemNamespace" => $el->getId()], $view["text"]);
                }
                else if (isset($el["libraryOfItem"])) {
                    $type = $el["libraryOfItem"]->getId();
                    unset($el["libraryOfItem"]);
                    $el = array_merge(["itemNamespace" => $type], $el);
                }
            }
            return $view["text"];
        }
        else {
            return $view["text"];
        }
    }

    /**
     * Gets the recipes from the cookbook that are
     * useful for the page editor
     *
     * @throws Exception
     */
    public static function getCookbook(Course $course): array {
        $content = [];

        $folderPath = COOKBOOK_FOLDER . "/pages";
        if (!is_dir($folderPath) || !is_readable($folderPath)) {
            throw new Exception("Cookbook folder not found or not readable");
        }

        $folderContents = scandir($folderPath);
        foreach ($folderContents as $fileName) {
            // Skip current and parent directory entries
            if ($fileName === "." || $fileName === "..") {
                continue;
            }

            $path = $folderPath . "/" . $fileName;
            $fileContent = file_get_contents($path);
            $name = pathinfo($fileName, PATHINFO_FILENAME);

            $content[] = ["name" => $name, "content" => $fileContent];
        }
        return $content;
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
    public function setAutomation(string $script, ...$data)
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
    public function setAutoEnabling(?string $startDate)
    {
        $script = ROOT_PATH . "models/GameCourse/Views/Page/scripts/AutoEnablingScript.php";
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
    public function setAutoDisabling(?string $endDate)
    {
        $script = ROOT_PATH . "models/GameCourse/Views/Page/scripts/AutoDisablingScript.php";
        if ($endDate) new CronJob($script, CronJob::dateToExpression($endDate), $this->id);
        else CronJob::removeCronJob($script, $this->id);
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Import/Export ------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Imports pages into a given course from a .zip file.
     * Returns the nr. of pages imported.
     *
     * @param int $courseId
     * @param string $contents
     * @param bool $replace
     * @return int
     * @throws Exception
     */
    public static function importPages(int $courseId, string $contents, bool $replace = true): int
    {
        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Extract contents
        $zipPath = $tempFolder . "/pages.zip";
        Utils::uploadFile($tempFolder, $contents, "pages.zip");
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) throw new Exception("Failed to create zip archive.");
        $zip->extractTo($tempFolder);
        $zip->close();
        Utils::deleteFile($tempFolder, "pages.zip");

        // Import pages
        $nrPagesImported = 0;
        $files = Utils::getDirectoryContents($tempFolder . "/");
        foreach ($files as $fileInfo) {
            $pageName = str_replace($fileInfo["extension"], "", $fileInfo["name"]);
            $viewTree = json_decode(file_get_contents($tempFolder . "/" . $fileInfo["name"]), true);

            $page = self::getPageByName($courseId, $pageName);
            if ($page) { // page already exists
                if ($replace) { // replace
                    // Set invisible
                    $page->setVisible(false);
                    $page->setVisibleFrom(null);
                    $page->setVisibleUntil(null);

                    // Reset timestamps
                    $now = date("Y/m/d H:i:s", time());
                    $page->setCreationTimestamp($now);
                    $page->setUpdateTimestamp($now);

                    // Replace view tree
                    $oldRoot = $page->getViewRoot();
                    $root = ViewHandler::insertViewTree($viewTree, $courseId);
                    $page->setViewRoot($root);
                    ViewHandler::deleteViewTree($page->id, $oldRoot);
                }

            } else { // page doesn't exist
                Page::addPage($courseId, CreationMode::BY_VALUE, $pageName, $viewTree);
                $nrPagesImported++;
            }
        }

        // Remove temporary folder
        Utils::deleteDirectory($tempFolder);
        if (Utils::getDirectorySize(ROOT_PATH . "temp") == 0)
            Utils::deleteDirectory(ROOT_PATH . "temp");

        return $nrPagesImported;
    }

    /**
     * Exports pages of a given course to a .zip file containing
     * each of their view trees.
     *
     * @param int $courseId
     * @param array $pageIds
     * @return array
     * @throws Exception
     */
    public static function exportPages(int $courseId, array $pageIds): array
    {
        $course = new Course($courseId);

        // Create a temporary folder to work with
        $tempFolder = ROOT_PATH . "temp/" . time();
        mkdir($tempFolder, 0777, true);

        // Create zip archive to store pages' view trees
        // NOTE: This zip will be automatically deleted after download is complete
        $zipPath = $tempFolder . "/" . Utils::strip($course->getShort() ?? $course->getName(), "_") . "-pages.zip";
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE))
            throw new Exception("Failed to create zip archive.");

        $pagesToExport = array_values(array_filter(self::getPages($courseId), function ($page) use ($pageIds) { return in_array($page["id"], $pageIds); }));
        foreach ($pagesToExport as $pageInfo) {
            $viewTree = ViewHandler::buildView($pageInfo["viewRoot"], null, true);
            $zip->addFromString($pageInfo["name"] . ".txt", json_encode($viewTree, JSON_PRETTY_PRINT));
        }

        $zip->close();
        return ["extension" => ".zip", "path" => str_replace(ROOT_PATH, API_URL . "/", $zipPath)];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Validations -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Validates page parameters.
     *
     * @param int $courseId
     * @param $name
     * @param $isVisible
     * @param $visibleFrom
     * @param $visibleUntil
     * @return void
     * @throws Exception
     */
    private static function validatePage(int $courseId, $name, $isVisible, $visibleFrom, $visibleUntil)
    {
        self::validateName($courseId, $name);
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
    private static function validateName(int $courseId, $name, int $pageId = null)
    {
        if (!is_string($name) || empty($name))
            throw new Exception("Page name can't be null neither empty.");

        if (iconv_strlen($name) > 100)
            throw new Exception("Page name is too long: maximum of 100 characters.");

        $whereNot = [];
        if ($pageId) $whereNot[] = ["id", $pageId];
        $pageNames = array_column(Core::database()->selectMultiple(self::TABLE_PAGE, ["course" => $courseId], "name", null, $whereNot), "name");
        if (in_array($name, $pageNames))
            throw new Exception("Duplicate page name: '$name'");
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
        $boolValues = ["isVisible", "isPublic"];

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