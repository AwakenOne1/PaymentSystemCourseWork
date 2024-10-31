<?php

class UserRole
{
    const ADMIN = 'admin';
    const MODERATOR = 'moderator';
    const USER = 'user';

    private static $roles = [
        self::ADMIN,
        self::MODERATOR,
        self::USER,
    ];

    public static function isValidRole($role)
    {
        return in_array($role, self::$roles);
    }
}
?>