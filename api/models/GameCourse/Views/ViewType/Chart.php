<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ExpressionLanguage\Node;
use GameCourse\Views\ViewHandler;

/**
 * This is the Chart view type, which represents a core view for
 * displaying different types of charts.
 */
class Chart extends ViewType
{
    const TABLE_VIEW_CHART = "view_chart";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "chart";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays various types of charts.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_CHART . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                chartType                   ENUM ('bar', 'combo', 'line', 'progress', 'radar') NOT NULL,
                data                        TEXT NOT NULL,
                options                     TEXT DEFAULT NULL,

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_CHART . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_CHART, ["id" => $viewId], "chartType, data, options"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_CHART, [
            "id" => $view["id"],
            "chartType" => $view["chartType"],
            "data" => is_array($view["data"]) ? json_encode($view["data"]) : $view["data"],
            "options" => isset($view["options"]) ? json_encode($view["options"]) : null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_CHART, [
            "chartType" => $view["chartType"],
            "data" => is_array($view["data"]) ? json_encode($view["data"]) : $view["data"],
            "options" => isset($view["options"]) ? json_encode($view["options"]) : null
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_CHART, ["id" => $viewId]);
    }

    public function build(array &$view, array $sortedAspects = null, bool $simplify = false)
    {
        // Simplify view chart
        if ($simplify) {
            if (isset($view["options"]) && empty($view["options"])) unset($view["options"]);
        }
    }

    public function translate(array $view, array &$logs, array &$views, array $parent = null)
    {
        // Nothing to do here
    }

    public function traverse(array &$view, $func, &$parent, &...$data)
    {
        $func($view, $parent, ...$data);
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @throws Exception
     */
    public function compile(array &$view)
    {
        $params = ["data", "options"];
        foreach ($params as $param) {
            if (!isset($view[$param])) continue;
            $this->traverseParam($view[$param], function (&$param) {
                if (is_string($param)) ViewHandler::compileExpression($param);
            });
        }
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        $params = ["data", "options"];
        foreach ($params as $param) {
            if (!isset($view[$param])) continue;
            $this->traverseParam($view[$param], function (&$param, EvaluateVisitor $visitor) {
                if ($param instanceof Node) ViewHandler::evaluateNode($param, $visitor);
            }, $visitor);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function parse(array $view = null, $field = null, string $fieldName = null)
    {
        if ($view) {
            $view["data"] = json_decode($view["data"], true) ?? $view["data"];
            if (isset($view["options"])) $view["options"] = json_decode($view["options"], true);
            return $view;

        } else {
            if ($fieldName == "data") return json_decode($field, true) ?? $field;
            if ($fieldName == "options") return json_decode($field, true);
            return $field;
        }
    }

    /**
     * Traverses a chart param and performs a given function.
     *
     * @param $param
     * @param $func
     * @param ...$data
     * @return void
     */
    private function traverseParam(&$param, $func, &...$data)
    {
        if (is_array($param)) {
            foreach ($param as &$value) {
                $this->traverseParam($value, $func, ...$data);
            }
        } else $func($param, ...$data);
    }
}