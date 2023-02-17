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

    private $providers; // FIXME: needs refactoring - make charts more customizable

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
        $this->initProviders();
    }

    protected function initDatabase()
    {
        Core::database()->executeQuery("
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_CHART . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                chartType                   ENUM ('bar', 'line', 'radar', 'progress'),
                info                        varchar(500),

                FOREIGN key(id) REFERENCES view(id) ON DELETE CASCADE
            );
        ");
    }

    private function initProviders()
    {
        $this->registerProvider("starPlot", function (array &$view, EvaluateVisitor $visitor) {
            if ($visitor->mockData()) {
                // TODO

            } else {
                // TODO
            }
        });

        $this->registerProvider("xpEvolution", function (array &$view, EvaluateVisitor $visitor) {
            if ($visitor->mockData()) {
                // TODO

            } else {
                // TODO
            }
        });

        $this->registerProvider("leaderboardEvolution", function (array &$view, EvaluateVisitor $visitor) {
            if ($visitor->mockData()) {
                // TODO

            } else {
                // TODO
            }
        });

        $this->registerProvider("xpWorld", function (array &$view, EvaluateVisitor $visitor) {
            if ($visitor->mockData()) {
                // TODO

            } else {
                // TODO
            }
        });

        $this->registerProvider("badgeWorld", function (array &$view, EvaluateVisitor $visitor) {
            if ($visitor->mockData()) {
                // TODO

            } else {
                // TODO
            }
        });

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
        return self::parse(Core::database()->select(self::TABLE_VIEW_CHART, ["id" => $viewId], "chartType, info"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_CHART, [
            "id" => $view["id"],
            "chartType" => $view["chartType"],
            "info" => json_encode($view["info"])
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_CHART, [
            "chartType" => $view["chartType"],
            "info" => json_encode($view["info"])
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

    public function compile(array &$view)
    {
        if ($view["chartType"] == "progress") {
            ViewHandler::compileExpression($view["info"]["value"]);
            ViewHandler::compileExpression($view["info"]["max"]);
        }
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        if ($view["chartType"] == "progress") {
            ViewHandler::evaluateNode($view["info"]["value"], $visitor);
            ViewHandler::evaluateNode($view["info"]["max"], $visitor);

        } else if (!empty($view["info"]["provider"])) {
            $evaluateFunc = $this->providers[$view["info"]["provider"]];
            $evaluateFunc($view, $visitor);
        }
    }


    /*** ---------------------------------------------------- ***/
    /*** ----------------------- Utils ---------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function parse(array $view = null, $field = null, string $fieldName = null)
    {
        if ($view) {
            if (isset($view["info"])) $view["info"] = json_decode($view["info"], true);
            return $view;

        } else {
            if ($fieldName == "info") return json_decode($field, true);
            return $field;
        }
    }

    private function registerProvider(string $name, $evaluateFunc) {
        $this->providers[$name] = $evaluateFunc;
    }
}