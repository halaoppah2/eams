<?php

namespace App\Services;

use App\Database;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Session;
use RuntimeException;

class PasswordResetService
{
    private UserRepository $users;
    private PasswordResetRepository $tokens;
    private MailService $mail;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->tokens = new PasswordResetRepository();
        $this->mail = new MailService();
    }

    public function request(string $email): void
    {
        $user = $this->users->findActiveByEmail(trim($email));

        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $db = Database::connection();

        try {
            $db->beginTransaction();
            $this->tokens->invalidateActiveTokens((int) $user['id']);
            $this->tokens->create((int) $user['id'], $tokenHash);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        $config = require __DIR__ . '/../../config/mail.php';
        $resetUrl = $config['app_url']
            . '/reset-password?token='
            . rawurlencode($token);

        $this->mail->sendPasswordResetLink(
            $user['email'],
            $resetUrl
        );
    }

    public function isTokenValid(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        return $this->tokens->findValid(hash('sha256', $token)) !== null;
    }

    public function reset(
        string $token,
        string $password,
        string $confirmation
    ): void {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new RuntimeException('This reset link is invalid or expired.');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException(
                'Password must be at least 8 characters long.'
            );
        }

        if ($password !== $confirmation) {
            throw new RuntimeException('The passwords do not match.');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            $reset = $this->tokens->findValid(hash('sha256', $token));

            if (!$reset || !$this->tokens->markUsed((int) $reset['id'])) {
                throw new RuntimeException(
                    'This reset link is invalid or expired.'
                );
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($passwordHash === false) {
                throw new RuntimeException('Unable to secure the password.');
            }

            if (!$this->users->updatePassword((int) $reset['user_id'], $passwordHash)) {
                throw new RuntimeException(
                    'Unable to update the password for this account.'
                );
            }
            $db->commit();
            Session::destroy();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }
}
