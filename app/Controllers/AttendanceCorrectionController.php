<?php

namespace App\Controllers;

use App\HttpException;
use App\Repositories\AttendanceCorrectionRepository;
use App\Repositories\NotificationRepository;
use App\Services\AttendanceCorrectionService;
use App\Services\PermissionService;
use App\Session;
use PDOException;
use RuntimeException;

class AttendanceCorrectionController
{
    private AttendanceCorrectionService $service;

    public function __construct()
    {
        $this->service = new AttendanceCorrectionService(
            new AttendanceCorrectionRepository(),
            new NotificationRepository()
        );
    }

    public function index(): void
    {
        $organizationId = $this->organizationId();
        $canReview = PermissionService::has('attendance.correction.view');
        if (
            !$canReview
            && !PermissionService::has('attendance.correction.request')
        ) {
            throw new HttpException(
                'You do not have permission to view correction requests.',
                403
            );
        }

        $isAdmin = $canReview;
        $requests = $isAdmin
            ? $this->service->getForOrganization($organizationId)
            : $this->service->getForEmployee(
                $this->currentUserId(),
                $organizationId
            );

        require __DIR__ . '/../Views/attendance-corrections/index.php';
    }

    public function requestForm(int $attendanceId): void
    {
        $this->requirePermission('attendance.correction.request');
        $attendance = $this->service->getAttendance(
            $attendanceId,
            $this->organizationId(),
            $this->employeeId()
        );
        if ($attendance === null) {
            throw new HttpException('Attendance record not found.', 404);
        }

        $hasPendingRequest = $this->service->hasPendingRequest(
            $attendanceId,
            $this->organizationId()
        );
        $csrfToken = $this->csrfToken();

        require __DIR__ . '/../Views/attendance-corrections/request.php';
    }

    public function storeRequest(int $attendanceId): void
    {
        $this->requirePermission('attendance.correction.request');
        $redirectUrl = APP_BASE_PATH
            . '/attendance/' . $attendanceId . '/correction-request';

        try {
            $this->verifyCsrfToken();
            $this->service->createRequest(
                $attendanceId,
                $this->organizationId(),
                $this->employeeId(),
                $this->currentUserId(),
                $this->postString('reason'),
                $this->postOptionalString('requested_clock_in'),
                $this->postOptionalString('requested_clock_out')
            );
        } catch (PDOException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            $this->flashAndRedirect($e->getMessage(), 'error', $redirectUrl);
            return;
        }

        $this->flashAndRedirect(
            'Attendance correction request submitted for review.',
            'success',
            APP_BASE_PATH . '/attendance/corrections'
        );
    }

    public function reviewForm(int $requestId): void
    {
        $this->requirePermission('attendance.correction.view');
        $request = $this->service->getRequest(
            $requestId,
            $this->organizationId()
        );
        if ($request === null) {
            throw new HttpException('Correction request not found.', 404);
        }
        if ($request['status_code'] !== 'PENDING') {
            throw new HttpException(
                'This correction request has already been reviewed.',
                409
            );
        }
        $csrfToken = $this->csrfToken();
        $canApprove = PermissionService::has('attendance.correction.approve');
        $canReject = PermissionService::has('attendance.correction.reject');

        require __DIR__ . '/../Views/attendance-corrections/review.php';
    }

    public function approve(int $requestId): void
    {
        $this->requirePermission('attendance.correction.approve');
        $this->review($requestId, true);
    }

    public function reject(int $requestId): void
    {
        $this->requirePermission('attendance.correction.reject');
        $this->review($requestId, false);
    }

    private function review(int $requestId, bool $approve): void
    {
        $redirectUrl = APP_BASE_PATH . '/attendance/corrections/' . $requestId;
        try {
            $this->verifyCsrfToken();
            $this->service->review(
                $requestId,
                $this->organizationId(),
                $this->currentUserId(),
                $approve,
                $this->postOptionalString('clock_in_at'),
                $this->postOptionalString('clock_out_at'),
                $this->postString('review_comment')
            );
        } catch (PDOException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            $this->flashAndRedirect($e->getMessage(), 'error', $redirectUrl);
            return;
        }

        $message = $approve
            ? 'Attendance corrected and the employee notified.'
            : 'Correction request rejected and the employee notified.';
        $this->flashAndRedirect(
            $message,
            'success',
            APP_BASE_PATH . '/attendance/corrections'
        );
    }

    private function requirePermission(string $permission): void
    {
        if (!PermissionService::has($permission)) {
            throw new HttpException(
                'You do not have permission to perform this action.',
                403
            );
        }
    }

    private function organizationId(): int
    {
        $organizationId = (int) (
            Session::get('user', [])['organization_id'] ?? 0
        );
        if ($organizationId <= 0) {
            throw new HttpException('Invalid organization.', 403);
        }
        return $organizationId;
    }

    private function employeeId(): int
    {
        $employeeId = (int) (Session::get('user', [])['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new HttpException('Invalid employee session.', 403);
        }
        return $employeeId;
    }

    private function currentUserId(): int
    {
        $userId = (int) (Session::get('user', [])['id'] ?? 0);
        if ($userId <= 0) {
            throw new HttpException('Invalid user session.', 403);
        }
        return $userId;
    }

    private function csrfToken(): string
    {
        $token = Session::get('attendance_correction_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('attendance_correction_csrf_token', $token);
        }
        return $token;
    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('attendance_correction_csrf_token');
        $submittedToken = $_POST['_csrf_token'] ?? null;
        if (
            !is_string($sessionToken)
            || !is_string($submittedToken)
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            throw new RuntimeException(
                'This form has expired. Reload the page and try again.'
            );
        }
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            throw new RuntimeException('Invalid form input.');
        }
        return $value;
    }

    private function postOptionalString(string $key): ?string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            throw new RuntimeException('Invalid form input.');
        }
        return trim($value) === '' ? null : $value;
    }

    private function flashAndRedirect(
        string $message,
        string $type,
        string $url
    ): void {
        Session::set('flash_message', [
            'type' => $type,
            'title' => $type === 'success' ? 'Attendance correction' : 'Action failed',
            'message' => $message,
        ]);
        header('Location: ' . $url, true, 303);
        exit;
    }
}
