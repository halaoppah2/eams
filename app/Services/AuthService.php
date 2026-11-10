<?php

namespace App\Services;

use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;
use App\Session;
use RuntimeException;

class AuthService
{
    private UserRepository $users;
    private AuditService $auditService;

    public function __construct()
    {
        $this->users = new UserRepository();

        $this->auditService =
            new AuditService(
                new AuditRepository()
            );
    }

    public function login(
        string $username,
        string $password
    ): bool {

        $username = trim($username);

        /*
         * Empty credentials.
         */
        if ($username === '' || $password === '') {

            $this->auditService->recordAction(
                'LOGIN_FAILED',
                'AUTHENTICATION',
                'user',
                null,
                null,
                [
                    'username' => $username,
                    'reason' => 'EMPTY_CREDENTIALS'
                ]
            );

            throw new RuntimeException(
                'Invalid username or password.'
            );
        }

        /*
         * Find user.
         */
        $user = $this->users->findByUsername($username);

        /*
         * User does not exist.
         */
        if (!$user) {

            $this->auditService->recordAction(
                'LOGIN_FAILED',
                'AUTHENTICATION',
                'user',
                null,
                null,
                [
                    'username' => $username,
                    'reason' => 'USER_NOT_FOUND'
                ]
            );

            throw new RuntimeException(
                'Invalid username or password.'
            );
        }

        /*
         * Account is not active.
         */
        if ($user['status_code'] !== 'ACTIVE') {

            /*
             * Temporarily set the organization/user
             * context so the audit entry can identify
             * the account involved.
             */
            $this->auditService->record(
                'LOGIN_FAILED',
                'AUTHENTICATION',
                'user',
                (int) $user['id'],
                null,
                [
                    'username' => $user['username'],
                    'reason' => 'ACCOUNT_NOT_ACTIVE'
                ]
            );

            throw new RuntimeException(
                'Invalid username or password.'
            );
        }

        /*
         * Verify password.
         */
        if (!password_verify(
            $password,
            $user['password_hash']
        )) {

            $this->auditService->record(
                'LOGIN_FAILED',
                'AUTHENTICATION',
                'user',
                (int) $user['id'],
                null,
                [
                    'username' => $user['username'],
                    'reason' => 'INVALID_PASSWORD'
                ]
            );

            throw new RuntimeException(
                'Invalid username or password.'
            );
        }

        /*
         * Prevent session fixation.
         */
        Session::regenerate();

        /*
         * Load roles and permissions.
         */
        $roles = $this->users->getRoles(
            (int) $user['id']
        );

        $permissions = $this->users->getPermissions(
            (int) $user['id']
        );

        /*
         * Establish authenticated session.
         */
        Session::set(
            'authenticated',
            true
        );
        Session::set('last_activity', time());

        Session::set('user', [
            'id' =>
                (int) $user['id'],

            'uuid' =>
                $user['uuid'],

            'organization_id' =>
                (int) $user['organization_id'],

            'employee_id' =>
                (int) $user['employee_id'],

            'is_system_admin' =>
                (bool) ($user['is_system_admin'] ?? false),

            'username' =>
                $user['username'],

            'email' =>
                $user['email'],

            'profile_picture' =>
                $user['profile_picture'] ?? null,

            'organization_logo' =>
                $user['organization_logo'] ?? null,

            'first_name' =>
                $user['first_name'],

            'last_name' =>
                $user['last_name'],
        ]);

        Session::set(
            'roles',
            $roles
        );

        Session::set(
            'permissions',
            array_column(
                $permissions,
                'name'
            )
        );

        /*
         * Update last login.
         */
        $this->users->updateLastLogin(
            (int) $user['id']
        );

        /*
         * Audit successful login.
         */
        $this->auditService->record(
            'LOGIN_SUCCESS',
            'AUTHENTICATION',
            'user',
            (int) $user['id'],
            null,
            [
                'username' => $user['username']
            ]
        );

        return true;
    }

    public function logout(): void
    {
        /*
         * Capture the authenticated user BEFORE
         * destroying the session.
         */
        $user = Session::get(
            'user',
            null
        );

        try {
            if (is_array($user) && isset($user['id'])) {
                $this->auditService->record(
                    'LOGOUT',
                    'AUTHENTICATION',
                    'user',
                    (int) $user['id'],
                    null,
                    [
                        'username' =>
                            $user['username'] ?? null
                    ]
                );
            }
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to record logout audit event for user %d: %s in %s:%d',
                (int) ($user['id'] ?? 0),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        } finally {
            Session::destroy();
        }
    }

    public function check(): bool
    {
        return Session::get(
            'authenticated',
            false
        ) === true;
    }

    public function user(): ?array
    {
        return Session::get('user');
    }

    public function refreshSessionUser(): bool
    {
        $user = Session::get('user');

        if (
            !is_array($user) ||
            empty($user['id']) ||
            empty($user['employee_id']) ||
            empty($user['organization_id'])
        ) {
            return false;
        }

        $currentUser = $this->users->findSessionDetails(
            (int) $user['id'],
            (int) $user['employee_id'],
            (int) $user['organization_id']
        );

        if ($currentUser === null) {
            return false;
        }

        Session::set('user', array_merge($user, $currentUser));

        return true;
    }

    public function hasPermission(
        string $permission
    ): bool {
        $permissions = Session::get(
            'permissions',
            []
        );

        return in_array(
            $permission,
            $permissions,
            true
        );
    }
}