<?php
namespace GameCourse\Views;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Component\Component;
use GameCourse\Views\Component\ComponentType;
use GameCourse\Views\Component\CoreComponent;
use GameCourse\Views\Component\CustomComponent;
use GameCourse\Views\Event\Event;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ExpressionLanguage\ExpressionEvaluatorBase;
use GameCourse\Views\ExpressionLanguage\Node;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\Logging\AddLog;
use GameCourse\Views\Logging\DeleteLog;
use GameCourse\Views\Logging\EditLog;
use GameCourse\Views\Logging\Logging;
use GameCourse\Views\Logging\MoveLog;
use GameCourse\Views\Page\Page;
use GameCourse\Views\Template\CoreTemplate;
use GameCourse\Views\Template\CustomTemplate;
use GameCourse\Views\Template\Template;
use GameCourse\Views\Template\TemplateType;
use GameCourse\Views\Variable\Variable;
use GameCourse\Views\ViewType\ViewType;
use GameCourse\Views\Visibility\VisibilityType;
use GameCourse\User\User;
use GameCourse\User\CourseUser;
use GameCourse\Core\AuthService;
use Faker\Factory;
use GameCourse\Views\Category\Category;

/**
 * This class is responsible for handling views.
 * It holds a set of functions that deal with updating/rendering views
 * to/from the database, as well as other utility functions.
 */
class ViewHandler
{
    const TABLE_VIEW = "view";
    const TABLE_VIEW_ASPECT = "view_aspect";
    const TABLE_VIEW_PARENT = "view_parent";

