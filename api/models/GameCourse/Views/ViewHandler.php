<?php
namespace GameCourse\Views;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Role\Role;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\Event\Event;
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
     * @return array|null
     */
    public static function getViewById(int $id): ?array
    {
        $view = Core::database()->select(self::TABLE_VIEW, ["id" => $id]);
        if (!$view) return null;
        $viewType = ViewType::getViewTypeById($view["type"]);
        return array_merge(self::parse($view), $viewType->get($id),
            ["variables" => Variable::getVariablesOfView($id)],
            ["events" => Event::getEventsOfView($id)]);
    }

    /**
     * Gets all views in the system.
     *
     * @return array
     */
    public static function getViews(): array
    {
        $views = Core::database()->selectMultiple(self::TABLE_VIEW, [], "*", "id");
        foreach ($views as &$view) {
            $view = array_merge(self::parse($view), self::getViewById($view["id"]));
        }
        return $views;
    }

    /**
     * Get a view's aspect.
     *
     * @param array $view
     * @param int $courseId
     * @return Aspect
     * @throws Exception
     */
    public static function getViewAspect(array $view, int $courseId): Aspect
    {
        $viewerRole = isset($view["aspect"]) ? $view["aspect"]["viewerRole"] ?? null : null;
        $userRole = isset($view["aspect"]) ? $view["aspect"]["userRole"] ?? null : null;
        $aspect = Aspect::getAspectBySpecs($courseId,
            $viewerRole ? Role::getRoleId($viewerRole, $courseId) : null,
            $userRole ? Role::getRoleId($userRole, $courseId) : null);

        if (!$aspect)
            throw new Exception("No aspect with vr = '" . $viewerRole . "' & ur = '" . $userRole . "' found for course with ID = " . $courseId . ".");

        return $aspect;
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
     * Moves a view's position.
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
            foreach ($view["variables"] as $name => $value) {
                Variable::addVariable($view["id"], $name, $value);
            }
        }
    }

    private static function insertEventsInView(array $view)
    {
        if (isset($view["events"])) {
            foreach ($view["events"] as $type => $action) {
                Event::addEvent($view["id"], $type, $action);
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Rendering views ------------------ ***/
    /*** ---------------- ( from database ) ----------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Builds a view to be rendered, either on a page or on editor.
     *
     * @param int $viewId
     * @param Course $course
     * @param bool $edit
     */
    public static function renderView(int $viewId, Course $course, bool $edit = false)
    {
        // TODO


//        // Pick a specific aspect and build it
//        self::buildView($view, false, $rolesHierarchy, $edit);
//        if (count($view) == 1) $view = $view[0];
//        else if (count($view) == 0) {
//            if (!$edit) API::error('There\'s no aspect to render for current view and roles.');
//            else $view = null;
//        }
//        else if (count($view) > 1) API::error('Should have only one aspect but got more.');
//
//        if (!$edit) {
//            // Populate view with actual data, not just view specifications.
//            self::parseView($view);
//            var_dump($viewParams);
//            self::processView($view, new EvaluateVisitor($viewParams));
//        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------- Parsing views ------------------ ***/
    /*** ---------------------------------------------------- ***/

    // TODO


    /*** ---------------------------------------------------- ***/
    /*** ----------------- Processing views ----------------- ***/
    /*** ---------------------------------------------------- ***/

    // TODO


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
}
