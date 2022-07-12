<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Views\ExpressionLanguage\ValueNode;

class SystemLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "system";    // NOTE: must match the name of the class
    const NAME = "System";
    const DESCRIPTION = "Provides general system functionality.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("if",
                "Checks a condition and returns the 2nd argument if true, or the 3rd if false.",
                ReturnType::MIXED,
                $this
            ),
            new DFunction("abs",
                "Returns the absolute value of an integer.",
                ReturnType::INT,
                $this
            ),
            new DFunction("min",
                "Returns the smallest number between two integers.",
                ReturnType::INT,
                $this
            ),
            new DFunction("max",
                "Returns the greatest number between two integers.",
                ReturnType::INT,
                $this
            ),
            new DFunction("time",
                "Returns the time in seconds since the epoch as a floating point number. The specific date of 
                the epoch and the handling of leap seconds is platform dependent. On Windows and most Unix systems, 
                the epoch is January 1, 1970, 00:00:00 (UTC) and leap seconds are not counted towards the time in seconds 
                since the epoch. This is commonly referred to as Unix time.",
                ReturnType::INT,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Checks a condition and returns the 2nd argument if true,
     * or the 3rd if false.
     *
     * @example system.if(1 >= 0, "yes", "no") --> "yes"
     * @example system.if("hello" == "olá", 1, 0) --> 0
     *
     * @param bool $condition
     * @param $ifTrue
     * @param $ifFalse
     * @return ValueNode
     */
    public function if(bool $condition, $ifTrue, $ifFalse): ValueNode
    {
        return new ValueNode($condition ? $ifTrue : $ifFalse);
    }

    /**
     * Returns the absolute value of an integer.
     *
     * @example system.abs(1) --> 1
     * @example system.abs(-1) --> 1
     * @example system.abs(0) --> 0
     *
     * @param int $value
     * @return ValueNode
     */
    public function abs(int $value): ValueNode
    {
        return new ValueNode(abs($value));
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
        return new ValueNode(min($value1, $value2));
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
        return new ValueNode(max($value1, $value2));
    }

    /**
     * Returns the time in seconds since the epoch as a floating point number.
     *
     * @example system.time() --> 1654297355
     *
     * @return ValueNode
     */
    public function time(): ValueNode
    {
        return new ValueNode(time());
    }
}
