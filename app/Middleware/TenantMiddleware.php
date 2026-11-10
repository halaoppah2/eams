<?php

namespace App\Middleware;

use App\HttpException;
use App\Services\TenantContext;

class TenantMiddleware
{
    public static function handle(): void
    {
        try {
            TenantContext::organizationId();
        } catch (\Throwable $e) {
            throw new HttpException(
                'Organization context unavailable.',
                403,
                $e
            );
        }
    }
}