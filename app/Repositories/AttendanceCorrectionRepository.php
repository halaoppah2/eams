<?php

namespace App\Repositories;

use App\Database;
use PDO;
use RuntimeException;

class AttendanceCorrectionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAttendance(
        int $attendanceId,
        int $organizationId,
        ?int $employeeId = null
    ): ?array {
        $employeeClause = $employeeId === null
            ? ''
            : ' AND a.employee_id = :employee_id';
        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.organization_id,
                a.employee_id,
                a.attendance_date,
                a.scheduled_start_time,
                a.scheduled_end_time,
                a.grace_period_minutes,
                a.clock_in_at,
                a.clock_out_at,
                a.attendance_status_id,
                s.name AS status_name
            FROM attendance a
            INNER JOIN attendance_statuses s
                ON s.id = a.attendance_status_id
            WHERE a.id = :attendance_id
              AND a.organization_id = :organization_id
              {$employeeClause}
            LIMIT 1
        ");
        $parameters = [
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
        ];
        if ($employeeId !== null) {
            $parameters['employee_id'] = $employeeId;
        }
        $stmt->execute($parameters);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        return $attendance ?: null;
    }

    public function hasPendingRequest(int $attendanceId, int $organizationId): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
            INNER JOIN correction_statuses cs
                ON cs.id = c.status_id
            WHERE c.attendance_id = :attendance_id
              AND a.organization_id = :organization_id
              AND cs.code = :status
        ');
        $stmt->execute([
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
            'status' => 'PENDING',
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function lockAttendance(
        int $attendanceId,
        int $organizationId,
        int $employeeId
    ): void {
        $stmt = $this->db->prepare('
            SELECT id
            FROM attendance
            WHERE id = :attendance_id
              AND organization_id = :organization_id
              AND employee_id = :employee_id
            FOR UPDATE
        ');
        $stmt->execute([
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
            'employee_id' => $employeeId,
        ]);
        if ($stmt->fetchColumn() === false) {
            throw new RuntimeException('Attendance record not found.');
        }
    }

    public function createRequest(
        int $attendanceId,
        int $organizationId,
        int $employeeId,
        int $requestedBy,
        string $reason,
        ?string $clockIn,
        ?string $clockOut
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO attendance_corrections (
                attendance_id,
                requested_by,
                reason,
                requested_clock_in,
                requested_clock_out,
                status_id
            )
            SELECT a.id, u.id, :reason, :clock_in, :clock_out, cs.id
            FROM attendance a
            INNER JOIN users u
                ON u.id = :requested_by
               AND u.organization_id = a.organization_id
               AND u.employee_id = a.employee_id
               AND u.deleted_at IS NULL
            INNER JOIN correction_statuses cs
                ON cs.code = :pending_status
            WHERE a.id = :attendance_id
              AND a.organization_id = :organization_id
              AND a.employee_id = :employee_id
        ');
        $stmt->execute([
            'reason' => $reason,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'requested_by' => $requestedBy,
            'pending_status' => 'PENDING',
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
            'employee_id' => $employeeId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The correction request could not be saved for this attendance record.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    public function getForEmployee(int $userId, int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                c.id,
                c.reason,
                c.requested_clock_in,
                c.requested_clock_out,
                c.review_comment,
                c.created_at,
                cs.code AS status_code,
                cs.name AS status_name,
                a.id AS attendance_id,
                a.attendance_date,
                a.clock_in_at,
                a.clock_out_at
            FROM attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
            INNER JOIN correction_statuses cs
                ON cs.id = c.status_id
            WHERE a.organization_id = :organization_id
              AND c.requested_by = :user_id
            ORDER BY c.created_at DESC, c.id DESC
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForOrganization(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                c.id,
                c.reason,
                c.requested_clock_in,
                c.requested_clock_out,
                c.review_comment,
                c.created_at,
                cs.code AS status_code,
                cs.name AS status_name,
                a.id AS attendance_id,
                a.attendance_date,
                a.clock_in_at,
                a.clock_out_at,
                e.employee_number,
                e.first_name,
                e.last_name
            FROM attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
               AND a.organization_id = :organization_id
            INNER JOIN correction_statuses cs
                ON cs.id = c.status_id
            INNER JOIN users requester
                ON requester.id = c.requested_by
               AND requester.organization_id = a.organization_id
            INNER JOIN employees e
                ON e.id = a.employee_id
               AND e.organization_id = a.organization_id
            ORDER BY
                CASE WHEN cs.code = :pending THEN 0 ELSE 1 END,
                c.created_at DESC,
                c.id DESC
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'pending' => 'PENDING',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find active organization users who can review correction requests.
     *
     * @return list<int>
     */
    public function getCorrectionReviewerIds(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT DISTINCT u.id
            FROM users u
            INNER JOIN user_statuses us
                ON us.id = u.status_id
               AND us.code = :active_status
            INNER JOIN employees e
                ON e.id = u.employee_id
               AND e.organization_id = u.organization_id
               AND e.deleted_at IS NULL
            INNER JOIN user_roles ur
                ON ur.user_id = u.id
            INNER JOIN roles r
                ON r.id = ur.role_id
               AND r.organization_id = u.organization_id
            INNER JOIN role_permissions rp
                ON rp.role_id = r.id
            INNER JOIN permissions p
                ON p.id = rp.permission_id
               AND p.name = :permission
            WHERE u.organization_id = :organization_id
              AND u.deleted_at IS NULL
            ORDER BY u.id
        ');
        $stmt->execute([
            'active_status' => 'ACTIVE',
            'permission' => 'attendance.correction.view',
            'organization_id' => $organizationId,
        ]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getRequest(int $requestId, int $organizationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT
                c.id,
                c.attendance_id,
                c.requested_by,
                c.reason,
                c.requested_clock_in,
                c.requested_clock_out,
                c.status_id,
                c.review_comment,
                c.created_at,
                cs.code AS status_code,
                cs.name AS status_name,
                a.organization_id,
                a.employee_id,
                a.attendance_date,
                a.scheduled_start_time,
                a.scheduled_end_time,
                a.grace_period_minutes,
                a.clock_in_at,
                a.clock_out_at,
                e.employee_number,
                e.first_name,
                e.last_name
            FROM attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
               AND a.organization_id = :organization_id
            INNER JOIN correction_statuses cs
                ON cs.id = c.status_id
            INNER JOIN users requester
                ON requester.id = c.requested_by
               AND requester.organization_id = a.organization_id
            INNER JOIN employees e
                ON e.id = a.employee_id
               AND e.organization_id = a.organization_id
            WHERE c.id = :request_id
            LIMIT 1
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'request_id' => $requestId,
        ]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }

    public function lockRequest(int $requestId, int $organizationId): void
    {
        $stmt = $this->db->prepare('
            SELECT c.id
            FROM attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
               AND a.organization_id = :organization_id
            WHERE c.id = :request_id
            FOR UPDATE
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'request_id' => $requestId,
        ]);
        if ($stmt->fetchColumn() === false) {
            throw new RuntimeException('Correction request not found.');
        }
    }

    public function getStatusId(string $code): int
    {
        $stmt = $this->db->prepare('
            SELECT id FROM correction_statuses WHERE code = :code LIMIT 1
        ');
        $stmt->execute(['code' => $code]);
        $statusId = $stmt->fetchColumn();
        if ($statusId === false) {
            throw new RuntimeException(
                "Correction status '{$code}' is not configured."
            );
        }

        return (int) $statusId;
    }

    public function getAttendanceStatusId(string $code): int
    {
        $stmt = $this->db->prepare('
            SELECT id FROM attendance_statuses WHERE code = :code LIMIT 1
        ');
        $stmt->execute(['code' => $code]);
        $statusId = $stmt->fetchColumn();
        if ($statusId === false) {
            throw new RuntimeException(
                "Attendance status '{$code}' is not configured."
            );
        }

        return (int) $statusId;
    }

    public function updateAttendance(
        int $attendanceId,
        int $organizationId,
        string $clockIn,
        ?string $clockOut,
        int $statusId
    ): void {
        $stmt = $this->db->prepare('
            UPDATE attendance
            SET clock_in_at = :clock_in,
                clock_out_at = :clock_out,
                attendance_status_id = :status_id
            WHERE id = :attendance_id
              AND organization_id = :organization_id
        ');
        $stmt->execute([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status_id' => $statusId,
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
        ]);
        if ($stmt->rowCount() > 1) {
            throw new RuntimeException('Unexpected attendance update result.');
        }
    }

    public function resolveRequest(
        int $requestId,
        int $organizationId,
        int $reviewerId,
        string $statusCode,
        ?string $reviewComment
    ): void {
        $stmt = $this->db->prepare('
            UPDATE attendance_corrections c
            INNER JOIN attendance a
                ON a.id = c.attendance_id
               AND a.organization_id = :organization_id
            INNER JOIN correction_statuses cs
                ON cs.code = :status_code
            SET c.status_id = cs.id,
                c.reviewed_by = :reviewed_by,
                c.reviewed_at = CURRENT_TIMESTAMP,
                c.review_comment = :review_comment
            WHERE c.id = :request_id
              AND c.status_id = (
                  SELECT pending.id
                  FROM correction_statuses pending
                  WHERE pending.code = :pending_code
                  LIMIT 1
              )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'status_code' => $statusCode,
            'reviewed_by' => $reviewerId,
            'review_comment' => $reviewComment,
            'request_id' => $requestId,
            'pending_code' => 'PENDING',
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'This request is no longer pending. Refresh and try again.'
            );
        }
    }

    public function createAttendanceLog(
        int $attendanceId,
        int $organizationId,
        int $employeeId,
        string $eventTime
    ): void {
        $stmt = $this->db->prepare('
            INSERT INTO attendance_logs (
                attendance_id,
                employee_id,
                event_type,
                event_time,
                source
            )
            SELECT a.id, a.employee_id, :event_type, :event_time, :source
            FROM attendance a
            WHERE a.id = :attendance_id
              AND a.organization_id = :organization_id
              AND a.employee_id = :employee_id
        ');
        $stmt->execute([
            'event_type' => 'CORRECTION',
            'event_time' => $eventTime,
            'source' => 'ADMIN_CORRECTION',
            'attendance_id' => $attendanceId,
            'organization_id' => $organizationId,
            'employee_id' => $employeeId,
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The attendance correction history could not be recorded.'
            );
        }
    }
}
