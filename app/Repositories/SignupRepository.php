<?php

namespace App\Repositories;

use App\Database;
use PDO;
use RuntimeException;

class SignupRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM users
            WHERE username = :username
        ');
        $stmt->execute(['username' => $username]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function statusId(string $table, string $code): int
    {
        $allowedTables = [
            'organization_statuses',
            'branch_statuses',
            'department_statuses',
            'position_statuses',
            'employee_statuses',
            'user_statuses',
            'employment_types',
            'leave_type_statuses',
        ];

        if (!in_array($table, $allowedTables, true)) {
            throw new \LogicException('Unsupported signup lookup table.');
        }

        $stmt = $this->db->prepare(
            "SELECT id FROM {$table} WHERE code = :code LIMIT 1"
        );
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(
                "Signup setup is incomplete: {$table} must contain a {$code} row."
            );
        }

        return (int) $id;
    }

    public function createOrganization(
        string $name,
        string $email,
        ?string $phone,
        string $timezone,
        int $statusId
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO organizations (
                uuid,
                name,
                email,
                phone,
                timezone,
                status_id
            )
            VALUES (
                :uuid,
                :name,
                :email,
                :phone,
                :timezone,
                :status_id
            )
        ');
        $stmt->execute([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'timezone' => $timezone,
            'status_id' => $statusId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createBranch(
        int $organizationId,
        int $statusId
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO branches (
                organization_id,
                uuid,
                name,
                code,
                status_id
            )
            VALUES (
                :organization_id,
                :uuid,
                :name,
                :code,
                :status_id
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Head Office',
            'code' => 'MAIN',
            'status_id' => $statusId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createDepartment(
        int $organizationId,
        int $branchId,
        int $statusId
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO departments (
                organization_id,
                branch_id,
                uuid,
                name,
                code,
                status_id
            )
            VALUES (
                :organization_id,
                :branch_id,
                :uuid,
                :name,
                :code,
                :status_id
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'branch_id' => $branchId,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'name' => 'Administration',
            'code' => 'ADMIN',
            'status_id' => $statusId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createPosition(
        int $organizationId,
        int $statusId
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO positions (
                organization_id,
                uuid,
                title,
                code,
                description,
                status_id
            )
            VALUES (
                :organization_id,
                :uuid,
                :title,
                :code,
                :description,
                :status_id
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'title' => 'Organization Owner',
            'code' => 'OWNER',
            'description' => 'Initial organization owner account',
            'status_id' => $statusId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createDefaultLeaveTypes(
        int $organizationId,
        int $activeStatusId
    ): void {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'description' => 'Paid annual vacation leave.',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'Leave taken due to illness or medical reasons.',
            ],
            [
                'name' => 'Casual Leave',
                'code' => 'CASUAL',
                'description' => 'Short-term personal or other approved leave.',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'MATERNITY',
                'description' => 'Leave for maternity and related purposes.',
            ],
        ];

        $stmt = $this->db->prepare('
            INSERT INTO leave_types (
                organization_id,
                name,
                code,
                description,
                days_allowed,
                requires_approval,
                status_id
            )
            VALUES (
                :organization_id,
                :name,
                :code,
                :description,
                NULL,
                1,
                :status_id
            )
        ');

        foreach ($leaveTypes as $leaveType) {
            $stmt->execute([
                'organization_id' => $organizationId,
                'name' => $leaveType['name'],
                'code' => $leaveType['code'],
                'description' => $leaveType['description'],
                'status_id' => $activeStatusId,
            ]);
        }
    }

    public function createEmployee(
        array $data
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO employees (
                organization_id,
                branch_id,
                department_id,
                position_id,
                employee_number,
                first_name,
                last_name,
                email,
                phone,
                hire_date,
                employment_type_id,
                employee_status_id
            )
            VALUES (
                :organization_id,
                :branch_id,
                :department_id,
                :position_id,
                :employee_number,
                :first_name,
                :last_name,
                :email,
                :phone,
                :hire_date,
                :employment_type_id,
                :employee_status_id
            )
        ');
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function createOwnerRole(int $organizationId): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO roles (
                organization_id,
                name,
                description,
                is_system_role
            )
            VALUES (
                :organization_id,
                :name,
                :description,
                1
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'name' => 'Organization Owner',
            'description' => 'Full access to this organization.',
        ]);

        $roleId = (int) $this->db->lastInsertId();
        $permissionStmt = $this->db->prepare('
            INSERT INTO role_permissions (role_id, permission_id)
            SELECT :role_id, id
            FROM permissions
        ');
        $permissionStmt->execute(['role_id' => $roleId]);

        if ($permissionStmt->rowCount() === 0) {
            throw new RuntimeException(
                'No permissions are configured for the organization owner role.'
            );
        }

        return $roleId;
    }

    public function createEmployeeRole(int $organizationId): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO roles (
                organization_id,
                name,
                description,
                is_system_role
            )
            VALUES (
                :organization_id,
                :name,
                :description,
                1
            )
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'name' => 'Employee',
            'description' => 'Self-service attendance and leave access.',
        ]);
        $roleId = (int) $this->db->lastInsertId();

        $permissionStmt = $this->db->prepare('
            INSERT INTO role_permissions (role_id, permission_id)
            SELECT :role_id, id
            FROM permissions
            WHERE name IN (
                :dashboard_view,
                :attendance_view_own,
                :attendance_clock_in,
                :attendance_clock_out,
                :leave_view_own,
                :leave_request,
                :notification_view,
                :holiday_view,
                :correction_request
            )
        ');
        $permissionStmt->execute([
            'role_id' => $roleId,
            'dashboard_view' => 'dashboard.view',
            'attendance_view_own' => 'attendance.view_own',
            'attendance_clock_in' => 'attendance.clock_in',
            'attendance_clock_out' => 'attendance.clock_out',
            'leave_view_own' => 'leave.view_own',
            'leave_request' => 'leave.request',
            'notification_view' => 'notification.view',
            'holiday_view' => 'holiday.view',
            'correction_request' => 'attendance.correction.request',
        ]);

        if ($permissionStmt->rowCount() !== 9) {
            throw new RuntimeException(
                'The Employee role requires dashboard, attendance, leave, holiday view, correction request, and notification view permissions.'
            );
        }
    }

    public function createUser(
        int $organizationId,
        int $employeeId,
        string $username,
        string $email,
        string $passwordHash,
        int $statusId
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO users (
                uuid,
                organization_id,
                employee_id,
                username,
                email,
                password_hash,
                status_id
            )
            VALUES (
                :uuid,
                :organization_id,
                :employee_id,
                :username,
                :email,
                :password_hash,
                :status_id
            )
        ');
        $stmt->execute([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'organization_id' => $organizationId,
            'employee_id' => $employeeId,
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'status_id' => $statusId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO user_roles (user_id, role_id)
            SELECT u.id, r.id
            FROM users u
            INNER JOIN roles r
                ON r.id = :role_id
               AND r.organization_id = u.organization_id
            WHERE u.id = :user_id
              AND u.deleted_at IS NULL
        ');
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The owner role could not be assigned to the new account.'
            );
        }
    }

    public function createTrialSubscription(
        int $organizationId,
        int $trialDays
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (
                organization_id,
                plan_id,
                status,
                trial_started_at,
                trial_ends_at
            )
            SELECT
                :organization_id,
                p.id,
                'trialing',
                UTC_TIMESTAMP(),
                TIMESTAMPADD(DAY, :trial_days, UTC_TIMESTAMP())
            FROM plans p
            WHERE p.code = 'TRIAL'
              AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->bindValue(':organization_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindValue(':trial_days', $trialDays, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The active TRIAL plan is missing. Run the plans and subscriptions SQL setup first.'
            );
        }
    }

    public function createVerificationToken(
        int $userId,
        string $tokenHash
    ): void {
        $stmt = $this->db->prepare('
            INSERT INTO email_verification_tokens (
                user_id,
                token_hash,
                expires_at
            )
            VALUES (
                :user_id,
                :token_hash,
                :expires_at
            )
        ');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 86400),
        ]);
    }

    public function findValidVerificationToken(
        string $tokenHash
    ): ?array {
        $stmt = $this->db->prepare('
            SELECT
                t.id AS token_id,
                u.id AS user_id,
                u.organization_id
            FROM email_verification_tokens t
            INNER JOIN users u
                ON u.id = t.user_id
               AND u.deleted_at IS NULL
            WHERE t.token_hash = :token_hash
              AND t.used_at IS NULL
              AND t.expires_at > UTC_TIMESTAMP()
            LIMIT 1
        ');
        $stmt->execute(['token_hash' => $tokenHash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        return $token ?: null;
    }

    public function activatePendingUser(
        int $userId,
        int $organizationId
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE users
            SET status_id = (
                SELECT id FROM user_statuses WHERE code = 'ACTIVE' LIMIT 1
            )
            WHERE id = :user_id
              AND organization_id = :organization_id
              AND deleted_at IS NULL
              AND status_id = (
                  SELECT id FROM user_statuses WHERE code = 'INACTIVE' LIMIT 1
              )
        ");
        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function activateOrganization(int $organizationId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE organizations
            SET status_id = (
                SELECT id
                FROM organization_statuses
                WHERE code = 'ACTIVE'
                LIMIT 1
            )
            WHERE id = :organization_id
              AND status_id = (
                  SELECT id
                  FROM organization_statuses
                  WHERE code = 'INACTIVE'
                  LIMIT 1
              )
        ");
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->rowCount() === 1;
    }

    public function markVerificationTokenUsed(int $tokenId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE email_verification_tokens
            SET used_at = UTC_TIMESTAMP()
            WHERE id = :token_id
              AND used_at IS NULL
              AND expires_at > UTC_TIMESTAMP()
        ');
        $stmt->execute(['token_id' => $tokenId]);

        return $stmt->rowCount() === 1;
    }
}
