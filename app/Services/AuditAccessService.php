<?php

namespace App\Services;

use App\Session;

class AuditAccessService
{
    private const ADMIN_ROLES = [
        'administrator',
        'super administrator',
        'organization owner',
    ];

    public static function canView(): bool
    {
        $user = Session::get('user', []);
        if (!empty($user['is_system_admin'])) {
            return true;
        }

        $roles = Session::get('roles', []);
        if (!is_array($roles)) {
            return false;
        }

        foreach ($roles as $role) {
            if (empty($role['is_system_role'])) {
                continue;
            }

            $name = strtolower(trim((string) ($role['name'] ?? '')));
            if (in_array($name, self::ADMIN_ROLES, true)) {
                return true;
            }
        }

        return false;
    }

    public static function canDelete(): bool
    {
        return self::canView();
    }
}
