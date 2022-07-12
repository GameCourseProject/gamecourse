<?php
namespace GameCourse\Views\Dictionary;

class UsersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "users";    // NOTE: must match the name of the class
    const NAME = "Users";
    const DESCRIPTION = "Provides access to information regarding users.";


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
