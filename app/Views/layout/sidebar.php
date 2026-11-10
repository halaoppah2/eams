<?php

use App\Services\AuditAccessService;

$user = $_SESSION['user'] ?? [];

$permissions = $_SESSION['permissions'] ?? [];

$hasPermission = function (string $permission) use ($permissions): bool {
    return in_array(
        $permission,
        $permissions,
        true
    );
};

$firstName = $user['first_name'] ?? 'User';
$roles = $_SESSION['roles'] ?? [];
$isOrganizationOwner = false;
foreach ($roles as $role) {
    if (strtolower(trim((string) ($role['name'] ?? ''))) === 'organization owner') {
        $isOrganizationOwner = true;
        break;
    }
}

?>

<aside
    class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark"
    style="width: 260px; min-height: 100%;"
>

    <!-- Brand -->
    <a
        href="<?= APP_BASE_PATH ?>/dashboard"
        class="d-flex align-items-center mb-4 text-white text-decoration-none"
    >

        <i class="bi bi-calendar-check fs-3 me-2"></i>

        <span class="fs-4 fw-semibold">
            AMS
        </span>

    </a>

    <!-- User -->
    <div class="border-bottom border-secondary pb-3 mb-3">

        <div class="small text-secondary">
            Signed in as
        </div>

        <div class="fw-semibold">

            <?= htmlspecialchars(
                $firstName,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    </div>

    <!-- Navigation -->
    <ul class="nav flex-column mb-auto">

        <!-- Dashboard -->
        <li class="nav-item">

            <a
                href="<?= APP_BASE_PATH ?>/dashboard"
                class="nav-link text-white"
            >
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
            </a>

        </li>

        <!-- Employees -->
        <?php if ($hasPermission('employee.view')): ?>

            <li>

                <a
                    href="<?= APP_BASE_PATH ?>/employees"
                    class="nav-link text-white"
                >
                    <i class="bi bi-people me-2"></i>
                    Employees
                </a>

            </li>

        <?php endif; ?>

        <!-- Attendance -->
        <?php
        if (
            $hasPermission('attendance.view') ||
            $hasPermission('attendance.view_own') ||
            $hasPermission('attendance.clock_in') ||
            $hasPermission('attendance.clock_out')
        ):
            ?>

            <li>
                <a
                    href="<?= APP_BASE_PATH ?>/attendance"
                    class="nav-link text-white"
                    >
                        <i class="bi bi-calendar-check me-2"></i>
                        Attendance
                </a>
            </li>

        <?php endif; ?>

        <?php if ($hasPermission('holiday.view')): ?>
            <li>
                <a href="<?= APP_BASE_PATH ?>/holidays" class="nav-link text-white">
                    <i class="bi bi-calendar2-heart me-2"></i>
                    Holidays
                </a>
            </li>
        <?php endif; ?>

        <?php if (
            $hasPermission('attendance.correction.view')
            || $hasPermission('attendance.correction.request')
        ): ?>
            <li>
                <a href="<?= APP_BASE_PATH ?>/attendance/corrections" class="nav-link text-white">
                    <i class="bi bi-pencil-square me-2"></i>
                    Attendance Corrections
                </a>
            </li>
        <?php endif; ?>

        <!-- Leave -->
        <?php
            if (
            $hasPermission('leave.view') ||
            $hasPermission('leave.view_own') ||
            $hasPermission('leave.request')
            ):
            ?>

            <li>
                <a
                    href="<?= APP_BASE_PATH ?>/leave"
                    class="nav-link text-white"
                >
                    <i class="bi bi-calendar-minus me-2"></i>
                    Leave
                </a>
            </li>

        <?php endif; ?>

        <!-- Shifts -->
        <?php if (
            $hasPermission('shift.view')
            || $hasPermission('shift.assign')
        ): ?>

            <li>

                <a
                    href="<?= APP_BASE_PATH ?>/shifts"
                    class="nav-link text-white"
                >
                    <i class="bi bi-clock me-2"></i>
                    Shifts
                </a>

            </li>

        <?php endif; ?>

        <!-- Notifications -->
       <?php if ($hasPermission('notification.view')): ?>

            <li>
                <a
                    href="<?= APP_BASE_PATH ?>/notifications"
                    class="nav-link text-white"
                >
                    <i class="bi bi-bell me-2"></i>
                    Notifications
                </a>
            </li>

        <?php endif; ?>

        <!-- Administration -->
        <?php if (!empty($user['is_system_admin'])): ?>
            <li>
                <a
                    href="<?= APP_BASE_PATH ?>/system/organizations"
                    class="nav-link text-white"
                >
                    <i class="bi bi-building-gear me-2"></i>
                    Organizations
                </a>
            </li>
        <?php endif; ?>

        <?php
        $showAdministration =
            $hasPermission('user.view') ||
            $hasPermission('role.view') ||
            $hasPermission('permission.view') ||
            AuditAccessService::canView() ||
            $hasPermission('employee.update') ||
            $isOrganizationOwner;
        ?>

        <?php if ($showAdministration): ?>

            <li class="mt-3 mb-1">

                <span class="px-3 text-secondary small text-uppercase">
                    Administration
                </span>

            </li>

            <?php if ($hasPermission('user.view')): ?>

                <li>

                    <a
                        href="<?= APP_BASE_PATH ?>/users"
                        class="nav-link text-white"
                    >
                        <i class="bi bi-person-gear me-2"></i>
                        Users
                    </a>

                </li>

            <?php endif; ?>

            <?php if ($hasPermission('role.view')): ?>
                <li>
                    <a href="<?= APP_BASE_PATH ?>/roles" class="nav-link text-white">
                        <i class="bi bi-shield-lock me-2"></i>
                        Roles & Permissions
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($hasPermission('employee.update')): ?>

                <li>
                    <a
                        href="<?= APP_BASE_PATH ?>/settings/organization-structure"
                        class="nav-link text-white"
                    >
                        <i class="bi bi-diagram-3 me-2"></i>
                        Organization Structure
                    </a>
                </li>

            <?php endif; ?>

            <?php if ($isOrganizationOwner): ?>
                <li>
                    <a href="<?= APP_BASE_PATH ?>/settings/organization" class="nav-link text-white">
                        <i class="bi bi-building me-2"></i>
                        Organization Account
                    </a>
                </li>
            <?php endif; ?>

            <?php if (AuditAccessService::canView()): ?>

                <li>
                    <a
                        href="<?= APP_BASE_PATH ?>/audit"
                        class="nav-link text-white"
                    >
                        <i class="bi bi-journal-text me-2"></i>
                        Audit Logs
                    </a>
                </li>

            <?php endif; ?>

        <?php endif; ?>

        <!-- Reports -->
        <?php if ($hasPermission('report.view')): ?>

            <li>

                <a
                    href="<?= APP_BASE_PATH ?>/reports"
                    class="nav-link text-white"
                >
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Attendance Reports
                </a>

            </li>

        <?php endif; ?>

        <!-- Recycle Bin -->
        <?php if ($hasPermission('employee.restore')): ?>
            <li class="mb-3">

                <a
                    href="<?= APP_BASE_PATH ?>/employees/recycle-bin"
                    class="nav-link text-white"
                >
                    <i class="bi bi-trash me-2"></i>
                    Recycle Bin
                </a>

            </li>
        <?php endif; ?>

    </ul>

    <!-- Logout -->
    <div class="border-top border-secondary pt-3">

        <a
            href="<?= APP_BASE_PATH ?>/logout"
            class="nav-link text-white"
        >
            <i class="bi bi-box-arrow-right me-2"></i>
            Logout
        </a>

    </div>

</aside>