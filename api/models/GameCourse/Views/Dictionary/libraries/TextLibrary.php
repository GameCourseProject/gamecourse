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
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace provides utilities for controlling the transformation of text.</p>
        HTML;
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("strip",
                [["name" => "text", "optional" => false, "type" => "string"]],
                "Removes all whitespace from a text.",
                ReturnType::TEXT,
                $this,
                "text.strip('hello world')\nReturns 'helloworld'"
            ),
            new DFunction("uppercase",
                [["name" => "text", "optional" => false, "type" => "string"]],
                "Make a text uppercase.",
                ReturnType::TEXT,
                $this,
                "text.capitalize('hello world')\nReturns 'HELLO WORLD'"
            ),
            new DFunction("lowercase",
                [["name" => "text", "optional" => false, "type" => "string"]],
                "Make a text lowercase.",
                ReturnType::TEXT,
                $this,
                "text.capitalize('HELLO WorlD')\nReturns 'hello world'"
            ),
            new DFunction("capitalize",
                [["name" => "text", "optional" => false, "type" => "string"]],
                "Converts text to title-case (changes to uppercase the first letter of each word).",
                ReturnType::TEXT,
                $this,
                "text.capitalize('hello world')\nReturns 'Hello World'"
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

    /**
     * Make a text uppercase.
     *
     * @param string $text
     * @return ValueNode
     */
    public function uppercase(string $text): ValueNode
    {
        return new ValueNode(strtoupper($text), $this);
    }

    /**
     * Make a text lowercase.
     *
     * @param string $text
     * @return ValueNode
     */
    public function lowercase(string $text): ValueNode
    {
        return new ValueNode(strtolower($text), $this);
    }

    /**
     * Converts text to title-case (changes to uppercase the first letter of each word).
     *
     * @param string $text
     * @return ValueNode
     */
    public function capitalize(string $text): ValueNode
    {
        return new ValueNode(ucwords($text), $this);
    }
}
