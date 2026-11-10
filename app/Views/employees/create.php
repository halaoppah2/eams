<?php

$branches = $formData['branches'] ?? [];
$departments = $formData['departments'] ?? [];
$positions = $formData['positions'] ?? [];
$employmentTypes = $formData['employment_types'] ?? [];
$employeeStatuses = $formData['employee_statuses'] ?? [];
$managers = $formData['managers'] ?? [];
$error = $error ?? null;

?>

<?php ob_start(); ?>

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                gap-3 mb-4">

        <div>
            <h2 class="fw-semibold mb-1">Create Employee</h2>
            <p class="text-muted mb-0">
                Add a new employee to your organization.
            </p>
        </div>

        <a
            href="<?= APP_BASE_PATH ?>/employees"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Back to Employees
        </a>

    </div>

    <!-- Employee Form -->
    <form method="POST" action="<?= APP_BASE_PATH ?>/employees">

        <!-- Personal Information -->
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="bi bi-person me-2 text-primary"></i>
                    Personal Information
                </h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employee Number</label>
                        <input
                            type="text"
                            name="employee_number"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">First Name</label>
                        <input
                            type="text"
                            name="first_name"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Middle Name</label>
                        <input
                            type="text"
                            name="middle_name"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last Name</label>
                        <input
                            type="text"
                            name="last_name"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input
                            type="date"
                            name="date_of_birth"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hire Date</label>
                        <input
                            type="date"
                            name="hire_date"
                            class="form-control"
                            required
                        >
                    </div>

                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="bi bi-telephone me-2 text-primary"></i>
                    Contact Information
                </h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea
                            name="address"
                            class="form-control"
                            rows="3"
                        ></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Employment Information -->
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="bi bi-briefcase me-2 text-primary"></i>
                    Employment Information
                </h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Branch</label>
                        <select
                            name="branch_id"
                            id="employee-branch"
                            class="form-select"
                            required
                        >
                            <option value="">Select Branch</option>

                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch['id'] ?>">
                                    <?= htmlspecialchars(
                                        $branch['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department</label>
                        <select
                            name="department_id"
                            id="employee-department"
                            class="form-select"
                            required
                        >
                            <option value="">Select Department</option>

                            <?php foreach ($departments as $department): ?>
                                <option value="<?= (int) $department['id'] ?>"
                                        data-branch-id="<?= (int) $department['branch_id'] ?>">
                                    <?= htmlspecialchars(
                                        $department['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    — <?= htmlspecialchars(
                                        $department['branch_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Position</label>
                        <select
                            name="position_id"
                            class="form-select"
                            required
                        >
                            <option value="">Select Position</option>

                            <?php foreach ($positions as $position): ?>
                                <option value="<?= (int) $position['id'] ?>">
                                    <?= htmlspecialchars(
                                        $position['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Employment Type</label>
                        <select
                            name="employment_type_id"
                            class="form-select"
                            required
                        >
                            <option value="">Select Employment Type</option>

                            <?php foreach ($employmentTypes as $type): ?>
                                <option value="<?= (int) $type['id'] ?>">
                                    <?= htmlspecialchars(
                                        $type['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Employee Status</label>
                        <select
                            name="employee_status_id"
                            class="form-select"
                            required
                        >
                            <option value="">Select Employee Status</option>

                            <?php foreach ($employeeStatuses as $status): ?>
                                <option value="<?= (int) $status['id'] ?>">
                                    <?= htmlspecialchars(
                                        $status['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Manager</label>
                        <select
                            name="manager_id"
                            class="form-select"
                        >
                            <option value="">No Manager</option>

                            <?php foreach ($managers as $manager): ?>
                                <option value="<?= (int) $manager['id'] ?>">
                                    <?= htmlspecialchars(
                                        $manager['first_name']
                                        . ' '
                                        . $manager['last_name']
                                        . ' - '
                                        . $manager['employee_number'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex justify-content-end gap-2 mb-4">

            <a
                href="<?= APP_BASE_PATH ?>/employees"
                class="btn btn-light border"
            >
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>
                Create Employee
            </button>

        </div>

    </form>

    <script>
        (() => {
            const branch = document.getElementById('employee-branch');
            const department = document.getElementById('employee-department');
            if (!branch || !department) return;

            const filterDepartments = () => {
                const branchId = branch.value;
                const hasSelectedBranch = branchId !== '';
                let selectedAvailable = false;
                Array.from(department.options).forEach((option) => {
                    if (!option.value) return;
                    const available = !hasSelectedBranch
                        || option.dataset.branchId === branchId;
                    option.hidden = !available;
                    option.disabled = !available;
                    if (available && option.selected) selectedAvailable = true;
                });
                if (!selectedAvailable) department.value = '';
            };

            branch.addEventListener('change', filterDepartments);
            filterDepartments();
        })();
    </script>

</div>

<?php ob_start(); ?>

<?php if (!empty($error)): ?>

    <div
        class="modal fade"
        id="employeeCreateFlashModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-0 bg-danger-subtle">
                    <h5 class="modal-title text-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Action failed
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                </div>

                <div class="modal-footer border-0">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById(
                'employeeCreateFlashModal'
            );

            if (modalElement) {
                new bootstrap.Modal(modalElement).show();
            }
        });
    </script>

<?php endif; ?>

<?php
$errorModal = ob_get_clean();

$content = ob_get_clean();
$content .= $errorModal;

$pageTitle = 'Create Employee';

require __DIR__ . '/../layout/app.php';