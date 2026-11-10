<?php

$user = $_SESSION['user'] ?? [];

$firstName = $user['first_name'] ?? 'User';
$lastName  = $user['last_name'] ?? '';
$fullName = trim( $firstName . ' ' . $lastName );
$navbarUnreadCount = (int) ($navbarUnreadCount ?? 0);

$currentPath = $_SERVER['REQUEST_URI'] ?? APP_BASE_PATH . '/dashboard';
$profileHref = APP_BASE_PATH . '/profile?return_to='
    . rawurlencode($currentPath);
$profilePicture = $user['profile_picture'] ?? null;
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

?>

<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">

    <div class="container-fluid">

        <!-- Mobile Sidebar Toggle -->
        <button
            class="btn btn-outline-secondary d-lg-none me-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileSidebar"
            aria-controls="mobileSidebar"
        >

            <i class="bi bi-list fs-5"></i>

        </button>

        <!-- Application Name -->
        <div>

            <span class="text-muted small ms-2 d-none d-md-inline">
                Attendance Management System
            </span>

        </div>

        <!-- Right Side -->
        <div class="d-flex align-items-center ms-auto">

            <!-- Notifications -->
            <a
                href="<?= APP_BASE_PATH ?>/notifications"
                class="btn btn-light position-relative me-2"
                title="Notifications"
                aria-label="Notifications<?= $navbarUnreadCount > 0
                    ? ', ' . $navbarUnreadCount . ' unread'
                    : '' ?>"
            >

                <i class="bi bi-bell fs-5"></i>
                <?php if ($navbarUnreadCount > 0): ?>
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        aria-hidden="true"
                    >
                        <?= $navbarUnreadCount > 99 ? '99+' : $navbarUnreadCount ?>
                    </span>
                <?php endif; ?>

            </a>

            <!-- User Menu -->
            <div class="dropdown">

                <button
                    class="btn btn-light dropdown-toggle d-flex align-items-center"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    >

                    <?php if ($profilePictureUrl !== null): ?>
                        <img
                            src="<?= htmlspecialchars(
                                $profilePictureUrl,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            alt=""
                            class="rounded-circle me-2"
                            style="width: 32px; height: 32px; object-fit: cover;"
                        >

                        <?php else: ?>

                        <i class="bi bi-person-circle fs-5 me-2"></i>
                        
                    <?php endif; ?>

                    <span class="d-none d-md-inline">
                        <?= htmlspecialchars(
                            $fullName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                    <li>

                        <h6 class="dropdown-header">

                            <?= htmlspecialchars(
                                $user['username'] ?? 'User',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h6>

                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>

                        <a
                            class="dropdown-item"
                            href="<?= htmlspecialchars(
                                $profileHref,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                            <i class="bi bi-person me-2"></i>

                            My Profile

                        </a>

                    </li>

                    <li>

                        <a
                            class="dropdown-item text-danger"
                            href="<?= APP_BASE_PATH ?>/logout"
                        >

                            <i class="bi bi-box-arrow-right me-2"></i>

                            Logout

                        </a>

                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>