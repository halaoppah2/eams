<?php

$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$selectedPermissionIds = array_map('intval', $selectedPermissionIds ?? []);
$isEdit = is_array($role);
$action = $isEdit
    ? APP_BASE_PATH . '/roles/' . (int) $role['id']
    : APP_BASE_PATH . '/roles';
$groupedPermissions = [];
foreach ($permissions as $permission) {
    $groupedPermissions[$permission['module']][] = $permission;
}
ob_start();
?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-semibold mb-1"><?= $isEdit ? 'Edit Role' : 'Create Role' ?></h2>
        <p class="text-muted mb-0">Choose only the permissions this role needs.</p>
    </div>
    <form method="POST" action="<?= $action ?>">
        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="role-name">Role name</label>
                    <input id="role-name" name="name" class="form-control" maxlength="100"
                           value="<?= $escape($role['name'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="form-label" for="role-description">Description</label>
                    <textarea id="role-description" name="description" class="form-control"
                              maxlength="255" rows="2"><?= $escape($role['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <?php foreach ($groupedPermissions as $module => $modulePermissions): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold text-capitalize"><?= $escape(str_replace('_', ' ', $module)) ?></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($modulePermissions as $permission): ?>
                            <?php $permissionId = (int) $permission['id']; ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="permission_ids[]"
                                           id="permission-<?= $permissionId ?>"
                                           value="<?= $permissionId ?>"
                                           <?= in_array($permissionId, $selectedPermissionIds, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="permission-<?= $permissionId ?>">
                                        <span class="fw-medium"><?= $escape($permission['name']) ?></span>
                                        <?php if (!empty($permission['description'])): ?>
                                            <small class="d-block text-muted"><?= $escape($permission['description']) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-end gap-2">
            <a href="<?= APP_BASE_PATH ?>/roles" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Role' ?></button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Role' : 'Create Role';
require __DIR__ . '/../layout/app.php';
