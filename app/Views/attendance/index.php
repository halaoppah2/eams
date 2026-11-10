<?php

$attendance = $attendance ?? null;
$shift = $shift ?? null;
$error = $error ?? null;
$canRequestCorrection = $canRequestCorrection
    ?? \App\Services\PermissionService::has('attendance.correction.request');
$canViewCorrectionRequests = $canViewCorrectionRequests
    ?? \App\Services\PermissionService::has('attendance.correction.view');

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? '—'),
        ENT_QUOTES,
        'UTF-8'
    );
};

$statusNames = [
    1 => 'Present',
    2 => 'Late',
    3 => 'Absent',
    4 => 'Early Departure',
    5 => 'Incomplete',
];

$statusClass = [
    1 => 'bg-success-subtle text-success',
    2 => 'bg-warning-subtle text-warning-emphasis',
    3 => 'bg-danger-subtle text-danger',
    4 => 'bg-warning-subtle text-warning-emphasis',
    5 => 'bg-secondary-subtle text-secondary',
];

$statusId = (int) ($attendance['attendance_status_id'] ?? 0);

$attendanceHistory = $attendanceHistory ?? [];
$attendanceSummary = $attendanceSummary ?? [];

$daysRecorded = (int) ($attendanceSummary['days_recorded'] ?? 0);
$completedDays = (int) ($attendanceSummary['completed_days'] ?? 0);
$lateArrivals = (int) ($attendanceSummary['late_arrivals'] ?? 0);
$totalHours = (float) ($attendanceSummary['total_hours_worked'] ?? 0);

$month = $month ?? date('Y-m');

ob_start();
?>

