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
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("abs",
                "Returns the absolute value of a number.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("min",
                "Returns the smallest number between two numbers.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("max",
                "Returns the greatest number between two numbers.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("floor",
                "Returns the next lower number by rounding down if necessary.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("ceil",
                "Returns the next highest number by rounding up if necessary.",
                ReturnType::NUMBER,
                $this
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
     * Returns the next lower number by rounding down if necessary.
     *
     * @param int $value
     * @return ValueNode
     */
    public function floor(int $value): ValueNode
    {
        return new ValueNode(floor($value), $this);
    }

    /**
     * Returns the next highest number by rounding up if necessary.
     *
     * @param int $value
     * @return ValueNode
     */
    public function ceil(int $value): ValueNode
    {
        return new ValueNode(ceil($value), $this);
    }
}
