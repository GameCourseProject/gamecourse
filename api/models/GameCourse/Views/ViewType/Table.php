<?php
namespace GameCourse\Views\ViewType;

use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Table view type, which represents a core view for
 * information to be displayed in a table format. Its immediate
 * children must be rows.
 */
class Table extends ViewType
{
    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "table";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays a table with columns and rows. Can display a variable number of headers as well.";


    /*** ----------------------------------------------- ***/
    /*** -------------------- Setup -------------------- ***/
    /*** ----------------------------------------------- ***/

    public function init()
    {
        // Nothing to do here
    }

    public function end()
    {
        // Nothing to do here
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        // Nothing to do here
        return [];
    }

    public function insert(array $view)
    {
        // Nothing to do here
    }

    public function update(array $view)
    {
        // Nothing to do here
    }

    public function delete(int $viewId)
    {
        // Nothing to do here
    }

    public function build(array &$view, array $sortedAspects = null, $populate = false)
    {
        $children = ViewHandler::getChildrenOfView($view["id"]);
        if (!empty($children)) {
            foreach ($children as &$child) {
                $child = ViewHandler::buildView($child, $sortedAspects, $populate);
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