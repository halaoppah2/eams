<?php

/** @var array{name: string, registration_no: ?string, email: string, phone: ?string, address: ?string, logo: ?string, timezone: string, status_code: string} $organization */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$logoFilename = is_string($organization['logo'] ?? null)
    ? basename($organization['logo'])
    : '';
$logoUrl = preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $logoFilename)
    ? APP_BASE_PATH . '/uploads/organization-logos/' . $logoFilename
    : null;

ob_start();
?>

<div class="container-fluid py-4">
    <h2 class="fw-semibold mb-1">
        Organization Account
    </h2>
    <p class="text-muted mb-4">
        Manage the logo and account access for <?= $escape($organization['name']) ?>.
    </p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Organization details
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_BASE_PATH ?>/settings/organization/details">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="organization-name" class="form-label">Organization name</label>
                        <input
                            id="organization-name"
                            name="name"
                            class="form-control"
                            maxlength="150"
                            value="<?= $escape($organization['name']) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="organization-email" class="form-label">Email</label>
                        <input
                            id="organization-email"
                            name="email"
                            type="email"
                            class="form-control"
                            maxlength="150"
                            value="<?= $escape($organization['email']) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="registration-no" class="form-label">Registration number</label>
                        <input
                            id="registration-no"
                            class="form-control"
                            value="<?= $escape($organization['registration_no']) ?>"
                            readonly
                            aria-describedby="registration-no-help"
                        >
                        <div id="registration-no-help" class="form-text">
                            This will be generated automatically when payment setup is implemented.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="organization-phone" class="form-label">Phone</label>
                        <input
                            id="organization-phone"
                            name="phone"
                            type="tel"
                            class="form-control"
                            maxlength="30"
                            value="<?= $escape($organization['phone']) ?>"
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="organization-timezone" class="form-label">Timezone</label>
                        <input
                            id="organization-timezone"
                            name="timezone"
                            class="form-control"
                            maxlength="50"
                            value="<?= $escape($organization['timezone']) ?>"
                            required
                        >
                        <div class="form-text">Use a timezone such as Africa/Accra or UTC.</div>
                    </div>
                    <div class="col-12">
                        <label for="organization-address" class="form-label">Address</label>
                        <textarea
                            id="organization-address"
                            name="address"
                            class="form-control"
                            rows="3"
                        ><?= $escape($organization['address']) ?></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i>Save details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Organization logo and favicon
        </div>
        <div class="card-body">
            <p class="text-muted">
                Upload your organization's logo to use it as the favicon shown in browser tabs.
            </p>
            <?php if ($logoUrl !== null): ?>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <img
                        src="<?= $escape($logoUrl) ?>"
                        alt="<?= $escape($organization['name']) ?> logo"
                        style="width: 64px; height: 64px; object-fit: contain;"
                    >
                    <span>Current organization logo</span>
                    <form
                        method="POST"
                        action="<?= APP_BASE_PATH ?>/settings/organization/logo/delete"
                        onsubmit="return confirm('Remove the organization logo and favicon?');"
                    >
                        <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-danger btn-sm" type="submit">
                            <i class="bi bi-trash me-1"></i>Remove logo
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/settings/organization/logo"
                enctype="multipart/form-data"
            >
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <label for="organization-logo" class="form-label">
                    Choose logo
                </label>
                <div class="d-flex flex-wrap gap-2">
                    <input
                        type="file"
                        id="organization-logo"
                        name="logo"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        style="max-width: 15rem;"
                    >
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bi bi-upload me-1"></i>
                        Upload logo
                    </button>
                </div>
                <div class="form-text">JPEG, PNG, or WebP; maximum file size 5 MB.</div>
            </form>
        </div>
    </div>

    <div class="card border-danger">
        <div class="card-header bg-danger-subtle text-danger fw-semibold">
            Deactivate organization
        </div>
        <div class="card-body">
            <p>
                Deactivation disables sign-in for this organization but retains its employees, attendance, leave, and audit history. A system administrator can reactivate it. You will be signed out immediately.
            </p>
            <?php if ($organization['status_code'] === 'INACTIVE'): ?>
                <div class="alert alert-warning mb-0">
                    This organization is already inactive.
                </div>
            <?php else: ?>
                <form method="POST" action="<?= APP_BASE_PATH ?>/settings/organization/deactivate"
                      onsubmit="return confirm('This will disable all organization accounts. Continue?');">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                    <label class="form-label" for="confirmation-name">
                        Type <strong><?= $escape($organization['name']) ?></strong> to confirm
                    </label>
                    <input id="confirmation-name" name="confirmation_name" class="form-control mb-3"
                           autocomplete="off" required>
                    <button class="btn btn-danger" type="submit">
                        Deactivate organization
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-danger mt-4">
        <div class="card-header bg-danger text-white fw-semibold">
            Permanently delete organization
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold">
                This permanently deletes the organization, user accounts, employees,
                attendance, leave, notifications, and other associated data. This
                cannot be undone.
            </p>
            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/settings/organization/delete"
                onsubmit="return confirm('This permanently deletes the organization and all its data. Continue?');"
            >
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <label class="form-label" for="delete-confirmation-name">
                    Type <strong><?= $escape($organization['name']) ?></strong> to confirm
                </label>
                <input
                    id="delete-confirmation-name"
                    name="confirmation_name"
                    class="form-control mb-3"
                    autocomplete="off"
                    required
                    style="max-width: 32rem;"
                >
                <button class="btn btn-danger" type="submit">
                    Permanently delete organization
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Organization Account';
require __DIR__ . '/../layout/app.php';
