<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Views\ExpressionLanguage\ValueNode;
use Utils\Utils;

class TextLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "text";    // NOTE: must match the name of the class
    const NAME = "Text";
    const DESCRIPTION = "Provides utility functions for text.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("strip",
                "Removes all whitespace from a text.",
                ReturnType::TEXT,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Removes all whitespace from a text.
     *
     * @param string $text
     * @return ValueNode
     */
    public function strip(string $text): ValueNode
    {
        return new ValueNode(Utils::trimWhitespace($text), $this);
    }
}
