<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Database;
use RuntimeException;

class UserService
{
    private UserRepository $userRepository;
    private AuditService $auditService;

    public function __construct(
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;

        $this->auditService =
            new AuditService(
                new \App\Repositories\AuditRepository()
            );
    }

    public function getUserByEmployeeId(
        int $employeeId
    ): ?array {
        return $this
            ->userRepository
            ->findByEmployeeId($employeeId);
    }

    public function getUserByEmail(
        string $email,
        int $organizationId
    ): ?array {
        return $this
            ->userRepository
            ->findByEmail($email, $organizationId);
    }

    public function getUserByUsername(
        string $username
    ): ?array {
        return $this
            ->userRepository
            ->findByUsernameForAccount($username);
    }

    public function createAccount(
        int $organizationId,
        int $employeeId,
        string $username,
        string $email,
        string $password,
        int $statusId,
        int $roleId
    ): int {
        /*
         * Employee can only have one user account.
         */
        if (
            $this
                ->userRepository
                ->findByEmployeeId($employeeId)
        ) {
            throw new RuntimeException(
                'This employee already has a user account.'
            );
        }

        /*
         * Username must be unique.
         */
        if ($this->userRepository->findByUsernameForAccount($username)) {
            throw new RuntimeException(
                'This username is already in use.'
            );
        }

        /*
         * Email must be unique.
         */
        if ($this->userRepository->findByEmail($email, $organizationId)) {
            throw new RuntimeException(
                'This email is already associated with a user account.'
            );
        }

        /*
         * Password validation.
         */
        if (strlen($password) < 8) {
            throw new RuntimeException(
                'Password must be at least 8 characters long.'
            );
        }

        /*
         * Secure password.
         */
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        if ($passwordHash === false) {
            throw new RuntimeException(
                'Unable to secure the password.'
            );
        }

        /*
         * Create user account.
         */
        $db = Database::connection();

        try {
            $db->beginTransaction();
            $userId = $this->userRepository->create([
                    'uuid' =>
                        \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'organization_id' =>
                        $organizationId,
                    'employee_id' =>
                        $employeeId,
                    'username' =>
                        $username,
                    'email' =>
                        $email,
                    'password_hash' =>
                        $passwordHash,
                    'status_id' =>
                        $statusId,
                    'password_changed_at' =>
                        date('Y-m-d H:i:s')
                ]);

            $this->userRepository->assignRole(
                $userId,
                $roleId
            );
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        /*
         * Audit user role assignment.
         *
         * The role is stored in the database, so we
         * record the role ID together with the newly
         * created user account.
         */
        $this
            ->auditService
            ->recordAction(
                'USER_ROLE_ASSIGNED',
                'USER_ACCOUNT',
                'user_role',
                $userId,
                null,
                [
                    'user_id' => $userId,
                    'employee_id' => $employeeId,
                    'username' => $username,
                    'role_id' => $roleId
                ]
            );

        return $userId;
    }

    public function getUsersByOrganization(int $organizationId): array
    {
        return $this->userRepository->findAllByOrganization(
            $organizationId
        );
    }
}
