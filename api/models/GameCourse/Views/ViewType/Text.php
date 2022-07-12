<?php
namespace GameCourse\Views\ViewType;

use GameCourse\Core\Core;

/**
 * This is the Text view type, which represents a core view for
 * written information.
 */
class Text extends ViewType
{
    const TABLE_VIEW_TEXT = "view_text";

    public function __construct()
    {
        $this->id = self::ID;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "text";  // NOTE: must match the name of the class
    const DESCRIPTION = "Displays either simple static written elements or more complex ones built using expressions.";


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
            CREATE TABLE " . self::TABLE_VIEW_TEXT . "(
                id                          bigint unsigned NOT NULL PRIMARY KEY,
                text                        varchar(500) NOT NULL,
                link                        varchar(200),

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
        Core::database()->executeQuery("DROP TABLE IF EXISTS " . self::TABLE_VIEW_TEXT . ";");
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ View Handling ------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function get(int $viewId): array
    {
        return self::parse(Core::database()->select(self::TABLE_VIEW_TEXT, ["id" => $viewId], "text, link"));
    }

    public function insert(array $view)
    {
        Core::database()->insert(self::TABLE_VIEW_TEXT, [
            "id" => $view["id"],
            "text" => $view["text"],
            "link" => $view["link"] ?? null
        ]);
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_TEXT, [
            "text" => $view["text"],
            "link" => $view["link"] ?? null
        ], ["id" => $view["id"]]);
    }

    public function delete(int $viewId)
    {
        Core::database()->delete(self::TABLE_VIEW_TEXT, ["id" => $viewId]);
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------------- Dictionary -------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function dissect(array &$view)
    {
        // TODO: Implement parse() method.
    }

    public function process(array &$view)
    {
        // TODO: Implement process() method.
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