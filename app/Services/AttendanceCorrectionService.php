<?php

namespace App\Services;

use App\Database;
use App\Repositories\AttendanceCorrectionRepository;
use App\Repositories\AuditRepository;
use App\Repositories\NotificationRepository;
use RuntimeException;
use Throwable;

class AttendanceCorrectionService
{
    private AttendanceCorrectionRepository $corrections;
    private NotificationRepository $notifications;
    private AuditService $audit;

    public function __construct(
        AttendanceCorrectionRepository $corrections,
        NotificationRepository $notifications
    ) {
        $this->corrections = $corrections;
        $this->notifications = $notifications;
        $this->audit = new AuditService(new AuditRepository());
    }

    public function getAttendance(
        int $attendanceId,
        int $organizationId,
        ?int $employeeId = null
    ): ?array {
        return $this->corrections->getAttendance(
            $attendanceId,
            $organizationId,
            $employeeId
        );
    }

    public function hasPendingRequest(
        int $attendanceId,
        int $organizationId
    ): bool {
        return $this->corrections->hasPendingRequest(
            $attendanceId,
            $organizationId
        );
    }

    public function createRequest(
        int $attendanceId,
        int $organizationId,
        int $employeeId,
        int $userId,
        string $reason,
        ?string $clockInInput,
        ?string $clockOutInput
    ): int {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new RuntimeException(
                'Please provide a reason of at most 2,000 characters.'
            );
        }

