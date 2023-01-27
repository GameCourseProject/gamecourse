<?php
namespace GameCourse\Core;

use ReflectionClass;

/**
 * This class holds all authentication services available in the
 * system and emulates an enumerator (not available in PHP 7.3).
 */
class AuthService
{
    /*** ----------------------------------------------- ***/
    /*** ------------------ Services ------------------- ***/
    /*** ----------------------------------------------- ***/

    const FENIX = "fenix";
    const GOOGLE = "google";
    const FACEBOOK = "facebook";
    const LINKEDIN = "linkedin";
    // NOTE: insert here new authentication services & update 'auth' table definition


    /**
     * Checks if a given authentication service is available in the system.
     *
     * @param string $authService
     * @return bool
     */
    public static function exists(string $authService): bool
    {
        $authServiceClass = new ReflectionClass(AuthService::class);
        $authServices = array_values($authServiceClass->getConstants());
        return in_array($authService, $authServices);
    }
}
