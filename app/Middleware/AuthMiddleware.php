<?php

namespace App\Middleware;

use App\Services\AuthService;
use App\Response;
use App\Session;

class AuthMiddleware
{
    public static function handle(): void
    {
        $auth = new AuthService();

        if (!$auth->check()) {
            Response::redirect(APP_BASE_PATH . '/');
        }

        $lastActivity = (int) Session::get(
            'last_activity',
            time()
        );

        if (time() - $lastActivity >= Session::IDLE_TIMEOUT_SECONDS) {
            Session::destroy();
            Response::redirect(APP_BASE_PATH . '/?expired=1');
        }

        if (!$auth->refreshSessionUser()) {
            Session::destroy();
            Response::redirect(APP_BASE_PATH . '/');
        }

        Session::set('last_activity', time());
    }
}