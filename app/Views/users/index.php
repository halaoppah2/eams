<?php
ob_start();

$users = $users ?? [];
?>

<div class="container-fluid px-0">

    <div class="container d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">User Management</h4>
            <p class="text-muted mb-0">
                View user accounts and their assigned roles.
            </p>
        </div>

        <a href="<?= APP_BASE_PATH ?>/employees"
           class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>
            Manage Employees
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <?php if (empty($users)): ?>

                <div class="text-center py-5">
                    <i class="bi bi-people display-5 text-muted"></i>

                    <h5 class="mt-3">No User Accounts Found</h5>

                    <p class="text-muted mb-0">
                        User accounts will appear here after they are created.
                    </p>
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Roles</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $fullName = trim(
                                    ($user['first_name'] ?? '') . ' ' .
                                    ($user['middle_name'] ?? '') . ' ' .
                                    ($user['last_name'] ?? '')
                                );

                                $status = strtoupper(
                                    $user['status_code'] ?? 'UNKNOWN'
                                );

                                $statusClass = match ($status) {
                                    'ACTIVE' => 'success',
                                    'INACTIVE' => 'secondary',
                                    'SUSPENDED', 'LOCKED' => 'danger',
                                    default => 'warning'
                                };

                                $roles = $user['roles'] ?? [];
                                ?>

                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($fullName ?: 'N/A') ?>
                                        </div>

                                        <small class="text-muted">
                                            <?= htmlspecialchars(
                                                $user['employee_number'] ?? 'N/A'
                                            ) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($user['username'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($user['email'] ?? '') ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($roles)): ?>
                                            <?php foreach ($roles as $role): ?>
                                                <span class="badge bg-light text-dark border me-1 mb-1">
                                                    <?= htmlspecialchars($role['name'] ?? '') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No role assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
$pageTitle = 'User Management';
require __DIR__ . '/../layout/app.php';