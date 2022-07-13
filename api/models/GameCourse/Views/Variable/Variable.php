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
    protected $position;

    public function __construct(int $viewId, string $name, string $value, int $position)
    {
        $this->viewId = $viewId;
        $this->name = $name;
        $this->value = $value;
        $this->position = $position;
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

    public function getPosition(): ?int
    {
        return $this->position;
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
        if ($data) return new Variable($viewId, $name, $data["value"], $data["position"]);
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
        return Core::database()->selectMultiple(self::TABLE_VARIABLE, ["view" => $viewId], "name, value, position", "position");
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
     * @param int $position
     * @return Variable
     */
    public static function addVariable(int $viewId, string $name, string $value, int $position): Variable
    {
        Core::database()->insert(self::TABLE_VARIABLE, [
            "view" => $viewId,
            "name" => $name,
            "value" => $value,
            "position" => $position
        ]);
        return new Variable($viewId, $name, $value, $position);
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