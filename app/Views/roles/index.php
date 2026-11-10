<?php

$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$permissions = $_SESSION['permissions'] ?? [];
$canCreate = in_array('role.create', $permissions, true);
$canUpdate = in_array('role.update', $permissions, true)
    && in_array('role.assign_permission', $permissions, true);
$canDelete = in_array('role.delete', $permissions, true);
ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">Roles &amp; Permissions</h2>
            <p class="text-muted mb-0">Manage access roles for this organization.</p>
        </div>
        <?php if ($canCreate): ?>
            <a href="<?= APP_BASE_PATH ?>/roles/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Create Role
            </a>
        <?php endif; ?>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Role</th><th>Kind</th><th>Permissions</th><th>Users</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td>
                            <strong><?= $escape($role['name']) ?></strong>
                            <?php if (!empty($role['description'])): ?>
                                <div class="small text-muted"><?= $escape($role['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $role['is_system_role'] === 1 ? 'System' : 'Custom' ?></td>
                        <td><?= (int) $role['permission_count'] ?></td>
                        <td><?= (int) $role['user_count'] ?></td>
                        <td>
                            <?php if ((int) $role['is_system_role'] === 1): ?>
                                <span class="text-muted small">Protected</span>
                            <?php else: ?>
                                <div class="d-flex gap-2">
                                    <?php if ($canUpdate): ?>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= APP_BASE_PATH ?>/roles/<?= (int) $role['id'] ?>/edit">Edit</a>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <form method="POST" action="<?= APP_BASE_PATH ?>/roles/<?= (int) $role['id'] ?>/delete"
                                              onsubmit="return confirm('Delete this role? Roles assigned to users cannot be deleted.');">
                                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($roles === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No roles found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="form-text mt-3">
        System roles are protected. Users whose role permissions change may need to sign in again before the change appears in their session.
    </p>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Roles & Permissions';
require __DIR__ . '/../layout/app.php';
