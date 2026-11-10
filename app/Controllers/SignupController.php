<?php

namespace App\Controllers;

use App\Services\OrganizationSignupService;
use App\Response;
use RuntimeException;

class SignupController extends Controller
{
    public function show(): string
    {
        return $this->view('auth/signup');
    }

    public function signup(): string
    {
        $formData = [
            'organization_name' => $this->postString('organization_name'),
            'first_name' => $this->postString('first_name'),
            'last_name' => $this->postString('last_name'),
            'email' => $this->postString('email'),
            'phone' => $this->postString('phone'),
            'username' => $this->postString('username'),
        ];

        try {
            (new OrganizationSignupService())->signup($_POST);

            return $this->view('auth/signup', [
                'submitted' => true,
                'email' => $formData['email'],
            ]);
        } catch (\PDOException $e) {
            error_log('Organization signup database operation failed: ' . $e->getMessage());

            return $this->view('auth/signup', [
                ...$formData,
                'error' => 'Organization signup is temporarily unavailable. Please try again later.',
            ]);
        } catch (RuntimeException $e) {
            return $this->view('auth/signup', [
                ...$formData,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function showVerification(): string
    {
        $token = is_string($_GET['token'] ?? null)
            ? $_GET['token']
            : '';

        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return $this->view('auth/verification-result', [
                'error' => 'This verification link is invalid or expired.',
            ]);
        }

        return $this->view('auth/verification-confirmation', [
            'token' => $token,
        ]);
    }

    public function verifyEmail(): never
    {
        $token = is_string($_POST['token'] ?? null)
            ? $_POST['token']
            : '';

        try {
            (new OrganizationSignupService())->verifyEmail($token);
            Response::redirect(APP_BASE_PATH . '/?verified=1');
        } catch (\PDOException $e) {
            error_log('Email verification database operation failed: ' . $e->getMessage());
            Response::html(
                $this->view('auth/verification-result', [
                    'error' => 'Email verification is temporarily unavailable. Please try again later.',
                ]),
                500
            );
            exit;
        } catch (RuntimeException $e) {
            Response::html(
                $this->view('auth/verification-result', [
                    'error' => $e->getMessage(),
                ]),
                422
            );
            exit;
        }
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
