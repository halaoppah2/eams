<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Response;
use RuntimeException;

class AuthController extends Controller
{
    private ?AuthService $auth = null;

    public function showLogin(): string
    {
        return $this->view('auth/login', [
            'expired' => isset($_GET['expired']),
            'reset' => isset($_GET['reset']),
            'loggedOut' => isset($_GET['logged_out']),
            'verified' => isset($_GET['verified']),
        ]);
    }

    public function login(): never
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        try {

            $this->auth()->login(
                $username,
                $password
            );

            Response::redirect(
                APP_BASE_PATH . '/dashboard'
            );

        } catch (RuntimeException $e) {

            $view = $this->view(
                'auth/login',
                [
                    'error' => $e->getMessage(),
                    'username' => $username
                ]
            );

            Response::html($view, 401);

            exit;
        }
    }

    public function logout(): never
    {
        $expired = isset($_GET['expired']);
        $this->auth()->logout();

        Response::redirect(
            $expired
                ? APP_BASE_PATH . '/?expired=1'
                : APP_BASE_PATH . '/'
        );
    }

    public function activity(): never
    {
        http_response_code(204);
        exit;
    }

    public function showForgotPassword(): string
    {
        return $this->view('auth/forgot-password');
    }

    public function requestPasswordReset(): string
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->view('auth/forgot-password', [
                'error' => 'Enter a valid email address.',
                'email' => $email,
            ]);
        }

        (new PasswordResetService())->request($email);

        return $this->view('auth/forgot-password', [
            'submitted' => true,
        ]);
    }

    public function showResetPassword(): string
    {
        $token = is_string($_GET['token'] ?? null)
            ? $_GET['token']
            : '';

        $valid = (new PasswordResetService())->isTokenValid($token);

        return $this->view('auth/reset-password', [
            'token' => $token,
            'invalidToken' => !$valid,
        ]);
    }

    public function resetPassword(): never
    {
        $token = is_string($_POST['token'] ?? null)
            ? $_POST['token']
            : '';
        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password']
            : '';
        $confirmation = is_string($_POST['password_confirmation'] ?? null)
            ? $_POST['password_confirmation']
            : '';

        try {
            (new PasswordResetService())->reset(
                $token,
                $password,
                $confirmation
            );

            Response::redirect(APP_BASE_PATH . '/?reset=1');
        } catch (RuntimeException $e) {
            $view = $this->view('auth/reset-password', [
                'token' => $token,
                'error' => $e->getMessage(),
                'invalidToken' => !(new PasswordResetService())
                    ->isTokenValid($token),
            ]);
            Response::html($view, 422);
            exit;
        }
    }

    private function auth(): AuthService
    {
        if ($this->auth === null) {
            $this->auth = new AuthService();
        }

        return $this->auth;
    }
}