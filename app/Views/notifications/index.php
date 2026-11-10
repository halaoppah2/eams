<?php

$notifications = $notifications ?? [];
$unreadCount = (int) ($unreadCount ?? 0);
$permissions = $_SESSION['permissions'] ?? [];
$canCreateNotifications = in_array(
    'notification.manage',
    $permissions,
    true
);

ob_start();
?>

<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Notifications</h2>
        <p class="text-muted mb-0">
            Stay updated with important activities and events.
        </p>
    </div>

    <?php if ($canCreateNotifications): ?>
        <div>
            <a href="<?= APP_BASE_PATH ?>/notifications/create"
            class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                Create Notification
            </a>
        </div>
    <?php endif; ?>


    <?php if ($unreadCount > 0): ?>
        <form method="POST" action="<?= APP_BASE_PATH ?>/notifications/read-all">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-check2-all me-1"></i>
                Mark all as read
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($unreadCount > 0): ?>
    <div class="container-fluid alert alert-primary d-flex align-items-center mb-4">
        <i class="bi bi-bell-fill me-2"></i>
        <div>
            You have <strong><?= $unreadCount ?></strong>
            unread notification<?= $unreadCount === 1 ? '' : 's' ?>.
        </div>
    </div>
<?php endif; ?>

<?php if (empty($notifications)): ?>
    <div class="container card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-bell-slash fs-1 text-muted"></i>
            <h5 class="mt-3">No notifications</h5>
            <p class="text-muted mb-0">
                You currently have no notifications.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="container-fluid card shadow-sm">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $isUnread = empty($notification['read_at']);
                $notificationId = (int) $notification['id'];

                $typeName = $notification['type_name']
                    ?? $notification['type_code']
                    ?? 'Notification';

                $title = $notification['title'] ?? 'Notification';
                $message = $notification['message'] ?? '';
                $createdAt = $notification['created_at'] ?? '';
                ?>

                <a
                    href="<?= APP_BASE_PATH ?>/notifications/<?= $notificationId ?>"
                    class="list-group-item list-group-item-action p-3 <?= $isUnread ? 'bg-light' : '' ?>"
                >
                    <div class="d-flex align-items-start">
                        <div class="me-3 flex-shrink-0">
                            <div
                                class="rounded-circle d-flex align-items-center justify-content-center <?= $isUnread ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary' ?>"
                                style="width: 42px; height: 42px;"
                            >
                                <i class="bi bi-bell"></i>
                            </div>
                        </div>

                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <h6 class="mb-1 <?= $isUnread ? 'fw-bold' : 'fw-semibold' ?>">
                                        <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                    </h6>

                                    <span class="badge <?= $isUnread ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                                        <?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <small class="text-muted">
                                    <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </div>

                            <p class="mb-0 mt-2 text-muted">
                                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <?php if ($isUnread): ?>
                            <div class="ms-3 pt-2 flex-shrink-0">
                                <span
                                    class="d-inline-block rounded-circle bg-primary"
                                    style="width: 9px; height: 9px;"
                                    title="Unread"
                                ></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php

$content = ob_get_clean();
$pageTitle = 'Notifications';

require __DIR__ . '/../layout/app.php';