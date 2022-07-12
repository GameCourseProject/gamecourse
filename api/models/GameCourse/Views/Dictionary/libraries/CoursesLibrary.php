<?php
namespace GameCourse\Views\Dictionary;

class CoursesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "courses";    // NOTE: must match the name of the class
    const NAME = "Courses";
    const DESCRIPTION = "Provides access to information regarding courses.";


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
