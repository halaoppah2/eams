<?php

namespace App;

class Session
{
    public const IDLE_TIMEOUT_SECONDS = 1800;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {

            ini_set(
                'session.gc_maxlifetime',
                (string) self::IDLE_TIMEOUT_SECONDS
            );

            session_name(
                $_ENV['SESSION_NAME'] ?? 'eams_session'
            );

            session_start();
        }
    }

    public static function set(
        string $key,
        mixed $value
    ): void {
        $_SESSION[$key] = $value;
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}