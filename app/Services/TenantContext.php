<?php

namespace App\Services;

use App\Session;
use RuntimeException;

class TenantContext
{
    public static function organizationId(): int
    {
        $user = Session::get('user');

        if (!$user || empty($user['organization_id'])) {
            throw new RuntimeException(
                'Organization context not available.'
            );
        }

        return (int) $user['organization_id'];
    }

    public static function employeeId(): int
    {
        $user = Session::get('user');

        if (!$user || empty($user['employee_id'])) {
            throw new RuntimeException(
                'Employee context not available.'
            );
        }

        return (int) $user['employee_id'];
    }
}