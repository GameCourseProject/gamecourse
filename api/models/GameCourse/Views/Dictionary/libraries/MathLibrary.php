<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Views\ExpressionLanguage\ValueNode;

class MathLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "math";    // NOTE: must match the name of the class
    const NAME = "Math";
    const DESCRIPTION = "Provides mathematical utility functions.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace has many methods that allow you to perform mathematical tasks on numbers,
        similarly to those found in programming languages.</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    // NOTE: This is hardcoded
    public function getFunctions(): ?array
    {
        return [
            new DFunction("abs",
                [[ "name" => "value", "optional" => false, "type" => "int"]],
                "Returns the absolute value of a number.",
                ReturnType::NUMBER,
                $this,
                "math.abs(-7)\nReturns 7"
            ),
            new DFunction("min",
                [[ "name" => "value1", "optional" => false, "type" => "int"], [ "name" => "value2", "optional" => false, "type" => "int"]],
                "Returns the smallest number between two numbers.",
                ReturnType::NUMBER,
                $this,
                "math.min(5, 10)\nReturns 5"
            ),
            new DFunction("max",
                [[ "name" => "value1", "optional" => false, "type" => "int"], [ "name" => "value2", "optional" => false, "type" => "int"]],
                "Returns the greatest number between two numbers.",
                ReturnType::NUMBER,
                $this,
                "math.max(5, 10)\nReturns 10"
            ),
            new DFunction("round",
                [[ "name" => "value", "optional" => false, "type" => "float"]],
                "Returns the rounded value of a number.",
                ReturnType::NUMBER,
                $this,
                "math.round('3.3') Returns 3\nmath.round('3.6') Returns 4"
            ),
            new DFunction("floor",
                [[ "name" => "value", "optional" => false, "type" => "float"]],
                "Returns the next lower number by rounding down if necessary.",
                ReturnType::NUMBER,
                $this,
                "math.floor('5.95')\nReturns 5"
            ),
            new DFunction("ceil",
                [[ "name" => "value", "optional" => false, "type" => "float"]],
                "Returns the next highest number by rounding up if necessary.",
                ReturnType::NUMBER,
                $this,
                "math.ceil('5.95')\nReturns 6"
            ),
            new DFunction("sqrt",
                [[ "name" => "value", "optional" => false, "type" => "int"]],
                "Returns the square root of a number.",
                ReturnType::NUMBER,
                $this,
                "math.sqrt(4)\nReturns 2"
            ),
            new DFunction("pow",
                [[ "name" => "value", "optional" => false, "type" => "int"],
                 [ "name" => "exponent", "optional" => false, "type" => "int"]],
                "Returns the value of a number raised to the power of an exponent.",
                ReturnType::NUMBER,
                $this,
                "math.pow(2, 3)\nReturns 8"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Returns the absolute value of a number.
     *
     * @param int $value
     * @return ValueNode
     */
    public function abs(int $value): ValueNode
    {
        return new ValueNode(abs($value), $this);
    }

    /**
     * Returns the smallest number between two numbers.
     *
     * @param int $value1
     * @param int $value2
     * @return ValueNode
     */
    public function min(int $value1, int $value2): ValueNode
    {
        return new ValueNode(min($value1, $value2), $this);
    }

    /**
     * Returns the greatest number between two numbers.
     *
     * @param int $value1
     * @param int $value2
     * @return ValueNode
     */
    public function max(int $value1, int $value2): ValueNode
    {
        return new ValueNode(max($value1, $value2), $this);
    }

    /**
     * Returns the rounded value of a number.
     *
     * @param float $value
     * @return ValueNode
     */
    public function round(float $value): ValueNode
    {
        return new ValueNode(round($value), $this);
    }

    /**
     * Returns the next lower number by rounding down if necessary.
     *
     * @param float $value
     * @return ValueNode
     */
    public function floor(float $value): ValueNode
    {
        return new ValueNode(floor($value), $this);
    }

    /**
     * Returns the next highest number by rounding up if necessary.
     *
     * @param float $value
     * @return ValueNode
     */
    public function ceil(float $value): ValueNode
    {
        return new ValueNode(ceil($value), $this);
    }

    /**
     * Returns the square root of a number.
     *
     * @param int $value
     * @return ValueNode
     */
    public function sqrt(int $value): ValueNode
    {
        return new ValueNode(sqrt($value), $this);
    }

    /**
     * Returns the value of a number raised to the power of an exponent.
     *
     * @param int $value
     * @param int $exponent
     * @return ValueNode
     */
    public function pow(int $value, int $exponent): ValueNode
    {
        return new ValueNode(pow($value, $exponent), $this);
    }
}
