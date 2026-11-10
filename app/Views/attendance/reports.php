
<?php
$reportRows = $reportRows ?? [];
$employees = $employees ?? [];
$employeeId = $employeeId ?? null;
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');

ob_start();
?>

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-2 mb-4">
        <div>
            <h3 class="mb-1">Attendance Reports</h3>
            <p class="text-muted mb-0">
                Organization-wide recorded attendance.
            </p>
        </div>

        <button type="button"
                class="btn btn-outline-secondary"
                onclick="window.print()">
            <i class="bi bi-printer"></i> 
            Print Report
        </button>
    </div>

    <!-- Date filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET"
                  action="<?= APP_BASE_PATH ?>/reports"
                  class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label for="start_date" class="form-label">
                        Start Date
                    </label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        class="form-control"
                        value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label for="end_date" class="form-label">
                        End Date
                    </label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        class="form-control"
                        value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label for="employee_id" class="form-label">
                        Employee
                    </label>
                    <select id="employee_id" name="employee_id" class="form-select">
                        <option value="">All employees</option>
                        <?php foreach ($employees as $employee): ?>
                            <?php
                            $fullName = trim(
                                ($employee['first_name'] ?? '') . ' ' .
                                ($employee['middle_name'] ?? '') . ' ' .
                                ($employee['last_name'] ?? '')
                            );
                            ?>
                            <option
                                value="<?= (int) $employee['id'] ?>"
                                <?= (int) $employeeId === (int) $employee['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    ($employee['employee_number'] ?? '') . ' - ' . $fullName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report summary -->
    <?php
    $totalRecords = count($reportRows);
    $lateCount = 0;
    $completedCount = 0;

    foreach ($reportRows as $row) {
        if ((int) ($row['arrived_late'] ?? 0) === 1) {
            $lateCount++;
        }

        if (!empty($row['clock_out_at'])) {
            $completedCount++;
        }
    }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Records Found</div>
                    <h3 class="mb-0"><?= $totalRecords ?></h3>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Completed Records</div>
                    <h3 class="mb-0"><?= $completedCount ?></h3>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Late Arrivals</div>
                    <h3 class="mb-0"><?= $lateCount ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Attendance Records</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employee No.</th>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Hours Worked</th>
                            <th>Late</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($reportRows)): ?>
                            <tr>
                                <td colspan="8"
                                    class="text-center text-muted py-4">
                                    No attendance records found for this date range.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportRows as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars(
                                            $row['attendance_date'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['employee_number'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            trim(
                                                ($row['first_name'] ?? '') . ' ' .
                                                ($row['last_name'] ?? '')
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['status_name'] ?? 'Unknown',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= !empty($row['clock_in_at'])
                                            ? htmlspecialchars(
                                                date('H:i', strtotime($row['clock_in_at'])),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : '—' ?>
                                    </td>

                                    <td>
                                        <?= !empty($row['clock_out_at'])
                                            ? htmlspecialchars(
                                                date('H:i', strtotime($row['clock_out_at'])),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'Incomplete' ?>
                                    </td>

                                    <td>
                                        <?= $row['hours_worked'] !== null
                                            ? number_format((float) $row['hours_worked'], 2) . ' hrs'
                                            : '—' ?>
                                    </td>

                                    <td>
                                        <?php if ((int) ($row['arrived_late'] ?? 0) === 1): ?>
                                            <span class="badge bg-warning text-dark">
                                                Yes
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                No
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar,
    nav,
    form,
    button,
    .btn {
        display: none !important;
    }

    .card {
        box-shadow: none !important;
    }

    body {
        background: #fff !important;
    }
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Attendance Reports';

require __DIR__ . '/../layout/app.php';
?>
