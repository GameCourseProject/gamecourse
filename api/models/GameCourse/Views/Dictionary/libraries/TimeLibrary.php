<?php
namespace GameCourse\Views\Dictionary;

class TimeLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "time";    // NOTE: must match the name of the class
    const NAME = "Time";
    const DESCRIPTION = "Provides access to utility functions regarding time.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above
}
