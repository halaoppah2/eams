<?php

namespace App\Controllers;

use App\HttpException;
use App\Repositories\AuditRepository;
use App\Repositories\RoleRepository;
use App\Services\AuditService;
use App\Session;
use PDOException;
use RuntimeException;

class RoleController
{
    private RoleRepository $roles;
    private AuditService $audit;

    public function __construct()
    {
        $this->roles = new RoleRepository();
        $this->audit = new AuditService(new AuditRepository());
    }

    public function index(): void
    {
        $organizationId = $this->organizationId();
        $roles = $this->roles->getAll($organizationId);
        $csrfToken = $this->csrfToken();
        require __DIR__ . '/../Views/roles/index.php';
    }

    public function create(): void
    {
        $permissions = $this->roles->getPermissions();
        $role = null;
        $selectedPermissionIds = [];
        $csrfToken = $this->csrfToken();
        require __DIR__ . '/../Views/roles/form.php';
    }

    public function store(): void
    {
        $this->verifyCsrfToken();
        [$name, $description, $permissionIds] = $this->input();
        try {
            $roleId = $this->roles->create(
                $this->organizationId(),
                $name,
                $description,
                $permissionIds
            );
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'A role with that name already exists in your organization.',
                    0,
                    $e
                );
            }
            throw $e;
        }
        $this->audit->recordAction(
            'ROLE_CREATED',
            'role',
            'role',
            $roleId,
            null,
            ['name' => $name, 'permission_ids' => $permissionIds]
        );
        $this->flashAndRedirect('Role created successfully.', 'success');
    }

    public function edit(int $id): void
    {
        $organizationId = $this->organizationId();
        $role = $this->roles->findById($id, $organizationId);
        if ($role === null) {
            throw new HttpException('Role not found.', 404);
        }
        if ((int) $role['is_system_role'] === 1) {
            throw new HttpException(
                'System roles are protected. Create a custom role to change access.',
                403
            );
        }

        $permissions = $this->roles->getPermissions();
        $selectedPermissionIds = $this->roles->getPermissionIds($id, $organizationId);
        $csrfToken = $this->csrfToken();
        require __DIR__ . '/../Views/roles/form.php';
    }

    public function update(int $id): void
    {
        $this->verifyCsrfToken();
        [$name, $description, $permissionIds] = $this->input();
        $organizationId = $this->organizationId();
        $role = $this->roles->findById($id, $organizationId);
        if ($role === null) {
            throw new HttpException('Role not found.', 404);
        }
        $oldName = $role['name'];
        try {
            $this->roles->update(
                $id,
                $organizationId,
                $name,
                $description,
                $permissionIds
            );
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException(
                    'A role with that name already exists in your organization.',
                    0,
                    $e
                );
            }
            throw $e;
        }
        $this->audit->recordAction(
            'ROLE_UPDATED',
            'role',
            'role',
            $id,
            ['name' => $oldName],
            ['name' => $name, 'permission_ids' => $permissionIds]
        );
        $this->flashAndRedirect('Role updated successfully.', 'success');
    }

    public function delete(int $id): void
    {
        $this->verifyCsrfToken();
        $organizationId = $this->organizationId();
        $role = $this->roles->findById($id, $organizationId);
        if ($role === null) {
            throw new HttpException('Role not found.', 404);
        }
        $this->roles->delete($id, $organizationId);
        $this->audit->recordDelete(
            'role',
            'role',
            $id,
            ['name' => $role['name']]
        );
        $this->flashAndRedirect('Role deleted successfully.', 'success');
    }

    private function input(): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            throw new RuntimeException('Role name is required and must be at most 100 characters.');
        }
        if (mb_strlen($description) > 255) {
            throw new RuntimeException('Role description cannot exceed 255 characters.');
        }

        $rawPermissionIds = $_POST['permission_ids'] ?? [];
        if (!is_array($rawPermissionIds)) {
            throw new RuntimeException('Invalid permission selection.');
        }
        $permissionIds = [];
        foreach ($rawPermissionIds as $value) {
            $permissionId = filter_var($value, FILTER_VALIDATE_INT);
            if ($permissionId === false || $permissionId <= 0) {
                throw new RuntimeException('Invalid permission selection.');
            }
            $permissionIds[] = (int) $permissionId;
        }
        $permissionIds = array_values(array_unique($permissionIds));

        return [
            $name,
            $description === '' ? null : $description,
            $permissionIds,
        ];
    }

    private function organizationId(): int
    {
        $organizationId = (int) (
            Session::get('user', [])['organization_id'] ?? 0
        );
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }
        return $organizationId;
    }

    private function csrfToken(): string
    {
        $token = Session::get('role_management_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('role_management_csrf_token', $token);
        }
        return $token;
    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('role_management_csrf_token');
        $submittedToken = $_POST['_csrf_token'] ?? null;
        if (
            !is_string($sessionToken)
            || !is_string($submittedToken)
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            throw new RuntimeException('This form has expired. Reload the page and try again.');
        }
    }

    private function flashAndRedirect(string $message, string $type): void
    {
        Session::set('flash_message', [
            'type' => $type,
            'title' => $type === 'success' ? 'Role updated' : 'Action failed',
            'message' => $message,
        ]);
        header('Location: ' . APP_BASE_PATH . '/roles', true, 303);
        exit;
    }
}
