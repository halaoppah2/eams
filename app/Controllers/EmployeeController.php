<?php

namespace App\Controllers;

use App\Services\EmployeeService;
use App\Services\AuditService;
use App\Repositories\AuditRepository;
use App\Session;
use RuntimeException;

class EmployeeController
{
    private EmployeeService $employeeService;
    private AuditService $auditService;

    private function redirectWithError(
        string $url,
        RuntimeException $e,
        int $statusCode = 500
    ): void {
        Session::set('flash_message', [
            'type' => 'error',
            'title' => 'Action failed',
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
        $this->employeeService =
            new EmployeeService();

        $this->auditService =
            new AuditService(
                new AuditRepository()
            );
    }

    /*
     * Employee list
     */
    public function index(): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            $employees =
                $this
                    ->employeeService
                    ->getEmployees(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/index.php';

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees',
                $e,
                403
            );
        }
    }

    /*
     * Employee profile
     */
    public function show(int $id): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            $employee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/show.php';

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees',
                $e,
                404
            );
        }
    }

    /*
     * Show Create Employee form
     */
    public function create(): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            $formData =
                $this
                    ->employeeService
                    ->getCreateFormData(
                        $organizationId
                    );

            $formData['managers'] =
                $this
                    ->employeeService
                    ->getManagers(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/create.php';

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees',
                $e,
                403
            );
        }
    }


    /*
     * Show Edit Employee form
     */
    public function edit(int $id): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            $employee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            $branches =
                $this
                    ->employeeService
                    ->getBranches(
                        $organizationId,
                        false
                    );

            $departments =
                $this
                    ->employeeService
                    ->getDepartments(
                        $organizationId,
                        false
                    );

            $positions =
                $this
                    ->employeeService
                    ->getPositions(
                        $organizationId,
                        false
                    );

            $employmentTypes =
                $this
                    ->employeeService
                    ->getEmploymentTypes();

            $employeeStatuses =
                $this
                    ->employeeService
                    ->getEmployeeStatuses();

            $managers =
                $this
                    ->employeeService
                    ->getManagers(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/edit.php';

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees',
                $e,
                404
            );
        }
    }

    /*
     * Delete employee
     *
     * This is a soft delete.
     */
    public function delete(int $id): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            /*
             * Capture employee information
             * before deletion.
             */
            $oldEmployee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            $this
                ->employeeService
                ->deleteEmployee(
                    $id,
                    $organizationId
                );

            /*
             * Record audit event.
             */
            $this
                ->auditService
                ->recordDelete(
                    'employee',
                    'employee',
                    $id,
                    $oldEmployee
                );

            header(
                'Location: ' . APP_BASE_PATH . '/employees'
            );

            exit;

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees',
                $e,
                422
            );
        }
    }


    /*
     * Restore employee
     */
    public function restore(int $id): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            /*
             * Get deleted employees before restoration.
             */
            $deletedEmployees =
                $this
                    ->employeeService
                    ->getDeletedEmployees(
                        $organizationId
                    );

            $oldEmployee = null;

            foreach (
                $deletedEmployees
                as $employee
            ) {

                if (
                    (int) $employee['id']
                    === $id
                ) {

                    $oldEmployee =
                        $employee;

                    break;
                }
            }

            $this
                ->employeeService
                ->restoreEmployee(
                    $id,
                    $organizationId
                );

            /*
             * Record restoration.
             */
            $this
                ->auditService
                ->recordAction(
                    'EMPLOYEE_RESTORED',
                    'employee',
                    'employee',
                    $id,
                    $oldEmployee,
                    [
                        'status' => 'RESTORED'
                    ]
                );

            header(
                'Location: ' . APP_BASE_PATH . '/employees/recycle-bin'
            );

            exit;

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees/recycle-bin',
                $e,
                422
            );
        }
    }

    /*
     * Update employee
     */
    public function update(int $id): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                ?? 0
            );

        try {
            /*
             * Capture old employee data
             * before modification.
             */
            $oldEmployee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            $this
                ->employeeService
                ->updateEmployee(
                    $id,
                    $_POST,
                    $organizationId
                );

            /*
             * Get updated employee data.
             */
            $newEmployee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            /*
             * Record audit event.
             */
            $this
                ->auditService
                ->recordUpdate(
                    'employee',
                    'employee',
                    $id,
                    $oldEmployee,
                    $newEmployee
                );

            header(
                'Location: ' . APP_BASE_PATH . '/employees/' . $id
            );

            exit;

        } catch (RuntimeException $e) {

            http_response_code(422);

            $error = $e->getMessage();

            $employee =
                $this
                    ->employeeService
                    ->getEmployee(
                        $id,
                        $organizationId
                    );

            $branches =
                $this
                    ->employeeService
                    ->getBranches(
                        $organizationId,
                        false
                    );

            $departments =
                $this
                    ->employeeService
                    ->getDepartments(
                        $organizationId,
                        false
                    );

            $positions =
                $this
                    ->employeeService
                    ->getPositions(
                        $organizationId,
                        false
                    );

            $employmentTypes =
                $this
                    ->employeeService
                    ->getEmploymentTypes();

            $employeeStatuses =
                $this
                    ->employeeService
                    ->getEmployeeStatuses();

            $managers =
                $this
                    ->employeeService
                    ->getManagers(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/edit.php';

            exit;
        }
    }

    /*
     * Store new employee
     */
    public function store(): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                ?? 0
            );

        try {
            $this
                ->employeeService
                ->createEmployee(
                    $_POST,
                    $organizationId
                );

            /*
             * Record employee creation.
             *
             * The current EmployeeService does not
             * return the new employee ID, so the audit
             * entry records the submitted employee data
             * without an entity ID.
             */
            $auditData = [
                'employee_number' =>
                    $_POST['employee_number']
                    ?? null,

                'first_name' =>
                    $_POST['first_name']
                    ?? null,

                'middle_name' =>
                    $_POST['middle_name']
                    ?? null,

                'last_name' =>
                    $_POST['last_name']
                    ?? null,

                'department_id' =>
                    $_POST['department_id']
                    ?? null,

                'position_id' =>
                    $_POST['position_id']
                    ?? null,

                'hire_date' =>
                    $_POST['hire_date']
                    ?? null,

                'employment_type_id' =>
                    $_POST['employment_type_id']
                    ?? null,

                'employee_status_id' =>
                    $_POST['employee_status_id']
                    ?? null
            ];

            $this
                ->auditService
                ->recordCreate(
                    'employee',
                    'employee',
                    0,
                    $auditData
                );

            Session::set('flash_message', [
                'type' => 'success',
                'title' => 'Employee created',
                'message' => 'The employee was created successfully.'
            ]);

            header(
                'Location: ' . APP_BASE_PATH . '/employees'
            );

            exit;

        } catch (RuntimeException $e) {

            http_response_code(422);

            $error = $e->getMessage();

            $formData =
                $this
                    ->employeeService
                    ->getCreateFormData(
                        $organizationId
                    );

            $formData['managers'] =
                $this
                    ->employeeService
                    ->getManagers(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/create.php';

            exit;
        }
    }


    /*
     * Recycle bin
     */
    public function recycleBin(): void
    {
        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            $employees =
                $this
                    ->employeeService
                    ->getDeletedEmployees(
                        $organizationId
                    );

            require __DIR__
                . '/../Views/employees/recycle-bin.php';

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees/recycle-bin',
                $e,
                403
            );
        }
    }


    /*
     * Permanently delete employee
     */
    public function permanentlyDelete(
        int $id
    ): void {

        try {

            $organizationId =
                (int) (
                    $_SESSION['user']['organization_id']
                    ?? 0
                );

            /*
             * Capture the employee before
             * permanent deletion.
             */
            $deletedEmployees =
                $this
                    ->employeeService
                    ->getDeletedEmployees(
                        $organizationId
                    );

            $oldEmployee = null;

            foreach (
                $deletedEmployees
                as $employee
            ) {

                if (
                    (int) $employee['id']
                    === $id
                ) {

                    $oldEmployee =
                        $employee;

                    break;
                }
            }

            $this
                ->employeeService
                ->permanentlyDeleteEmployee(
                    $id,
                    $organizationId
                );

            /*
             * Record permanent deletion.
             */
            $this
                ->auditService
                ->recordAction(
                    'EMPLOYEE_PERMANENTLY_DELETED',
                    'employee',
                    'employee',
                    $id,
                    $oldEmployee,
                    null
                );

            header(
                'Location: ' . APP_BASE_PATH . '/employees/recycle-bin'
            );

            exit;

        } catch (RuntimeException $e) {

            $this->redirectWithError(
                APP_BASE_PATH . '/employees/recycle-bin',
                $e,
                422
            );
        }
    }
}
