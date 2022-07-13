<?php
namespace GameCourse\Views;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Event\Event;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ExpressionLanguage\ExpressionEvaluatorBase;
use GameCourse\Views\ExpressionLanguage\Node;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\Variable\Variable;
use GameCourse\Views\ViewType\ViewType;
use GameCourse\Views\Visibility\VisibilityType;

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

        // TODO: register core components
        // TODO: register page templates
        // TODO: regoster editor categories
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
            ["events" => Event::getEventsOfView($id)]);

        if ($onlyNonNull) return array_filter($view, function ($param) { return $param != null; });
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
        $children = Core::database()->selectMultiple(self::TABLE_VIEW_PARENT, ["parent" => $parentId], "child, position", "position");
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
     * Updates an existing view in the database.
     *
     * @param array $view
     * @param Aspect $aspect
     * @return void
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
    /*** ----------------- Rendering views ------------------ ***/
    /*** ---------------- ( from database ) ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Builds a view which creates the entire view tree that has
     * the view at its root.
     * Option to build for a specific set of aspects and/or to populate
     * with actual data instead of expressions.
     *
     * @param int $viewRoot
     * @param array|null $sortedAspects
     * @param bool|array $populate --> (false or array with params e.g. ["course" => 1, "viewer" => 10, "user" => 20])
     * @return array
     * @throws Exception
     */
    public static function buildView(int $viewRoot, array $sortedAspects = null, $populate = false): array
    {
        $viewTree = [];

        // Filter views by aspect
        $viewsInfo = Core::database()->selectMultiple(self::TABLE_VIEW_ASPECT, ["viewRoot" => $viewRoot], "aspect, view", "aspect");
        if ($sortedAspects) $viewsInfo = [self::pickViewByAspect($viewsInfo, $sortedAspects)];

        // Add views of aspect to the view tree
        foreach ($viewsInfo as $info) {
            $view = self::getViewById($info["view"]);

            // Build view of a specific type
            $viewType = ViewType::getViewTypeById($view["type"]);
            $viewType->build($view, $sortedAspects, $populate);

            // Create param 'aspect' for view
            $viewAspect = Aspect::getAspectById($info["aspect"]);
            $viewerRoleId = $viewAspect->getViewerRoleId();
            $userRoleId = $viewAspect->getUserRoleId();
            $viewAspect = [
                "viewerRole" => $viewerRoleId ? Role::getRoleName($viewerRoleId) : null,
                "userRole" =>  $userRoleId ? Role::getRoleName($userRoleId) : null
            ];

            // Add params 'viewRoot' and 'aspect'
            $pos = 1;
            $view = array_slice($view, 0, $pos) + ["viewRoot" => $viewRoot] + ["aspect" => $viewAspect] + array_slice($view, $pos);

            // Populate with data
            if ($populate) {
                self::compileView($view);
                self::evaluateView($view, new EvaluateVisitor($populate));
            }

            $viewTree[] = $view;
        }

        return $viewTree;
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
        // Compile basic parameters
        $params = ["cssId", "class", "style", "visibilityCondition", "loopData"];
        foreach ($params as $param) {
            if (isset($view[$param])) self::compileExpression($view[$param]);
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
        // Evaluate variables & add them to visitor params
        // NOTE: needs to be 1st so that expressions that use any of the variables can be evaluated
        if (isset($view["variables"])) {
            foreach ($view["variables"] as &$variable) {
                self::evaluateNode($variable["value"], $visitor);
                $visitor->addParam($variable["name"], $variable["value"]);
            }
        }

        // Evaluate basic parameters
        $params = ["cssId", "class", "style", "visibilityCondition", "loopData"];
        foreach ($params as $param) {
            if (isset($view[$param])) self::evaluateNode($view[$param], $visitor);
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
        // Get collection to loop
        self::evaluateNode($view["loopData"], $visitor);
        $collection = $view["loopData"];
        if (is_null($collection)) $collection = [];
        if (!is_array($collection)) throw new Exception("Loop data must be a collection");

        // Transform to sequential array
        $collection = array_values($collection);

        // Format items with a key
        $key = "item";
        $items = array_map(function ($item) use ($key) { return [$key => $item]; }, $collection);

        // Repeat views
        $repeatedViews = [];
        for ($i = 0; $i < count($items); $i++) {
            // Copy view
            $newView = $view;

            // Update visitor params with %item and %index
            $visitor->addParam($key, $items[$i][$key]);
            $visitor->addParam("index", $i);

            // Evaluate new view
            self::evaluateView($newView, $visitor);
            unset($newView["loopData"]);
            $repeatedViews[] = $newView;
        }
        $view = $repeatedViews;
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

    private static function insertVariablesInView(array $view)
    {
        if (isset($view["variables"])) {
            foreach ($view["variables"] as $variable) {
                Variable::addVariable($view["id"], $variable["name"], $variable["value"], $variable["position"]);
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
     *
     * @param array $viewsInfo
     * @param array $aspectsSortedByMostSpecific
     * @return array
     * @throws Exception
     */
    private static function pickViewByAspect(array $viewsInfo, array $aspectsSortedByMostSpecific): array
    {
        foreach ($aspectsSortedByMostSpecific as $aspect) {
            foreach ($viewsInfo as $info) {
                if ($aspect->equals(Aspect::getAspectById($info["aspect"]))) return $info;
            }
        }
        throw new Exception("There's no view to pick for current aspects.");
    }
}
