<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

/**
 * This is the Table view type, which represents a core view for
 * information to be displayed in a table format. Its immediate
 * children must be rows.
 */
class Table extends ViewType
{
    const TABLE_VIEW_TABLE = "view_table";

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
        $this->initDatabase();
    }

    protected function initDatabase()
    {
        Core::database()->executeQuery("
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_TABLE . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                footers                     boolean NOT NULL DEFAULT TRUE,
                searching                   boolean NOT NULL DEFAULT TRUE,
                columnFiltering             boolean NOT NULL DEFAULT TRUE,
                paging                      boolean NOT NULL DEFAULT TRUE,
                lengthChange                boolean NOT NULL DEFAULT TRUE,
                info                        boolean NOT NULL DEFAULT TRUE,
                ordering                    boolean NOT NULL DEFAULT TRUE,

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_TABLE . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_TABLE, ["id" => $viewId], "footers, searching, columnFiltering, paging, lengthChange, info, ordering"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_TABLE, [
            "id" => $view["id"],
            "footers" => $view["footers"] ?? true,
            "searching" => $view["searching"] ?? true,
            "columnFiltering" => $view["columnFiltering"] ?? true,
            "paging" => $view["paging"] ?? true,
            "lengthChange" => $view["lengthChange"] ?? true,
            "info" => $view["info"] ?? true,
            "ordering" => $view["ordering"] ?? true
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_TABLE, [
            "footers" => $view["footers"] ?? true,
            "searching" => $view["searching"] ?? true,
            "columnFiltering" => $view["columnFiltering"] ?? true,
            "paging" => $view["paging"] ?? true,
            "lengthChange" => $view["lengthChange"] ?? true,
            "info" => $view["info"] ?? true,
            "ordering" => $view["ordering"] ?? true
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_TABLE, ["id" => $viewId]);
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

        // Simplify view table
        if ($simplify) {
            if (isset($view["footers"]) && $view["footers"]) unset($view["footers"]);
            if (isset($view["searching"]) && $view["searching"]) unset($view["searching"]);
            if (isset($view["columnFiltering"]) && $view["columnFiltering"]) unset($view["columnFiltering"]);
            if (isset($view["paging"]) && $view["paging"]) unset($view["paging"]);
            if (isset($view["lengthChange"]) && $view["lengthChange"]) unset($view["lengthChange"]);
            if (isset($view["info"]) && $view["info"]) unset($view["info"]);
            if (isset($view["ordering"]) && $view["ordering"]) unset($view["ordering"]);
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
        $boolValues = ["footers", "searching", "columnFiltering", "paging", "lengthChange", "info", "ordering"];
        return Utils::parse(["bool" => $boolValues], $view, $field, $fieldName);
    }
}