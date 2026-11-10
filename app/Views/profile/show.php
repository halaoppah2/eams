<?php

$profile = $profile ?? [];
$returnTo = $returnTo ?? APP_BASE_PATH . '/dashboard';
$profilePictureUrl = $profilePictureUrl ?? null;

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? 'N/A'),
        ENT_QUOTES,
        'UTF-8'
    );
};

$fullName = trim(
    ($profile['first_name'] ?? '') . ' '
    . ($profile['middle_name'] ?? '') . ' '
    . ($profile['last_name'] ?? '')
);

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">My Profile</h2>
            <p class="text-muted mb-0">
                View your employee and account information.
            </p>
        </div>
        <a
            href="<?= $escape($returnTo) ?>"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <?php if ($profilePictureUrl !== null): ?>
                    <img
                        src="<?= $escape($profilePictureUrl) ?>"
                        alt="Profile picture"
                        class="rounded-circle border"
                        style="width: 104px; height: 104px; object-fit: cover;"
                    >
                <?php else: ?>
                    <div
                        class="rounded-circle bg-primary-subtle text-primary
                               d-flex align-items-center justify-content-center"
                        style="width: 104px; height: 104px; font-size: 40px;"
                        aria-label="No profile picture"
                    >
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>

                <div class="flex-grow-1">
                    <h4 class="mb-1">
                        <?= $escape($fullName !== '' ? $fullName : 'Employee') ?>
                    </h4>
                    <div class="text-muted">
                        <?= $escape($profile['position_name'] ?? null) ?>
                    </div>
                    <div class="small text-muted mt-1">
                        Employee No:
                        <?= $escape($profile['employee_number'] ?? null) ?>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/profile/picture"
                enctype="multipart/form-data"
                class="d-flex flex-nowrap align-items-end gap-3"
            >
                <input
                    type="hidden"
                    name="return_to"
                    value="<?= $escape($returnTo) ?>"
                >
                <div class="flex-grow-1" style="min-width: 0;">
                    <label for="profile_picture" class="form-label">
                        Profile picture
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <input
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            class="form-control w-50"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-upload me-1"></i>Upload picture
                        </button>
                    </div>
                    <div class="form-text">
                        JPEG, PNG, or WebP; maximum file size 5 MB.
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Personal and contact details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">First name</dt>
                        <dd class="col-sm-7"><?= $escape($profile['first_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Middle name</dt>
                        <dd class="col-sm-7"><?= $escape($profile['middle_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Last name</dt>
                        <dd class="col-sm-7"><?= $escape($profile['last_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Date of birth</dt>
                        <dd class="col-sm-7"><?= $escape($profile['date_of_birth'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Employee email</dt>
                        <dd class="col-sm-7 text-break"><?= $escape($profile['email'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Phone</dt>
                        <dd class="col-sm-7"><?= $escape($profile['phone'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Address</dt>
                        <dd class="col-sm-7"><?= $escape($profile['address'] ?? null) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Employment and account details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">Username</dt>
                        <dd class="col-sm-7"><?= $escape($profile['username'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Account email</dt>
                        <dd class="col-sm-7 text-break"><?= $escape($profile['account_email'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Branch</dt>
                        <dd class="col-sm-7"><?= $escape($profile['branch_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Department</dt>
                        <dd class="col-sm-7"><?= $escape($profile['department_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Position</dt>
                        <dd class="col-sm-7"><?= $escape($profile['position_name'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Employment type</dt>
                        <dd class="col-sm-7"><?= $escape($profile['employment_type'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Hire date</dt>
                        <dd class="col-sm-7"><?= $escape($profile['hire_date'] ?? null) ?></dd>
                        <dt class="col-sm-5 text-muted">Employment status</dt>
                        <dd class="col-sm-7"><?= $escape($profile['employee_status'] ?? null) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'My Profile';
require __DIR__ . '/../layout/app.php';
?>
