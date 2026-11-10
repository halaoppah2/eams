<?php

namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByUsername(string $username): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.uuid,
                u.organization_id,
                u.employee_id,
                u.username,
                u.email,
                u.profile_picture,
                o.logo AS organization_logo,
                u.password_hash,
                u.status_id,
                u.is_system_admin,
                us.code AS status_code,
                e.first_name,
                e.last_name
            FROM users u
            INNER JOIN organizations o
                ON o.id = u.organization_id
            INNER JOIN organization_statuses os
                ON os.id = o.status_id
            INNER JOIN user_statuses us
                ON us.id = u.status_id

            INNER JOIN employees e
                ON e.id = u.employee_id
               AND e.organization_id = u.organization_id
               AND e.deleted_at IS NULL

            WHERE u.username = :username
              AND u.deleted_at IS NULL
              AND us.code = 'ACTIVE'
              AND (os.code = 'ACTIVE' OR u.is_system_admin = 1)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'username' => $username
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findSessionDetails(
        int $userId,
        int $employeeId,
        int $organizationId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                u.is_system_admin,
                u.email,
                u.profile_picture,
                o.logo AS organization_logo,
                e.first_name,
                e.last_name
            FROM users u
            INNER JOIN organizations o
                ON o.id = u.organization_id
            INNER JOIN organization_statuses os
                ON os.id = o.status_id
            INNER JOIN user_statuses us
                ON us.id = u.status_id
            INNER JOIN employees e
                ON e.id = u.employee_id
               AND e.organization_id = u.organization_id
               AND e.deleted_at IS NULL
            WHERE u.id = :user_id
              AND u.employee_id = :employee_id
              AND u.organization_id = :organization_id
              AND u.deleted_at IS NULL
              AND us.code = 'ACTIVE'
              AND (os.code = 'ACTIVE' OR u.is_system_admin = 1)
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function getRoles(int $userId): array
    {
        $sql = '
            SELECT
                r.id,
                r.name,
                r.description,
                r.is_system_role
            FROM user_roles ur

            INNER JOIN users u
                ON u.id = ur.user_id

            INNER JOIN roles r
                ON r.id = ur.role_id
               AND r.organization_id = u.organization_id

            WHERE ur.user_id = :user_id
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function getPermissions(int $userId): array
    {
        $sql = '
            SELECT DISTINCT
                p.id,
                p.name,
                p.module,
                p.action
            FROM user_roles ur

            INNER JOIN users u
                ON u.id = ur.user_id

            INNER JOIN role_permissions rp
                ON rp.role_id = ur.role_id

            INNER JOIN permissions p
                ON p.id = rp.permission_id

            INNER JOIN roles r
                ON r.id = ur.role_id
               AND r.organization_id = u.organization_id

            WHERE ur.user_id = :user_id
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function updateLastLogin(int $userId): void
    {
        $sql = '
            UPDATE users
            SET last_login_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $userId
        ]);
    }

    public function findByEmployeeId(int $employeeId): ?array
    {
        $sql = '
        SELECT
            u.id,
            u.uuid,
            u.organization_id,
            u.employee_id,
            u.username,
            u.email,
            u.status_id
        FROM users u
        WHERE u.employee_id = :employee_id
          AND u.deleted_at IS NULL
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'employee_id' => $employeeId
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findProfile(
        int $userId,
        int $employeeId,
        int $organizationId
    ): ?array {
        $stmt = $this->db->prepare('
            SELECT
                e.*,
                u.username,
                u.email AS account_email,
                u.profile_picture,
                b.name AS branch_name,
                d.name AS department_name,
                p.title AS position_name,
                et.name AS employment_type,
                es.name AS employee_status
            FROM users u
            INNER JOIN employees e
                ON e.id = u.employee_id
               AND e.organization_id = u.organization_id
               AND e.deleted_at IS NULL
            LEFT JOIN branches b
                ON b.id = e.branch_id
               AND b.organization_id = e.organization_id
            LEFT JOIN departments d
                ON d.id = e.department_id
               AND d.organization_id = e.organization_id
            LEFT JOIN positions p
                ON p.id = e.position_id
               AND p.organization_id = e.organization_id
            LEFT JOIN employment_types et
                ON et.id = e.employment_type_id
            LEFT JOIN employee_statuses es
                ON es.id = e.employee_status_id
            WHERE u.id = :user_id
              AND u.employee_id = :employee_id
              AND u.organization_id = :organization_id
              AND u.deleted_at IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    public function updateProfilePicture(
        int $userId,
        int $employeeId,
        int $organizationId,
        string $profilePicture
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE users
            SET profile_picture = :profile_picture
            WHERE id = :user_id
              AND employee_id = :employee_id
              AND organization_id = :organization_id
              AND deleted_at IS NULL
        ');
        $stmt->execute([
            'profile_picture' => $profilePicture,
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function findByEmail(
        string $email,
        int $organizationId
    ): ?array
    {
        $sql = '
        SELECT
            u.id,
            u.uuid,
            u.organization_id,
            u.employee_id,
            u.username,
            u.email,
            u.status_id
        FROM users u
        WHERE u.email = :email
          AND u.organization_id = :organization_id
          AND u.deleted_at IS NULL
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'email' => $email,
            'organization_id' => $organizationId,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('
            SELECT
                u.id,
                u.email
            FROM users u
            INNER JOIN organizations o
                ON o.id = u.organization_id
            INNER JOIN organization_statuses os
                ON os.id = o.status_id
            INNER JOIN user_statuses us
                ON us.id = u.status_id
            WHERE u.email = :email
              AND u.deleted_at IS NULL
              AND us.code = :active_status
              AND os.code = :active_organization
            LIMIT 1
        ');
        $stmt->execute([
            'email' => $email,
            'active_status' => 'ACTIVE',
            'active_organization' => 'ACTIVE',
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET
                password_hash = :password_hash,
                password_changed_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND deleted_at IS NULL
        ');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function updateEmail(
        int $userId,
        int $employeeId,
        int $organizationId,
        string $email
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE users
            SET email = :email
            WHERE id = :user_id
              AND employee_id = :employee_id
              AND organization_id = :organization_id
              AND deleted_at IS NULL
        ');
        $stmt->execute([
            'email' => $email,
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'organization_id' => $organizationId,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function findByUsernameForAccount(
        string $username
    ): ?array {
        $sql = '
        SELECT
            u.id,
            u.employee_id,
            u.username,
            u.email
        FROM users u
        WHERE u.username = :username
          AND u.deleted_at IS NULL
        LIMIT 1
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'username' => $username
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $sql = '
        INSERT INTO users (
            uuid,
            organization_id,
            employee_id,
            username,
            email,
            password_hash,
            status_id,
            password_changed_at
        )
        SELECT
            :uuid,
            :organization_id,
            :employee_id,
            :username,
            :email,
            :password_hash,
            :status_id,
            :password_changed_at
        FROM employees e
        WHERE e.id = :employee_check_id
          AND e.organization_id = :employee_organization_id
          AND e.deleted_at IS NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'uuid' => $data['uuid'],
            'organization_id' => $data['organization_id'],
            'employee_id' => $data['employee_id'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'status_id' => $data['status_id'],
            'password_changed_at' => $data['password_changed_at'] ?? null,
            'employee_check_id' => $data['employee_id'],
            'employee_organization_id' => $data['organization_id'],
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException(
                'The employee must belong to this organization before an account can be created.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    public function assignRole(
        int $userId,
        int $roleId
    ): void {
        $sql = '
        INSERT INTO user_roles (
            user_id,
            role_id
        )
        SELECT u.id, r.id
        FROM users u
        INNER JOIN roles r
            ON r.id = :role_id
           AND r.organization_id = u.organization_id
        WHERE u.id = :user_id
          AND u.deleted_at IS NULL
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException(
                'The role does not belong to the user organization.'
            );
        }
    }

    public function findRoleIdByName(
        string $roleName,
        int $organizationId
    ): ?int {
        $stmt = $this->db->prepare('
            SELECT id
            FROM roles
            WHERE organization_id = :organization_id
              AND name = :role_name
            LIMIT 1
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'role_name' => $roleName,
        ]);
        $roleId = $stmt->fetchColumn();

        return $roleId === false ? null : (int) $roleId;
    }

    public function findStatusIdByCode(string $code): ?int
    {
        $stmt = $this->db->prepare('
            SELECT id
            FROM user_statuses
            WHERE code = :code
            LIMIT 1
        ');
        $stmt->execute(['code' => strtoupper($code)]);
        $statusId = $stmt->fetchColumn();

        return $statusId === false ? null : (int) $statusId;
    }

    public function findAllByOrganization(int $organizationId): array
    {
        $sql = '
        SELECT
            u.id,
            u.employee_id,
            u.username,
            u.email,
            u.status_id,
            us.code AS status_code,
            e.employee_number,
            e.first_name,
            e.middle_name,
            e.last_name
        FROM users u

        INNER JOIN user_statuses us
            ON us.id = u.status_id

        INNER JOIN employees e
            ON e.id = u.employee_id
           AND e.organization_id = u.organization_id

        WHERE u.organization_id = :organization_id
          AND u.deleted_at IS NULL

        ORDER BY e.first_name ASC, e.last_name ASC
    ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId
        ]);

        $users = $stmt->fetchAll();

        foreach ($users as &$user) {
            $user['roles'] = $this->getRoles((int) $user['id']);
        }

        unset($user);

        return $users;
    }

    
    /**
     * Get active user accounts belonging to an organization.
     */
    public function findActiveByOrganization(
        int $organizationId
    ): array {
        $sql = '
            SELECT
                u.id,
                u.employee_id,
                u.username,
                u.email,
                e.employee_number,
                e.first_name,
                e.last_name
            FROM users u

            INNER JOIN user_statuses us
                ON us.id = u.status_id

            INNER JOIN employees e
                ON e.id = u.employee_id
               AND e.organization_id = u.organization_id

            WHERE u.organization_id = :organization_id
              AND u.deleted_at IS NULL
              AND us.code = :active_status

            ORDER BY e.first_name ASC, e.last_name ASC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId,
            'active_status' => 'ACTIVE'
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
