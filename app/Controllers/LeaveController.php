<?php

namespace App\Controllers;

use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRepository;
use App\Services\LeaveService;
use App\Services\PermissionService;
use RuntimeException;

class LeaveController
{
    private LeaveService $leaveService;
    private EmployeeRepository $employeeRepository;

    public function __construct()
    {
        $this->leaveService = new LeaveService(
            new LeaveRepository(),
            new EmployeeRepository()
        );

        $this->employeeRepository =
            new EmployeeRepository();
    }

    /*
     * Leave request listing page.
     */

    public function index(): void
    {
        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        $userId = (int) (
            $_SESSION['user']['id'] ?? 0
        );

        if ($organizationId <= 0 || $userId <= 0) {
            throw new RuntimeException('Invalid user or organization.');
        }

        // Users with this permission can view all requests
        // within their organization.
        if (PermissionService::has('leave.view')) {
            $leaveRequests = $this->leaveService->getAll(
                $organizationId
            );
        } else {
            // Other users can view only their own requests.
            $employeeId =
                $this->employeeRepository->getEmployeeIdByUserId(
                    $userId,
                    $organizationId
                );

            $leaveRequests = $employeeId > 0
                ? $this->leaveService->getByEmployee(
                    $employeeId,
                    $organizationId
                )
                : [];
        }

        $leaveTypes = $this->leaveService->getLeaveTypes(
            $organizationId
        );

        require __DIR__ . '/../Views/leave/index.php';
    }

    /*
     * Create leave request page.
     */
    public function create(): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $leaveTypes =
            $this
                ->leaveService
                ->getLeaveTypes($organizationId);

        if (PermissionService::has('leave.create_for_others')) {
            $employees =
                $this
                    ->employeeRepository
                    ->getAll($organizationId);
        } else {
            $employees = [];
        }

        require __DIR__ . '/../Views/leave/create.php';
    }

    /*
     * Store a leave request.
     */

    public function store(): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        $userId =
            (int) (
                $_SESSION['user']['id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user.'
            );
        }

        /*
         * Determine whether the logged-in user can create
         * leave requests on behalf of other employees.
         *
         * Employees must always create leave for themselves.
         */
        $canCreateForOthers =
            PermissionService::has('leave.create_for_others');

        if ($canCreateForOthers) {
            $employeeId =
                (int) (
                    $_POST['employee_id']
                        ?? 0
                );

            if ($employeeId <= 0) {
                throw new RuntimeException(
                    'Please select an employee.'
                );
            }
        } else {
            /*
             * Employee:
             * Ignore any employee_id submitted from the form.
             *
             * Get the employee belonging to the logged-in user.
             */
            $employeeId =
                $this
                    ->employeeRepository
                    ->getEmployeeIdByUserId(
                        $userId,
                        $organizationId
                    );

            if ($employeeId <= 0) {
                throw new RuntimeException(
                    'Your user account is not linked to an employee.'
                );
            }
        }

        $leaveTypeId =
            (int) (
                $_POST['leave_type_id']
                    ?? 0
            );

        $startDate =
            trim($_POST['start_date'] ?? '');

        $endDate =
            trim($_POST['end_date'] ?? '');

        $reason =
            trim($_POST['reason'] ?? '');

        if ($reason === '') {
            $reason = null;
        }

        if ($leaveTypeId <= 0) {
            throw new RuntimeException(
                'Please select a leave type.'
            );
        }

        if ($startDate === '') {
            throw new RuntimeException(
                'Start date is required.'
            );
        }

        if ($endDate === '') {
            throw new RuntimeException(
                'End date is required.'
            );
        }

        $this
            ->leaveService
            ->createRequest(
                $organizationId,
                $employeeId,
                $leaveTypeId,
                $startDate,
                $endDate,
                $reason
            );

        header(
            'Location: ' . APP_BASE_PATH . '/leave'
        );

        exit;
    }

    // Check if the current user can make/view leave decisions
    private function canMakeLeaveDecision(): bool
    {
        $allowedRoles = [
            'super administrator',
            'administrator',
            'hr manager',
        ];

        foreach ($_SESSION['roles'] ?? [] as $role) {
            $name = strtolower(trim(
                (string) ($role['name'] ?? '')
            ));

            if (in_array($name, $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /*
     * View one leave request.
     */

    public function show(int $id): void
    {
        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        $userId = (int) (
            $_SESSION['user']['id'] ?? 0
        );

        if ($organizationId <= 0 || $userId <= 0) {
            throw new RuntimeException('Invalid user or organization.');
        }

        $leaveRequest = $this->leaveService->getById(
            $id,
            $organizationId
        );

        if (!$leaveRequest) {
            throw new RuntimeException('Leave request not found.');
        }

        // Users without leave.view may open only their own request.
        if (!PermissionService::has('leave.view')) {
            $employeeId =
                $this->employeeRepository->getEmployeeIdByUserId(
                    $userId,
                    $organizationId
                );

            if (
                $employeeId <= 0 ||
                (int) $leaveRequest['employee_id'] !== $employeeId
            ) {
                // Do not reveal whether another employee's request exists.
                throw new RuntimeException('Leave request not found.');
            }
        }

        $approvals = $this->leaveService->getApprovals(
            $id,
            $organizationId
        );

        $canMakeLeaveDecision = $this->canMakeLeaveDecision();

        require __DIR__ . '/../Views/leave/show.php';
    }

    public function approve(int $id): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        $approverId =
            (int) (
                $_SESSION['user']['id']
                    ?? 0
            );

        $comment =
            trim($_POST['comment'] ?? '');

        if ($comment === '') {
            $comment = null;
        }

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($approverId <= 0) {
            throw new RuntimeException(
                'Invalid approver.'
            );
        }

        $this->leaveService->approve(
            $id,
            $organizationId,
            $approverId,
            $comment
        );

        header(
            'Location: ' . APP_BASE_PATH . '/leave/' . $id
        );

        exit;
    }

    public function reject(int $id): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        $approverId =
            (int) (
                $_SESSION['user']['id']
                    ?? 0
            );

        $comment =
            trim($_POST['comment'] ?? '');

        if ($comment === '') {
            $comment = null;
        }

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($approverId <= 0) {
            throw new RuntimeException(
                'Invalid approver.'
            );
        }

        $this->leaveService->reject(
            $id,
            $organizationId,
            $approverId,
            $comment
        );

        header(
            'Location: ' . APP_BASE_PATH . '/leave/' . $id
        );

        exit;
    }

    

}
