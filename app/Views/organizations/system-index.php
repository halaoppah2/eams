<?php

/** @var list<array{id: int|string, name: string, email: ?string, created_at: string, status_code: string, status_name: string}> $organizations */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-semibold mb-1">
            Organizations
        </h2>
        <p class="text-muted mb-0">
            Change access or permanently delete an organization and its associated data.
        </p>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                
                <tbody>
                <?php foreach ($organizations as $organization): ?>
                    <tr>
                        <td><?= $escape($organization['name']) ?></td>
                        <td><?= $escape($organization['email']) ?></td>
                        <td><?= $escape($organization['created_at']) ?></td>
                        <td>
                            <span class="badge <?= $organization['status_code'] === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $escape($organization['status_name']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-2">
                                <form method="POST"
                                      action="<?= APP_BASE_PATH ?>/system/organizations/<?= (int) $organization['id'] ?>/status"
                                      onsubmit="return confirm('Change organization access? Organization data will be retained.');">
                                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="status"
                                           value="<?= $organization['status_code'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' ?>">
                                    <button class="btn btn-sm <?= $organization['status_code'] === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            type="submit">
                                        <?= $organization['status_code'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="POST"
                                      action="<?= APP_BASE_PATH ?>/system/organizations/<?= (int) $organization['id'] ?>/delete"
                                      onsubmit="return confirm('Permanently delete this organization and all its data? This cannot be undone.');">
                                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <label class="visually-hidden"
                                           for="delete-organization-<?= (int) $organization['id'] ?>">
                                        Type <?= $escape($organization['name']) ?> to confirm deletion
                                    </label>
                                    <input
                                        id="delete-organization-<?= (int) $organization['id'] ?>"
                                        name="confirmation_name"
                                        class="form-control form-control-sm mb-1"
                                        placeholder="Type organization name"
                                        autocomplete="off"
                                        required
                                    >
                                    <button class="btn btn-sm btn-danger" type="submit">
                                        Permanently delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($organizations === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No organizations found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Organizations';
require __DIR__ . '/../layout/app.php';
