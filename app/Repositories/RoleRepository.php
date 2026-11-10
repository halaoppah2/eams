<?php

namespace App\Repositories;

use App\Database;
use PDO;
use RuntimeException;

class RoleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAll(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                r.id,
                r.name,
                r.description,
                r.is_system_role,
                COUNT(DISTINCT rp.permission_id) AS permission_count,
                COUNT(DISTINCT ur.user_id) AS user_count
            FROM roles r
            LEFT JOIN role_permissions rp ON rp.role_id = r.id
            LEFT JOIN user_roles ur ON ur.role_id = r.id
            WHERE r.organization_id = :organization_id
            GROUP BY r.id, r.name, r.description, r.is_system_role
            ORDER BY r.is_system_role DESC, r.name
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissions(): array
    {
        return $this->db->query('
            SELECT id, name, module, action, description
            FROM permissions
            WHERE name NOT IN (\'audit.view\', \'audit.delete\')
            ORDER BY module, action, name
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $roleId, int $organizationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, organization_id, name, description, is_system_role
            FROM roles
            WHERE id = :role_id
              AND organization_id = :organization_id
            LIMIT 1
        ');
        $stmt->execute([
            'role_id' => $roleId,
            'organization_id' => $organizationId,
        ]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role ?: null;
    }

    public function getPermissionIds(int $roleId, int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT rp.permission_id
            FROM role_permissions rp
            INNER JOIN roles r ON r.id = rp.role_id
            WHERE r.id = :role_id
              AND r.organization_id = :organization_id
            ORDER BY rp.permission_id
        ');
        $stmt->execute([
            'role_id' => $roleId,
            'organization_id' => $organizationId,
        ]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(
        int $organizationId,
        string $name,
        ?string $description,
        array $permissionIds
    ): int {
        $this->assertPermissionIds($permissionIds);
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                INSERT INTO roles (organization_id, name, description, is_system_role)
                VALUES (:organization_id, :name, :description, 0)
            ');
            $stmt->execute([
                'organization_id' => $organizationId,
                'name' => $name,
                'description' => $description,
            ]);
            $roleId = (int) $this->db->lastInsertId();
            $this->replacePermissions($roleId, $permissionIds);
            $this->db->commit();

            return $roleId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(
        int $roleId,
        int $organizationId,
        string $name,
        ?string $description,
        array $permissionIds
    ): void {
        $role = $this->findById($roleId, $organizationId);
        if ($role === null) {
            throw new RuntimeException('Role not found.');
        }
        if ((int) $role['is_system_role'] === 1) {
            throw new RuntimeException(
                'System roles are protected. Create a custom role to change access.'
            );
        }
        $this->assertPermissionIds($permissionIds);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                UPDATE roles
                SET name = :name, description = :description
                WHERE id = :role_id AND organization_id = :organization_id
            ');
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'role_id' => $roleId,
                'organization_id' => $organizationId,
            ]);
            $this->replacePermissions($roleId, $permissionIds);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $roleId, int $organizationId): void
    {
        $role = $this->findById($roleId, $organizationId);
        if ($role === null) {
            throw new RuntimeException('Role not found.');
        }
        if ((int) $role['is_system_role'] === 1) {
            throw new RuntimeException('System roles cannot be deleted.');
        }

        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM user_roles
            WHERE role_id = :role_id
        ');
        $stmt->execute(['role_id' => $roleId]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException(
                'This role is assigned to users. Reassign them before deleting it.'
            );
        }

        $this->db->beginTransaction();
        try {
            $permissionStmt = $this->db->prepare('
                DELETE FROM role_permissions WHERE role_id = :role_id
            ');
            $permissionStmt->execute(['role_id' => $roleId]);
            $deleteStmt = $this->db->prepare('
                DELETE FROM roles
                WHERE id = :role_id
                  AND organization_id = :organization_id
                  AND is_system_role = 0
            ');
            $deleteStmt->execute([
                'role_id' => $roleId,
                'organization_id' => $organizationId,
            ]);
            if ($deleteStmt->rowCount() !== 1) {
                throw new RuntimeException('Role could not be deleted.');
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function assertPermissionIds(array $permissionIds): void
    {
        if ($permissionIds === []) {
            return;
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_values($permissionIds) as $index => $permissionId) {
            $placeholder = ':permission_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $permissionId;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM permissions WHERE id IN ('
            . implode(', ', $placeholders)
            . ')'
        );
        $stmt->execute($parameters);
        if ((int) $stmt->fetchColumn() !== count($permissionIds)) {
            throw new RuntimeException('One or more selected permissions are invalid.');
        }
    }

    private function replacePermissions(int $roleId, array $permissionIds): void
    {
        $deleteStmt = $this->db->prepare(
            'DELETE FROM role_permissions WHERE role_id = :role_id'
        );
        $deleteStmt->execute(['role_id' => $roleId]);

        $insertStmt = $this->db->prepare('
            INSERT INTO role_permissions (role_id, permission_id)
            VALUES (:role_id, :permission_id)
        ');
        foreach ($permissionIds as $permissionId) {
            $insertStmt->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
