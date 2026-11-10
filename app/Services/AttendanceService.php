<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use RuntimeException;

class AttendanceService
{
    private AttendanceRepository $attendanceRepository;

    public function __construct(
        AttendanceRepository $attendanceRepository
    ) {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Get an employee's attendance for a specific date.
     */
    public function getAttendanceForDate(
        int $employeeId,
        int $organizationId,
        string $date
    ): ?array {
        return $this
            ->attendanceRepository
            ->findByEmployeeAndDate(
                $employeeId,
                $organizationId,
                $date
            );
    }

    /**
     * Get the employee's active shift assignment
     * for a specific date.
     */
    public function getActiveShift(
        int $employeeId,
        int $organizationId,
        string $date
    ): ?array {
        return $this
            ->attendanceRepository
            ->getActiveShiftAssignment(
                $employeeId,
                $organizationId,
                $date
            );
    }

    /**
     * Get an attendance status by code.
     */
    public function getStatus(
        string $code
    ): array {
        $status =
            $this
                ->attendanceRepository
                ->getStatusByCode($code);

        if (!$status) {
            throw new RuntimeException(
                "Attendance status '{$code}' was not found."
            );
        }

        return $status;
    }

    // Clock-in method

    public function clockIn(
        int $organizationId,
        int $employeeId,
        string $dateTime,
        string $source = 'WEB'
    ): int {
        if ($organizationId <= 0 || $employeeId <= 0) {
            throw new RuntimeException('Invalid employee session.');
        }

        $clockIn = new \DateTimeImmutable($dateTime);

        $date = $clockIn->format('Y-m-d');
        $clockInTime = $clockIn->format('H:i:s');

        // Prevent duplicate clock-in for the same day.
        $existing =
            $this
                ->attendanceRepository
                ->findByEmployeeAndDate(
                    $employeeId,
                    $organizationId,
                    $date
                );

        if ($existing) {
            throw new RuntimeException(
                'Attendance has already been recorded for this employee today.'
            );
        }

        // Get the employee's active shift.
        $shift =
            $this
                ->attendanceRepository
                ->getActiveShiftAssignment(
                    $employeeId,
                    $organizationId,
                    $date
                );

        if ($shift) {
            $scheduledStart = $shift['start_time'];

            $graceEnd = new \DateTimeImmutable(
                $date . ' ' . $scheduledStart
            );

            $graceEnd = $graceEnd->modify(
                '+' . (int) $shift['grace_period_minutes'] . ' minutes'
            );

            if (
                $clockIn <= $graceEnd
            ) {
                $status = $this->getStatus('PRESENT');
            } else {
                $status = $this->getStatus('LATE');
            }

            $scheduledStartTime = $shift['start_time'];
            $scheduledEndTime = $shift['end_time'];
            $gracePeriod = (int) $shift['grace_period_minutes'];
            $shiftAssignmentId = (int) $shift['id'];
        } else {
            // No-shift organization.
            $status = $this->getStatus('PRESENT');

            $scheduledStartTime = null;
            $scheduledEndTime = null;
            $gracePeriod = 0;
            $shiftAssignmentId = null;
        }

        // Create attendance record.
        $attendanceId =
            $this->attendanceRepository->create([
                'organization_id' => $organizationId,
                'employee_id' => $employeeId,
                'attendance_date' => $date,
                'scheduled_start_time' => $scheduledStartTime,
                'scheduled_end_time' => $scheduledEndTime,
                'grace_period_minutes' => $gracePeriod,
                'shift_assignment_id' => $shiftAssignmentId,
                'clock_in_at' => $clockIn->format('Y-m-d H:i:s'),
                'clock_out_at' => null,
                'attendance_status_id' => $status['id'],
                'source' => $source
            ]);

        // Record clock-in event.
        $this->attendanceRepository->createLog([
            'attendance_id' => $attendanceId,
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
            'event_type' => 'CLOCK_IN',
            'event_time' => $clockIn->format('Y-m-d H:i:s'),
            'source' => $source,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'device_identifier' => null
        ]);

        return $attendanceId;
    }

    //clockOut method

    public function clockOut(
        int $attendanceId,
        int $organizationId,
        int $employeeId,
        string $dateTime,
        string $source = 'WEB'
    ): bool {
        if (
            $attendanceId <= 0 ||
            $organizationId <= 0 ||
            $employeeId <= 0
        ) {
            throw new RuntimeException('Invalid attendance details.');
        }

        $attendance =
            $this
                ->attendanceRepository
                ->findById(
                    $attendanceId,
                    $organizationId,
                    $employeeId
                );

        if (!$attendance) {
            throw new RuntimeException(
                'Attendance record was not found.'
            );
        }

        // Prevent duplicate clock-out.
        if ($attendance['clock_out_at'] !== null) {
            throw new RuntimeException(
                'This attendance record has already been clocked out.'
            );
        }

        $clockOut = new \DateTimeImmutable($dateTime);

        $statusId = (int) $attendance['attendance_status_id'];

        // Check early departure when a scheduled
        // end time exists.
        if ($attendance['scheduled_end_time'] !== null) {
            $scheduledEnd = new \DateTimeImmutable(
                $attendance['attendance_date']
                . ' '
                . $attendance['scheduled_end_time']
            );

            if ($clockOut < $scheduledEnd) {
                $status =
                    $this->getStatus('EARLY_DEPARTURE');

                $statusId = (int) $status['id'];
            }
        }

        $updated =
            $this
                ->attendanceRepository
                ->clockOut(
                    $attendanceId,
                    $organizationId,
                    $employeeId,
                    $clockOut->format('Y-m-d H:i:s'),
                    $statusId
                );

        if (!$updated) {
            throw new RuntimeException(
                'Unable to record clock-out.'
            );
        }

        // Record clock-out event.
        $this->attendanceRepository->createLog([
            'attendance_id' => $attendanceId,
            'employee_id' => (int) $attendance['employee_id'],
            'organization_id' => $organizationId,
            'event_type' => 'CLOCK_OUT',
            'event_time' => $clockOut->format('Y-m-d H:i:s'),
            'source' => $source,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'device_identifier' => null
        ]);

        return true;
    }

    
    /**
     * Get an employee's attendance history for a date range.
     */
    public function getEmployeeAttendanceHistory(
        int $employeeId,
        int $organizationId,
        string $startDate,
        string $endDate
    ): array {
        return $this->attendanceRepository
            ->getEmployeeAttendanceHistory(
                $employeeId,
                $organizationId,
                $startDate,
                $endDate
            );
    }

    /**
     * Get an employee's attendance summary for a date range.
     */
    public function getEmployeeAttendanceSummary(
        int $employeeId,
        int $organizationId,
        string $startDate,
        string $endDate
    ): array {
        return $this->attendanceRepository
            ->getEmployeeAttendanceSummary(
                $employeeId,
                $organizationId,
                $startDate,
                $endDate
            );
    }

    /**
     * Get organization-wide attendance report.
     */
    public function getOrganizationAttendanceReport(
        int $organizationId,
        string $startDate,
        string $endDate,
        ?int $employeeId = null
    ): array {
        return $this->attendanceRepository
            ->getOrganizationAttendanceReport(
                $organizationId,
                $startDate,
                $endDate,
                $employeeId
            );
    }


}
