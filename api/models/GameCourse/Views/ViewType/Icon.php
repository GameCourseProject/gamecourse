<?php
namespace GameCourse\Views\ViewType;

use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Icon view type, which represents a core view for
 * icon elements.
 */
class Icon extends ViewType
{
    const TABLE_VIEW_ICON = "view_icon";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "icon";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays an icon.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_ICON . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                icon                        varchar(25) NOT NULL,
                size                        varchar(10),

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_ICON . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_ICON, ["id" => $viewId], "icon, size"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_ICON, [
            "id" => $view["id"],
            "icon" => $view["icon"],
            "size" => $view["size"] ?? null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_ICON, [
            "icon" => $view["icon"],
            "size" => $view["size"] ?? null
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_ICON, ["id" => $viewId]);
    }

    public function build(array &$view, array $sortedAspects = null)
    {
        // Nothing to do here
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
        if (isset($view["size"])) ViewHandler::compileExpression($view["size"]);
        ViewHandler::compileExpression($view["icon"]);
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        if (isset($view["size"])) ViewHandler::evaluateNode($view["size"], $visitor);
        ViewHandler::evaluateNode($view["icon"], $visitor);
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