    const ROOT_VIEW = [["type" => "block"]];
    const REPLACE_VIEW = [["type" => "text", "text" => "Element not found", "class" => "font-semibold text-error text-center"]];


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Setup ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Registers view types, components, page templates and editor
     * categories available in the system.
     * This is only performed once during system setup.
     *
     * @return void
     * @throws Exception
     */
    public static function setupViews()
    {
        // Register view types available
        ViewType::setupViewTypes();

        // Register view categories available
        Category::setupViewCategories();

        // Register core components
        CoreComponent::setupCoreComponents();
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- General ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a view by its ID.
     * Returns null if view doesn't exist.
     *
     * @param int $id
     * @param bool $onlyNonNull
     * @return array|null
     */
    public static function getViewById(int $id, bool $onlyNonNull = true): ?array
    {
        $view = Core::database()->select(self::TABLE_VIEW, ["id" => $id]);
        if (!$view) return null;

        $viewType = ViewType::getViewTypeById($view["type"]);
        $view = array_merge(self::parse($view), $viewType->get($id),
            ["variables" => Variable::getVariablesOfView($id)],
            ["events" => Event::getEventsOfView($id)]
        );

        if ($onlyNonNull) return array_filter($view, function ($param) { return $param !== null; });
        return $view;
    }

    /**
     * Gets all views in the system.
     * Option to get all parameters even if null.
     *
     * @param bool $onlyNonNull
     * @return array
     */
    public static function getViews(bool $onlyNonNull = true): array
    {
        $views = [];
        $viewIds = array_column(Core::database()->selectMultiple(self::TABLE_VIEW, [], "id", "id"), "id");
        foreach ($viewIds as $viewId) {
            $views[] = self::getViewById($viewId, $onlyNonNull);
        }
        return $views;
    }

    /**
     * Gets children of a view parent.
     * Option to get children's position in parent.
     *
     * @param int $parentId
     * @param bool $onlyViewRoots
     * @return array
     */
    public static function getChildrenOfView(int $parentId, bool $onlyViewRoots = true): array
    {
        $children = array_map(function ($child) {
            $child["child"] = intval($child["child"]);
            $child["position"] = intval($child["position"]);
            return $child;
        }, Core::database()->selectMultiple(self::TABLE_VIEW_PARENT, ["parent" => $parentId], "child, position", "position"));
        if ($onlyViewRoots) return array_column($children, "child");
        return $children;
    }

    /**
     * Checks whether view exists.
     *
     * @param int $viewId
     * @return bool
     */
    public static function viewExists(int $viewId): bool
    {
        return !empty(Core::database()->select(self::TABLE_VIEW, ["id" => $viewId], "id"));
    }

    /**
     * Gets all existing viewTypes from DB
     * @param bool $idsOnly
     * @return array
     */
    public static function getViewTypes(bool $idsOnly = false): array {
        return ViewType::getViewTypes($idsOnly);
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------- Manipulating views ---------------- ***/
    /*** ----------------- ( in database ) ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Inserts a new view in the database.
     *
     * @param array $view
     * @param Aspect $aspect
     * @return void
     * @throws Exception
     */
    public static function insertView(array $view, Aspect $aspect)
    {
        // Create view in database
        $viewParams = self::prepareViewParams($view);
        Core::database()->insert(self::TABLE_VIEW, $viewParams);

        // Create view of a specific type
        $viewType = ViewType::getViewTypeById($view["type"]);
        $viewType->insert($view);

        // Link to aspect
        Core::database()->insert(self::TABLE_VIEW_ASPECT, [
            "viewRoot" => $view["viewRoot"],
            "aspect" => $aspect->getId(),
            "view" => $view["id"]
        ]);

        // Insert variables
        self::insertVariablesInView($view);

        // Insert events
        self::insertEventsInView($view);
    }

    /**
     * Inserts the complete view tree in the database.
     * Returns the root.
     *
     * @param array $viewTree
     * @param int $courseId
     * @return int
     * @throws Exception
     */
    public static function insertViewTree(array $viewTree, int $courseId): int
    {
        // Translate tree into logs
        $translatedTree = self::translateViewTree($viewTree);
        $logs = $translatedTree["logs"];
        $views = $translatedTree["views"];

        // Process logs
        Logging::processLogs($logs, $views, $courseId);
        return array_values($views)[0]["viewRoot"];
    }

    /**
     * Updates an existing view in the database.
     *
     * @param array $view
     * @param Aspect $aspect
     * @return void
     * @throws Exception
     */
    public static function updateView(array $view, Aspect $aspect)
    {
        // Update view in database
        $viewParams = self::prepareViewParams($view);
        Core::database()->update(self::TABLE_VIEW, $viewParams, ["id" => $view["id"]]);

        // Update view of a specific type
        $viewType = ViewType::getViewTypeById($view["type"]);
        $viewType->update($view);

        // Update aspect
        Core::database()->update(self::TABLE_VIEW_ASPECT, [
            "aspect" => $aspect->getId(),
        ], ["viewRoot" => $view["viewRoot"], "view" => $view["id"]]);

        // Update variables
        Variable::deleteAllVariables($view["id"]);
        self::insertVariablesInView($view);

        // Update events
        Event::deleteAllEvents($view["id"]);
        self::insertEventsInView($view);
    }

    /**
     * Deletes a given view from the database.
     *
     * @param int $viewId
     * @return void
     */
    public static function deleteView(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW, ["id" => $viewId]);
    }

    /**
     * Deletes the complete view tree in the database.
     * Option to keep views linked to it (created by reference)
     * intact or to replace them by a placeholder view.
     *
     * @param int $itemId
     * @param int $viewRoot
     * @param bool $keepLinked
     * @return void
     * @throws Exception
     */
    public static function deleteViewTree(int $itemId, int $viewRoot, bool $keepLinked = true)
    {
        // Replace linked views
        ViewHandler::replaceLinkedViews($itemId, $viewRoot, $keepLinked);

        // Delete all aspects of view
        $viewsInfo = Aspect::getAspectsInfoOfView($viewRoot);
        foreach ($viewsInfo as $info) {
            // Delete aspects of children
            $children = self::getChildrenOfView($info["view"]);
            if (!empty($children)) {
                foreach ($children as $child) {
                    self::deleteViewTree($itemId, $child, $keepLinked);
                }
            }
            self::deleteView($info["view"]);
        }
    }

    /**
     * Moves a view's position in the database.
     *
     * @param int $viewRoot
     * @param array|null $from
     * @param array|null $to
     * @return void
     */
    public static function moveView(int $viewRoot, ?array $from, ?array $to)
    {
        if ($to) {
            if ($from) { // Change view parent and/or position
                Core::database()->update(self::TABLE_VIEW_PARENT, [
                    "parent" => $to["parent"],
                    "position" => $to["pos"]
                ], ["parent" => $from["parent"], "child" => $viewRoot, "position" => $from["pos"]]);

            } else { // Add view to parent
                Core::database()->insert(self::TABLE_VIEW_PARENT, [
                    "parent" => $to["parent"],
                    "child" => $viewRoot,
                    "position" => $to["pos"]
                ]);
            }

        } else if ($from) { // Remove view from parent
            Core::database()->delete(self::TABLE_VIEW_PARENT, [
                "parent" => $from["parent"],
                "child" => $viewRoot,
                "position" => $from["pos"]
            ]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Building views ------------------ ***/
    /*** ---------------- ( from database ) ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Renders a view which creates the entire view tree that has
     * the view at its root.
     * Option to build for a specific set of aspects and/or to
     * populate with data instead of expressions.
     *
     * @param int $viewRoot
     * @param array|null $sortedAspects
     * @param bool|array $populate
     *  - false --> don't populate;
     *  - true --> populate w/ mocked data;
     *  - array with params --> populate with actual data (e.g. ["course" => 1, "viewer" => 10, "user" => 20])
     * @param bool|array $aspectToMock
     *  - array with params --> e.g. ["course" => 1, "viewerRole" => 1, "userRole" => 2]
     * @return array
     * @throws Exception
     */
    public static function renderView(int $viewRoot, array $sortedAspects = null, $populate = false, $aspectToMock = null): array
    {
        $mockData = $populate && !is_array($populate);

        if ($mockData) {
            $course = Course::getCourseById($aspectToMock["course"]);

            // Create temporary viewer with viewer role
            $fakeViewer = User::getUserByEmail("viewer@not.real");
            if (!isset($fakeViewer)) {
                $fakeViewer = User::addUser("Preview's Viewer", "viewer@not.real", AuthService::FENIX, "viewer@not.real",
                    0, null, null, false, true);
                CourseUser::addCourseUser($fakeViewer->getId(), $aspectToMock["course"], $aspectToMock["viewerRole"], null, false);
            } else {
                $courseViewer = CourseUser::getCourseUserById($fakeViewer->getId(), $course);
                if (!isset($courseViewer)) {
                    CourseUser::addCourseUser($fakeViewer->getId(), $aspectToMock["course"], $aspectToMock["viewerRole"], null, false);
                } else {
                    if (isset($aspectToMock["viewerRole"])) $courseViewer->setRoles([$aspectToMock["viewerRole"]]);
                    else $courseViewer->setRoles([]);
                }
            }

            // Create temporary user with user role
            $fakeUser = User::getUserByEmail("user@not.real");
            if (!isset($fakeUser)) {
                $fakeUser = User::addUser("Preview's User", "user@not.real", AuthService::FENIX, "user@not.real",
                    1, null, null, false, true);
                CourseUser::addCourseUser($fakeUser->getId(), $aspectToMock["course"], $aspectToMock["userRole"], null, false);
            } else {
                $courseUser = CourseUser::getCourseUserById($fakeUser->getId(), $course);
                if (!isset($courseUser)) {
                    CourseUser::addCourseUser($fakeUser->getId(), $aspectToMock["course"], $aspectToMock["userRole"], null, false);
                } else {
                    if (isset($aspectToMock["userRole"])) $courseUser->setRoles([$aspectToMock["userRole"]]);
                    else $courseUser->setRoles([]);
                }
            }

            $sortedAspects = Aspect::getAspectsByViewerAndUser($aspectToMock["course"], $fakeViewer->getId(), $fakeUser->getId(), true);
        }

        // Build view tree
        $viewTree = self::buildView($viewRoot, $sortedAspects);

        // Populate with data
        if ($populate) {
            // Compile each view
            foreach ($viewTree as &$view) {
                self::compileView($view);
            }

            foreach ($viewTree as &$view) {
                self::evaluateView($view, new EvaluateVisitor(
                    $mockData ? ["course" => $aspectToMock["course"], "viewer" => $fakeViewer->getId(), "user" => $fakeUser->getId()] : $populate,
                    $mockData
                ));
            }
        }

        // Delete temporary users
        if ($mockData) {
            User::deleteUser($fakeViewer->getId());
            User::deleteUser($fakeUser->getId());
        }
        
        // If rendering for specific aspects, put in correct format
        if ($sortedAspects) {
            $nrAspects = count($viewTree);
            if ($nrAspects == 1) $viewTree = $viewTree[0];
            else if ($nrAspects > 1) throw new Exception("Should have picked only one aspect but got more.");
        }

        return $viewTree;
    }

    /**
     * Builds a view which creates the entire view tree that has
     * the view at its root.
     * Option to build for a specific set of aspects or to simplify
     * view tree by removing IDs and redundant params (e.g. for exporting).
     *
     * @param int $viewRoot
     * @param array|null $sortedAspects
     * @param bool $simplify
     * @return array
     * @throws Exception
     */
    public static function buildView(int $viewRoot, array $sortedAspects = null, bool $simplify = false): array
    {
        $viewTree = [];
        $viewsInfo = Aspect::getAspectsInfoOfView($viewRoot);

        // Filter views by aspect
        if ($sortedAspects) {
            $viewPicked = self::pickViewByAspect($viewsInfo, $sortedAspects);
            $viewsInfo = $viewPicked ? [$viewPicked] : [];
        }

        // Add views of aspect to the view tree
        foreach ($viewsInfo as $info) {
            $view = self::getViewById($info["view"]);

            // Build view of a specific type
            $viewType = ViewType::getViewTypeById($view["type"]);
            $viewType->build($view, $sortedAspects, $simplify);

            // Create param 'aspect' for view
            $viewAspect = Aspect::getAspectById($info["aspect"]);
            $viewerRoleId = $viewAspect->getViewerRoleId();
            $userRoleId = $viewAspect->getUserRoleId();
            $viewAspect = [
                "viewerRole" => $viewerRoleId ? Role::getRoleName($viewerRoleId) : null,
                "userRole" =>  $userRoleId ? Role::getRoleName($userRoleId) : null
            ];

            // Add params 'viewRoot' and 'aspect' immediately after ID
            $pos = 1;
            $view = array_slice($view, 0, $pos) + ["viewRoot" => $viewRoot] + ["aspect" => $viewAspect] + array_slice($view, $pos);

            // Simplify view tree
            if ($simplify) {
                unset($view["id"]);
                unset($view["viewRoot"]);
                if (!$viewerRoleId && !$userRoleId) unset($view["aspect"]);
                if ($view["visibilityType"] === VisibilityType::VISIBLE) unset($view["visibilityType"]);
                if (empty($view["variables"])) unset($view["variables"]);
                else $view["variables"] = array_map(function ($v) { unset($v["position"]); return $v; }, $view["variables"]);
                if (empty($view["events"])) unset($view["events"]);
            }

            $viewTree[] = $view;
        }

        return $viewTree;
    }

    /**
     * Builds a view by getting its entire view tree, as well as
     * its view trees for each of its aspects.
     *
     * @param int $viewRoot
     * @param int $courseId
     * @return array
     * @throws Exception
     */
    public static function buildViewComplete(int $viewRoot, int $courseId): array
    {
        // Get entire view tree
        $viewTree = self::buildView($viewRoot);

        // Get view tree for each aspect of view root
        $viewTreeByAspect = [];
        $aspects = Aspect::getAspectsInViewTree($viewRoot);

        $hierarchy = Role::getCourseRoles($courseId, false, true);

        foreach ($aspects as $aspect) {
            $viewerRoleName = $aspect["viewerRole"] ? Role::getRoleName($aspect["viewerRole"]) : null;
            $userRoleName = $aspect["userRole"] ? Role::getRoleName($aspect["userRole"]) : null;

            // Add aspects that are higher in hierarchy by checking roles above and all possible combinations
            $sortedAspects = [];

            $parentsOfViewer = [$viewerRoleName];
            if (isset($aspect["viewerRole"])) {
                $parentsOfViewer = array_merge($parentsOfViewer, Role::getParentNamesOfRole($hierarchy, null, $aspect["viewerRole"]));
            }
            $parentsOfViewer[] = null;

            $parentsOfUser = [$userRoleName];
            if (isset($aspect["userRole"])) {
                $parentsOfUser = array_merge($parentsOfUser, Role::getParentNamesOfRole($hierarchy, null, $aspect["userRole"]));
            }
            $parentsOfUser[] = null;

            foreach ($parentsOfUser as $userRole) {
                $userRoleId = null;
                if (isset($userRole)) $userRoleId = Role::getRoleId($userRole, $courseId);

                foreach ($parentsOfViewer as $viewerRole) {
                    $viewerRoleId = null;
                    if (isset($viewerRole)) $viewerRoleId = Role::getRoleId($viewerRole, $courseId);

                    $parentAspect = Aspect::getAspectBySpecs($courseId, $viewerRoleId, $userRoleId)->getData("id, viewerRole, userRole");
                    $sortedAspects[] = $parentAspect;
                }
            }

            // Render and associate with aspect
            $viewTreeOfAspect = self::renderView($viewRoot, $sortedAspects);
            $aspectRoles = ["viewerRole" => $viewerRoleName, "userRole" => $userRoleName];
            $viewTreeByAspect[] = ["aspect" => $aspectRoles, "view" => $viewTreeOfAspect];
        }
        return ["viewTree" => $viewTree, "viewTreeByAspect" => $viewTreeByAspect];
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Compiling views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Compiles a view which puts each of its parameters in an
     * appropriate format to be evaluated.
     *
     * @param array $view
     * @throws Exception
     */
    public static function compileView(array &$view)
    {
        // Store view information on the dictionary
        Core::dictionary()->storeView($view);

        // Compile basic parameters
        $params = ["cssId", "class", "style", "visibilityCondition", "loopData"];
        foreach ($params as $param) {
            if (isset($view[$param])) {
                // Ignore % that are not variables, e.g. 'width: 100%'
                $pattern = "/(\d+)%/";
                preg_match_all($pattern, $view[$param], $matches);
                if (!empty($matches) && count($matches) == 2) {
                    foreach ($matches[0] as $i => $v) {
                        $view[$param] = preg_replace("/$v/", $matches[1][$i] . "?", $view[$param]);
                    }
                }

                self::compileExpression($view[$param]);
            }
        }

        // Compile variables
        if (isset($view["variables"])) {
            foreach ($view["variables"] as &$variable) {
                self::compileExpression($variable["value"]);
            }
        }

        // Compile events
        if (isset($view["events"])) {
            foreach ($view["events"] as &$event) {
                self::compileExpression($event["action"]);
            }
        }

        // Compile view of a specific type
        $viewType = ViewType::getViewTypeById($view["type"]);
        $viewType->compile($view);
    }

    /**
     * Stripped-down version of the above, that only compiles
     * the fields needed for other expressions.
     *
     * @param array $view
     * @throws Exception
     */
    public static function compileReducedView(array &$view)
    {
        // Store view information on the dictionary
        Core::dictionary()->storeView($view);

        // Compile basic parameters
        if (isset($view["loopData"])) {
            // Ignore % that are not variables, e.g. 'width: 100%'
            $pattern = "/(\d+)%/";
            preg_match_all($pattern, $view["loopData"], $matches);
            if (!empty($matches) && count($matches) == 2) {
                foreach ($matches[0] as $i => $v) {
                    $view["loopData"] = preg_replace("/$v/", $matches[1][$i] . "?", $view["loopData"]);
                }
            }

            self::compileExpression($view["loopData"]);
        }

        // Compile variables
        if (isset($view["variables"])) {
            foreach ($view["variables"] as &$variable) {
                self::compileExpression($variable["value"]);
            }
        }

        if (isset($view["children"])) {
            foreach ($view["children"] as &$child) {
                self::compileReducedView($child);
            }
        }
    }

    /**
     * Compiles an expression which puts it in an appropriate format to
     * be evaluated.
     *
     * @param string $expression
     * @throws Exception
     */
    public static function compileExpression(string &$expression)
    {
        static $parser;
        if (!$parser) $parser = new ExpressionEvaluatorBase();
        $expression = !empty(trim($expression)) ? $parser->parse($expression) : new ValueNode("");
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Evaluating views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Evaluates a view which processes each of its parameters to
     * a certain value.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @throws Exception
     */
    public static function evaluateView(array &$view, EvaluateVisitor $visitor)
    {
        self::prepareViewVisitor($view, $visitor);

        // Remove invisible views
        self::evaluateVisibility($view, $visitor);
        if ($view["visibilityType"] === VisibilityType::INVISIBLE ||
            ($view["visibilityType"] === VisibilityType::CONDITIONAL && !$view["visibilityCondition"])) {
            $view = null;
            return;
        }

        // Evaluate basic parameters
        $params = ["cssId", "class", "style"];
        foreach ($params as $param) {
            if (isset($view[$param])) {
                self::evaluateNode($view[$param], $visitor);

                // Replace % that are not variables, e.g. 'width: 100%'
                $view[$param] = str_replace("?", "%", $view[$param]);
            }
        }

        // Evaluate events
        if (isset($view["events"])) {
            foreach ($view["events"] as &$event) {
                self::evaluateNode($event["action"], $visitor);
            }
        }

        // Evaluate view of a specific type
        $viewType = ViewType::getViewTypeById($view["type"]);
        $viewType->evaluate($view, $visitor);
    }

    /**
     * Stripped-down version of the above, that only evaluates
     * the fields needed for other expressions.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @throws Exception
     */
    public static function evaluateReducedView(array &$view, EvaluateVisitor $visitor)
    {
        if (isset($view["variables"])) {
            foreach ($view["variables"] as $variable) {
                $visitor->addParam($variable["name"], $variable["value"]);
            }
        }

        if (isset($view["children"])) {
            $childrenEvaluated = [];
            foreach ($view["children"] as &$child) {
                if (isset($child["loopData"])) {
                    self::evaluateReducedLoop($child, $visitor);
                    $childrenEvaluated = array_merge($childrenEvaluated, $child);

                } else {
                    self::evaluateReducedView($child, $visitor);
                    if ($child) $childrenEvaluated[] = $child;
                }
            }
            $view["children"] = $childrenEvaluated;
        }
    }

    /**
     * Evaluates a node which processes it to a certain value.
     *
     * @param Node $node
     * @param EvaluateVisitor $visitor
     */
    public static function evaluateNode(Node &$node, EvaluateVisitor $visitor)
    {
        $node = $node->accept($visitor)->getValue();
    }

    /**
     * Evaluates a loop which repeats a given view and processes
     * each of their parameters to a certain value.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @throws Exception
     */
    public static function evaluateLoop(array &$view, EvaluateVisitor $visitor)
    {
        Core::dictionary()->storeViewIdAsViewWithLoopData($view["id"]);
        self::prepareViewVisitor($view, $visitor);

        // Get collection to loop
        self::evaluateNode($view["loopData"], $visitor);
        $collection = $view["loopData"];
        unset($view["loopData"]);
        if (is_null($collection)) $collection = [];
        if (!is_array($collection)) throw new Exception("Loop data must be a collection");

        // Transform to sequential array
        $collection = array_values($collection);

        // Repeat views
        $repeatedViews = [];
        for ($i = 0; $i < count($collection); $i++) {
            // Copy view & visitor
            $newView = $view;
            $newVisitor = $visitor->copy();

            // If inner loop, replace item params
            if ($newVisitor->hasParam("item")) {
                $viewIdsWithLoopData = Core::dictionary()->getViewIdsWithLoopData();
                $newItemKey = "item" . str_repeat("N", count($viewIdsWithLoopData) - 1);
                foreach (Core::dictionary()->getView($viewIdsWithLoopData[count($viewIdsWithLoopData) - 2])["variables"] as $variable) {
                    $newValue = str_replace("%item", "%$newItemKey", $variable["value"]);
                    self::compileExpression($newValue);
                    $newVisitor->addParam($variable["name"], $newValue);
                }
                $newVisitor->addParam($newItemKey, $newVisitor->getParam("item"));
            }

            // Update visitor params with %item and %index
            $newVisitor->addParam("item", new ValueNode($collection[$i], $collection[$i]["libraryOfItem"]));
            $newVisitor->addParam("index", $i);
            Core::dictionary()->setVisitor($newVisitor);

            // Evaluate new view
            self::evaluateView($newView, $newVisitor);
            if ($newView) $repeatedViews[] = $newView;
        }
        $view = $repeatedViews;
    }

    /**
     * Stripped-down version of the above, that only evaluates
     * the fields needed for other expressions.
     * Sets item and index to the first element of the collection.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @throws Exception
     */
    public static function evaluateReducedLoop(array &$view, EvaluateVisitor $visitor)
    {
        Core::dictionary()->storeViewIdAsViewWithLoopData($view["id"]);

        if (isset($view["variables"])) {
            foreach ($view["variables"] as $variable) {
                $visitor->addParam($variable["name"], $variable["value"]);
            }
        }
        self::evaluateNode($view["loopData"], $visitor);
        $collection = $view["loopData"];
        unset($view["loopData"]);
        if (is_null($collection)) $collection = [];
        if (!is_array($collection)) throw new Exception("Loop data must be a collection");

        // Transform to sequential array
        $collection = array_values($collection);

        // If inner loop, replace item params
        if ($visitor->hasParam("item")) {
            $viewIdsWithLoopData = Core::dictionary()->getViewIdsWithLoopData();
            $newItemKey = "item" . str_repeat("N", count($viewIdsWithLoopData) - 1);
            foreach (Core::dictionary()->getView($viewIdsWithLoopData[count($viewIdsWithLoopData) - 2])["variables"] as $variable) {
                $newValue = str_replace("%item", "%$newItemKey", $variable["value"]);
                self::compileExpression($newValue);
                $visitor->addParam($variable["name"], $newValue);
            }
            $visitor->addParam($newItemKey, $visitor->getParam("item"));
        }

        // Update visitor params with %item and %index
        if (isset($collection[0])) {
            $visitor->addParam("item", new ValueNode($collection[0], $collection[0]["libraryOfItem"]));
            $visitor->addParam("index", 0);
        }

        self::evaluateReducedView($view, $visitor);
    }

    /**
     * Evaluates visibility of a given view.
     *
     * @param array $view
     * @param EvaluateVisitor $visitor
     * @return void
     * @throws Exception
     */
    private static function evaluateVisibility(array &$view, EvaluateVisitor $visitor)
    {
        if ($view["visibilityType"] === VisibilityType::CONDITIONAL) {
            if (!isset($view["visibilityCondition"]))
                throw new Exception("View has a conditional visibility type but no condition was is set.");
            self::evaluateNode($view["visibilityCondition"], $visitor);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Parses a view coming from the database to appropriate types.
     * Option to pass a specific field to parse instead.
     *
     * @param array|null $view
     * @param null $field
     * @param string|null $fieldName
     * @return array|int|null
     */
    public static function parse(array $view = null, $field = null, string $fieldName = null)
    {
        if ($view) {
            if (isset($view["id"])) $view["id"] = intval($view["id"]);
            return $view;

        } else {
            if ($fieldName == "id") return intval($field);
            return $field;
        }
    }

    /**
     * Traverses a view tree and performs a given function.
     *
     * @param array $viewTree
     * @param $func
     * @param null $parent
     * @param ...$data
     * @return void
     */
    public static function traverseViewTree(array &$viewTree, $func, &$parent = null, &...$data)
    {
        foreach ($viewTree as &$view) {
            $viewType = ViewType::getViewTypeById($view["type"]);
            $viewType->traverse($view, $func, $parent, ...$data);
        }
    }

    /**
     * Translates a view tree into logs.
     *
     * @param array $viewTree
     * @param array|null $parent
     * @param array|null $viewsDeleted
     * @return array
     */
    public static function translateViewTree(array $viewTree, array $parent = null, array $viewsDeleted = null): array
    {
        $logs = [];
        $views = [];

        // Delete views
        if (isset($viewsDeleted)) {
            foreach ($viewsDeleted as $viewId) {
                $logs[] = new DeleteLog($viewId);
            }
        }

        $viewRoot = null;   // used to set the same viewRoot for all aspects
        foreach ($viewTree as $view) {
            if (!isset($view["id"]) || $view["id"] < 0) {
                // Create a unique ID and viewRoot
                $view["id"] = hexdec(uniqid());
                $view["viewRoot"] = $viewRoot ?? $view["id"];
    
                // Add view
                $views[$view["id"]] = $view;
                $logs[] = new AddLog($view["id"], CreationMode::BY_VALUE);
            }
            else {
                // Update view
                $views[$view["id"]] = $view;
                $logs[] = new EditLog($view["id"]);
            }

            // Move view
            // WARNING: this isn't creating logs, it's moving the views immediately
            // I needed this to not lose track of the positions when moving to an already occupied position
            if (isset($parent["parent"])) {
                $occupying = Core::database()->select(self::TABLE_VIEW_PARENT, ["parent" => $parent["parent"], "position" => $parent["pos"]], "child");

                // If the position is occupied, move the occupying one out temporarily
                if (!empty($occupying) && $occupying != $view["id"]) {
                    Core::database()->executeQuery("SET @i=0");
                    $sql = "SELECT MAX(if(@i=position,@i:=position+1,@i)) FROM " . self::TABLE_VIEW_PARENT . " WHERE parent = " . $parent["parent"] . " ORDER BY position";
                    $tempPos = intval(Core::database()->executeQuery($sql)->fetchColumn());

                    self::moveView($occupying,
                        ["parent" => $parent["parent"], "pos" => $parent["pos"]],
                        ["parent" => $parent["parent"], "pos" => $tempPos]
                    );
                }

                // If this view was somewhere else previously, move it
                $prevPos = Core::database()->select(self::TABLE_VIEW_PARENT, ["child" => $view["id"]], "*");
                if (isset($prevPos)) {
                    self::moveView($view["id"],
                        ["parent" => $prevPos["parent"], "pos" => $prevPos["position"]],
                        $parent
                    );
                }
            }

            // Translate view of a specific type
            $viewType = ViewType::getViewTypeById($view["type"]);
            $viewType->translate($view, $logs, $views, $parent);

            // Update view root
            if (!$viewRoot) $viewRoot = $view["viewRoot"];
        }

        // Move view
        if (isset($parent["parent"])) {
            $where = ["parent" => $parent["parent"], "child" => $viewRoot];
            if (empty(Core::database()->select(self::TABLE_VIEW_PARENT, $where))) {
                $logs[] = new MoveLog($viewRoot, null, $parent);
            }
        }

        return ["logs" => $logs, "views" => $views];
    }

    /**
     * Replaces views linked (created by reference) to a given view.
     * Option to keep views linked intact or to replace them by a
     * placeholder view.
     *
     * @param int $itemId
     * @param int $viewRoot
     * @param bool $keepLinked
     * @return void
     * @throws Exception
     */
    public static function replaceLinkedViews(int $itemId, int $viewRoot, bool $keepLinked = true)
    {
        foreach (self::getLinkedViews($viewRoot) as $view) {
            // NOTE: ignore item to which views are linked to
            if ($view["id"] === $itemId) continue;

            if ($view["type"] === ComponentType::CORE . " component") {
                $courseId = 0;
                $table = CoreComponent::TABLE_COMPONENT;

            } else if ($view["type"] === ComponentType::CUSTOM . " component") {
                $courseId = $view["course"];
                $table = CustomComponent::TABLE_COMPONENT;

            } else if ($view["type"] === TemplateType::CORE . " template") {
                $courseId = 0;
                $table = CoreTemplate::TABLE_TEMPLATE;

            } else if ($view["type"] === TemplateType::CUSTOM . " template") {
                $courseId = $view["course"];
                $table = CustomTemplate::TABLE_TEMPLATE;

            } else {
                $courseId = $view["course"];
                $table = Page::TABLE_PAGE;
            }

            // Insert replacement into the database
            $replacement = $keepLinked ? ViewHandler::buildView($viewRoot, null, true) : ViewHandler::REPLACE_VIEW;
            $root = self::insertViewTree($replacement, $courseId);

            // Replace with new view
            Core::database()->update($table, ["viewRoot" => $root], ["id" => $view["id"]]);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** --------------------- Helpers ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    private static function prepareViewParams(array $view): array
    {
        return [
            "id" => $view["id"],
            "type" => $view["type"],
            "cssId" => $view["cssId"] ?? null,
            "class" => $view["class"] ?? null,
            "style" => $view["style"] ?? null,
            "visibilityType" => $view["visibilityType"] ?? VisibilityType::VISIBLE,
            "visibilityCondition" => $view["visibilityCondition"] ?? null,
            "loopData" => $view["loopData"] ?? null
        ];
    }

    private static function prepareViewVisitor(array $view, EvaluateVisitor $visitor)
    {
        // Add variables as visitor params
        // NOTE: needs to be 1st so that expressions that use any of the variables can be evaluated
        if (isset($view["variables"])) {
            foreach ($view["variables"] as $variable) {
                $visitor->addParam($variable["name"], $variable["value"]);
            }
        }

        // Set dictionary visitor as the current view's visitor
        Core::dictionary()->setVisitor($visitor);
    }

    /**
     * @throws Exception
     */
    private static function insertVariablesInView(array $view)
    {
        $notAllowed = ["course", "viewer", "user", "item", "index", "value", "seriesIndex", "sort"];
        $notAllowedToStartWith = ["item", "sort"];
        if (isset($view["variables"])) {
            foreach ($view["variables"] as $i => $variable) {
                $name = $variable["name"];
                if (in_array($name, $notAllowed))
                    throw new Exception("Variable with name '$name' is not allowed.");

                foreach ($notAllowedToStartWith as $startWith) {
                    if (str_starts_with($name, $startWith))
                        throw new Exception("Variable with name '$name' can't start with '$startWith'");
                }

                Variable::addVariable($view["id"], $name, $variable["value"], $i);
            }
        }
    }

    private static function insertEventsInView(array $view)
    {
        if (isset($view["events"])) {
            foreach ($view["events"] as $event) {
                Event::addEvent($view["id"], $event["type"], $event["action"]);
            }
        }
    }

    /**
     * Picks the most specific aspect available in a view root.
     * Returns null if there's no view for given aspects.
     *
     * @param array $viewsInfo
     * @param array $aspectsSortedByMostSpecific
     * @return array|null
     */
    private static function pickViewByAspect(array $viewsInfo, array $aspectsSortedByMostSpecific): ?array
    {
        foreach ($aspectsSortedByMostSpecific as $aspectInfo) {
            foreach ($viewsInfo as $info) {
                $aspect = Aspect::getAspectById($aspectInfo["id"]);
                if ($aspect->equals(Aspect::getAspectById($info["aspect"]))) return $info;
            }
        }
        return null;
    }

    /**
     * Gets views linked (created by reference) to a given view.
     *
     * NOTE: views linked are always either a component, a template or a page,
     * otherwise they couldn't have been linked.
     *
     * @param int $viewRoot
     * @return array
     */
    private static function getLinkedViews(int $viewRoot): array
    {
        // Get linked components
        $linkedComponents = Component::getComponentsByViewRoot($viewRoot);

        // Get linked templates
        $linkedTemplates = Template::getTemplatesByViewRoot($viewRoot);

        // Get linked pages
        $linkedPages = array_map(function ($view) {
            $view["type"] = "page";
            return $view;
        }, Page::getPagesByViewRoot($viewRoot));

        return array_merge($linkedComponents, $linkedTemplates, $linkedPages);
    }
}