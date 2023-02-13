<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Core\Core;
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
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    public function strip(string $text): ValueNode
    {
        return new ValueNode(Utils::trimWhitespace($text), Core::dictionary()->getLibraryById(TextLibrary::ID));
    }
}
