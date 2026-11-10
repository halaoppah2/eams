<?php

    $pageTitle = $pageTitle ?? 'EAMS';

    $content = $content ?? '';
    $navbarUnreadCount = 0;

    if (\App\Services\PermissionService::has('notification.view')) {
        $navbarUnreadCount = (new \App\Services\NotificationService(
            new \App\Repositories\NotificationRepository()
        ))->countUnreadForCurrentUser();
    }

    $organizationLogo = $_SESSION['user']['organization_logo'] ?? null;
    $organizationLogoFilename = is_string($organizationLogo)
        ? basename($organizationLogo)
        : '';
    $organizationLogoExtension = pathinfo(
        $organizationLogoFilename,
        PATHINFO_EXTENSION
    );
    $organizationLogoMimeTypes = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $organizationFavicon = preg_match(
        '/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/',
        $organizationLogoFilename
    )
        ? APP_BASE_PATH . '/uploads/organization-logos/'
            . rawurlencode($organizationLogoFilename)
        : null;

    $flashMessage = $_SESSION['flash_message'] ?? $_SESSION['flash'] ?? null;

    unset($_SESSION['flash_message'], $_SESSION['flash']);

    if (is_array($flashMessage)) {
        $flashType = strtolower((string) ($flashMessage['type'] ?? 'info'));
        $flashTitle = (string) ($flashMessage['title'] ?? 'Notice');
        $flashText = (string) ($flashMessage['message'] ?? '');
    } else {
        $flashType = '';
        $flashTitle = '';
        $flashText = '';
    }

    $flashIsError = in_array($flashType, ['error', 'danger'], true);

    $flashModalClass = $flashIsError
        ? 'bg-danger-subtle text-danger'
        : 'bg-primary-subtle text-primary';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
        - AMS
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <?php if ($organizationFavicon !== null): ?>
        <link
            rel="icon"
            type="<?= htmlspecialchars(
                $organizationLogoMimeTypes[$organizationLogoExtension],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            href="<?= htmlspecialchars($organizationFavicon, ENT_QUOTES, 'UTF-8') ?>"
        >
    <?php endif; ?>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= APP_BASE_PATH ?>/assets/css/app.css"
    >

</head>

<body>

    <div class="d-flex min-vh-100">

        <!-- Desktop Sidebar -->
        <div class="d-none d-lg-block">

            <?php require __DIR__ . '/sidebar.php'; ?>

        </div>

        <!-- Mobile Sidebar -->
        <div
            class="offcanvas offcanvas-start text-bg-dark"
            tabindex="-1"
            id="mobileSidebar"
            aria-labelledby="mobileSidebarLabel"
            style="width: 260px;"
            >

            <div class="offcanvas-header">

                <h5
                    class="offcanvas-title"
                    id="mobileSidebarLabel"
                >
                    AMS
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="offcanvas"
                    aria-label="Close"
                ></button>

            </div>

            <div class="offcanvas-body p-0">

                <?php require __DIR__ . '/sidebar.php'; ?>

            </div>

        </div>

        <!-- Main Application Area -->
        <div class="flex-grow-1 min-w-0 d-flex flex-column">

            <!-- Navbar -->
            <?php require __DIR__ . '/navbar.php'; ?>

            <!-- Page Content -->
            <main>

                <?= $content ?>

            </main>

            <!-- footer -->
        <?php require __DIR__ . '/footer.php'; ?>

        </div>

    </div>

    <?php if ($flashText !== ''): ?>
        <div class="container-fluid pt-3" id="flashMessageFallback">
            <div
                class="alert <?= $flashIsError ? 'alert-danger' : 'alert-primary' ?>"
                role="<?= $flashIsError ? 'alert' : 'status' ?>"
            >
                <strong><?= htmlspecialchars($flashTitle, ENT_QUOTES, 'UTF-8') ?></strong>
                <?= htmlspecialchars($flashText, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div
            class="modal fade"
            id="flashMessageModal"
            tabindex="-1"
            aria-labelledby="flashMessageTitle"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 <?= $flashModalClass ?>">

                        <h2 class="modal-title h5 mb-0" id="flashMessageTitle">
                            <?= htmlspecialchars($flashTitle, ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            >
                        </button>
                    </div>

                    <div class="modal-body" role="<?= $flashIsError ? 'alert' : 'status' ?>">
                        <?= htmlspecialchars($flashText, ENT_QUOTES, 'UTF-8') ?>
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
    <?php endif; ?>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <?php if ($flashText !== ''): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('flashMessageModal');
                if (modal && window.bootstrap) {
                    const fallback = document.getElementById('flashMessageFallback');
                    if (fallback) {
                        fallback.hidden = true;
                    }
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    <?php endif; ?>

    <script>
        (function () {
            const idleLimit = 30 * 60 * 1000;
            const heartbeatInterval = 60 * 1000;
            let lastActivity = Date.now();
            let lastHeartbeat = Date.now();
            let idleTimer;

            function resetIdleTimer() {
                lastActivity = Date.now();
                window.clearTimeout(idleTimer);
                idleTimer = window.setTimeout(function () {
                    window.location.href = '<?= APP_BASE_PATH ?>/logout?expired=1';
                }, idleLimit);

                if (lastActivity - lastHeartbeat >= heartbeatInterval) {
                    lastHeartbeat = lastActivity;
                    fetch('<?= APP_BASE_PATH ?>/session/activity', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    }).then(function (response) {
                        if (response.redirected) {
                            window.location.href = '<?= APP_BASE_PATH ?>/?expired=1';
                        }
                    }).catch(function (error) {
                        console.warn('Unable to refresh the authenticated session.', error);
                    });
                }
            }

            ['click', 'keydown', 'pointerdown', 'pointermove', 'scroll', 'touchstart']
                .forEach(function (eventName) {
                    document.addEventListener(eventName, resetIdleTimer, {passive: true});
                });

            resetIdleTimer();
        })();
    </script>

</body>

</html>