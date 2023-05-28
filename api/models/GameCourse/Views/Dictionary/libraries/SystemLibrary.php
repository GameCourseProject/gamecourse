<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\ViewHandler;

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
                [["name" => "condition", "optional" => false, "type" => "bool"],
                 ["name" => "ifTrue", "optional" => false, "type" => "any"],
                 ["name" => "ifFalse", "optional" => false, "type" => "any"]],
                "Checks a condition and returns the 2nd argument if true, or the 3rd if false.",
                ReturnType::MIXED,
                $this
            ),
            new DFunction("time",
                [],
                "Returns the time in seconds since the epoch as a floating point number. The specific date of 
                the epoch and the handling of leap seconds is platform dependent. On Windows and most Unix systems, 
                the epoch is January 1, 1970, 00:00:00 (UTC) and leap seconds are not counted towards the time in seconds 
                since the epoch. This is commonly referred to as Unix time.",
                ReturnType::NUMBER,
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
     * @example system.if("hello" == "olá", 1, 0) --> 0
     * @example system.if(1 >= 0, "yes", "no") --> "yes"
     *
     * @param bool $condition
     * @param $ifTrue
     * @param $ifFalse
     * @return ValueNode
     * @throws Exception
     */
    public function if(bool $condition, $ifTrue, $ifFalse): ValueNode
    {
        $value = $condition ? $ifTrue : $ifFalse;

        // Compile and evaluate if expression
        if (is_string($value)) {
            preg_match_all("/({.*})/", $value, $matches);
            foreach ($matches[1] as $match) {
                $expression = $match;
                ViewHandler::compileExpression($expression);
                ViewHandler::evaluateNode($expression, Core::dictionary()->getVisitor());
                $match = str_replace("(", "\(", $match);
                $match = str_replace(")", "\)", $match);
                $value = preg_replace("/" . $match . "/", "$expression", $value);
            }
        }

        $library = null;
        if (is_array($value)) $library = Core::dictionary()->getLibraryById(CollectionLibrary::ID);
        if (is_numeric($value)) $library = Core::dictionary()->getLibraryById(MathLibrary::ID);
        if (is_string($value)) $library = Core::dictionary()->getLibraryById(TextLibrary::ID);

        return new ValueNode($value, $library);
    }

    /**
     * Returns the time in seconds since the epoch as a floating point number.
     *
     * @example system.time() --> 1654297355
     *
     * @return ValueNode
     * @throws Exception
     */
    public function time(): ValueNode
    {
        return new ValueNode(time(), Core::dictionary()->getLibraryById(MathLibrary::ID));
    }
}
