<?php
namespace GameCourse\Views\ViewType;

use GameCourse\Core\Core;

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
            CREATE TABLE " . self::TABLE_VIEW_BLOCK . "(
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

        if (isset($view["children"])) {
            // TODO
        }
    }

    public function update(array $view)
    {
        Core::database()->update(self::TABLE_VIEW_BLOCK, [
            "direction" => $view["direction"] ?? "vertical"
        ], ["id" => $view["id"]]);

        if (isset($view["children"])) {
            // TODO
        }
    }

    public function delete(int $viewId)
    {
        // TODO
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