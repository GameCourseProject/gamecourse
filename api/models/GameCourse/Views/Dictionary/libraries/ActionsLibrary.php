<?php
namespace GameCourse\Views\Dictionary;

class ActionsLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "actions";    // NOTE: must match the name of the class
    const NAME = "Actions";
    const DESCRIPTION = "Library to be used only on EVENTS. These functions define the response to event triggers.";


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
}
