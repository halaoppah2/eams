<?php

$user = $user ?? $_SESSION['user'] ?? [];

$firstName = $user['first_name'] ?? 'User';

ob_start();

?>

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="mb-4">

        <h2 class="fw-semibold mb-1">
            Dashboard
        </h2>

        <p class="text-muted mb-0">
            Welcome back,
            <?= htmlspecialchars(
                $firstName,
                ENT_QUOTES,
                'UTF-8'
            ) ?>.
        </p>

    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">

        <!-- Employees -->
        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">
                                Employees
                            </p>

                            <h3 class="fw-bold mb-0">
                                <?= $employeeCount ?? 0 ?>
                            </h3>
                        </div>

                        <div
                            class="rounded-circle bg-primary-subtle
                                   text-primary d-flex
                                   align-items-center
                                   justify-content-center"
                            style="width: 48px; height: 48px;"
                            >
                            <i class="bi bi-people fs-4"></i>
                        </div>
                    </div>

                </div>

            </div>

        </div>


        <!-- Present Today -->
        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Present Today
                            </p>

                            <h3 class="fw-bold mb-0">
                                <?= $presentToday ?? 0 ?>
                            </h3>

                        </div>

                        <div
                            class="rounded-circle bg-success-subtle
                                   text-success d-flex
                                   align-items-center
                                   justify-content-center"
                            style="width: 48px; height: 48px;"
                        >
                            <i class="bi bi-person-check fs-4"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- On Leave -->
        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                On Leave
                            </p>

                            <h3 class="fw-bold mb-0">
                                <?= $onLeaveToday ?? 0 ?>
                            </h3>

                        </div>

                        <div
                            class="rounded-circle bg-warning-subtle
                                   text-warning d-flex
                                   align-items-center
                                   justify-content-center"
                            style="width: 48px; height: 48px;"
                        >
                            <i class="bi bi-calendar-minus fs-4"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Notifications -->
        <div class="col-sm-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Notifications
                            </p>

                            <h3 class="fw-bold mb-0">
                                <?= $notificationCount ?? 0 ?>
                            </h3>

                        </div>

                        <div
                            class="rounded-circle bg-info-subtle
                                   text-info d-flex
                                   align-items-center
                                   justify-content-center"
                            style="width: 48px; height: 48px;"
                        >
                            <i class="bi bi-bell fs-4"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Main Dashboard Area -->
    <div class="row g-4">

    <!-- Attendance Overview -->
       
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">
                        Attendance Trends
                    </h5>
                    <p class="text-muted small mb-0">
                        Daily attendance over the last 7 days
                    </p>
                </div>
            </div>

            <div style="height: 320px; position: relative;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>

            <?php if (empty($attendanceTrend)): ?>
                <p class="text-muted small mt-2 mb-0">
                    No attendance records are available for this period yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">

                <h5 class="fw-semibold mb-4">
                    Quick Actions
                </h5>

            <div class="d-grid gap-2">

                <?php
                    $permissions =
                    $_SESSION['permissions'] ?? [];
                    ?>

                    <?php if (
                        in_array(
                            'employee.create',
                            $permissions,
                            true
                            )
                        ): ?>

                        <a
                            href="<?= APP_BASE_PATH ?>/employees/create"
                            class="btn btn-primary"
                            >
                            <i class="bi bi-person-plus me-2"></i>
                                Add Employee
                            </a>
                    <?php endif; ?>

                    <?php if (
                        in_array(
                            'leave.view',
                            $permissions,
                            true
                            )): 
                        ?>

                        <a
                            href="<?= APP_BASE_PATH ?>/leave/create"
                            class="btn btn-outline-primary"
                            >
                            <i class="bi bi-calendar-plus me-2"></i>
                                Request Leave
                        </a>

                    <?php endif; ?>

                    <a
                        href="<?= APP_BASE_PATH ?>/notifications"
                        class="btn btn-outline-secondary"
                        >
                        <i class="bi bi-bell me-2"></i>
                            View Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const trendData = <?= json_encode(
    $attendanceTrend ?? [],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const canvas = document.getElementById('attendanceTrendChart');

    if (canvas) {
        const labels = trendData.map(row => row.attendance_date);
        const presentData = trendData.map(row => Number(row.present_count));
        const lateData = trendData.map(row => Number(row.late_count));

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.12)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Late',
                        data: lateData,
                        borderColor: '#fd7e14',
                        backgroundColor: 'rgba(253, 126, 20, 0.08)',
                        tension: 0.3,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
</script>

<?php

$content = ob_get_clean();

$pageTitle = 'Dashboard';

require __DIR__ . '/../layout/app.php';
