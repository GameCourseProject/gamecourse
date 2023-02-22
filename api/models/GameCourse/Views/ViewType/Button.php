<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Button view type, which represents a core view for
 * button elements.
 */
class Button extends ViewType
{
    const TABLE_VIEW_BUTTON = "view_button";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "button";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays a button.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_BUTTON . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                text                        TEXT NOT NULL,
                color                       TEXT DEFAULT NULL,
                icon                        TEXT DEFAULT NULL,

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_BUTTON . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_BUTTON, ["id" => $viewId], "text, color, icon"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_BUTTON, [
            "id" => $view["id"],
            "text" => $view["text"],
            "color" => $view["color"] ?? null,
            "icon" => $view["icon"] ?? null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_BUTTON, [
            "id" => $view["id"],
            "text" => $view["text"],
            "color" => $view["color"] ?? null,
            "icon" => $view["icon"] ?? null,
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_BUTTON, ["id" => $viewId]);
    }

    public function build(array &$view, array $sortedAspects = null, bool $simplify = false)
    {
        // Simplify view icon
        if ($simplify) {
            if (isset($view["color"]) && !$view["color"]) unset($view["color"]);
            if (isset($view["icon"]) && !$view["icon"]) unset($view["icon"]);
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
        if (isset($view["color"])) ViewHandler::compileExpression($view["color"]);
        if (isset($view["icon"])) ViewHandler::compileExpression($view["icon"]);
        ViewHandler::compileExpression($view["text"]);
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        if (isset($view["color"])) ViewHandler::evaluateNode($view["color"], $visitor);
        if (isset($view["icon"])) ViewHandler::evaluateNode($view["icon"], $visitor);
        ViewHandler::evaluateNode($view["text"], $visitor);
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