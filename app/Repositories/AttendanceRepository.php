<?php

namespace App\Repositories;

use PDO;
use RuntimeException;

class AttendanceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmployeeAndDate(
        int $employeeId,
        int $organizationId,
        string $date
    ): ? array {
        $stmt = $this->db->prepare('
            SELECT *
            FROM attendance
            WHERE employee_id = ?
              AND organization_id = ?
              AND attendance_date = ?
            LIMIT 1
        ');

        $stmt->execute([
            $employeeId,
            $organizationId,
            $date
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getActiveShiftAssignment(
        int $employeeId,
        int $organizationId,
        string $date
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                sa.id,
                sa.employee_id,
                sa.shift_id,
                sa.effective_from,
                sa.effective_to,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.grace_period_minutes,
                s.working_hours
            FROM shift_assignments sa
            INNER JOIN shifts s
                ON s.id = sa.shift_id
               AND s.organization_id = ?
            INNER JOIN employees e
                ON e.id = sa.employee_id
               AND e.organization_id = s.organization_id
            WHERE sa.employee_id = ?
              AND e.deleted_at IS NULL
              AND sa.effective_from <= ?
              AND (
                    sa.effective_to IS NULL
                    OR sa.effective_to >= ?
              )
              AND s.status_id = (
                    SELECT id
                    FROM shift_statuses
                    WHERE code = 'ACTIVE'
                    LIMIT 1
              )
            ORDER BY sa.effective_from DESC
            LIMIT 1
        ");

        $stmt->execute([
            $organizationId,
            $employeeId,
            $date,
            $date
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getStatusByCode(
        string $code
    ): ?array {
        $stmt = $this->db->prepare('
            SELECT *
            FROM attendance_statuses
            WHERE code = ?
            LIMIT 1
        ');

        $stmt->execute([$code]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO attendance (
                organization_id,
                employee_id,
                attendance_date,
                scheduled_start_time,
                scheduled_end_time,
                grace_period_minutes,
                shift_assignment_id,
                clock_in_at,
                clock_out_at,
                attendance_status_id,
                source
            )
            SELECT
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            FROM employees e
            WHERE e.id = ?
              AND e.organization_id = ?
              AND e.deleted_at IS NULL
              AND (
                    ? IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM shift_assignments sa
                        INNER JOIN shifts s
                            ON s.id = sa.shift_id
                           AND s.organization_id = e.organization_id
                        WHERE sa.id = ?
                          AND sa.employee_id = e.id
                    )
              )
        ');

        $stmt->execute([
            $data['organization_id'],
            $data['employee_id'],
            $data['attendance_date'],
            $data['scheduled_start_time'],
            $data['scheduled_end_time'],
            $data['grace_period_minutes'],
            $data['shift_assignment_id'],
            $data['clock_in_at'],
            $data['clock_out_at'],
            $data['attendance_status_id'],
            $data['source'],
            $data['employee_id'],
            $data['organization_id'],
            $data['shift_assignment_id'],
            $data['shift_assignment_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Attendance could not be created for this employee and organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    public function clockOut(
        int $attendanceId,
        int $organizationId,
        int $employeeId,
        string $clockOutAt,
        int $statusId
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE attendance
            SET
                clock_out_at = ?,
                attendance_status_id = ?
            WHERE id = ?
              AND organization_id = ?
              AND employee_id = ?
              AND clock_out_at IS NULL
        ');

        $stmt->execute([
            $clockOutAt,
            $statusId,
            $attendanceId,
            $organizationId,
            $employeeId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function createLog(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO attendance_logs (
                attendance_id,
                employee_id,
                event_type,
                event_time,
                source,
                ip_address,
                user_agent,
                device_identifier
            )
            SELECT
                ?, ?, ?, ?, ?, ?, ?, ?
            FROM attendance a
            INNER JOIN employees e
                ON e.id = a.employee_id
               AND e.organization_id = a.organization_id
            WHERE a.id = ?
              AND a.employee_id = ?
              AND a.organization_id = ?
        ');

        $stmt->execute([
            $data['attendance_id'],
            $data['employee_id'],
            $data['event_type'],
            $data['event_time'],
            $data['source'],
            $data['ip_address'],
            $data['user_agent'],
            $data['device_identifier'],
            $data['attendance_id'],
            $data['employee_id'],
            $data['organization_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Attendance event could not be recorded for this organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    public function findById(
        int $attendanceId,
        int $organizationId,
        int $employeeId
    ): ?array
    {
        $stmt = $this->db->prepare('
        SELECT *
        FROM attendance
        WHERE id = ?
          AND organization_id = ?
          AND employee_id = ?
        LIMIT 1
    ');

        $stmt->execute([
            $attendanceId,
            $organizationId,
            $employeeId,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get attendance history for one employee
     * within an organization and date range.
     */
    public function getEmployeeAttendanceHistory(
        int $employeeId,
        int $organizationId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare('
        SELECT
            a.id,
            a.attendance_date,
            a.scheduled_start_time,
            a.scheduled_end_time,
            a.grace_period_minutes,
            a.clock_in_at,
            a.clock_out_at,
            a.attendance_status_id,
            s.name AS status_name,
            s.code AS status_code,

            CASE
                WHEN a.clock_out_at IS NOT NULL
                THEN ROUND(
                    TIMESTAMPDIFF(
                        SECOND,
                        a.clock_in_at,
                        a.clock_out_at
                    ) / 3600,
                    2
                )
                ELSE NULL
            END AS hours_worked,

            CASE
                WHEN a.scheduled_start_time IS NOT NULL
                 AND TIME(a.clock_in_at) >
                     ADDTIME(
                         a.scheduled_start_time,
                         SEC_TO_TIME(
                             a.grace_period_minutes * 60
                         )
                     )
                THEN 1
                ELSE 0
            END AS arrived_late

        FROM attendance a

        INNER JOIN attendance_statuses s
            ON s.id = a.attendance_status_id

        WHERE a.employee_id = :employee_id
          AND a.organization_id = :organization_id
          AND a.attendance_date BETWEEN :start_date AND :end_date

        ORDER BY a.attendance_date DESC, a.id DESC
    ');

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate attendance statistics for one employee.
     */
    public function getEmployeeAttendanceSummary(
        int $employeeId,
        int $organizationId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare('
        SELECT
            COUNT(*) AS days_recorded,

            COALESCE(
                SUM(
                    CASE
                        WHEN a.clock_out_at IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS completed_days,

            COALESCE(
                SUM(
                    CASE
                        WHEN a.clock_out_at IS NULL
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS incomplete_days,

            COALESCE(
                SUM(
                    CASE
                        WHEN a.scheduled_start_time IS NOT NULL
                         AND TIME(a.clock_in_at) >
                             ADDTIME(
                                 a.scheduled_start_time,
                                 SEC_TO_TIME(
                                     a.grace_period_minutes * 60
                                 )
                             )
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS late_arrivals,

            COALESCE(
                SUM(
                    CASE
                        WHEN a.scheduled_end_time IS NOT NULL
                         AND TIME(a.clock_out_at) < a.scheduled_end_time
                         AND a.clock_out_at IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ) AS early_departures,

            ROUND(
                COALESCE(
                    SUM(
                        CASE
                            WHEN a.clock_out_at IS NOT NULL
                            THEN TIMESTAMPDIFF(
                                SECOND,
                                a.clock_in_at,
                                a.clock_out_at
                            )
                            ELSE 0
                        END
                    ),
                    0
                ) / 3600,
                2
            ) AS total_hours_worked

        FROM attendance a

        WHERE a.employee_id = :employee_id
          AND a.organization_id = :organization_id
          AND a.attendance_date BETWEEN :start_date AND :end_date
    ');

        $stmt->execute([
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    
    /**
     * Get organization-wide attendance records for a date range.
     */
    public function getOrganizationAttendanceReport(
        int $organizationId,
        string $startDate,
        string $endDate,
        ?int $employeeId = null
    ): array {
        $employeeFilter = $employeeId === null
            ? ''
            : ' AND a.employee_id = :employee_id';

        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.attendance_date,
                a.clock_in_at,
                a.clock_out_at,
                a.scheduled_start_time,
                a.scheduled_end_time,
                a.grace_period_minutes,
                a.attendance_status_id,
                e.employee_number,
                e.first_name,
                e.last_name,
                CASE
                    WHEN a.clock_in_at IS NOT NULL
                     AND a.clock_out_at IS NULL
                    THEN 'Incomplete'
                    ELSE s.name
                END AS status_name,
                CASE
                    WHEN a.clock_in_at IS NOT NULL
                     AND a.clock_out_at IS NULL
                    THEN 'INCOMPLETE'
                    ELSE s.code
                END AS status_code,

                CASE
                    WHEN a.clock_out_at IS NOT NULL
                    THEN ROUND(
                        TIMESTAMPDIFF(
                            SECOND,
                            a.clock_in_at,
                            a.clock_out_at
                        ) / 3600,
                        2
                    )
                    ELSE NULL
                END AS hours_worked,

                CASE
                    WHEN a.scheduled_start_time IS NOT NULL
                        AND TIME(a.clock_in_at) >
                            ADDTIME(
                                a.scheduled_start_time,
                                SEC_TO_TIME(a.grace_period_minutes * 60)
                            )
                    THEN 1
                    ELSE 0
                END AS arrived_late

            FROM attendance a

            INNER JOIN employees e
                ON e.id = a.employee_id
                AND e.organization_id = a.organization_id

            INNER JOIN attendance_statuses s
                ON s.id = a.attendance_status_id

            WHERE a.organization_id = :organization_id
            AND a.attendance_date BETWEEN :start_date AND :end_date
            {$employeeFilter}

            ORDER BY
                a.attendance_date DESC,
                e.last_name ASC,
                e.first_name ASC
        ");

        $parameters = [
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($employeeId !== null) {
            $parameters['employee_id'] = $employeeId;
        }

        $stmt->execute($parameters);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDashboardSummary(int $organizationId, string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT CASE
                    WHEN a.clock_in_at IS NOT NULL
                    THEN a.employee_id
                END) AS present_today,

                COUNT(DISTINCT CASE
                    WHEN s.code = 'LATE'
                    THEN a.employee_id
                END) AS late_today,

                COUNT(DISTINCT CASE
                    WHEN a.clock_in_at IS NOT NULL
                    AND a.clock_out_at IS NULL
                    THEN a.employee_id
                END) AS currently_working

            FROM attendance a
            LEFT JOIN attendance_statuses s
                ON s.id = a.attendance_status_id
            WHERE a.organization_id = :organization_id
            AND a.attendance_date = :attendance_date
        ");

        $stmt->execute([
            'organization_id' => $organizationId,
            'attendance_date' => $date
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'present_today' => 0,
            'late_today' => 0,
            'currently_working' => 0
        ];
    }
    
    public function getAttendanceTrend(
        int $organizationId,
        string $startDate,
        string $endDate
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                attendance_date,

                COUNT(DISTINCT CASE
                    WHEN clock_in_at IS NOT NULL
                    THEN employee_id
                END) AS present_count,

                COUNT(DISTINCT CASE
                    WHEN clock_in_at IS NOT NULL
                    AND attendance_status_id IN (
                        SELECT id
                        FROM attendance_statuses
                        WHERE code = 'LATE'
                    )
                    THEN employee_id
                END) AS late_count

            FROM attendance

            WHERE organization_id = :organization_id
            AND attendance_date BETWEEN :start_date AND :end_date

            GROUP BY attendance_date
            ORDER BY attendance_date ASC
        ");

        $stmt->execute([
            'organization_id' => $organizationId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
