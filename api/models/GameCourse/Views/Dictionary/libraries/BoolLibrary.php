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
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("not",
                "Gets the opposite bool value of a given value.",
            ReturnType::BOOLEAN,
                $this
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
}
