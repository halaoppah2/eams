<?php

namespace App\Middleware;

use App\HttpException;
use App\Services\PermissionService;

class PermissionMiddleware
{
    public static function handle(
        string $permission
    ): void {

        if (!PermissionService::has($permission)) {
            throw new HttpException(
                'You do not have permission to perform this action.',
                403
            );
        }
    }
}