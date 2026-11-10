<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use App\Database;
use RuntimeException;

class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->employeeRepository = new EmployeeRepository();
        $this->userRepository = new UserRepository();
    }

    /*
     * Get all employees
     */
    public function getEmployees(int $organizationId): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getAll($organizationId);
    }

    /*
     * Get one employee
     */
    public function getEmployee(
        int $employeeId,
        int $organizationId
    ): array {
        if ($employeeId <= 0) {
            throw new RuntimeException(
                'Invalid employee.'
            );
        }

        $employee = $this->employeeRepository->findById(
            $employeeId,
            $organizationId
        );

        if ($employee === null) {
            throw new RuntimeException(
                'Employee not found.'
            );
        }

        return $employee;
    }

    /*
     * Find employee by employee number
     */
    public function findByEmployeeNumber(
        string $employeeNumber,
        int $organizationId
    ): ?array {
        $employeeNumber = trim($employeeNumber);

        if ($employeeNumber === '') {
            return null;
        }

        return $this
            ->employeeRepository
            ->findByEmployeeNumber(
                $employeeNumber,
                $organizationId
            );
    }

    public function getBranches(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getBranches($organizationId, $activeOnly);
    }

    public function getDepartments(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getDepartments($organizationId, $activeOnly);
    }

    public function getPositions(
        int $organizationId,
        bool $activeOnly = true
    ): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getPositions($organizationId, $activeOnly);
    }

    public function getEmploymentTypes(): array
    {
        return $this
            ->employeeRepository
            ->getEmploymentTypes();
    }

    public function getEmployeeStatuses(): array
    {
        return $this
            ->employeeRepository
            ->getEmployeeStatuses();
    }

    public function getManagers(int $organizationId): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getManagers($organizationId);
    }

    /*
     * Get data required by the Create Employee form
     */
    public function getCreateFormData(
        int $organizationId
    ): array {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return [
            'branches' => $this
                ->employeeRepository
                ->getBranches($organizationId),
            'departments' => $this
                ->employeeRepository
                ->getDepartments($organizationId),
            'positions' => $this
                ->employeeRepository
                ->getPositions($organizationId),
            'employment_types' => $this
                ->employeeRepository
                ->getEmploymentTypes(),
            'employee_statuses' => $this
                ->employeeRepository
                ->getEmployeeStatuses(),
        ];
    }

    /*
     * Create employee
     */
    public function createEmployee(
        array $data,
        int $organizationId
    ): int {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        /*
         * Required fields
         */
        $required = [
            'employee_number',
            'first_name',
            'last_name',
            'branch_id',
            'department_id',
            'position_id',
            'hire_date',
            'employment_type_id',
            'employee_status_id'
        ];

        foreach ($required as $field) {
            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {
                throw new RuntimeException(
                    "The {$field} field is required."
                );
            }
        }

        /*
         * Clean input
         */
        $employeeNumber = trim(
            $data['employee_number']
        );

        /*
         * Prevent duplicate employee numbers
         */
        $existingEmployee =
            $this
                ->employeeRepository
                ->findByEmployeeNumber(
                    $employeeNumber,
                    $organizationId
                );

        if ($existingEmployee !== null) {
            throw new RuntimeException(
                'Employee number already exists.'
            );
        }

        /*
         * Prepare data
         */
        $employeeData = [
            'organization_id' =>
                $organizationId,
            'branch_id' =>
                (int) $data['branch_id'],
            'department_id' =>
                (int) $data['department_id'],
            'position_id' =>
                (int) $data['position_id'],
            'manager_id' =>
                !empty($data['manager_id'])
                    ? (int) $data['manager_id']
                    : null,
            'employee_number' =>
                $employeeNumber,
            'first_name' =>
                trim($data['first_name']),
            'middle_name' =>
                !empty($data['middle_name'])
                    ? trim($data['middle_name'])
                    : null,
            'last_name' =>
                trim($data['last_name']),
            'date_of_birth' =>
                !empty($data['date_of_birth'])
                    ? $data['date_of_birth']
                    : null,
            'email' =>
                !empty($data['email'])
                    ? trim($data['email'])
                    : null,
            'phone' =>
                !empty($data['phone'])
                    ? trim($data['phone'])
                    : null,
            'address' =>
                !empty($data['address'])
                    ? trim($data['address'])
                    : null,
            'hire_date' =>
                $data['hire_date'],
            'employment_type_id' =>
                (int) $data['employment_type_id'],
            'employee_status_id' =>
                (int) $data['employee_status_id']
        ];

        return $this
            ->employeeRepository
            ->create($employeeData);
    }

    // delete employee
    public function deleteEmployee(
        int $employeeId,
        int $organizationId
    ): void {
        if ($employeeId <= 0) {
            throw new RuntimeException('Invalid employee.');
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Soft-delete the employee's linked user account, if one exists.
            $this->employeeRepository->softDeleteUserAccount(
                $employeeId,
                $organizationId
            );

            // Soft-delete the employee.
            $deleted = $this->employeeRepository->softDelete(
                $employeeId,
                $organizationId
            );

            if (!$deleted) {
                throw new RuntimeException(
                    'Employee not found or already deleted.'
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public function restoreEmployee(
        int $employeeId,
        int $organizationId
    ): void {
        if ($employeeId <= 0) {
            throw new RuntimeException('Invalid employee.');
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $employee = $this->employeeRepository->findDeletedById(
            $employeeId,
            $organizationId
        );

        if ($employee === null) {
            throw new RuntimeException('Deleted employee not found.');
        }

        $existingEmployee = $this->employeeRepository->findByEmployeeNumber(
            $employee['employee_number'],
            $organizationId
        );

        if ($existingEmployee !== null) {
            throw new RuntimeException(
                'Employee cannot be restored because the employee number is already in use.'
            );
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Restore the employee.
            $restored = $this->employeeRepository->restore(
                $employeeId,
                $organizationId
            );

            if (!$restored) {
                throw new RuntimeException('Unable to restore employee.');
            }

            // Restore the linked user account, if one exists.
            $this->employeeRepository->restoreUserAccount(
                $employeeId,
                $organizationId
            );

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    /*
     * Update employee
     */

    public function updateEmployee(
        int $employeeId,
        array $data,
        int $organizationId
    ): bool {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($employeeId <= 0) {
            throw new RuntimeException(
                'Invalid employee.'
            );
        }

        /*
         * Required fields
         */
        $required = [
            'employee_number',
            'first_name',
            'last_name',
            'branch_id',
            'department_id',
            'position_id',
            'hire_date',
            'employment_type_id',
            'employee_status_id'
        ];

        foreach ($required as $field) {
            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {
                throw new RuntimeException(
                    "The {$field} field is required."
                );
            }
        }

        /*
         * Make sure employee exists
         * and belongs to this organization
         */
        $employee = $this
            ->employeeRepository
            ->findById(
                $employeeId,
                $organizationId
            );

        if ($employee === null) {
            throw new RuntimeException(
                'Employee not found.'
            );
        }

        /*
         * Clean employee number
         */
        $employeeNumber = trim(
            $data['employee_number']
        );

        /*
         * Prevent duplicate employee numbers
         *
         * Allow the current employee to
         * keep its own employee number.
         */
        $existingEmployee =
            $this
                ->employeeRepository
                ->findByEmployeeNumber(
                    $employeeNumber,
                    $organizationId
                );

        if (
            $existingEmployee !== null &&
            (int) $existingEmployee['id'] !== $employeeId
        ) {
            throw new RuntimeException(
                'Employee number already exists.'
            );
        }

        /*
         * Prepare update data
         */
        $employeeData = [
            'branch_id' =>
                (int) $data['branch_id'],
            'department_id' =>
                (int) $data['department_id'],
            'position_id' =>
                (int) $data['position_id'],
            'manager_id' =>
                !empty($data['manager_id'])
                    ? (int) $data['manager_id']
                    : null,
            'employee_number' =>
                $employeeNumber,
            'first_name' =>
                trim($data['first_name']),
            'middle_name' =>
                !empty($data['middle_name'])
                    ? trim($data['middle_name'])
                    : null,
            'last_name' =>
                trim($data['last_name']),
            'date_of_birth' =>
                !empty($data['date_of_birth'])
                    ? $data['date_of_birth']
                    : null,
            'email' =>
                !empty($data['email'])
                    ? trim($data['email'])
                    : null,
            'phone' =>
                !empty($data['phone'])
                    ? trim($data['phone'])
                    : null,
            'address' =>
                !empty($data['address'])
                    ? trim($data['address'])
                    : null,
            'hire_date' =>
                $data['hire_date'],
            'employment_type_id' =>
                (int) $data['employment_type_id'],
            'employee_status_id' =>
                (int) $data['employee_status_id']
        ];

        $db = Database::connection();

        try {
            $db->beginTransaction();

            $user = $this->userRepository->findByEmployeeId(
                $employeeId
            );

            if (
                $user !== null &&
                (int) $user['organization_id'] !== $organizationId
            ) {
                throw new RuntimeException(
                    'The linked user account does not belong to this organization.'
                );
            }

            if ($user !== null) {
                if ($employeeData['email'] === null) {
                    throw new RuntimeException(
                        'An email address is required for an employee with a user account.'
                    );
                }

                $existingUser = $this->userRepository->findByEmail(
                    $employeeData['email'],
                    $organizationId
                );

                if (
                    $existingUser !== null &&
                    (int) $existingUser['id'] !== (int) $user['id']
                ) {
                    throw new RuntimeException(
                        'This email is already associated with another user account.'
                    );
                }
            }

            $updated = $this
                ->employeeRepository
                ->update(
                    $employeeId,
                    $organizationId,
                    $employeeData
                );

            if (!$updated) {
                throw new RuntimeException(
                    'Unable to update employee.'
                );
            }
            if (
                $user !== null &&
                $user['email'] !== $employeeData['email'] &&
                !$this->userRepository->updateEmail(
                    (int) $user['id'],
                    $employeeId,
                    $organizationId,
                    $employeeData['email']
                )
            ) {
                throw new RuntimeException(
                    'Unable to update the employee user account.'
                );
            }

            $db->commit();

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public function getDeletedEmployees(
        int $organizationId
    ): array {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->employeeRepository
            ->getDeletedEmployees(
                $organizationId
            );
    }

    public function permanentlyDeleteEmployee(
        int $employeeId,
        int $organizationId
    ): void {
        if ($employeeId <= 0) {
            throw new RuntimeException('Invalid employee.');
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        // Confirm that the employee exists in the recycle bin.
        $employee = $this->employeeRepository->findDeletedById(
            $employeeId,
            $organizationId
        );

        if ($employee === null) {
            throw new RuntimeException('Deleted employee not found.');
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // Permanently delete the linked user account first.
            $this->employeeRepository->permanentlyDeleteUserAccount(
                $employeeId,
                $organizationId
            );

            // Permanently delete the employee.
            $deleted = $this->employeeRepository->permanentlyDelete(
                $employeeId,
                $organizationId
            );

            if (!$deleted) {
                throw new RuntimeException(
                    'Unable to permanently delete employee.'
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }
}
