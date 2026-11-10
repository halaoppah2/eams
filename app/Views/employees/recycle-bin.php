<?php

$employees = $employees ?? [];

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? '—'),
        ENT_QUOTES,
        'UTF-8'
    );
};

ob_start();
?>

<div class="container-fluid py-3">

    <!-- Page heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-trash3 me-2"></i>Employee Recycle Bin
            </h4>
            <p class="text-muted mb-0">
                View deleted employees and restore them when necessary.
            </p>
        </div>

        <a href="<?= APP_BASE_PATH ?>/employees"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Employees
        </a>
    </div>

    <!-- Summary -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex align-items-center gap-3 py-3">
            <div class="rounded-circle bg-danger-subtle text-danger
                        d-flex align-items-center justify-content-center"
                 style="width: 48px; height: 48px; font-size: 20px;">
                <i class="bi bi-trash3"></i>
            </div>

            <div>
                <div class="text-muted small">Deleted Employees</div>
                <h5 class="mb-0"><?= count($employees) ?></h5>
            </div>
        </div>
    </div>

    <?php if (empty($employees)): ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-trash3 text-muted"
                   style="font-size: 3rem;"></i>

                <h6 class="mt-3">Recycle Bin is Empty</h6>

                <p class="text-muted mb-3">
                    There are no deleted employees to display.
                </p>

                <a href="<?= APP_BASE_PATH ?>/employees"
                   class="btn btn-primary">
                    <i class="bi bi-people me-1"></i>View Employees
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white py-3">
                <h6 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Deleted Employee Records
                </h6>
            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Employee Number</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Deleted At</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($employees as $employee): ?>

                            <?php
                            $employeeName = trim(
                                ($employee['first_name'] ?? '') . ' ' .
                                ($employee['middle_name'] ?? '') . ' ' .
                                ($employee['last_name'] ?? '')
                            );
                            ?>

                            <tr>
                                <td class="ps-3">
                                    <span class="fw-semibold">
                                        <?= $escape($employee['employee_number'] ?? null) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= $escape($employeeName !== '' ? $employeeName : 'Unknown') ?>
                                </td>

                                <td>
                                    <?= $escape($employee['department_name'] ?? null) ?>
                                </td>

                                <td>
                                    <?= $escape($employee['position_name'] ?? null) ?>
                                </td>

                                <td>
                                    <span class="text-muted small">
                                        <?= $escape($employee['deleted_at'] ?? null) ?>
                                    </span>
                                </td>

                                <td class="pe-3">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">

                                        <!-- Restore -->
                                        <form
                                            method="POST"
                                            action="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>/restore"
                                            onsubmit="return confirm('Restore this employee?');"
                                        >
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                Restore
                                            </button>
                                        </form>

                                        <!-- Permanent Delete -->
                                        <?php if (
                                            class_exists(\App\Services\PermissionService::class) &&
                                            \App\Services\PermissionService::has('employee.delete_permanent')
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>/permanent-delete"
                                                onsubmit="return confirm('Permanently delete this employee? This action cannot be undone.');"
                                            >
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash3 me-1"></i>
                                                    Delete Permanently
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();

$pageTitle = 'Employee Recycle Bin';
require __DIR__ . '/../layout/app.php';
?>