
<?php

$notification = $notification ?? [];

ob_start();

$notificationId = (int) ($notification['id'] ?? 0);
$title = $notification['title'] ?? 'Notification Details';
$typeName = $notification['type_name']
    ?? $notification['type_code']
    ?? 'Notification';
$message = $notification['message'] ?? '';
$typeCode = $notification['type_code'] ?? 'N/A';
$createdAt = $notification['created_at'] ?? 'N/A';

?>

<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Notification Details</h2>
        <p class="text-muted mb-0">
            View the details of this notification.
        </p>
    </div>

    <a href="<?= APP_BASE_PATH ?>/notifications" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Back to Notifications
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">
                <i class="bi bi-bell me-2 text-primary"></i>
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
            </h5>

            <span class="badge text-bg-success">Read</span>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="mb-4">
            <span class="badge text-bg-primary mb-2">
                <?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <div class="text-muted small">
                Received:
                <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="border rounded p-3 bg-light mb-4">
            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <h6 class="fw-bold mb-3">Notification Information</h6>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width: 35%;">Notification ID</th>
                        <td><?= $notificationId ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Type Code</th>
                        <td><?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Date Created</th>
                        <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Status</th>
                        <td><span class="badge text-bg-success">Read</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white text-end py-3">
        <a href="<?= APP_BASE_PATH ?>/notifications" class="btn btn-primary">
            <i class="bi bi-list-ul me-1"></i>
            All Notifications
        </a>
    </div>
</div>

<?php

$content = ob_get_clean();
$pageTitle = 'Notification Details';

require __DIR__ . '/../layout/app.php';
