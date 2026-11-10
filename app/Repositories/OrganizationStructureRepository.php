<?php

namespace App\Repositories;

use App\Database;
use PDO;

class OrganizationStructureRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getStructure(int $organizationId): array
    {
        $branches = $this->db->prepare('
            SELECT 
            b.id, 
            b.name, 
            b.code, 
            b.email, 
            b.phone, 
            b.address,
            s.code AS status_code, s.name AS status_name
            FROM branches b
            INNER JOIN 
            branch_statuses s ON s.id = b.status_id
            WHERE b.organization_id = :organization_id
            ORDER BY b.name, b.id
        ');

        $branches->execute(['organization_id' => $organizationId]);

        $departments = $this->db->prepare('
            SELECT 
            d.id, 
            d.branch_id, 
            d.name, 
            d.code, 
            d.manager_id,
                   s.code AS status_code, s.name AS status_name,
                   b.name AS branch_name,
                   e.first_name AS manager_first_name,
                   e.last_name AS manager_last_name
            FROM departments d
            INNER JOIN department_statuses s ON s.id = d.status_id
            INNER JOIN branches b
                ON b.id = d.branch_id
               AND b.organization_id = d.organization_id
            LEFT JOIN employees e
                ON e.id = d.manager_id
               AND e.organization_id = d.organization_id
            WHERE d.organization_id = :organization_id
            ORDER BY b.name, d.name, d.id
        ');
        $departments->execute(['organization_id' => $organizationId]);

        $positions = $this->db->prepare('
            SELECT p.id, p.title, p.code, p.description,
                   s.code AS status_code, s.name AS status_name
            FROM positions p
            INNER JOIN position_statuses s ON s.id = p.status_id
            WHERE p.organization_id = :organization_id
            ORDER BY p.title, p.id
        ');
        $positions->execute(['organization_id' => $organizationId]);

        return [
            'branches' => $branches->fetchAll(PDO::FETCH_ASSOC),
            'departments' => $departments->fetchAll(PDO::FETCH_ASSOC),
            'positions' => $positions->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getActiveBranches(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT b.id, b.name
            FROM branches b
            INNER JOIN branch_statuses s ON s.id = b.status_id
            WHERE b.organization_id = :organization_id
              AND s.code = \'ACTIVE\'
            ORDER BY b.name, b.id
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getManagers(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT id, employee_number, first_name, last_name
            FROM employees
            WHERE organization_id = :organization_id
              AND deleted_at IS NULL
            ORDER BY first_name, last_name, id
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatusId(string $type, string $code): ?int
    {
        $table = $this->statusTable($type);
        $stmt = $this->db->prepare(
            "SELECT id FROM {$table} WHERE code = :code LIMIT 1"
        );
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function create(
        string $type,
        int $organizationId,
        int $activeStatusId,
        array $data
    ): void {
        if ($type === 'branch') {
            $stmt = $this->db->prepare('
                INSERT INTO branches (
                    organization_id, uuid, name, code, email, phone,
                    address, status_id
                ) VALUES (
                    :organization_id, :uuid, :name, :code, :email, :phone,
                    :address, :status_id
                )
            ');
            $stmt->execute([
                'organization_id' => $organizationId,
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name' => $data['name'],
                'code' => $data['code'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'status_id' => $activeStatusId,
            ]);
            return;
        }

        if ($type === 'department') {
            $stmt = $this->db->prepare('
                INSERT INTO departments (
                    organization_id, branch_id, uuid, name, code,
                    manager_id, status_id
                )
                SELECT :organization_id, b.id, :uuid, :name, :code,
                       :manager_id, :status_id
                FROM branches b
                INNER JOIN branch_statuses bs ON bs.id = b.status_id
                WHERE b.id = :branch_id
                  AND b.organization_id = :branch_organization_id
                  AND bs.code = \'ACTIVE\'
                  AND (
                    :manager_check_id IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM employees e
                        WHERE e.id = :manager_id_check
                          AND e.organization_id = :manager_organization_id
                          AND e.deleted_at IS NULL
                    )
                  )
            ');
            $stmt->execute([
                'organization_id' => $organizationId,
                'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name' => $data['name'],
                'code' => $data['code'],
                'manager_id' => $data['manager_id'],
                'status_id' => $activeStatusId,
                'branch_id' => $data['branch_id'],
                'branch_organization_id' => $organizationId,
                'manager_check_id' => $data['manager_id'],
                'manager_id_check' => $data['manager_id'],
                'manager_organization_id' => $organizationId,
            ]);
            $this->assertCreated($stmt->rowCount(), 'active branch or manager');
            return;
        }

        $stmt = $this->db->prepare('
            INSERT INTO positions (
                organization_id, uuid, title, code, description, status_id
            ) VALUES (
                :organization_id, :uuid, :title, :code, :description, :status_id
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'title' => $data['title'],
            'code' => $data['code'],
            'description' => $data['description'],
            'status_id' => $activeStatusId,
        ]);
    }

    public function update(
        string $type,
        int $organizationId,
        int $id,
        array $data
    ): bool {
        if ($type === 'branch') {
            $stmt = $this->db->prepare('
                UPDATE branches
                SET name = :name, code = :code, email = :email,
                    phone = :phone, address = :address
                WHERE id = :id AND organization_id = :organization_id
            ');
            $stmt->execute([
                'name' => $data['name'],
                'code' => $data['code'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'id' => $id,
                'organization_id' => $organizationId,
            ]);
            return $this->exists($type, $organizationId, $id);
        }

        if ($type === 'department') {
            if (!$this->departmentReferencesAreValid(
                $organizationId,
                (int) $data['branch_id'],
                $data['manager_id']
            )) {
                throw new \RuntimeException(
                    'Select an active branch and a manager from this organization.'
                );
            }

            $stmt = $this->db->prepare('
                UPDATE departments d
                INNER JOIN branches b
                    ON b.id = :branch_id
                   AND b.organization_id = :branch_organization_id
                INNER JOIN branch_statuses bs
                    ON bs.id = b.status_id
                   AND bs.code = \'ACTIVE\'
                SET d.branch_id = b.id,
                    d.name = :name,
                    d.code = :code,
                    d.manager_id = :manager_id
                WHERE d.id = :id
                  AND d.organization_id = :organization_id
                  AND (
                    :manager_check_id IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM employees e
                        WHERE e.id = :manager_id_check
                          AND e.organization_id = :manager_organization_id
                          AND e.deleted_at IS NULL
                    )
                  )
            ');
            $stmt->execute([
                'branch_id' => $data['branch_id'],
                'branch_organization_id' => $organizationId,
                'name' => $data['name'],
                'code' => $data['code'],
                'manager_id' => $data['manager_id'],
                'id' => $id,
                'organization_id' => $organizationId,
                'manager_check_id' => $data['manager_id'],
                'manager_id_check' => $data['manager_id'],
                'manager_organization_id' => $organizationId,
            ]);
            return $this->exists($type, $organizationId, $id);
        }

        $stmt = $this->db->prepare('
            UPDATE positions
            SET title = :title, code = :code, description = :description
            WHERE id = :id AND organization_id = :organization_id
        ');
        $stmt->execute([
            'title' => $data['title'],
            'code' => $data['code'],
            'description' => $data['description'],
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        return $this->exists($type, $organizationId, $id);
    }

    public function updateStatus(
        string $type,
        int $organizationId,
        int $id,
        int $statusId
    ): bool {
        $table = $this->table($type);
        $stmt = $this->db->prepare(
            "UPDATE {$table} SET status_id = :status_id
             WHERE id = :id AND organization_id = :organization_id"
        );
        $stmt->execute([
            'status_id' => $statusId,
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        return $stmt->rowCount() > 0 || $this->exists($type, $organizationId, $id);
    }

    public function exists(
        string $type,
        int $organizationId,
        int $id
    ): bool {
        $table = $this->table($type);
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$table}
             WHERE id = :id AND organization_id = :organization_id LIMIT 1"
        );
        $stmt->execute([
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function assertCreated(int $rowCount, string $requirement): void
    {
        if ($rowCount !== 1) {
            throw new \RuntimeException(
                "The selected {$requirement} could not be found in this organization."
            );
        }
    }

    private function departmentReferencesAreValid(
        int $organizationId,
        int $branchId,
        ?int $managerId
    ): bool {
        $stmt = $this->db->prepare('
            SELECT EXISTS (
                SELECT 1
                FROM branches b
                INNER JOIN branch_statuses bs ON bs.id = b.status_id
                WHERE b.id = :branch_id
                  AND b.organization_id = :branch_organization_id
                  AND bs.code = \'ACTIVE\'
            )
            AND (
                :manager_id IS NULL
                OR EXISTS (
                    SELECT 1
                    FROM employees e
                    WHERE e.id = :manager_check_id
                      AND e.organization_id = :manager_organization_id
                      AND e.deleted_at IS NULL
                )
            )
        ');
        $stmt->execute([
            'branch_id' => $branchId,
            'branch_organization_id' => $organizationId,
            'manager_id' => $managerId,
            'manager_check_id' => $managerId,
            'manager_organization_id' => $organizationId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function table(string $type): string
    {
        return match ($type) {
            'branch' => 'branches',
            'department' => 'departments',
            'position' => 'positions',
            default => throw new \InvalidArgumentException('Invalid structure type.'),
        };
    }

    private function statusTable(string $type): string
    {
        return match ($type) {
            'branch' => 'branch_statuses',
            'department' => 'department_statuses',
            'position' => 'position_statuses',
            default => throw new \InvalidArgumentException('Invalid structure type.'),
        };
    }
}
