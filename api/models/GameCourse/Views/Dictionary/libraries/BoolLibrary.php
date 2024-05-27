<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Views\ExpressionLanguage\ValueNode;

class BoolLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "bool";    // NOTE: must match the name of the class
    const NAME = "Bool";
    const DESCRIPTION = "Provides utility functions for booleans.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>A bool represents a truth value: <span class="text-secondary">true</span> or <span class="text-secondary">false</span>.</p><br>
        <p>This namespace provides some basic operators over booleans, and functions that return a boolean.</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("not",
                [[ "name" => "value", "optional" => false, "type" => "bool"]],
                "Gets the opposite bool value of a given value.",
            ReturnType::BOOLEAN,
                $this,
                "bool.not(true)"
            ),
            new DFunction("exists",
                [[ "name" => "value", "optional" => false, "type" => "bool"]],
                "Checks whether a given value exists.",
                ReturnType::BOOLEAN,
                $this,
                "bool.exists(%user)"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Gets the opposite bool value of a given value.
     *
     * @param $value
     * @return ValueNode
     */
    public function not($value): ValueNode
    {
        return new ValueNode(!$value, $this);
    }

    /**
     * Checks whether a given value exists.
     *
     * @param $value
     * @return ValueNode
     */
    public function exists($value): ValueNode
    {
        return new ValueNode(!!$value, $this);
    }
}
