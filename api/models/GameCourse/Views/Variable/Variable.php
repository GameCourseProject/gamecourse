<?php
namespace GameCourse\Views\Variable;

use GameCourse\Core\Core;

/**
 * This is the Variable model, which implements the necessary methods
 * to interact with view variables in the MySQL database.
 */
class Variable
{
    const TABLE_VARIABLE = "view_variable";

    protected $viewId;
    protected $name;
    protected $value;

    public function __construct(int $viewId, string $name, string $value)
    {
        $this->viewId = $viewId;
        $this->name = $name;
        $this->value = $value;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Getters --------------------- ***/
    /*** ---------------------------------------------------- ***/

    public function getViewId(): int
    {
        return $this->viewId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- General --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets a view variable by its name.
     * Returns null if variable doesn't exist.
     *
     * @param int $viewId
     * @param string $name
     * @return Variable|null
     */
    public static function getVariableByName(int $viewId, string $name): ?Variable
    {
        $data = Core::database()->select(self::TABLE_VARIABLE, ["view" => $viewId, "name" => $name]);
        if ($data) return new Variable($viewId, $name, $data["value"]);
        else return null;
    }

    /**
     * Gets variables available in a view.
     *
     * @param int $viewId
     * @return array
     */
    public static function getVariablesOfView(int $viewId): array
    {
        return Core::database()->selectMultiple(self::TABLE_VARIABLE, ["view" => $viewId], "name, value");
    }


    /*** ---------------------------------------------------- ***/
    /*** -------------- Variable Manipulation --------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Adds a variable to the database.
     * Returns the newly created variable.
     *
     * @param int $viewId
     * @param string $name
     * @param string $value
     * @return Variable
     */
    public static function addVariable(int $viewId, string $name, string $value): Variable
    {
        Core::database()->insert(self::TABLE_VARIABLE, [
            "view" => $viewId,
            "name" => $name,
            "value" => $value
        ]);
        return new Variable($viewId, $name, $value);
    }

    /**
     * Deletes a variable from the database.
     *
     * @param int $viewId
     * @param string $name
     * @return void
     */
    public static function deleteVariable(int $viewId, string $name)
    {
        Core::database()->delete(self::TABLE_VARIABLE, ["view" => $viewId, "name" => $name]);
    }

    /**
     * Deletes all variables of a given view from the database.
     *
     * @param int $viewId
     * @return void
     */
    public static function deleteAllVariables(int $viewId)
    {
        Core::database()->delete(self::TABLE_VARIABLE, ["view" => $viewId]);
    }
}