<?php

namespace App\Repositories;

use App\Database;
use PDO;

class OrganizationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('
            SELECT
                o.id,
                o.name,
                o.email,
                o.created_at,
                s.code AS status_code,
                s.name AS status_name
            FROM organizations o
            INNER JOIN organization_statuses s ON s.id = o.status_id
            ORDER BY o.created_at DESC, o.id DESC
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $organizationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT
                o.id,
                o.name,
                o.registration_no,
                o.email,
                o.phone,
                o.address,
                o.logo,
                o.timezone,
                s.code AS status_code
            FROM organizations o
            INNER JOIN organization_statuses s ON s.id = o.status_id
            WHERE o.id = :organization_id
            LIMIT 1
        ');
        $stmt->execute(['organization_id' => $organizationId]);
        $organization = $stmt->fetch(PDO::FETCH_ASSOC);

        return $organization ?: null;
    }

    public function updateLogo(int $organizationId, ?string $logo): bool
    {
        $stmt = $this->db->prepare('
            UPDATE organizations
            SET logo = :logo
            WHERE id = :organization_id
        ');
        return $stmt->execute([
            'organization_id' => $organizationId,
            'logo' => $logo,
        ]);
    }

    public function updateDetails(int $organizationId, array $details): bool
    {
        $stmt = $this->db->prepare('
            UPDATE organizations
            SET name = :name,
                email = :email,
                phone = :phone,
                address = :address,
                timezone = :timezone
            WHERE id = :organization_id
        ');
        return $stmt->execute([
            'organization_id' => $organizationId,
            'name' => $details['name'],
            'email' => $details['email'],
            'phone' => $details['phone'],
            'address' => $details['address'],
            'timezone' => $details['timezone'],
        ]);
    }

    public function updateStatus(int $organizationId, string $statusCode): bool
    {
        $statusStmt = $this->db->prepare('
            SELECT id FROM organization_statuses
            WHERE code = :status_code
            LIMIT 1
        ');
        $statusStmt->execute(['status_code' => $statusCode]);
        if ($statusStmt->fetchColumn() === false) {
            throw new \RuntimeException(
                "The {$statusCode} organization status is not configured."
            );
        }

        $stmt = $this->db->prepare('
            UPDATE organizations o
            INNER JOIN organization_statuses s
                ON s.code = :status_code
            SET o.status_id = s.id
            WHERE o.id = :organization_id
        ');
        $stmt->execute([
            'status_code' => $statusCode,
            'organization_id' => $organizationId,
        ]);

        $organization = $this->findById($organizationId);

        return $organization !== null
            && $organization['status_code'] === $statusCode;
    }

    /**
     * Permanently remove an organization and its tenant data.
     *
     * @return array{logo: ?string, profile_pictures: list<string>}
     */
    public function deletePermanently(
        int $organizationId,
        int $actorUserId
    ): array {
        if ($organizationId <= 0 || $actorUserId <= 0) {
            throw new \RuntimeException('Invalid organization or user.');
        }

        $db = $this->db;
        try {
            $db->beginTransaction();
            $organization = $this->findByIdForUpdate($organizationId);
            if ($organization === null) {
                throw new \RuntimeException('Organization not found.');
            }

            $assets = $this->getUploadedAssets($organizationId);
            $auditId = $this->recordDeletionAudit(
                $organizationId,
                $actorUserId,
                (string) $organization['name']
            );

            $this->executeDelete(
                'DELETE al FROM attendance_logs al
                 LEFT JOIN attendance a ON a.id = al.attendance_id
                 LEFT JOIN employees e ON e.id = al.employee_id
                 WHERE a.organization_id = :organization_id_attendance
                    OR e.organization_id = :organization_id_employee',
                [
                    'organization_id_attendance' => $organizationId,
                    'organization_id_employee' => $organizationId,
                ]
            );
            $this->executeDelete(
                'DELETE ac FROM attendance_corrections ac
                 INNER JOIN attendance a ON a.id = ac.attendance_id
                 WHERE a.organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE la FROM leave_approvals la
                 INNER JOIN leave_requests lr
                    ON lr.id = la.leave_request_id
                 WHERE lr.organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE sa FROM shift_assignments sa
                 LEFT JOIN employees e ON e.id = sa.employee_id
                 LEFT JOIN shifts s ON s.id = sa.shift_id
                 WHERE e.organization_id = :organization_id_employee
                    OR s.organization_id = :organization_id_shift',
                [
                    'organization_id_employee' => $organizationId,
                    'organization_id_shift' => $organizationId,
                ]
            );

            foreach ([
                'employee_contacts',
                'employee_documents',
                'employee_transfers',
            ] as $table) {
                $this->executeDelete(
                    "DELETE child FROM {$table} child
                     INNER JOIN employees e ON e.id = child.employee_id
                     WHERE e.organization_id = :organization_id",
                    ['organization_id' => $organizationId]
                );
            }

            $this->executeDelete(
                'DELETE FROM audit_logs
                 WHERE organization_id = :organization_id
                   AND id <> :deletion_audit_id',
                [
                    'organization_id' => $organizationId,
                    'deletion_audit_id' => $auditId,
                ]
            );
            $this->executeDelete(
                'DELETE FROM login_logs
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM notifications
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM leave_requests
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM attendance
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM users
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM employees
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );
            $this->executeDelete(
                'DELETE FROM departments
                 WHERE organization_id = :organization_id',
                ['organization_id' => $organizationId]
            );

            foreach ([
                'branches',
                'holidays',
                'leave_types',
                'positions',
                'roles',
                'settings',
                'shifts',
                'subscriptions',
            ] as $table) {
                $this->executeDelete(
                    "DELETE FROM {$table}
                     WHERE organization_id = :organization_id",
                    ['organization_id' => $organizationId]
                );
            }

            $deleteOrganization = $db->prepare(
                'DELETE FROM organizations WHERE id = :organization_id'
            );
            $deleteOrganization->execute([
                'organization_id' => $organizationId,
            ]);
            if ($deleteOrganization->rowCount() !== 1) {
                throw new \RuntimeException(
                    'The organization could not be deleted.'
                );
            }

            $db->commit();

            return $assets;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function findByIdForUpdate(int $organizationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT o.id, o.name, o.logo
            FROM organizations o
            WHERE o.id = :organization_id
            LIMIT 1
            FOR UPDATE
        ');
        $stmt->execute(['organization_id' => $organizationId]);
        $organization = $stmt->fetch(PDO::FETCH_ASSOC);

        return $organization ?: null;
    }

    /**
     * @return array{logo: ?string, profile_pictures: list<string>}
     */
    private function getUploadedAssets(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT logo
            FROM organizations
            WHERE id = :organization_id
        ');
        $stmt->execute(['organization_id' => $organizationId]);
        $logo = $stmt->fetchColumn();

        $stmt = $this->db->prepare('
            SELECT profile_picture
            FROM users
            WHERE organization_id = :organization_id
              AND profile_picture IS NOT NULL
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return [
            'logo' => is_string($logo) ? $logo : null,
            'profile_pictures' => $stmt->fetchAll(PDO::FETCH_COLUMN),
        ];
    }

    private function recordDeletionAudit(
        int $organizationId,
        int $actorUserId,
        string $organizationName
    ): int {
        $stmt = $this->db->prepare('
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
            ) VALUES (
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
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'user_id' => $actorUserId,
            'action' => 'ORGANIZATION_DELETED',
            'module' => 'organization',
            'entity_type' => 'organization',
            'entity_id' => $organizationId,
            'old_values' => json_encode(
                ['name' => $organizationName],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'new_values' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function executeDelete(string $sql, array $parameters): void
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);
    }
}
