<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRepository;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use App\Database;
use DateTime;
use RuntimeException;

class LeaveService
{
    private LeaveRepository $leaveRepository;
    private EmployeeRepository $employeeRepository;
    private NotificationService $notificationService;

    public function __construct(
        LeaveRepository $leaveRepository,
        EmployeeRepository $employeeRepository
    ) {
        $this->leaveRepository = $leaveRepository;
        $this->employeeRepository = $employeeRepository;

        $this->notificationService = new NotificationService(
            new NotificationRepository()
        );
    }

    /*
     * Get active leave types
     * for an organization.
     */
    public function getLeaveTypes(
        int $organizationId
    ): array {
        return $this
            ->leaveRepository
            ->getLeaveTypes($organizationId);
    }

    /*
     * Get all leave requests
     * for an organization.
     */
    public function getAll(
        int $organizationId
    ): array {
        return $this
            ->leaveRepository
            ->getAll($organizationId);
    }

    /*
     * Get leave requests
     * for one employee.
     */
    public function getByEmployee(
        int $employeeId,
        int $organizationId
    ): array {
        return $this
            ->leaveRepository
            ->getByEmployee(
                $employeeId,
                $organizationId
            );
    }

    /*
     * Get one leave request.
     */
    public function getById(
        int $leaveRequestId,
        int $organizationId
    ): ?array {
        return $this
            ->leaveRepository
            ->findById(
                $leaveRequestId,
                $organizationId
            );
    }

