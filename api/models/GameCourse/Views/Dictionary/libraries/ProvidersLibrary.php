<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use Utils\Cache;
use Utils\Time;

class ProvidersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "providers";    // NOTE: must match the name of the class
    const NAME = "Data Providers";
    const DESCRIPTION = "Gives access to a set of data providers that can be injected into charts.";


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
