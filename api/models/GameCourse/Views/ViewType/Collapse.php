<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Collapse view type, which represents a core view that
 * can hide or show other views.
 */
class Collapse extends ViewType
{
    const TABLE_VIEW_COLLAPSE = "view_collapse";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "collapse";  // NOTE: must match the name of the class
    const DESCRIPTION = "Element that can hide or show other elements on demand.";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        $this->initDatabase();
    }

    protected function initDatabase()
    {
        Core::database()->executeQuery("
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_COLLAPSE . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                icon                        ENUM ('arrow', 'plus') DEFAULT NULL,

                FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
            );
        ");
    }

    public function end()
    {
        $this->cleanDatabase();
    }

    protected function cleanDatabase()
    {
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_COLLAPSE . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return ["icon" => Core::database()->select(self::TABLE_VIEW_COLLAPSE, ["id" => $viewId], "icon")];
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_COLLAPSE, [
            "id" => $view["id"],
            "icon" => $view["icon"] ?? null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_COLLAPSE, [
            "icon" => $view["icon"] ?? null
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_COLLAPSE, ["id" => $viewId]);
    }

    /**
     * @throws Exception
     */
    public function build(array &$view, array $sortedAspects = null, bool $simplify = false)
    {
        // NOTE: can only have two children - header and content
        $children = ViewHandler::getChildrenOfView($view["id"]);
        if (!empty($children)) {
            foreach ($children as &$child) {
                $child = ViewHandler::buildView($child, $sortedAspects, $simplify);
                if (!empty($child)) $view["children"][] = $child;
            }
        }

        // Simplify view collapse
        if ($simplify) {
            if (isset($view["icon"]) && !$view["icon"]) unset($view["icon"]);
        }
    }

    public function translate(array $view, array &$logs, array &$views, array $parent = null)
    {
        // NOTE: can only have two children - header and content
        if (isset($view["children"])) {
            for ($i = 0; $i < count($view["children"]); $i++) {
                $child = $view["children"][$i];
                $translatedTree = ViewHandler::translateViewTree($child, ["parent" => $view["id"], "pos" => $i]);
                $logs = array_merge($logs, $translatedTree["logs"]);
                $views += $translatedTree["views"];
            }
        }
    }

    public function traverse(array &$view, $func, &$parent, &...$data)
    {
        // NOTE: can only have two children - header and content
        $func($view, $parent, ...$data);
        if (isset($view["children"])) {
            foreach ($view["children"] as &$child) {
                ViewHandler::traverseViewTree($child, $func, $view,...$data);
            }
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function compile(array &$view)
    {
        // NOTE: can only have two children - header and content
        $this->compileChildren($view);
    }

    /**
     * @throws Exception
     */
    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        // NOTE: can only have two children - header and content
        $this->evaluateChildren($view, $visitor);
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function parse(array $view = null, $field = null, string $fieldName = null)
    {
        if ($view) return $view;
        else return $field;
    }
}