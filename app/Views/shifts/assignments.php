
<?php

$employees = $employees ?? [];
$shifts = $shifts ?? [];

ob_start();
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Manage Employee Shifts</h2>
            <p class="text-muted mb-0">
                Assign, change, or unassign an employee's shift.
            </p>
        </div>

        <a href="<?= APP_BASE_PATH ?>/shifts"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
             Back to Shifts
        </a>
    </div>

    <?php if (empty($employees)): ?>
        <div class="alert alert-warning">
            No employees are available.
        </div>
    <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <form method="POST"
                      action="<?= APP_BASE_PATH ?>/shifts/assign"
                      id="shiftAssignmentForm">

                    <div class="mb-3">
                        <label for="action" class="form-label">
                            Action
                        </label>

                        <select name="action"
                                id="action"
                                class="form-select"
                                required>
                            <option value="assign">
                                Assign / Change Shift
                            </option>
                            <option value="unassign">
                                Unassign Shift
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label">
                            Employee
                        </label>

                        <select name="employee_id"
                                id="employee_id"
                                class="form-select"
                                required>
                            <option value="">Select employee</option>

                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= (int) $employee['id'] ?>">
                                    <?= htmlspecialchars(
                                        $employee['employee_number'] . ' - ' .
                                        $employee['first_name'] . ' ' .
                                        ($employee['middle_name'] ?? '') . ' ' .
                                        $employee['last_name']
                                    )?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="shiftField">
                        <label for="shift_id" class="form-label">
                            Active Shift
                        </label>

                        <select name="shift_id"
                                id="shift_id"
                                class="form-select">
                            <option value="">Select shift</option>

                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?= (int) $shift['id'] ?>">
                                    <?= htmlspecialchars(
                                        $shift['name'] . ' (' .
                                        $shift['start_time'] . ' - ' .
                                        $shift['end_time'] . ')'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="effective_from" class="form-label">
                            Effective From
                        </label>

                        <input type="date"
                               name="effective_from"
                               id="effective_from"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               required>

                        <small class="text-muted" id="dateHelp">
                            The selected shift applies from this date.
                        </small>
                    </div>

                    <button type="submit"
                            class="btn btn-primary"
                            id="submitButton">
                        <i class="bi bi-calendar-check"></i>
                        Assign / Change Shift
                    </button>

                    <a href="<?= APP_BASE_PATH ?>/shifts"
                       class="btn btn-outline-secondary ms-2">
                        Cancel
                    </a>

                </form>

            </div>
        </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const action = document.getElementById('action');
    const shiftField = document.getElementById('shiftField');
    const shiftSelect = document.getElementById('shift_id');
    const submitButton = document.getElementById('submitButton');
    const dateHelp = document.getElementById('dateHelp');
    const form = document.getElementById('shiftAssignmentForm');

    if (!action || !form) return;

    function updateForm() {
        const unassigning = action.value === 'unassign';

        shiftField.hidden = unassigning;
        shiftSelect.required = !unassigning;

        submitButton.className = unassigning
            ? 'btn btn-danger'
            : 'btn btn-primary';

        submitButton.innerHTML = unassigning
            ? '<i class="bi bi-calendar-x"></i> Unassign Shift'
            : '<i class="bi bi-calendar-check"></i> Assign / Change Shift';

        dateHelp.textContent = unassigning
            ? 'The existing shift assignment will end before this date.'
            : 'The selected shift applies from this date.';
    }

    action.addEventListener('change', updateForm);

    form.addEventListener('submit', function (event) {
        if (
            action.value === 'unassign' &&
            !confirm(
                'Are you sure you want to unassign this employee’s shift from the selected date?'
            )
        ) {
            event.preventDefault();
        }
    });

    updateForm();
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Manage Employee Shifts';

require __DIR__ . '/../layout/app.php';
?>
