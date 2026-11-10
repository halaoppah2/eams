<?php

namespace App\Controllers;

use App\Services\ShiftService;
use App\Repositories\AuditRepository;
use App\Services\AuditService;
use App\Session;
use RuntimeException;

class ShiftController
{
    private ShiftService $shiftService;
    private AuditService $auditService;

    private function redirectWithError(
        string $url,
        RuntimeException $e,
        int $statusCode = 422
    ): void {
        Session::set('flash_message', [
            'type' => 'error',
            'title' => 'Shift action failed',
            'message' => $e->getMessage(),
            'status_code' => $statusCode
        ]);

        header(
            'Location: ' . $url,
            true,
            303
        );

        exit;
    }

    public function __construct()
    {
        $this->shiftService = new ShiftService();
        $this->auditService = new AuditService(
            new AuditRepository()
        );
    }

    /*
     * Shift list
     */
    public function index(): void
    {
        try {
            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $shifts = $this
                ->shiftService
                ->getShifts($organizationId);

            require __DIR__ . '/../Views/shifts/index.php';
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts',
                $e,
                403
            );
        }
    }

    /*
     * Show Create Shift form
     */
    public function create(): void
    {
        try {
            $statuses = $this
                ->shiftService
                ->getShiftStatuses();

            require __DIR__ . '/../Views/shifts/create.php';
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts',
                $e,
                403
            );
        }
    }

    /*
     * Show Edit Shift form
     */
    public function edit(int $id): void
    {
        try {
            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $shift = $this
                ->shiftService
                ->getShift(
                    $id,
                    $organizationId
                );

            $statuses = $this
                ->shiftService
                ->getShiftStatuses();

            require __DIR__ . '/../Views/shifts/edit.php';
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts',
                $e,
                404
            );
        }
    }

    /*
     * Store new shift
     */
    public function store(): void
    {
        try {
            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $this
                ->shiftService
                ->createShift(
                    $_POST,
                    $organizationId
                );

            header(
                'Location: ' . APP_BASE_PATH . '/shifts'
            );

            exit;
        } catch (RuntimeException $e) {
            http_response_code(422);

            $error = $e->getMessage();

            $statuses = $this
                ->shiftService
                ->getShiftStatuses();

            require __DIR__ . '/../Views/shifts/create.php';
            exit;
        }
    }

    /*
     * Update shift
     */
    public function update(int $id): void
    {
        try {
            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $this
                ->shiftService
                ->updateShift(
                    $id,
                    $_POST,
                    $organizationId
                );

            header(
                'Location: ' . APP_BASE_PATH . '/shifts/' . $id . '/edit'
            );

            exit;
        } catch (RuntimeException $e) {
            http_response_code(422);

            $error = $e->getMessage();

            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $shift = $this
                ->shiftService
                ->getShift(
                    $id,
                    $organizationId
                );

            $statuses = $this
                ->shiftService
                ->getShiftStatuses();

            require __DIR__ . '/../Views/shifts/edit.php';
            exit;
        }
    }

    /*
     * Delete shift
     */
    public function delete(int $id): void
    {
        try {
            $organizationId =
                (int) ($_SESSION['user']['organization_id'] ?? 0);

            $shift = $this->shiftService->getShift(
                $id,
                $organizationId
            );

            $this
                ->shiftService
                ->deleteShift(
                    $id,
                    $organizationId
                );

            $this->auditService->recordDelete(
                'shift',
                'shift',
                $id,
                $shift
            );

            header('Location: ' . APP_BASE_PATH . '/shifts');
            exit;
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts',
                $e,
                422
            );
        }
    }

    /*
     * Show shift assignment form.
     */

    public function assignments(): void
    {
        try {
            $organizationId = (int) (
                $_SESSION['user']['organization_id'] ?? 0
            );

            $employees = $this
                ->shiftService
                ->getEmployeesForAssignment($organizationId);

            $shifts = $this
                ->shiftService
                ->getActiveShiftsForAssignment($organizationId);

            require __DIR__ . '/../Views/shifts/assignments.php';
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts',
                $e,
                403
            );
        }
    }

    /*
     * Assign, change, or unassign an employee's shift.
     */

    public function assign(): void
    {
        try {
            $organizationId = (int) (
                $_SESSION['user']['organization_id'] ?? 0
            );

            $userId = (int) (
                $_SESSION['user']['id'] ?? 0
            );

            $employeeId = (int) ($_POST['employee_id'] ?? 0);

            $effectiveFrom = trim(
                (string) ($_POST['effective_from'] ?? '')
            );

            $action = trim(
                (string) ($_POST['action'] ?? 'assign')
            );

            if ($action === 'unassign') {
                $this->shiftService->unassignShift(
                    $employeeId,
                    $effectiveFrom,
                    $organizationId
                );

                Session::set('flash_message', [
                    'type' => 'success',
                    'title' => 'Shift unassigned',
                    'message' =>
                        'The employee shift assignment was ended.'
                ]);
            } elseif ($action === 'assign') {
                $shiftId = (int) ($_POST['shift_id'] ?? 0);

                $this->shiftService->assignShift(
                    $employeeId,
                    $shiftId,
                    $effectiveFrom,
                    $organizationId,
                    $userId
                );

                Session::set('flash_message', [
                    'type' => 'success',
                    'title' => 'Shift assigned',
                    'message' =>
                        'The employee shift assignment was saved.'
                ]);
            } else {
                throw new RuntimeException(
                    'Invalid shift action.'
                );
            }

            header('Location: ' . APP_BASE_PATH . '/shifts/assignments');
            exit;
        } catch (RuntimeException $e) {
            $this->redirectWithError(
                APP_BASE_PATH . '/shifts/assignments',
                $e,
                422
            );
        }
    }
}
