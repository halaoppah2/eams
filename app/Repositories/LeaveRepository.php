<?php

namespace App\Repositories;

use App\Database;
use PDO;

class LeaveRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /*
     * Get all active leave types
     * for an organization.
     */
    public function getLeaveTypes(
        int $organizationId
    ): array {
        $sql = "
            SELECT
                lt.id,
                lt.organization_id,
                lt.name,
                lt.code,
                lt.description,
                lt.days_allowed,
                lt.requires_approval,
                lts.name AS status_name,
                lts.code AS status_code
            FROM leave_types lt

            INNER JOIN leave_type_statuses lts
                ON lts.id = lt.status_id

            WHERE lt.organization_id = :organization_id
              AND lts.code = 'ACTIVE'

            ORDER BY lt.name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Get all leave requests
     * for an organization.
     */
    public function getAll(
        int $organizationId
    ): array {
        $sql = '
            SELECT
                lr.id,
                lr.organization_id,
                lr.employee_id,
                lr.leave_type_id,
                lr.start_date,
                lr.end_date,
                lr.reason,
                lr.status_id,
                lr.created_at,
                lr.updated_at,

                e.employee_number,
                e.first_name,
                e.middle_name,
                e.last_name,

                lt.name AS leave_type,
                lt.code AS leave_type_code,

                lrs.name AS status_name,
                lrs.code AS status_code

            FROM leave_requests lr

            INNER JOIN employees e
                ON e.id = lr.employee_id
               AND e.organization_id = lr.organization_id
               AND e.deleted_at IS NULL

            INNER JOIN leave_types lt
                ON lt.id = lr.leave_type_id
               AND lt.organization_id = lr.organization_id

            INNER JOIN leave_request_statuses lrs
                ON lrs.id = lr.status_id

            WHERE lr.organization_id = :organization_id

            ORDER BY lr.created_at DESC, lr.id DESC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEmployeesOnLeave(
        int $organizationId,
        string $date
    ): int {
        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT lr.employee_id)
            FROM leave_requests lr
            INNER JOIN employees e
                ON e.id = lr.employee_id
               AND e.organization_id = lr.organization_id
               AND e.deleted_at IS NULL
            INNER JOIN leave_request_statuses lrs
                ON lrs.id = lr.status_id
               AND lrs.code = :approved_status
            WHERE lr.organization_id = :organization_id
              AND lr.start_date <= :period_end
              AND lr.end_date >= :period_start
        ');
        $stmt->execute([
            'approved_status' => 'APPROVED',
            'organization_id' => $organizationId,
            'period_start' => $date,
            'period_end' => $date,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /*
     * Get leave requests belonging
     * to one employee.
     */
    public function getByEmployee(
        int $employeeId,
        int $organizationId
    ): array {
        $sql = '
    SELECT
        lr.id,
        lr.organization_id,
        lr.employee_id,
        lr.leave_type_id,
        lr.start_date,
        lr.end_date,
        lr.reason,
        lr.status_id,
        lr.created_at,
        lr.updated_at,

        e.employee_number,
        e.first_name,
        e.middle_name,
        e.last_name,

        lt.name AS leave_type,
        lt.code AS leave_type_code,

        lrs.name AS status_name,
        lrs.code AS status_code

    FROM leave_requests lr

    INNER JOIN employees e
        ON e.id = lr.employee_id
       AND e.organization_id = lr.organization_id

    INNER JOIN leave_types lt
        ON lt.id = lr.leave_type_id
       AND lt.organization_id = lr.organization_id

    INNER JOIN leave_request_statuses lrs
        ON lrs.id = lr.status_id

    WHERE lr.employee_id = :employee_id
      AND lr.organization_id = :organization_id

    ORDER BY lr.start_date DESC, lr.id DESC
    ';
        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
     * Find one leave request
     * within an organization.
     */
    public function findById(
        int $leaveRequestId,
        int $organizationId
    ): ?array {
        $sql = '
            SELECT
                lr.id,
                lr.organization_id,
                lr.employee_id,
                lr.leave_type_id,
                lr.start_date,
                lr.end_date,
                lr.reason,
                lr.status_id,
                lr.created_at,
                lr.updated_at,

                e.employee_number,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.email AS employee_email,

                lt.name AS leave_type,
                lt.code AS leave_type_code,
                lt.description AS leave_type_description,
                lt.days_allowed,
                lt.requires_approval,

                lrs.name AS status_name,
                lrs.code AS status_code

            FROM leave_requests lr

            INNER JOIN employees e
                ON e.id = lr.employee_id
               AND e.organization_id = lr.organization_id

            INNER JOIN leave_types lt
                ON lt.id = lr.leave_type_id
               AND lt.organization_id = lr.organization_id

            INNER JOIN leave_request_statuses lrs
                ON lrs.id = lr.status_id

            WHERE lr.id = :leave_request_id
              AND lr.organization_id = :organization_id

            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'leave_request_id' => $leaveRequestId,
            'organization_id' => $organizationId
        ]);

        $leaveRequest =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $leaveRequest ?: null;
    }

    /*
     * Create a new leave request.
     */
    public function create(
        array $data
    ): int {
        $sql = '
            INSERT INTO leave_requests (
                organization_id,
                employee_id,
                leave_type_id,
                start_date,
                end_date,
                reason,
                status_id
            )
            SELECT
                e.organization_id,
                e.id,
                lt.id,
                :start_date,
                :end_date,
                :reason,
                lrs.id
            FROM employees e
            INNER JOIN leave_types lt
                ON lt.id = :leave_type_id
               AND lt.organization_id = e.organization_id
            INNER JOIN leave_request_statuses lrs
                ON lrs.id = :status_id
            WHERE e.id = :employee_id
              AND e.organization_id = :organization_id
              AND e.deleted_at IS NULL
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'leave_type_id' => $data['leave_type_id'],
            'status_id' => $data['status_id'],
            'employee_id' => $data['employee_id'],
            'organization_id' => $data['organization_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException(
                'The employee, leave type, or request status is invalid for this organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    /*
     * Record an approval action
     * for a leave request.
     *
     * approver_id references users.id.
     */
    public function addApproval(
        array $data
    ): int {
        $sql = '
            INSERT INTO leave_approvals (
                leave_request_id,
                approver_id,
                action_id,
                comment
            )
            SELECT
                lr.id,
                u.id,
                aaa.id,
                :comment
            FROM leave_requests lr
            INNER JOIN users u
                ON u.id = :approver_id
               AND u.organization_id = lr.organization_id
               AND u.deleted_at IS NULL
            INNER JOIN leave_approval_actions aaa
                ON aaa.id = :action_id
            WHERE lr.id = :leave_request_id
              AND lr.organization_id = :organization_id
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'leave_request_id' => $data['leave_request_id'],
            'approver_id' => $data['approver_id'],
            'action_id' => $data['action_id'],
            'comment' => $data['comment'],
            'organization_id' => $data['organization_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException(
                'The approver and leave request must belong to the same organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    /*
     * Get approval history for
     * a specific leave request.
     */
    public function getApprovals(
        int $leaveRequestId,
        int $organizationId
    ): array {
        $sql = '
            SELECT
                la.id,
                la.leave_request_id,
                la.approver_id,
                la.action_id,
                la.comment,
                la.created_at,

                u.username AS approver_username,
                u.email AS approver_email,

                aaa.name AS action_name,
                aaa.code AS action_code

            FROM leave_approvals la

            INNER JOIN leave_requests lr
                ON lr.id = la.leave_request_id
               AND lr.organization_id = :organization_id

            INNER JOIN users u
                ON u.id = la.approver_id
               AND u.organization_id = lr.organization_id

            INNER JOIN leave_approval_actions aaa
                ON aaa.id = la.action_id

            WHERE la.leave_request_id = :leave_request_id

            ORDER BY la.created_at ASC, la.id ASC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'leave_request_id' => $leaveRequestId,
            'organization_id' => $organizationId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(
        int $leaveRequestId,
        int $organizationId,
        int $statusId
    ): bool {
        $sql = '
        UPDATE leave_requests
        SET
            status_id = :status_id
        WHERE id = :leave_request_id
          AND organization_id = :organization_id
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'status_id' => $statusId,
            'leave_request_id' => $leaveRequestId,
            'organization_id' => $organizationId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getRequestStatus(
        int $leaveRequestId,
        int $organizationId
    ): ?array {
        $sql = '
        SELECT
            lr.status_id,
            lrs.name AS status_name,
            lrs.code AS status_code
        FROM leave_requests lr

        INNER JOIN leave_request_statuses lrs
            ON lrs.id = lr.status_id

        WHERE lr.id = :leave_request_id
          AND lr.organization_id = :organization_id

        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'leave_request_id' => $leaveRequestId,
            'organization_id' => $organizationId
        ]);

        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        return $status ?: null;
    }

    public function getApprovalActionId(
        string $actionCode
    ): ?int {
        $sql = '
        SELECT id
        FROM leave_approval_actions
        WHERE code = :action_code
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'action_code' => strtoupper($actionCode)
        ]);

        $action = $stmt->fetch(PDO::FETCH_ASSOC);

        return $action
            ? (int) $action['id']
            : null;
    }

    public function getStatusByCode(
        string $statusCode
    ): ?array {
        $sql = '
        SELECT
            id,
            name,
            code
        FROM leave_request_statuses
        WHERE code = :status_code
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'status_code' => strtoupper($statusCode)
        ]);

        $status = $stmt->fetch(PDO::FETCH_ASSOC);

        return $status ?: null;
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
           AND u.organization_id = e.organization_id
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

    public function getUserIdByEmployeeId(
        int $employeeId,
        int $organizationId
    ): int {
        $sql = '
        SELECT u.id
        FROM users u
        INNER JOIN user_statuses us
            ON us.id = u.status_id
        WHERE u.employee_id = :employee_id
          AND u.organization_id = :organization_id
          AND u.deleted_at IS NULL
          AND us.code = :active_status
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
            'active_status' => 'ACTIVE'
        ]);

        $userId = $stmt->fetchColumn();

        return $userId ? (int) $userId : 0;
    }
}
