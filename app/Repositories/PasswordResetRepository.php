<?php

namespace App\Repositories;

use App\Database;
use PDO;

class PasswordResetRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function invalidateActiveTokens(int $userId): void
    {
        $stmt = $this->db->prepare('
            UPDATE password_reset_tokens
            SET used_at = UTC_TIMESTAMP()
            WHERE user_id = :user_id
              AND used_at IS NULL
        ');
        $stmt->execute(['user_id' => $userId]);
    }

    public function create(int $userId, string $tokenHash): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO password_reset_tokens (
                user_id,
                token_hash,
                expires_at
            )
            VALUES (
                :user_id,
                :token_hash,
                :expires_at
            )
        ');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
        ]);
    }

    public function findValid(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, user_id
            FROM password_reset_tokens
            WHERE token_hash = :token_hash
              AND used_at IS NULL
              AND expires_at > UTC_TIMESTAMP()
            LIMIT 1
        ');
        $stmt->execute(['token_hash' => $tokenHash]);

        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        return $token ?: null;
    }

    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE password_reset_tokens
            SET used_at = UTC_TIMESTAMP()
            WHERE id = :id
              AND used_at IS NULL
              AND expires_at > UTC_TIMESTAMP()
        ');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() === 1;
    }
}
