<?php
namespace GameCourse\Views\Dictionary;

class ParticipationLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "participations";    // NOTE: must match the name of the class
    const NAME = "Participations";
    const DESCRIPTION = "Provides access to information regarding participations.";

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
