<?php
namespace GameCourse\Module\QR;

use ReflectionClass;

/**
 * This class holds all QR participation class types available in the system
 * and emulates an enumerator (not available in PHP 7.3).
 */
class ClassType
{
    /*** ----------------------------------------------- ***/
    /*** -------------------- Types -------------------- ***/
    /*** ----------------------------------------------- ***/

    const LECTURE = "Lecture";
    const INVITED_LECTURE = "Invited Lecture";
    // NOTE: insert here new types of classes & update 'qr_code' table


    /**
     * Gets all class types.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        $classTypeClass = new ReflectionClass(ClassType::class);
        return array_values($classTypeClass->getConstants());
    }
}
