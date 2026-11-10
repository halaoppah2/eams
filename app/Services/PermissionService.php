<?php

namespace App\Services;

use App\Session;

class PermissionService
{
    public static function has(string $permission): bool
    {
        $permissions = Session::get('permissions', []);

        return in_array(
            $permission,
            $permissions,
            true
        );
    }

    public static function hasAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::has($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function hasAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::has($permission)) {
                return false;
            }
        }

        return true;
    }
}