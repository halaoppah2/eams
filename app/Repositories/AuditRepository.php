<?php

namespace App\Repositories;

use App\Database;
use PDO;

class AuditRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Create an audit log entry.
     */
    public function create(
        ?int $organizationId,
        ?int $userId,
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        $sql = "
            INSERT INTO audit_logs (
                organization_id,
                user_id,
                action,
                module,
                entity_type,
                entity_id,
                old_values,
                new_values,
                ip_address,
                user_agent
            )
            VALUES (
                :organization_id,
                :user_id,
                :action,
                :module,
                :entity_type,
                :entity_id,
                :old_values,
                :new_values,
                :ip_address,
                :user_agent
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':organization_id' => $organizationId,
            ':user_id' => $userId,
            ':action' => $action,
            ':module' => $module,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,

            ':old_values' => $oldValues !== null
                ? json_encode(
                    $oldValues,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
                : null,

            ':new_values' => $newValues !== null
                ? json_encode(
                    $newValues,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
                : null,

            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all audit logs for an organization.
     */
    public function getAll(
        int $organizationId
    ): array {
        $sql = "
            SELECT
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
               AND u.organization_id = al.organization_id
            WHERE al.organization_id = :organization_id
            ORDER BY al.created_at DESC,
                     al.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get one audit log.
     */
    public function getById(
        int $id,
        int $organizationId
    ): ?array {
        $sql = "
            SELECT
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
               AND u.organization_id = al.organization_id
            WHERE al.id = :id
              AND al.organization_id = :organization_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':organization_id' => $organizationId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get audit logs for a specific user.
     */
    public function getByUser(
        int $userId,
        int $organizationId
    ): array {
        $sql = "
            SELECT
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
               AND u.organization_id = al.organization_id
            WHERE al.user_id = :user_id
              AND al.organization_id = :organization_id
            ORDER BY al.created_at DESC,
                     al.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit logs for a specific module.
     */
    public function getByModule(
        string $module,
        int $organizationId
    ): array {
        $sql = "
            SELECT
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
               AND u.organization_id = al.organization_id
            WHERE al.module = :module
              AND al.organization_id = :organization_id
            ORDER BY al.created_at DESC,
                     al.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':module' => $module,
            ':organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit logs for a specific entity.
     */
    public function getByEntity(
        string $entityType,
        int $entityId,
        int $organizationId
    ): array {
        $sql = "
            SELECT
                al.*,
                u.username
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
            WHERE al.entity_type = :entity_type
              AND al.entity_id = :entity_id
              AND al.organization_id = :organization_id
            ORDER BY al.created_at DESC,
                     al.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    /**
     * Delete one audit log belonging to an organization.
     */
    public function deleteById(
        int $id,
        int $organizationId
    ): bool {
        $sql = "
            DELETE FROM audit_logs
            WHERE id = :id
              AND organization_id = :organization_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':organization_id' => $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all audit logs belonging to an organization.
     */
    public function deleteAll(
        int $organizationId
    ): int {
        $sql = "
            DELETE FROM audit_logs
            WHERE organization_id = :organization_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':organization_id' => $organizationId
        ]);

        return $stmt->rowCount();
    }

}
