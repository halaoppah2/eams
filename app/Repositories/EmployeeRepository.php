<?php

namespace App\Repositories;

use App\Database;
use PDO;

class EmployeeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAll(int $organizationId): array
    {
        $sql = '
        SELECT
            e.id,
            e.employee_number,
            e.first_name,
            e.middle_name,
            e.last_name,
            e.email,
            e.phone,
            e.hire_date,
            d.name AS department_name,
            p.title AS position_name,
            et.name AS employment_type,
            es.name AS employee_status,

            u.id AS user_id,
            u.username AS username,
            u.profile_picture AS profile_picture,
            us.name AS user_status,
            us.code AS user_status_code

        FROM employees e

        LEFT JOIN departments d
            ON d.id = e.department_id
           AND d.organization_id = e.organization_id

        LEFT JOIN positions p
            ON p.id = e.position_id
           AND p.organization_id = e.organization_id

        LEFT JOIN employment_types et
            ON et.id = e.employment_type_id

        LEFT JOIN employee_statuses es
            ON es.id = e.employee_status_id

        LEFT JOIN users u
            ON u.employee_id = e.id
           AND u.deleted_at IS NULL

        LEFT JOIN user_statuses us
            ON us.id = u.status_id

        WHERE e.organization_id = :organization_id
          AND e.deleted_at IS NULL

        ORDER BY e.id ASC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReportEmployees(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT 
            id, 
            employee_number, 
            first_name, 
            middle_name, 
            last_name

            FROM employees
            
            WHERE organization_id = :organization_id
              AND deleted_at IS NULL

            ORDER BY

            first_name ASC, 
            last_name ASC, 
            id ASC
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(
        int $employeeId,
        int $organizationId
    ): ?array {
        $sql = '
        SELECT
            e.*,
            b.name AS branch_name,
            d.name AS department_name,
            p.title AS position_name,
            et.name AS employment_type,
            es.name AS employee_status,
            u.profile_picture
        FROM employees e

        LEFT JOIN users u
            ON u.employee_id = e.id
           AND u.organization_id = e.organization_id
           AND u.deleted_at IS NULL

        LEFT JOIN branches b
            ON b.id = e.branch_id
           AND b.organization_id = e.organization_id

        LEFT JOIN departments d
            ON d.id = e.department_id
           AND d.organization_id = e.organization_id

        LEFT JOIN positions p
            ON p.id = e.position_id
           AND p.organization_id = e.organization_id

        LEFT JOIN employment_types et
            ON et.id = e.employment_type_id

        LEFT JOIN employee_statuses es
            ON es.id = e.employee_status_id

        WHERE e.id = :employee_id
          AND e.organization_id = :organization_id
          AND e.deleted_at IS NULL

        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    public function findByEmployeeNumber(
        string $employeeNumber,
        int $organizationId
    ): ?array {
        $sql = '
            SELECT *
            FROM employees
            WHERE employee_number = :employee_number
              AND organization_id = :organization_id
              AND deleted_at IS NULL
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_number' => $employeeNumber,
            'organization_id' => $organizationId
        ]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    public function getManagers(int $organizationId): array
    {
        $sql = '
        SELECT
            id,
            employee_number,
            first_name,
            last_name
        FROM employees
        WHERE organization_id = :organization_id
          AND deleted_at IS NULL
        ORDER BY first_name ASC, last_name ASC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBranches(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        $statusFilter = $activeOnly ? "AND bs.code = 'ACTIVE'" : '';
        $sql = "
            SELECT b.id, b.name, bs.code AS status_code
            FROM branches b
            INNER JOIN branch_statuses bs ON bs.id = b.status_id
            WHERE b.organization_id = :organization_id
              {$statusFilter}
            ORDER BY b.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        $statusFilter = $activeOnly
            ? "AND ds.code = 'ACTIVE' AND bs.code = 'ACTIVE'"
            : '';
        $sql = "
            SELECT
                d.id,
                d.name,
                d.branch_id,
                b.name AS branch_name,
                ds.code AS status_code
            FROM departments d
            INNER JOIN department_statuses ds ON ds.id = d.status_id
            INNER JOIN branches b
                ON b.id = d.branch_id
               AND b.organization_id = d.organization_id
            INNER JOIN branch_statuses bs ON bs.id = b.status_id
            WHERE d.organization_id = :organization_id
              {$statusFilter}
            ORDER BY d.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPositions(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        $statusFilter = $activeOnly ? "AND ps.code = 'ACTIVE'" : '';
        $sql = "
            SELECT p.id, p.title, ps.code AS status_code
            FROM positions p
            INNER JOIN position_statuses ps ON ps.id = p.status_id
            WHERE p.organization_id = :organization_id
              {$statusFilter}
            ORDER BY p.title ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmploymentTypes(): array
    {
        $sql = '
        SELECT id, name
        FROM employment_types
        ORDER BY name ASC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeStatuses(): array
    {
        $sql = '
        SELECT id, name
        FROM employee_statuses
        ORDER BY name ASC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        if (!$this->referencesBelongToOrganization(
            $data,
            (int) $data['organization_id']
        )) {
            throw new \RuntimeException(
                'The branch, department, position, or manager must belong to this organization.'
            );
        }

        $sql = '
        INSERT INTO employees (
            organization_id,
            branch_id,
            department_id,
            position_id,
            manager_id,
            employee_number,
            first_name,
            middle_name,
            last_name,
            date_of_birth,
            email,
            phone,
            address,
            hire_date,
            employment_type_id,
            employee_status_id
        ) VALUES (
            :organization_id,
            :branch_id,
            :department_id,
            :position_id,
            :manager_id,
            :employee_number,
            :first_name,
            :middle_name,
            :last_name,
            :date_of_birth,
            :email,
            :phone,
            :address,
            :hire_date,
            :employment_type_id,
            :employee_status_id
        )
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $data['organization_id'],
            'branch_id' => $data['branch_id'],
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'],
            'manager_id' => $data['manager_id'],
            'employee_number' => $data['employee_number'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'],
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'hire_date' => $data['hire_date'],
            'employment_type_id' => $data['employment_type_id'],
            'employee_status_id' => $data['employee_status_id']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function referencesBelongToOrganization(
        array $data,
        int $organizationId,
        ?int $existingEmployeeId = null
    ): bool {
        $stmt = $this->db->prepare('
            SELECT
                EXISTS (
                    SELECT 1
                    FROM branches b
                    INNER JOIN branch_statuses bs ON bs.id = b.status_id
                    WHERE b.id = :branch_id
                      AND b.organization_id = :branch_organization_id
                      AND (
                        bs.code = \'ACTIVE\'
                        OR EXISTS (
                            SELECT 1
                            FROM employees current_employee
                            WHERE current_employee.id = :existing_branch_employee_id
                              AND current_employee.organization_id = :existing_branch_organization_id
                              AND current_employee.branch_id = b.id
                              AND current_employee.deleted_at IS NULL
                        )
                      )
                )
                AND EXISTS (
                    SELECT 1
                    FROM departments d
                    INNER JOIN department_statuses ds ON ds.id = d.status_id
                    INNER JOIN branches db
                        ON db.id = d.branch_id
                       AND db.organization_id = d.organization_id
                    INNER JOIN branch_statuses dbs ON dbs.id = db.status_id
                    WHERE d.id = :department_id
                      AND d.organization_id = :department_organization_id
                      AND d.branch_id = :department_branch_id
                      AND (
                        (ds.code = \'ACTIVE\' AND dbs.code = \'ACTIVE\')
                        OR EXISTS (
                            SELECT 1
                            FROM employees current_employee
                            WHERE current_employee.id = :existing_department_employee_id
                              AND current_employee.organization_id = :existing_department_organization_id
                              AND current_employee.department_id = d.id
                              AND current_employee.deleted_at IS NULL
                        )
                      )
                )
                AND EXISTS (
                    SELECT 1
                    FROM positions p
                    INNER JOIN position_statuses ps ON ps.id = p.status_id
                    WHERE p.id = :position_id
                      AND p.organization_id = :position_organization_id
                      AND (
                        ps.code = \'ACTIVE\'
                        OR EXISTS (
                            SELECT 1
                            FROM employees current_employee
                            WHERE current_employee.id = :existing_position_employee_id
                              AND current_employee.organization_id = :existing_position_organization_id
                              AND current_employee.position_id = p.id
                              AND current_employee.deleted_at IS NULL
                        )
                      )
                )
                AND (
                    :manager_id IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM employees manager
                        WHERE manager.id = :manager_check_id
                          AND manager.organization_id = :manager_organization_id
                          AND manager.deleted_at IS NULL
                    )
                )
        ');
        $stmt->execute([
            'branch_id' => $data['branch_id'],
            'branch_organization_id' => $organizationId,
            'existing_branch_employee_id' => $existingEmployeeId,
            'existing_branch_organization_id' => $organizationId,
            'department_id' => $data['department_id'],
            'department_organization_id' => $organizationId,
            'department_branch_id' => $data['branch_id'],
            'existing_department_employee_id' => $existingEmployeeId,
            'existing_department_organization_id' => $organizationId,
            'position_id' => $data['position_id'],
            'position_organization_id' => $organizationId,
            'existing_position_employee_id' => $existingEmployeeId,
            'existing_position_organization_id' => $organizationId,
            'manager_id' => $data['manager_id'],
            'manager_check_id' => $data['manager_id'],
            'manager_organization_id' => $organizationId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function softDelete(
        int $employeeId,
        int $organizationId
    ): bool {
        $sql = '
        UPDATE employees
        SET deleted_at = CURRENT_TIMESTAMP
        WHERE id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function update(
        int $employeeId,
        int $organizationId,
        array $data
    ): bool {
        if (!$this->referencesBelongToOrganization(
            $data,
            $organizationId,
            $employeeId
        )) {
            throw new \RuntimeException(
                'The branch, department, position, or manager must belong to this organization.'
            );
        }

        $sql = '
        UPDATE employees e
        SET
            branch_id = :branch_id,
            department_id = :department_id,
            position_id = :position_id,
            manager_id = :manager_id,
            employee_number = :employee_number,
            first_name = :first_name,
            middle_name = :middle_name,
            last_name = :last_name,
            date_of_birth = :date_of_birth,
            email = :email,
            phone = :phone,
            address = :address,
            hire_date = :hire_date,
            employment_type_id = :employment_type_id,
            employee_status_id = :employee_status_id
        WHERE e.id = :employee_id
          AND e.organization_id = :organization_id
          AND e.deleted_at IS NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'branch_id' =>
                $data['branch_id'],
            'department_id' =>
                $data['department_id'],
            'position_id' =>
                $data['position_id'],
            'manager_id' =>
                $data['manager_id'],
            'employee_number' =>
                $data['employee_number'],
            'first_name' =>
                $data['first_name'],
            'middle_name' =>
                $data['middle_name'],
            'last_name' =>
                $data['last_name'],
            'date_of_birth' =>
                $data['date_of_birth'],
            'email' =>
                $data['email'],
            'phone' =>
                $data['phone'],
            'address' =>
                $data['address'],
            'hire_date' =>
                $data['hire_date'],
            'employment_type_id' =>
                $data['employment_type_id'],
            'employee_status_id' =>
                $data['employee_status_id'],
            'employee_id' =>
                $employeeId,
            'organization_id' =>
                $organizationId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getDeletedEmployees(
        int $organizationId
    ): array {
        $sql = '
        SELECT
            e.id,
            e.employee_number,
            e.first_name,
            e.middle_name,
            e.last_name,
            e.email,
            e.phone,
            e.hire_date,
            e.deleted_at,
            d.name AS department_name,
            p.title AS position_name,
            et.name AS employment_type,
            es.name AS employee_status
        FROM employees e

        LEFT JOIN departments d
            ON d.id = e.department_id
           AND d.organization_id = e.organization_id

        LEFT JOIN positions p
            ON p.id = e.position_id
           AND p.organization_id = e.organization_id

        LEFT JOIN employment_types et
            ON et.id = e.employment_type_id

        LEFT JOIN employee_statuses es
            ON es.id = e.employee_status_id

        WHERE e.organization_id = :organization_id
          AND e.deleted_at IS NOT NULL

        ORDER BY e.deleted_at DESC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDeletedById(
        int $employeeId,
        int $organizationId
    ): ?array {
        $sql = '
        SELECT *
        FROM employees
        WHERE id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NOT NULL
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    public function restore(
        int $employeeId,
        int $organizationId
    ): bool {
        $sql = '
        UPDATE employees
        SET deleted_at = NULL
        WHERE id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NOT NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function permanentlyDelete(
        int $employeeId,
        int $organizationId
    ): bool {
        $sql = '
        DELETE FROM employees
        WHERE id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NOT NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getEmployeeIdByUserId(
        int $userId,
        int $organizationId
    ): int {
        $sql = '
        SELECT e.id
        FROM employees e
        INNER JOIN users u
            ON u.employee_id = e.id
        WHERE u.id = :user_id
          AND e.organization_id = :organization_id
          AND e.deleted_at IS NULL
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId
        ]);

        $employeeId = $stmt->fetchColumn();

        return $employeeId
            ? (int) $employeeId
            : 0;
    }

    /**
     * Count employees belonging to an organization.
     */
    public function countByOrganization(int $organizationId): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM employees
            WHERE organization_id = :organization_id
            AND deleted_at IS NULL
            
        ');

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function softDeleteUserAccount(
        int $employeeId,
        int $organizationId
    ): void {
        $sql = '
        UPDATE users
        SET deleted_at = CURRENT_TIMESTAMP
        WHERE employee_id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);
    }

    public function restoreUserAccount(
        int $employeeId,
        int $organizationId
    ): void {
        $sql = '
        UPDATE users
        SET deleted_at = NULL
        WHERE employee_id = :employee_id
          AND organization_id = :organization_id
          AND deleted_at IS NOT NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);
    }

    public function permanentlyDeleteUserAccount(
        int $employeeId,
        int $organizationId
    ): void {
        $sql = '
        DELETE FROM users
        WHERE employee_id = :employee_id
          AND organization_id = :organization_id
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);
    }
}