<div class="container-fluid py-3">

    <!-- Page heading -->
    <div class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1">
                    <i class="bi bi-calendar-check me-2"></i>
                    My Attendance
                </h4>
                <p class="text-muted mb-0"><?= date('l, F j, Y') ?></p>
            </div>
            <?php if ($canRequestCorrection || $canViewCorrectionRequests): ?>
                <a href="<?= APP_BASE_PATH ?>/attendance/corrections"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>
                    <?= $canViewCorrectionRequests ? 'Correction Requests' : 'My Correction Requests' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    
    <!-- Monthly Attendance History -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-3 mb-4">
                <div>
                    <h5 class="mb-1">Monthly Attendance Summary</h5>
                    <p class="text-muted mb-0">
                        Review your attendance for a selected month.
                    </p>
                </div>

                <form method="GET"
                    action="<?= APP_BASE_PATH ?>/attendance"
                    class="d-flex gap-2">
                    <input
                        type="month"
                        name="month"
                        class="form-control"
                        value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <button type="submit" class="btn btn-primary">
                        View
                    </button>
                </form>
            </div>

            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Days Recorded</div>
                        <h3 class="mb-0"><?= $daysRecorded ?></h3>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Completed Days</div>
                        <h3 class="mb-0"><?= $completedDays ?></h3>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Late Arrivals</div>
                        <h3 class="mb-0"><?= $lateArrivals ?></h3>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Total Hours Worked</div>
                        <h3 class="mb-0">
                            <?= number_format($totalHours, 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row g-4">

        <!-- Today's attendance -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        Today's Attendance
                    </h6>
                </div>

                <div class="card-body p-4">

                    <?php if (!$attendance): ?>

                        <div class="text-center py-5">

                            <div class="rounded-circle bg-success-subtle text-success
                                        d-flex align-items-center justify-content-center
                                        mx-auto mb-3"
                                 style="width: 76px; height: 76px; font-size: 32px;">
                                <i class="bi bi-clock"></i>
                            </div>

                            <h5 class="mb-2">Ready to start your day?</h5>

                            <p class="text-muted mb-4">
                                You have not clocked in today.
                            </p>

                            <form
                                method="POST"
                                action="<?= APP_BASE_PATH ?>/attendance/clock-in"
                            >
                                <button type="submit"
                                        class="btn btn-success px-4">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Clock In
                                </button>
                            </form>

                        </div>

                    <?php else: ?>

                        <!-- Attendance summary -->
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Attendance Status
                                    </small>

                                    <span class="badge <?= $statusClass[$statusId] ?? 'bg-secondary-subtle text-secondary' ?>">
                                        <?= $escape($statusNames[$statusId] ?? 'Unknown') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Clock In
                                    </small>

                                    <div class="fw-semibold text-break">
                                        <i class="bi bi-box-arrow-in-right text-success me-1"></i>
                                        <?= $escape($attendance['clock_in_at'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Clock Out
                                    </small>

                                    <div class="fw-semibold text-break">
                                        <i class="bi bi-box-arrow-right text-danger me-1"></i>
                                        <?= $escape($attendance['clock_out_at'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <?php if (empty($attendance['clock_out_at'])): ?>

                            <div class="border-top pt-4 text-center">
                                <p class="text-muted mb-3">
                                    Your workday is currently in progress.
                                </p>

                                <form
                                    method="POST"
                                    action="<?= APP_BASE_PATH ?>/attendance/clock-out"
                                >
                                    <button type="submit"
                                            class="btn btn-danger px-4">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Clock Out
                                    </button>
                                </form>
                            </div>

                        <?php else: ?>

                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                Your attendance for today has been completed.
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Shift information -->
        <div class="col-12 col-xl-4">

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar-event me-2 text-primary"></i>
                        Today's Shift
                    </h6>
                </div>

                <div class="card-body p-4">

                    <?php if ($shift): ?>

                        <div class="mb-4">
                            <small class="text-muted d-block mb-1">
                                Shift Name
                            </small>
                            <h6 class="mb-0">
                                <?= $escape($shift['shift_name'] ?? null) ?>
                            </h6>
                        </div>

                        <div class="row g-3">

                            <div class="col-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Start Time
                                    </small>
                                    <div class="fw-semibold">
                                        <i class="bi bi-clock me-1 text-success"></i>
                                        <?= $escape($shift['start_time'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        End Time
                                    </small>
                                    <div class="fw-semibold">
                                        <i class="bi bi-clock-history me-1 text-danger"></i>
                                        <?= $escape($shift['end_time'] ?? null) ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="alert alert-light border mt-3 mb-0">
                            <small class="text-muted">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Grace period:
                                <strong>
                                    <?= (int) ($shift['grace_period_minutes'] ?? 0) ?>
                                    minutes
                                </strong>
                            </small>
                        </div>

                    <?php else: ?>

                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted"
                               style="font-size: 2.5rem;"></i>

                            <h6 class="mt-3">No Shift Assigned</h6>

                            <p class="text-muted small mb-0">
                                Your organization may not require shifts.
                                Follow your organization's working hours.
                            </p>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <!-- Attendance guidance -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h6 class="mb-3">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Attendance Information
                    </h6>

                    <p class="text-muted small mb-0">
                        Clock in when you begin work and clock out when your
                        workday ends. Submitted attendance records cannot be
                        directly edited.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>


    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Attendance History</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Hours Worked</th>
                            <th>Late Arrival</th>
                            <?php if ($canRequestCorrection): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($attendanceHistory)): ?>
                            <tr>
                                <td colspan="<?= $canRequestCorrection ? 7 : 6 ?>"
                                    class="text-center text-muted py-4">
                                    No attendance records found for this month.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceHistory as $record): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars(
                                            $record['attendance_date'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars(
                                                $record['status_name'] ?? 'Unknown',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($record['clock_in_at'])
                                            ? htmlspecialchars(
                                                date('H:i', strtotime($record['clock_in_at'])),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : '—' ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($record['clock_out_at'])): ?>
                                            <?= htmlspecialchars(
                                                date('H:i', strtotime($record['clock_out_at'])),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        <?php elseif (
            ($record['attendance_date'] ?? '') < date('Y-m-d')
        ): ?>
                                            <span class="badge bg-secondary">Incomplete</span>
                                        <?php else: ?>
                                            <span class="text-muted">In progress</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= $record['hours_worked'] !== null
                                            ? number_format((float) $record['hours_worked'], 2) . ' hrs'
                                            : '—' ?>
                                    </td>

                                    <td>
                                        <?php if ((int) ($record['arrived_late'] ?? 0) === 1): ?>
                                            <span class="badge bg-warning text-dark">
                                                Yes
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                No
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($canRequestCorrection): ?>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="<?= APP_BASE_PATH ?>/attendance/<?= (int) $record['id'] ?>/correction-request">
                                                Request correction
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


<?php if (!empty($error)): ?>
    <div class="modal fade"
         id="attendanceFlashModal"
         tabindex="-1"
         aria-labelledby="attendanceFlashModalLabel"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-danger-subtle border-0">
                    <h5 class="modal-title text-danger"
                        id="attendanceFlashModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Attendance Action Failed
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <?= $escape($error) ?>
                </div>

                <div class="modal-footer border-0">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement =
                document.getElementById('attendanceFlashModal');

            if (modalElement && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();

$pageTitle = 'My Attendance';
require __DIR__ . '/../layout/app.php';
?>