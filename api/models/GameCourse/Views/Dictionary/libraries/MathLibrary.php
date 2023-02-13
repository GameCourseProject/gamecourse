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
                "Returns the absolute value of an integer.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("min",
                "Returns the smallest number between two integers.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("max",
                "Returns the greatest number between two integers.",
                ReturnType::NUMBER,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Returns the absolute value of an integer.
     *
     * @example integer.abs(1) --> 1
     * @example integer.abs(-1) --> 1
     * @example integer.abs(0) --> 0
     *
     * @param int $value
     * @return ValueNode
     */
    public function abs(int $value): ValueNode
    {
        return new ValueNode(abs($value), $this);
    }

    /**
     * Returns the smallest number between two integers.
     *
     * @example system.min(1, 2) --> 1
     * @example system.min(2, 0) --> 0
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
     * Returns the greatest number between two integers.
     *
     * @example system.max(1, 2) --> 2
     * @example system.max(0, -3) --> -3
     *
     * @param int $value1
     * @param int $value2
     * @return ValueNode
     */
    public function max(int $value1, int $value2): ValueNode
    {
        return new ValueNode(max($value1, $value2), $this);
    }
}
