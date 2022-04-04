<?php
namespace GameCourse\User;

class Auth
{
    const TABLE_AUTH = "auth";

    const TYPES = ["fenix", "google", "facebook", "linkedin"];

    /**
     * Checks if a given authentication service is available in the system.
     *
     * @param string $authService
     * @return bool
     */
    public static function exists(string $authService): bool
    {
        return in_array($authService, self::TYPES);
    }
}
