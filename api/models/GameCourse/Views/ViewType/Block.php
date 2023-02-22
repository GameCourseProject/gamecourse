<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

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
                columns                     int unsigned DEFAULT NULL,
                responsive                  boolean NOT NULL DEFAULT TRUE,

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
        return self::parse(Core::database()->select(self::TABLE_VIEW_BLOCK, ["id" => $viewId], "direction, columns, responsive"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_BLOCK, [
            "id" => $view["id"],
            "direction" => $view["direction"] ?? "vertical",
            "columns" => $view["columns"] ?? null,
            "responsive" => +($view["responsive"] ?? true)
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_BLOCK, [
            "direction" => $view["direction"] ?? "vertical",
            "columns" => $view["columns"] ?? null,
            "responsive" => +($view["responsive"] ?? true)
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_BLOCK, ["id" => $viewId]);
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

        // Simplify view block
        if ($simplify) {
            if (isset($view["direction"]) && $view["direction"] === "vertical") unset($view["direction"]);
            if (isset($view["columns"]) && !$view["columns"]) unset($view["columns"]);
            if (isset($view["responsive"]) && $view["responsive"]) unset($view["responsive"]);
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
        $intValues = ["columns"];
        $boolValues = ["responsive"];
        return Utils::parse(["int" => $intValues, "bool" => $boolValues], $view, $field, $fieldName);
    }
}