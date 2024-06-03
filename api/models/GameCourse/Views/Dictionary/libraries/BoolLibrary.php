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
            ),
            new DFunction("and",
                [[ "name" => "value1", "optional" => false, "type" => "bool"],
                 [ "name" => "value2", "optional" => false, "type" => "bool"]],
                "Given two operands, returns true if both are true and false otherwise.",
                ReturnType::BOOLEAN,
                $this,
                "bool.and(true, false) Returns false\nbool.and(true, true) Returns true"
            ),
            new DFunction("or",
                [[ "name" => "value1", "optional" => false, "type" => "bool"],
                 [ "name" => "value2", "optional" => false, "type" => "bool"]],
                "Given two operands, returns true if at least one of them is true.",
                ReturnType::BOOLEAN,
                $this,
                "bool.or(true, false) Returns true\nbool.or(false, false) Returns false"
            ),
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

    /**
     * Given two operands, returns true if both are true and false otherwise.
     *
     * @param $value1
     * @param $value2
     * @return ValueNode
     */
    public function and($value1, $value2): ValueNode
    {
        return new ValueNode($value1 && $value2, $this);
    }

    /**
     * Given two operands, returns true if at least one of them is true.
     *
     * @param $value1
     * @param $value2
     * @return ValueNode
     */
    public function or($value1, $value2): ValueNode
    {
        return new ValueNode($value1 || $value2, $this);
    }
}
