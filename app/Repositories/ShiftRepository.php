<?php

namespace App\Repositories;

use App\Database;
use PDO;
use RuntimeException;

class ShiftRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Get all shifts for an organization.
     */
    public function getAll(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                s.*,
                ss.name AS status_name,
                ss.code AS status_code
            FROM shifts s
            INNER JOIN shift_statuses ss
                ON ss.id = s.status_id
            WHERE s.organization_id = ?
            ORDER BY s.name DESC
        ');

        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a shift by ID within an organization.
     */
    public function findById(
        int $id,
        int $organizationId
    ): ?array {
        $stmt = $this->db->prepare('
            SELECT
                s.*,
                ss.name AS status_name,
                ss.code AS status_code
            FROM shifts s
            INNER JOIN shift_statuses ss
                ON ss.id = s.status_id
            WHERE s.id = ?
              AND s.organization_id = ?
            LIMIT 1
        ');

        $stmt->execute([
            $id,
            $organizationId
        ]);

        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shift ?: null;
    }

    /**
     * Find a shift by code within an organization.
     */
    public function findByCode(
        string $code,
        int $organizationId
    ): ?array {
        $stmt = $this->db->prepare('
            SELECT *
            FROM shifts
            WHERE code = ?
              AND organization_id = ?
            LIMIT 1
        ');

        $stmt->execute([
            $code,
            $organizationId
        ]);

        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        return $shift ?: null;
    }

    /**
     * Create a new shift.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO shifts (
                organization_id,
                name,
                code,
                start_time,
                end_time,
                grace_period_minutes,
                break_duration_minutes,
                working_hours,
                is_overnight,
                status_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['organization_id'],
            $data['name'],
            $data['code'],
            $data['start_time'],
            $data['end_time'],
            $data['grace_period_minutes'] ?? 0,
            $data['break_duration_minutes'] ?? 0,
            $data['working_hours'],
            $data['is_overnight'] ?? 0,
            $data['status_id']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing shift.
     */
    public function update(
        int $id,
        int $organizationId,
        array $data
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE shifts
            SET
                name = ?,
                code = ?,
                start_time = ?,
                end_time = ?,
                grace_period_minutes = ?,
                break_duration_minutes = ?,
                working_hours = ?,
                is_overnight = ?,
                status_id = ?
            WHERE id = ?
              AND organization_id = ?
        ');

        return $stmt->execute([
            $data['name'],
            $data['code'],
            $data['start_time'],
            $data['end_time'],
            $data['grace_period_minutes'] ?? 0,
            $data['break_duration_minutes'] ?? 0,
            $data['working_hours'],
            $data['is_overnight'] ?? 0,
            $data['status_id'],
            $id,
            $organizationId
        ]);
    }

    /**
     * Delete a shift within an organization.
     */
    public function delete(
        int $id,
        int $organizationId
    ): bool {
        $stmt = $this->db->prepare('
            DELETE FROM shifts
            WHERE id = ?
            AND organization_id = ?
        ');

        $stmt->execute([
            $id,
            $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    // has assignment
    public function hasAssignments(
        int $shiftId,
        int $organizationId
    ): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM shift_assignments sa
            INNER JOIN shifts s
                ON s.id = sa.shift_id
               AND s.organization_id = ?
            WHERE sa.shift_id = ?
        ');

        $stmt->execute([$organizationId, $shiftId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get all shift statuses.
     */
    public function getStatuses(): array
    {
        $stmt = $this->db->query('
            SELECT *
            FROM shift_statuses
            ORDER BY name ASC
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employees available for shift assignment
     * within an organization.
     */
    public function getEmployeesForAssignment(
        int $organizationId
    ): array {
        $stmt = $this->db->prepare('
            SELECT
                id,
                employee_number,
                first_name,
                middle_name,
                last_name
            FROM employees
            WHERE organization_id = ?
            AND deleted_at IS NULL
            ORDER BY first_name ASC, last_name ASC
        ');

        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get shifts available for assignment
     * within an organization.
     */
    public function getActiveShiftsForAssignment(
        int $organizationId
    ): array {
        $stmt = $this->db->prepare("
            SELECT s.id, s.name, s.code, s.start_time, s.end_time
            FROM shifts s
            INNER JOIN shift_statuses ss
                ON ss.id = s.status_id
            WHERE s.organization_id = ?
            AND ss.code = 'ACTIVE'
            ORDER BY s.name ASC
        ");

        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether employee belongs to organization.
     */
    public function employeeBelongsToOrganization(
        int $employeeId,
        int $organizationId
    ): bool {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM employees
            WHERE id = ?
            AND organization_id = ?
            AND deleted_at IS NULL
        ');

        $stmt->execute([$employeeId, $organizationId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check whether shift is active in organization.
     */
    public function activeShiftBelongsToOrganization(
        int $shiftId,
        int $organizationId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM shifts s
            INNER JOIN shift_statuses ss
                ON ss.id = s.status_id
            WHERE s.id = ?
            AND s.organization_id = ?
            AND ss.code = 'ACTIVE'
        ");

        $stmt->execute([$shiftId, $organizationId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check for an existing assignment that conflicts
     * with the proposed effective date.
     */
    public function hasConflictingAssignment(
        int $employeeId,
        int $organizationId,
        string $effectiveFrom
    ): bool {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM shift_assignments sa
            INNER JOIN employees e
                ON e.id = sa.employee_id
               AND e.organization_id = ?
            INNER JOIN shifts s
                ON s.id = sa.shift_id
               AND s.organization_id = e.organization_id
            WHERE sa.employee_id = ?
            AND sa.effective_from >= ?
            AND (
                sa.effective_to IS NULL
                OR sa.effective_to >= ?
            )
        ');

        $stmt->execute([
            $organizationId,
            $employeeId,
            $effectiveFrom,
            $effectiveFrom
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * End an existing assignment before the new
     * assignment takes effect.
     */
    public function endPreviousAssignments(
        int $employeeId,
        int $organizationId,
        string $effectiveFrom
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE shift_assignments sa
            INNER JOIN employees e
                ON e.id = sa.employee_id
               AND e.organization_id = ?
            INNER JOIN shifts s
                ON s.id = sa.shift_id
               AND s.organization_id = e.organization_id
            SET sa.effective_to = DATE_SUB(?, INTERVAL 1 DAY)
            WHERE sa.employee_id = ?
            AND sa.effective_from < ?
            AND (
                sa.effective_to IS NULL
                OR sa.effective_to >= ?
            )
        ');

        return $stmt->execute([
            $organizationId,
            $effectiveFrom,
            $employeeId,
            $effectiveFrom,
            $effectiveFrom
        ]);
    }

    /**
     * Create a new shift assignment.
     */
    public function createAssignment(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO shift_assignments (
                employee_id,
                shift_id,
                effective_from,
                effective_to,
                assigned_by
            )
            SELECT
                e.id,
                s.id,
                ?,
                NULL,
                u.id
            FROM employees e
            INNER JOIN shifts s
                ON s.id = ?
               AND s.organization_id = e.organization_id
            INNER JOIN shift_statuses ss
                ON ss.id = s.status_id
               AND ss.code = ?
            INNER JOIN users u
                ON u.id = ?
               AND u.organization_id = e.organization_id
               AND u.deleted_at IS NULL
            WHERE e.id = ?
              AND e.organization_id = ?
              AND e.deleted_at IS NULL
        ');

        $stmt->execute([
            $data['effective_from'],
            $data['shift_id'],
            'ACTIVE',
            $data['assigned_by'],
            $data['employee_id'],
            $data['organization_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The employee, shift, and assigning user must belong to the same organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }
    
    // Unassign a shift from an employee
    public function unassignShift(
        int $employeeId,
        int $organizationId,
        string $effectiveFrom
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE shift_assignments sa
            INNER JOIN employees e
                ON e.id = sa.employee_id
               AND e.organization_id = ?
            INNER JOIN shifts s
                ON s.id = sa.shift_id
               AND s.organization_id = e.organization_id
            SET sa.effective_to = DATE_SUB(?, INTERVAL 1 DAY)
            WHERE sa.employee_id = ?
              AND sa.effective_from < ?
              AND (
                    sa.effective_to IS NULL
                    OR sa.effective_to >= ?
              )
        ');

        $stmt->execute([
            $organizationId,
            $effectiveFrom,
            $employeeId,
            $effectiveFrom,
            $effectiveFrom
        ]);

        return $stmt->rowCount() > 0;
    }
}
