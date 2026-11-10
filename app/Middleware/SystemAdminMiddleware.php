<?php

namespace App\Middleware;

use App\HttpException;
use App\Session;

class SystemAdminMiddleware
{
    public static function handle(): void
    {
        $user = Session::get('user', []);
        if (
            !is_array($user)
            || empty($user['is_system_admin'])
        ) {
            throw new HttpException(
                'You do not have permission to manage organizations.',
                403
            );
        }
    }
}
