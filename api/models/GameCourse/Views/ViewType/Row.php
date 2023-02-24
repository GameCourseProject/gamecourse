<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Row view type, which represents a core view that
 * can contain other views in a horizontal order.
 *
 * It's similar to the Block view type but alike blocks, rows
 * can only be used inside tables as immediate children.
 */
class Row extends ViewType
{
    const TABLE_VIEW_ROW = "view_row";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "row";  // NOTE: must match the name of the class
    const DESCRIPTION = "Wrapper element that can contain other elements in an horizontal order. Can only be used inside tables.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_ROW . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                rowType                     ENUM ('header', 'body') DEFAULT 'body',

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_ROW . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return ["rowType" => Core::database()->select(self::TABLE_VIEW_ROW, ["id" => $viewId], "rowType")];
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_ROW, [
            "id" => $view["id"],
            "rowType" => $view["rowType"] ?? "body"
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_ROW, [
            "rowType" => $view["rowType"] ?? "body"
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_ROW, ["id" => $viewId]);
    }

    /**
     * @throws Exception
     */
    public function build(array &$view, array $sortedAspects = null, bool $simplify = false)
    {
        $children = ViewHandler::getChildrenOfView($view["id"]);
        if (!empty($children)) {
            foreach ($children as &$child) {
                $child = ViewHandler::buildView($child, $sortedAspects, $simplify);
                if (!empty($child)) $view["children"][] = $child;
            }
        }
    }

    public function translate(array $view, array &$logs, array &$views, array $parent = null)
    {
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
        $this->compileChildren($view);
    }

    /**
     * @throws Exception
     */
    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
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