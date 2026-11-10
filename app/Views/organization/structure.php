<?php

$branches = $settings['branches'] ?? [];
$departments = $settings['departments'] ?? [];
$positions = $settings['positions'] ?? [];
$activeBranches = $settings['active_branches'] ?? [];
$managers = $settings['managers'] ?? [];

$escape = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

ob_start();
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-semibold mb-1">
            Organization Structure
        </h2>

        <p class="text-muted mb-0">
            Manage your organization’s branches, departments, and positions.
            Inactive items are kept for existing employee records but are not
            offered for new assignments.
        </p>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                Branches
            </h5>
        </div>

        <!-- Branch Form -->
        <div class="card-body">
            <form method="POST" action="<?= APP_BASE_PATH ?>/settings/organization-structure/branch"
                  class="row g-3 mb-4">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">

                <div class="col-md-4">
                    <label class="form-label" for="branch-name">
                        Branch Name
                    </label>
                    <input id="branch-name" name="name" class="form-control" maxlength="150" placeholder="Cape Coast" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="branch-code">
                        Code
                    </label>

                    <input id="branch-code" name="code" class="form-control" maxlength="30"
                    pattern="[A-Za-z0-9][A-Za-z0-9_-]*" placeholder="HQ" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="branch-email">
                        Email
                    </label>

                    <input id="branch-email" name="email" type="email" maxlength="150" class="form-control" placeholder="branch@company.com">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="branch-phone">
                        Phone
                    </label>

                    <input id="branch-phone" name="phone" maxlength="30" class="form-control" placeholder="0203025772">
                </div>

                <div class="col-12">
                    <label class="form-label" for="branch-address">
                        Address
                    </label>

                    <textarea id="branch-address" name="address" rows="2" class="form-control" placeholder="123 Main Street, City, Country"></textarea>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>
                        Add Branch
                    </button>
                </div>
            </form>

            <?php if ($branches === []): ?>
                <p class="text-muted mb-0">
                    No branches have been added.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td>
                                    <?= $escape($branch['name']) ?>
                                    <details class="mt-1">
                                        <summary class="small text-primary">
                                            Edit details
                                        </summary>
                                        <form method="POST"
                                              action="<?= APP_BASE_PATH ?>/settings/organization-structure/branch/<?= (int) $branch['id'] ?>"
                                              class="row g-2 mt-2">

                                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">

                                            <div class="col-md-6">
                                                <label class="form-label">Name</label>
                                                <input name="name" class="form-control"
                                                       value="<?= $escape($branch['name']) ?>" maxlength="150" required>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">
                                                    Code
                                                </label>
                                                <input name="code" class="form-control"
                                                       value="<?= $escape($branch['code']) ?>" maxlength="30"
                                                       pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">
                                                    Email
                                                </label>
                                                <input name="email" type="email" class="form-control"
                                                       value="<?= $escape($branch['email']) ?>" maxlength="150">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Phone
                                                </label>
                                                <input name="phone" class="form-control"
                                                       value="<?= $escape($branch['phone']) ?>" maxlength="30">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">
                                                    Address
                                                </label>
                                                <textarea name="address" rows="2" class="form-control"><?= $escape($branch['address']) ?></textarea>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button class="btn btn-sm btn-primary" type="submit">Save branch</button>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                                <td>
                                    <?= $escape($branch['code']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $branch['status_code'] === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= $escape($branch['status_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="<?= APP_BASE_PATH ?>/settings/organization-structure/branch/<?= (int) $branch['id'] ?>/status">
                                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                        <button class="btn btn-sm <?= $branch['status_code'] === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success btn-sm' ?>"
                                                type="submit">
                                            <?= $branch['status_code'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Departments -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                Departments
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_BASE_PATH ?>/settings/organization-structure/department"
                  class="row g-3 mb-4">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                <div class="col-md-3">
                    <label class="form-label" for="department-branch">
                        Branch
                    </label>
                    <select id="department-branch" name="branch_id" class="form-select" required>
                        <option value="">Select branch</option>
                        <?php foreach ($activeBranches as $branch): ?>
                            <option value="<?= (int) $branch['id'] ?>"><?= $escape($branch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="department-name">
                        Department name
                    </label>
                    <input id="department-name" name="name" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="department-code">
                        Code
                    </label>
                    <input id="department-code" name="code" class="form-control" maxlength="30"
                           pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="department-manager">
                        Department manager
                    </label>
                    <select id="department-manager" name="manager_id" class="form-select">
                        <option value="">No manager</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?= (int) $manager['id'] ?>">
                                <?= $escape($manager['first_name'] . ' ' . $manager['last_name'] . ' - ' . $manager['employee_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>
                        Add Department
                    </button>
                </div>
            </form>

            <?php if ($departments === []): ?>
                <p class="text-muted mb-0">No departments have been added.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Branch</th>
                                <th>Manager</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($departments as $department): ?>
                            <?php
                            $managerFound = false;
                            foreach ($managers as $manager) {
                                if ((int) $manager['id'] === (int) ($department['manager_id'] ?? 0)) {
                                    $managerFound = true;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <?= $escape($department['name']) ?>
                                    <details class="mt-1">
                                        <summary class="small text-primary">Edit details</summary>
                                        <form method="POST"
                                              action="<?= APP_BASE_PATH ?>/settings/organization-structure/department/<?= (int) $department['id'] ?>"
                                              class="row g-2 mt-2">
                                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                            <div class="col-md-3">
                                                <label class="form-label">
                                                    Branch
                                                </label>
                                                <select name="branch_id" class="form-select" required>
                                                    <?php
                                                    $departmentBranchIsActive = false;
                                                    foreach ($activeBranches as $branch) {
                                                        if ((int) $branch['id'] === (int) $department['branch_id']) {
                                                            $departmentBranchIsActive = true;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <?php if (!$departmentBranchIsActive): ?>
                                                        <option value="" selected>Select an active branch</option>
                                                    <?php endif; ?>
                                                    <?php foreach ($activeBranches as $branch): ?>
                                                        <option value="<?= (int) $branch['id'] ?>"
                                                            <?= $departmentBranchIsActive && (int) $department['branch_id'] === (int) $branch['id'] ? 'selected' : '' ?>>
                                                            <?= $escape($branch['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">
                                                    Name
                                                </label>
                                                <input name="name" class="form-control"
                                                       value="<?= $escape($department['name']) ?>" maxlength="150" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">
                                                    Code
                                                </label>
                                                <input name="code" class="form-control"
                                                       value="<?= $escape($department['code']) ?>" maxlength="30"
                                                       pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Manager
                                                </label>
                                                <select name="manager_id" class="form-select">
                                                    <option value="">No manager</option>
                                                    <?php if (!$managerFound && !empty($department['manager_id'])): ?>
                                                        <option value="<?= (int) $department['manager_id'] ?>" selected>
                                                            <?= $escape(trim(($department['manager_first_name'] ?? '') . ' ' . ($department['manager_last_name'] ?? '')) ?: 'Unavailable manager') ?>
                                                        </option>
                                                    <?php endif; ?>
                                                    <?php foreach ($managers as $manager): ?>
                                                        <option value="<?= (int) $manager['id'] ?>"
                                                            <?= (int) ($department['manager_id'] ?? 0) === (int) $manager['id'] ? 'selected' : '' ?>>
                                                            <?= $escape($manager['first_name'] . ' ' . $manager['last_name'] . ' - ' . $manager['employee_number']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button class="btn btn-sm btn-primary" type="submit">Save department</button>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                                <td><?= $escape($department['branch_name']) ?></td>
                                <td><?= $escape(trim(($department['manager_first_name'] ?? '') . ' ' . ($department['manager_last_name'] ?? '')) ?: '—') ?></td>
                                <td><?= $escape($department['code']) ?></td>
                                <td>
                                    <span class="badge <?= $department['status_code'] === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= $escape($department['status_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="<?= APP_BASE_PATH ?>/settings/organization-structure/department/<?= (int) $department['id'] ?>/status">
                                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                        <button class="btn btn-sm <?= $department['status_code'] === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success btn-sm' ?>"
                                                type="submit">
                                            <?= $department['status_code'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Positions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                Positions
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_BASE_PATH ?>/settings/organization-structure/position"
                  class="row g-3 mb-4">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                <div class="col-md-4">
                    <label class="form-label" for="position-title">
                        Position title
                    </label>
                    <input id="position-title" name="title" class="form-control" maxlength="150" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="position-code">
                        Code
                    </label>
                    <input id="position-code" name="code" class="form-control" maxlength="30"
                           pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="position-description">
                        Description
                    </label>
                    <input id="position-description" name="description" class="form-control">
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-lg me-1"></i>Add Position
                    </button>
                </div>
            </form>

            <?php if ($positions === []): ?>
                <p class="text-muted mb-0">
                    No positions have been added.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($positions as $position): ?>
                            <tr>
                                <td>
                                    <?= $escape($position['title']) ?>
                                    <details class="mt-1">
                                        <summary class="small text-primary">
                                            Edit details
                                        </summary>
                                        <form method="POST"
                                              action="<?= APP_BASE_PATH ?>/settings/organization-structure/position/<?= (int) $position['id'] ?>"
                                              class="row g-2 mt-2">
                                            <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                            <div class="col-md-4">
                                                <label class="form-label">Title</label>
                                                <input name="title" class="form-control"
                                                       value="<?= $escape($position['title']) ?>" maxlength="150" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Code</label>
                                                <input name="code" class="form-control"
                                                       value="<?= $escape($position['code']) ?>" maxlength="30"
                                                       pattern="[A-Za-z0-9][A-Za-z0-9_-]*" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">
                                                    Description
                                                </label>
                                                <textarea name="description" rows="2" class="form-control"><?= $escape($position['description']) ?></textarea>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button class="btn btn-sm btn-primary" type="submit">Save position</button>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                                <td>
                                    <?= $escape($position['code']) ?>
                                </td>
                                <td>
                                    <?= $escape($position['description'] ?: '—') ?>
                                </td>
                                <td>
                                    <span class="badge <?= $position['status_code'] === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= $escape($position['status_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="<?= APP_BASE_PATH ?>/settings/organization-structure/position/<?= (int) $position['id'] ?>/status">
                                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
                                        <button class="btn btn-sm <?= $position['status_code'] === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success btn-sm' ?>"
                                                type="submit">
                                            <?= $position['status_code'] === 'ACTIVE' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
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
$pageTitle = 'Organization Structure';
require __DIR__ . '/../layout/app.php';
