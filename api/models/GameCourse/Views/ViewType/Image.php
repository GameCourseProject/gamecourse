<?php
namespace GameCourse\Views\ViewType;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ViewHandler;

/**
 * This is the Image view type, which represents a core view for
 * visual elements.
 */
class Image extends ViewType
{
    const TABLE_VIEW_IMAGE = "view_image";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "image";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays either simple static visual elements or more complex ones built using expressions.";


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
            CREATE TABLE IF NOT EXISTS " . self::TABLE_VIEW_IMAGE . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                src                         TEXT NOT NULL,
                link                        TEXT,

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_IMAGE . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_IMAGE, ["id" => $viewId], "src, link"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_IMAGE, [
            "id" => $view["id"],
            "src" => $view["src"],
            "link" => $view["link"] ?? null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_IMAGE, [
            "src" => $view["src"],
            "link" => $view["link"] ?? null
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_IMAGE, ["id" => $viewId]);
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

    /**
     * @throws Exception
     */
    public function compile(array &$view)
    {
        if (isset($view["link"])) ViewHandler::compileExpression($view["link"]);
        ViewHandler::compileExpression($view["src"]);
    }

    public function evaluate(array &$view, EvaluateVisitor $visitor)
    {
        if (isset($view["link"])) ViewHandler::evaluateNode($view["link"], $visitor);
        ViewHandler::evaluateNode($view["src"], $visitor);
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