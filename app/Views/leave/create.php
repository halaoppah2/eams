<?php

use App\Services\PermissionService;

$employees = $employees ?? [];
$leaveTypes = $leaveTypes ?? [];

ob_start();
?>

<div class="container mb-4">
    <h2 class="mb-1">Request Leave</h2>
    <p class="text-muted mb-0">
        Submit a new employee leave request.
    </p>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Leave Request Information</h5>
    </div>

    <div class="card-body">
        <form method="POST" action="<?= APP_BASE_PATH ?>/leave">
            <div class="row g-3">

                <?php if (PermissionService::has('leave.create_for_others')): ?>
                    <div class="col-12">
                        <label for="employee_id" class="form-label">
                            Employee
                        </label>

                        <select
                            name="employee_id"
                            id="employee_id"
                            class="form-select"
                            required
                        >
                            <option value="">Select employee</option>

                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= (int) $employee['id'] ?>">
                                    <?= htmlspecialchars(
                                        $employee['employee_number'] . ' - ' .
                                        $employee['first_name'] . ' ' .
                                        $employee['last_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-person-check me-1"></i>
                            You are requesting leave for yourself.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <label for="leave_type_id" class="form-label">
                        Leave Type
                    </label>

                    <select
                        name="leave_type_id"
                        id="leave_type_id"
                        class="form-select"
                        required
                    >
                        <option value="">Select leave type</option>

                        <?php foreach ($leaveTypes as $leaveType): ?>
                            <option value="<?= (int) $leaveType['id'] ?>">
                                <?= htmlspecialchars($leaveType['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="start_date" class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="end_date" class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-12">
                    <label for="reason" class="form-label">
                        Reason
                    </label>

                    <textarea
                        name="reason"
                        id="reason"
                        class="form-control"
                        rows="4"
                        placeholder="Enter the reason for the leave request..."
                    ></textarea>
                </div>

                <div class="col-12">
                    <hr>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>
                            Submit Request
                        </button>

                        <a
                            href="<?= APP_BASE_PATH ?>/leave"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Cancel
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php

$content = ob_get_clean();
$pageTitle = 'Request Leave';

require __DIR__ . '/../layout/app.php';