<?php
namespace GameCourse\Views\ViewType;

use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Block view type, which represents a core view that
 * can contain other views.
 */
class Block extends ViewType
{
    const TABLE_VIEW_BLOCK = "view_block";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "block";  // NOTE: must match the name of the class
    const DESCRIPTION = "Wrapper element that can contain other elements either in an horizontal or vertical order.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_BLOCK . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                direction                   ENUM ('vertical', 'horizontal') DEFAULT 'vertical',

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_BLOCK . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return ["direction" => Core::database()->select(self::TABLE_VIEW_BLOCK, ["id" => $viewId], "direction")];
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_BLOCK, [
            "id" => $view["id"],
            "direction" => $view["direction"] ?? "vertical"
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_BLOCK, [
            "direction" => $view["direction"] ?? "vertical"
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_BLOCK, ["id" => $viewId]);
    }

    public function build(array &$view, array $sortedAspects = null)
    {
        $children = ViewHandler::getChildrenOfView($view["id"]);
        if (!empty($children)) {
            foreach ($children as &$child) {
                $child = ViewHandler::buildView($child, $sortedAspects);
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

    public function compile(array &$view)
    {
        if (isset($view["children"])) {
            foreach ($view["children"] as &$vr) {
                foreach ($vr as &$child) {
                    ViewHandler::compileView($child);
                }
            }
        }
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        if (isset($view["children"])) {
            $childrenEvaluated = [];
            foreach ($view["children"] as &$vr) {
                foreach ($vr as &$child) {
                    if (isset($child["loopData"])) {
                        ViewHandler::evaluateLoop($child, $visitor);
                        $childrenEvaluated = array_merge($childrenEvaluated, $child);

                    } else {
                        ViewHandler::evaluateView($child, $visitor);
                        $childrenEvaluated[] = $child;
                    }
                }
            }
            $view["children"] = $childrenEvaluated;
        }
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