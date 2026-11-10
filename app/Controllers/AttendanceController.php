<?php

namespace App\Controllers;

use App\Repositories\AttendanceRepository;
use App\Repositories\EmployeeRepository;
use App\Services\AttendanceService;
use RuntimeException;


class AttendanceController
{
    private AttendanceService $attendanceService;

    public function __construct()
    {
        $this->attendanceService =
            new AttendanceService(
                new AttendanceRepository(\App\Database::connection())
            );
    }

    /*
     * Clock in
     */
    public function clockIn(): void
    {
        try {
            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                        ?? 0
                );

            $employeeId =
                (int) (
                    $_SESSION['user']['employee_id']
                        ?? 0
                );

            if ($organizationId <= 0 || $employeeId <= 0) {
                throw new RuntimeException(
                    'Invalid employee session.'
                );
            }

            $this
                ->attendanceService
                ->clockIn(
                    $organizationId,
                    $employeeId,
                    date('Y-m-d H:i:s')
                );

            header(
                'Location: ' . APP_BASE_PATH . '/attendance'
            );

            exit;
        } catch (RuntimeException $e) {
            http_response_code(422);

            $error = $e->getMessage();

            $employeeId =
                (int) (
                    $_SESSION['user']['employee_id']
                        ?? 0
                );
            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                        ?? 0
                );

            $date = date('Y-m-d');

            $attendance =
                $this
                    ->attendanceService
                    ->getAttendanceForDate(
                        $employeeId,
                        $organizationId,
                        $date
                    );

            $shift =
                $this
                    ->attendanceService
                    ->getActiveShift(
                        $employeeId,
                        $organizationId,
                        $date
                    );

            require __DIR__ . '/../Views/attendance/index.php';
            exit;
        }
    }

    /*
     * Clock out
     */

    public function clockOut(): void
    {
        try {
            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                        ?? 0
                );
            $employeeId =
                (int) (
                    $_SESSION['user']['employee_id']
                        ?? 0
                );

            if ($employeeId <= 0 || $organizationId <= 0) {
                throw new RuntimeException(
                    'Invalid employee session.'
                );
            }

            $date = date('Y-m-d');

            $attendance =
                $this
                    ->attendanceService
                    ->getAttendanceForDate(
                        $employeeId,
                        $organizationId,
                        $date
                    );

            if (!$attendance) {
                throw new RuntimeException(
                    'No attendance record found for today.'
                );
            }

            $this
                ->attendanceService
                ->clockOut(
                    (int) $attendance['id'],
                    $organizationId,
                    $employeeId,
                    date('Y-m-d H:i:s')
                );

            header(
                'Location: ' . APP_BASE_PATH . '/attendance'
            );

            exit;
        } catch (RuntimeException $e) {
            http_response_code(422);

            $error = $e->getMessage();

            $employeeId =
                (int) (
                    $_SESSION['user']['employee_id']
                        ?? 0
                );
            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                        ?? 0
                );

            $date = date('Y-m-d');

            $attendance =
                $this
                    ->attendanceService
                    ->getAttendanceForDate(
                        $employeeId,
                        $organizationId,
                        $date
                    );

            $shift =
                $this
                    ->attendanceService
                    ->getActiveShift(
                        $employeeId,
                        $organizationId,
                        $date
                    );

            require __DIR__ . '/../Views/attendance/index.php';
            exit;
        }
    }

    /*
     * Attendance page
     */

   
    public function index(): void
    {
        $employeeId = (int) ($_SESSION['user']['employee_id'] ?? 0);
        $organizationId = (int) ($_SESSION['user']['organization_id'] ?? 0);

        if ($employeeId <= 0 || $organizationId <= 0) {
            throw new RuntimeException('Invalid employee session.');
        }

        // Keep today's attendance and shift for clock-in/clock-out.
        $today = date('Y-m-d');

        $attendance = $this->attendanceService
            ->getAttendanceForDate(
                $employeeId,
                $organizationId,
                $today
            );

        $shift = $this->attendanceService
            ->getActiveShift(
                $employeeId,
                $organizationId,
                $today
            );

        // Read the selected month, defaulting to the current month.
        $month = $_GET['month'] ?? date('Y-m');

        // Validate the month format and reject invalid dates.
        $monthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
        $dateErrors = \DateTimeImmutable::getLastErrors();

        if (
            !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ||
            $monthDate === false ||
            ($dateErrors !== false &&
                ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            $month = date('Y-m');
            $monthDate = new \DateTimeImmutable($month . '-01');
        }

        $startDate = $monthDate->format('Y-m-01');
        $endDate = $monthDate->format('Y-m-t');

        // Load this employee's history and summary for the selected month.
        $attendanceHistory = $this->attendanceService
            ->getEmployeeAttendanceHistory(
                $employeeId,
                $organizationId,
                $startDate,
                $endDate
            );

        $attendanceSummary = $this->attendanceService
            ->getEmployeeAttendanceSummary(
                $employeeId,
                $organizationId,
                $startDate,
                $endDate
            );
        $canRequestCorrection = \App\Services\PermissionService::has(
            'attendance.correction.request'
        );
        $canViewCorrectionRequests = \App\Services\PermissionService::has(
            'attendance.correction.view'
        );

        require __DIR__ . '/../Views/attendance/index.php';
    }

    
    /**
     * Display organization-wide attendance reports.
     */
    public function report(): void
    {
        // Only users with organization-wide attendance permission can view this.
        if (!\App\Services\PermissionService::has('attendance.view')) {
            throw new RuntimeException(
                'You do not have permission to view organization attendance reports.'
            );
        }

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization session.');
        }

        // Default to the current month.
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        // Validate both dates strictly as YYYY-MM-DD.
        $isValidDate = static function (string $date): bool {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return false;
            }

            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $errors = \DateTimeImmutable::getLastErrors();

            return $parsed !== false
                && $parsed->format('Y-m-d') === $date
                && (
                    $errors === false
                    || (
                        $errors['warning_count'] === 0
                        && $errors['error_count'] === 0
                    )
                );
        };

        if (
            !is_string($startDate) ||
            !is_string($endDate) ||
            !$isValidDate($startDate) ||
            !$isValidDate($endDate) ||
            $startDate > $endDate
        ) {
            throw new RuntimeException('Please select a valid date range.');
        }

        $employeeIdInput = $_GET['employee_id'] ?? '';

        if (!is_string($employeeIdInput)) {
            throw new RuntimeException('Please select a valid employee.');
        }

        $employeeId = null;
        if ($employeeIdInput !== '') {
            $validatedEmployeeId = filter_var(
                $employeeIdInput,
                FILTER_VALIDATE_INT
            );

            if ($validatedEmployeeId === false || $validatedEmployeeId <= 0) {
                throw new RuntimeException('Please select a valid employee.');
            }

            $employeeId = $validatedEmployeeId;
        }

        $employees = (new EmployeeRepository())
            ->getReportEmployees($organizationId);

        if (
            $employeeId !== null
            && !in_array(
                $employeeId,
                array_map(
                    static fn(array $employee): int => (int) $employee['id'],
                    $employees
                ),
                true
            )
        ) {
            throw new RuntimeException('The selected employee was not found.');
        }

        $reportRows = $this->attendanceService
            ->getOrganizationAttendanceReport(
                $organizationId,
                $startDate,
                $endDate,
                $employeeId
            );

        $employeeId = $employeeId ?? 0;

        require __DIR__ . '/../Views/attendance/reports.php';
    }

}
