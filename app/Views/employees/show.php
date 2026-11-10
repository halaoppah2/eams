<?php

$employee = $employee ?? [];

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? 'N/A'),
        ENT_QUOTES,
        'UTF-8'
    );
};

$fullName = trim(
    ($employee['first_name'] ?? '') . ' ' .
    ($employee['middle_name'] ?? '') . ' ' .
    ($employee['last_name'] ?? '')
);

$profilePicture = $employee['profile_picture'] ?? null;
$profilePictureFilename = is_string($profilePicture)
    ? basename($profilePicture)
    : '';
$profilePicturePrefix = APP_BASE_PATH . '/uploads/profile-pictures/';
$profilePictureUrl = preg_match(
    '/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/',
    $profilePictureFilename
)
    ? $profilePicturePrefix . $profilePictureFilename
    : null;

ob_start();
?>

<div class="container-fluid py-3">

    <!-- Page heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <?php if ($profilePictureUrl !== null): ?>
                <img
                    src="<?= $escape($profilePictureUrl) ?>"
                    alt=""
                    class="rounded-circle border flex-shrink-0"
                    style="width: 56px; height: 56px; object-fit: cover;"
                >
            <?php else: ?>
                <div
                    class="rounded-circle bg-primary-subtle text-primary
                           d-flex align-items-center justify-content-center
                           flex-shrink-0"
                    style="width: 56px; height: 56px; font-size: 24px;"
                    aria-hidden="true"
                >
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>

            <div>
            <h4 class="mb-1">
                Employee Profile
            </h4>
            <p class="text-muted mb-0">
                View employee information and employment details.
            </p>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= APP_BASE_PATH ?>/employees"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Employees
            </a>

            <?php if (
                !empty($employee['id']) &&
                function_exists('can') &&
                can('employee.update')
            ): ?>
                <a href="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>/edit"
                   class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i>Edit Employee
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Employee summary -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">

                <div class="min-w-0">
                    <h5 class="mb-1">
                        <?= $escape($fullName !== '' ? $fullName : 'Employee') ?>
                    </h5>

                    <div class="text-muted small">
                        Employee No:
                        <?= $escape($employee['employee_number'] ?? null) ?>
                    </div>

                    <span class="badge bg-success-subtle text-success mt-2">
                        <?= $escape($employee['employee_status'] ?? null) ?>
                    </span>
                </div>

            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="row g-4">

        <!-- Personal information -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>
                        Personal Information
                    </h6>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block mb-1">First Name</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['first_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block mb-1">Middle Name</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['middle_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block mb-1">Last Name</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['last_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block mb-1">Date of Birth</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['date_of_birth'] ?? null) ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Contact information -->
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-telephone me-2 text-primary"></i>
                        Contact Information
                    </h6>
                </div>

                <div class="card-body">

                    <div class="mb-4">
                        <small class="text-muted d-block mb-1">Email Address</small>
                        <div class="fw-semibold text-break">
                            <?= $escape($employee['email'] ?? null) ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <small class="text-muted d-block mb-1">Phone Number</small>
                        <div class="fw-semibold">
                            <?= $escape($employee['phone'] ?? null) ?>
                        </div>
                    </div>

                    <div>
                        <small class="text-muted d-block mb-1">Address</small>
                        <div class="fw-semibold">
                            <?= $escape($employee['address'] ?? null) ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Employment information -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-briefcase me-2 text-primary"></i>
                        Employment Information
                    </h6>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Branch</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['branch_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Department</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['department_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Position</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['position_name'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Employment Type</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['employment_type'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Hire Date</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['hire_date'] ?? null) ?>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-4">
                            <small class="text-muted d-block mb-1">Employment Status</small>
                            <div class="fw-semibold">
                                <?= $escape($employee['employee_status'] ?? null) ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();

$pageTitle = 'Employee Profile';
require __DIR__ . '/../layout/app.php';
?>