        $clockIn = $this->parseDateTimeInput($clockInInput);
        $clockOut = $this->parseDateTimeInput($clockOutInput);
        if ($clockIn === null) {
            throw new RuntimeException(
                'Enter the corrected clock-in time.'
            );
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();
            $this->corrections->lockAttendance(
                $attendanceId,
                $organizationId,
                $employeeId
            );
            $attendance = $this->corrections->getAttendance(
                $attendanceId,
                $organizationId,
                $employeeId
            );
            if ($attendance === null) {
                throw new RuntimeException('Attendance record not found.');
            }
            if ($this->corrections->hasPendingRequest($attendanceId, $organizationId)) {
                throw new RuntimeException(
                    'There is already a pending correction request for this record.'
                );
            }

            $this->validateTimes($attendance, $clockIn, $clockOut);
            if (
                $clockIn === $this->normalizeStoredDateTime($attendance['clock_in_at'])
                && $clockOut === $this->normalizeStoredDateTime($attendance['clock_out_at'])
            ) {
                throw new RuntimeException(
                    'The requested times must differ from the current attendance record.'
                );
            }

            $requestId = $this->corrections->createRequest(
                $attendanceId,
                $organizationId,
                $employeeId,
                $userId,
                $reason,
                $clockIn,
                $clockOut
            );

            $reviewerIds = $this->corrections
                ->getCorrectionReviewerIds($organizationId);
            if ($reviewerIds === []) {
                throw new RuntimeException(
                    'No active attendance correction reviewers are configured for this organization.'
                );
            }

            $notificationType = $this->notifications
                ->getTypeByCode('ATTENDANCE_CORRECTION');
            if ($notificationType === null) {
                throw new RuntimeException(
                    'Attendance correction notifications are not configured.'
                );
            }

            $request = $this->corrections->getRequest(
                $requestId,
                $organizationId
            );
            if ($request === null) {
                throw new RuntimeException(
                    'The attendance correction request could not be loaded.'
                );
            }

            $employeeName = trim(
                ($request['first_name'] ?? '')
                . ' '
                . ($request['last_name'] ?? '')
            );
            $employeeNumber = trim(
                (string) ($request['employee_number'] ?? '')
            );
            $employeeLabel = $employeeName !== ''
                ? $employeeName
                : 'An employee';
            if ($employeeNumber !== '') {
                $employeeLabel .= ' (' . $employeeNumber . ')';
            }

            $notificationId = $this->notifications->create(
                $organizationId,
                (int) $notificationType['id'],
                'Attendance correction request',
                $employeeLabel
                    . ' submitted an attendance correction request for '
                    . $request['attendance_date']
                    . '. Please review the request in Attendance Corrections.',
                $userId
            );
            $this->notifications->addRecipients(
                $notificationId,
                $organizationId,
                $reviewerIds
            );

            $this->audit->recordAction(
                'ATTENDANCE_CORRECTION_REQUESTED',
                'attendance_correction',
                'attendance_correction',
                $requestId,
                null,
                [
                    'attendance_id' => $attendanceId,
                    'reason' => $reason,
                    'requested_clock_in' => $clockIn,
                    'requested_clock_out' => $clockOut,
                ]
            );
            $db->commit();

            return $requestId;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function getForEmployee(int $userId, int $organizationId): array
    {
        return $this->corrections->getForEmployee($userId, $organizationId);
    }

    public function getForOrganization(int $organizationId): array
    {
        return $this->corrections->getForOrganization($organizationId);
    }

    public function getRequest(int $requestId, int $organizationId): ?array
    {
        return $this->corrections->getRequest($requestId, $organizationId);
    }

    public function review(
        int $requestId,
        int $organizationId,
        int $reviewerId,
        bool $approve,
        ?string $clockInInput,
        ?string $clockOutInput,
        string $reviewComment
    ): void {
        $db = Database::connection();
        try {
            $db->beginTransaction();
            $this->corrections->lockRequest($requestId, $organizationId);
            $request = $this->corrections->getRequest(
                $requestId,
                $organizationId
            );
            if ($request === null) {
                throw new RuntimeException('Correction request not found.');
            }
            if ($request['status_code'] !== 'PENDING') {
                throw new RuntimeException(
                    'This correction request has already been reviewed.'
                );
            }

            $reviewComment = trim($reviewComment);
            if (mb_strlen($reviewComment) > 2000) {
                throw new RuntimeException(
                    'The review comment cannot exceed 2,000 characters.'
                );
            }

            $clockIn = null;
            $clockOut = null;
            $newAttendanceStatusId = null;
            if ($approve) {
                $clockIn = $this->parseDateTimeInput($clockInInput);
                $clockOut = $this->parseDateTimeInput($clockOutInput);
                $this->validateTimes($request, $clockIn, $clockOut);
                if ($clockIn === null) {
                    throw new RuntimeException(
                        'A clock-in time is required to approve a correction.'
                    );
                }
                if (
                    $clockIn === $this->normalizeStoredDateTime($request['clock_in_at'])
                    && $clockOut === $this->normalizeStoredDateTime($request['clock_out_at'])
                ) {
                    throw new RuntimeException(
                        'Change at least one attendance time before approving.'
                    );
                }
                $newAttendanceStatusId = $this->attendanceStatusId(
                    $request,
                    $clockIn,
                    $clockOut
                );
            } elseif ($reviewComment === '') {
                throw new RuntimeException(
                    'Enter a reason when rejecting a correction request.'
                );
            }

            if ($approve) {
                $this->corrections->updateAttendance(
                    (int) $request['attendance_id'],
                    $organizationId,
                    $clockIn,
                    $clockOut,
                    $newAttendanceStatusId
                );
                $this->corrections->createAttendanceLog(
                    (int) $request['attendance_id'],
                    $organizationId,
                    (int) $request['employee_id'],
                    date('Y-m-d H:i:s')
                );
            }

            $statusCode = $approve ? 'APPROVED' : 'REJECTED';
            $this->corrections->resolveRequest(
                $requestId,
                $organizationId,
                $reviewerId,
                $statusCode,
                $reviewComment === '' ? null : $reviewComment
            );

            $notificationTypeCode = $approve
                ? 'CORRECTION_APPROVED'
                : 'CORRECTION_REJECTED';
            $notificationType = $this->notifications
                ->getTypeByCode($notificationTypeCode);
            if ($notificationType === null) {
                throw new RuntimeException(
                    'Attendance correction notifications are not configured.'
                );
            }
            $decision = $approve ? 'approved' : 'rejected';
            $message = 'Your attendance correction request for '
                . $request['attendance_date'] . ' was ' . $decision . '.';
            if ($reviewComment !== '') {
                $message .= ' Admin comment: ' . $reviewComment;
            }
            $notificationId = $this->notifications->create(
                $organizationId,
                (int) $notificationType['id'],
                'Attendance correction ' . ucfirst($decision),
                $message,
                $reviewerId
            );
            $this->notifications->addRecipients(
                $notificationId,
                $organizationId,
                [(int) $request['requested_by']]
            );

            $this->audit->recordAction(
                'ATTENDANCE_CORRECTION_' . $statusCode,
                'attendance_correction',
                'attendance_correction',
                $requestId,
                [
                    'attendance_id' => (int) $request['attendance_id'],
                    'clock_in_at' => $request['clock_in_at'],
                    'clock_out_at' => $request['clock_out_at'],
                    'status' => 'PENDING',
                ],
                [
                    'clock_in_at' => $approve ? $clockIn : $request['clock_in_at'],
                    'clock_out_at' => $approve ? $clockOut : $request['clock_out_at'],
                    'status' => $statusCode,
                    'review_comment' => $reviewComment,
                ]
            );

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function attendanceStatusId(
        array $attendance,
        string $clockIn,
        ?string $clockOut
    ): int {
        if ($clockOut === null) {
            return $this->corrections->getAttendanceStatusId('INCOMPLETE');
        }

        $clockInTime = new \DateTimeImmutable($clockIn);
        if ($attendance['scheduled_start_time'] !== null) {
            $scheduledStart = new \DateTimeImmutable(
                $attendance['attendance_date'] . ' '
                . $attendance['scheduled_start_time']
            );
            $graceEnd = $scheduledStart->modify(
                '+' . (int) $attendance['grace_period_minutes'] . ' minutes'
            );
            if ($clockInTime > $graceEnd) {
                return $this->corrections->getAttendanceStatusId('LATE');
            }
        }

        if (
            $attendance['scheduled_end_time'] !== null
            && substr($clockOut, 11, 8) < $attendance['scheduled_end_time']
        ) {
            return $this->corrections->getAttendanceStatusId('EARLY_DEPARTURE');
        }

        return $this->corrections->getAttendanceStatusId('PRESENT');
    }

    private function validateTimes(
        array $attendance,
        ?string $clockIn,
        ?string $clockOut
    ): void {
        if ($clockIn === null && $clockOut === null) {
            throw new RuntimeException(
                'Enter at least one attendance time.'
            );
        }
        if (
            $clockIn !== null
            && substr($clockIn, 0, 10) !== $attendance['attendance_date']
        ) {
            throw new RuntimeException(
                'The clock-in time must be on the recorded attendance date.'
            );
        }
        if (
            $clockIn !== null
            && $clockOut !== null
            && $clockOut < $clockIn
        ) {
            throw new RuntimeException(
                'Clock-out must be later than or equal to clock-in.'
            );
        }
    }

    private function parseDateTimeInput(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            throw new RuntimeException('Enter a valid attendance date and time.');
        }

        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (
            $dateTime === false
            || $dateTime->format('Y-m-d\TH:i') !== $value
            || (
                $errors !== false
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException('Enter a valid attendance date and time.');
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    private function normalizeStoredDateTime(?string $value): ?string
    {
        return $value === null
            ? null
            : (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
    }
}
