<?php

namespace App\Controllers;

use App\Repositories\AuditRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\UserService;
use App\Session;
use RuntimeException;

class UserController
{
    private UserService $userService;
    private EmployeeRepository $employeeRepository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->userService =
            new UserService(
                new UserRepository()
            );

        $this->employeeRepository =
            new EmployeeRepository();

        $this->auditService =
            new AuditService(
                new AuditRepository()
            );
    }

    /*
     * Create user account page
     */
    public function create(): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        $employeeId =
            (int) (
                $_GET['employee_id']
                    ?? 0
            );

        if (
            $organizationId <= 0 ||
            $employeeId <= 0
        ) {
            throw new RuntimeException(
                'Invalid employee information.'
            );
        }

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

        $existingAccount =
            $this
                ->userService
                ->getUserByEmployeeId(
                    $employeeId
                );

        if ($existingAccount) {
            throw new RuntimeException(
                'This employee already has a user account.'
            );
        }

        $data = [
            'employee' => $employee
        ];

        require __DIR__
            . '/../Views/users/create.php';
    }

    /*
     * Store new user account
     */
    public function store(): void
    {
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        $employeeId =
            (int) (
                $_POST['employee_id']
                    ?? 0
            );

        try {
            $username =
                trim(
                    $_POST['username']
                        ?? ''
                );

            $email =
                trim(
                    $_POST['email']
                        ?? ''
                );

            $password =
                $_POST['password']
                    ?? '';

            $passwordConfirmation =
                $_POST['password_confirmation']
                    ?? '';

            /*
             * Basic request validation
             */
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

            if ($username === '') {
                throw new RuntimeException(
                    'Username is required.'
                );
            }

            if ($email === '') {
                throw new RuntimeException(
                    'Email is required.'
                );
            }

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new RuntimeException(
                    'Please provide a valid email address.'
                );
            }

            if ($password === '') {
                throw new RuntimeException(
                    'Password is required.'
                );
            }

            if (strlen($password) < 8) {
                throw new RuntimeException(
                    'Password must be at least 8 characters long.'
                );
            }

            if (
                $password !==
                $passwordConfirmation
            ) {
                throw new RuntimeException(
                    'Passwords do not match.'
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
             * Make sure employee does not
             * already have a user account.
             */
            if (
                $this
                    ->userService
                    ->getUserByEmployeeId(
                        $employeeId
                    )
            ) {
                throw new RuntimeException(
                    'This employee already has a user account.'
                );
            }

            /*
             * Make sure username is unique.
             */
            if (
                $this
                    ->userService
                    ->getUserByUsername(
                        $username
                    )
            ) {
                throw new RuntimeException(
                    'This username is already in use.'
                );
            }

            /*
             * Make sure email is unique.
             */
            if (
                $this
                    ->userService
                    ->getUserByEmail(
                        $email,
                        $organizationId
                    )
            ) {
                throw new RuntimeException(
                    'This email is already associated with a user account.'
                );
            }

            $statusId = (new UserRepository())
                ->findStatusIdByCode('ACTIVE');
            $roleId = (new UserRepository())
                ->findRoleIdByName('Employee', $organizationId);

            if ($statusId === null || $roleId === null) {
                throw new RuntimeException(
                    'The active user status or Employee role is not configured for this organization.'
                );
            }

            $this
                ->userService
                ->createAccount(
                    $organizationId,
                    $employeeId,
                    $username,
                    $email,
                    $password,
                    $statusId,
                    $roleId
                );

            /*
             * Audit the account creation.
             *
             * IMPORTANT:
             * Password and password hash are NEVER
             * stored in the audit log.
             */
            $auditData = [
                'employee_id' =>
                    $employeeId,
                'username' =>
                    $username,
                'email' =>
                    $email,
                'status' =>
                    'Active',
                'role_id' =>
                    $roleId
            ];

            $this
                ->auditService
                ->recordAction(
                    'USER_CREATED',
                    'user',
                    'user',
                    null,
                    null,
                    $auditData
                );

            Session::set('flash_message', [
                'type' => 'success',
                'title' => 'Account created',
                'message' => 'The employee user account was created successfully.'
            ]);

            /*
             * Redirect back to employees.
             */
            header(
                'Location: ' . APP_BASE_PATH . '/employees'
            );

            exit;
        } catch (RuntimeException $e) {
            http_response_code(422);

            $error = $e->getMessage();

            $employee = $this
                ->employeeRepository
                ->findById(
                    $employeeId,
                    $organizationId
                );

            $data = [
                'employee' => $employee
            ];

            require __DIR__
                . '/../Views/users/create.php';

            exit;
        }
    }

    public function index(): void
    {
        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $users = $this->userService->getUsersByOrganization(
            $organizationId
        );

        require __DIR__ . '/../Views/users/index.php';
    }
}