    /*
     * Create a leave request.
     *
     * Initial status:
     * 1 = Pending
     */
    public function createRequest(
        int $organizationId,
        int $employeeId,
        int $leaveTypeId,
        string $startDate,
        string $endDate,
        ?string $reason
    ): int {
        /*
         * Validate organization.
         */
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        /*
         * Validate employee.
         */
        if ($employeeId <= 0) {
            throw new RuntimeException(
                'Invalid employee.'
            );
        }

        /*
         * Validate leave type.
         */
        if ($leaveTypeId <= 0) {
            throw new RuntimeException(
                'Invalid leave type.'
            );
        }

        /*
         * Validate dates.
         */
        $start = DateTime::createFromFormat(
            'Y-m-d',
            $startDate
        );

        $end = DateTime::createFromFormat(
            'Y-m-d',
            $endDate
        );

        if (
            !$start ||
            !$end ||
            $start->format('Y-m-d') !== $startDate ||
            $end->format('Y-m-d') !== $endDate
        ) {
            throw new RuntimeException(
                'Invalid leave dates.'
            );
        }

        /*
         * End date cannot be before
         * start date.
         */
        if ($end < $start) {
            throw new RuntimeException(
                'Leave end date cannot be before the start date.'
            );
        }

        /*
         * Verify employee belongs
         * to this organization.
         */
        $employee =
            $this
                ->employeeRepository
                ->findById(
                    $employeeId,
                    $organizationId
                );

        if (!$employee) {
            throw new RuntimeException(
                'Employee not found.'
            );
        }

        /*
         * Verify leave type belongs
         * to this organization.
         */
        $leaveTypes =
            $this
                ->leaveRepository
                ->getLeaveTypes(
                    $organizationId
                );

        $leaveType = null;

        foreach ($leaveTypes as $type) {
            if ((int) $type['id'] === $leaveTypeId) {
                $leaveType = $type;
                break;
            }
        }

        if (!$leaveType) {
            throw new RuntimeException(
                'Leave type not found or inactive.'
            );
        }

        /*
         * Check for overlapping leave.
         *
         * Only approved or pending requests
         * should prevent another request
         * for the same period.
         */
        $existingRequests =
            $this
                ->leaveRepository
                ->getByEmployee(
                    $employeeId,
                    $organizationId
                );

        foreach ($existingRequests as $existing) {
            $statusCode =
                strtoupper(
                    $existing['status_code'] ?? ''
                );

            if (
                $statusCode !== 'PENDING' &&
                $statusCode !== 'APPROVED'
            ) {
                continue;
            }

            $existingStart =
                $existing['start_date'];

            $existingEnd =
                $existing['end_date'];

            /*
             * Overlap condition:
             *
             * New start <= existing end
             * AND
             * New end >= existing start
             */
            if (
                $startDate <= $existingEnd &&
                $endDate >= $existingStart
            ) {
                throw new RuntimeException(
                    'The employee already has a pending or approved leave request covering this period.'
                );
            }
        }

        /*
         * Clean reason.
         */
        $reason = $reason !== null
            ? trim($reason)
            : null;

        if ($reason === '') {
            $reason = null;
        }

        $pendingStatus = $this->leaveRepository->getStatusByCode('PENDING');

        if ($pendingStatus === null) {
            throw new RuntimeException(
                'The pending leave request status is not configured.'
            );
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            $leaveRequestId =
                $this
                    ->leaveRepository
                    ->create([
                        'organization_id' => $organizationId,
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'reason' => $reason,
                        'status_id' => (int) $pendingStatus['id']
                    ]);

            $db->commit();

            return $leaveRequestId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    /*
     * Get approval history.
     */
    public function getApprovals(
        int $leaveRequestId,
        int $organizationId
    ): array {
        /*
         * Make sure the request exists
         * inside the organization.
         */
        $request =
            $this
                ->leaveRepository
                ->findById(
                    $leaveRequestId,
                    $organizationId
                );

        if (!$request) {
            throw new RuntimeException(
                'Leave request not found.'
            );
        }

        return $this
            ->leaveRepository
            ->getApprovals(
                $leaveRequestId,
                $organizationId
            );
    }

    public function approve(
        int $leaveRequestId,
        int $organizationId,
        int $approverId,
        ?string $comment = null
    ): bool {
        return $this->processDecision(
            $leaveRequestId,
            $organizationId,
            $approverId,
            'APPROVED',
            $comment
        );
    }

    public function reject(
        int $leaveRequestId,
        int $organizationId,
        int $approverId,
        ?string $comment = null
    ): bool {
        return $this->processDecision(
            $leaveRequestId,
            $organizationId,
            $approverId,
            'REJECTED',
            $comment
        );
    }

    private function processDecision(
        int $leaveRequestId,
        int $organizationId,
        int $approverId,
        string $decision,
        ?string $comment = null
    ): bool {
        $decision = strtoupper($decision);

        if (!in_array($decision, ['APPROVED', 'REJECTED'], true)) {
            throw new RuntimeException('Invalid leave decision.');
        }

        if ($approverId <= 0) {
            throw new RuntimeException('Invalid approver.');
        }

        // Get the leave request, including its employee details.
        $leaveRequest = $this->leaveRepository->findById(
            $leaveRequestId,
            $organizationId
        );

        if (!$leaveRequest) {
            throw new RuntimeException('Leave request not found.');
        }

        // Only pending requests can be approved or rejected.
        if (strtoupper($leaveRequest['status_code']) !== 'PENDING') {
            throw new RuntimeException(
                'Only pending leave requests can be approved or rejected.'
            );
        }

        // Get the employee's active user account.
        $recipientUserId = $this->leaveRepository->getUserIdByEmployeeId(
            (int) $leaveRequest['employee_id'],
            $organizationId
        );

        if ($recipientUserId <= 0) {
            throw new RuntimeException(
                'The employee does not have an active user account.'
            );
        }

        // Get approval action ID.
        $actionId = $this->leaveRepository->getApprovalActionId($decision);

        if (!$actionId) {
            throw new RuntimeException('Approval action not found.');
        }

        // Get leave request status ID.
        $status = $this->leaveRepository->getStatusByCode($decision);

        if (!$status) {
            throw new RuntimeException('Leave request status not found.');
        }

        $comment = $comment !== null ? trim($comment) : null;

        if ($comment === '') {
            $comment = null;
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Update leave request status.
            $updated = $this->leaveRepository->updateStatus(
                $leaveRequestId,
                $organizationId,
                (int) $status['id']
            );

            if (!$updated) {
                throw new RuntimeException(
                    'Unable to update leave request status.'
                );
            }

            // Record approval/rejection history.
            $this->leaveRepository->addApproval([
                'leave_request_id' => $leaveRequestId,
                'approver_id' => $approverId,
                'action_id' => $actionId,
                'comment' => $comment,
                'organization_id' => $organizationId,
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        // Prepare notification details.
        $typeCode = $decision === 'APPROVED'
            ? 'LEAVE_APPROVED'
            : 'LEAVE_REJECTED';

        $title = $decision === 'APPROVED'
            ? 'Leave Request Approved'
            : 'Leave Request Rejected';

        $employeeName = trim(
            $leaveRequest['first_name'] . ' '
            . $leaveRequest['last_name']
        );

        $message = sprintf(
            'Hello %s, your %s leave request from %s to %s has been %s.',
            $employeeName,
            $leaveRequest['leave_type'],
            $leaveRequest['start_date'],
            $leaveRequest['end_date'],
            strtolower($decision)
        );

        if ($comment !== null) {
            $message .= ' Comment: ' . $comment;
        }

        // Send notification only to the affected employee.
        try {
            $this->notificationService->create(
                $organizationId,
                $typeCode,
                $title,
                $message,
                $approverId,
                [$recipientUserId]
            );
        } catch (\Throwable $e) {
            // Do not undo a successfully recorded leave decision.
            error_log(
                'Leave notification failed: ' . $e->getMessage()
            );
        }

        return true;
    }
}
