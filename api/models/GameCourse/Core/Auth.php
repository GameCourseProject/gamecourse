<?php
namespace GameCourse\Core;

use ReflectionClass;

class Auth
{
    const TABLE_AUTH = "auth";

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
