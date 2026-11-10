<?php

use App\Services\PermissionService;

$shift = $shift ?? [];

$statuses = $statuses ?? [];
$error = $error ?? null;

ob_start();

?>

<div class="container py-5">

    <!-- Header -->

    <div class="mb-4 justify-content-between align-items-center d-flex">

    <div>    
        <h2 class="mb-1">
            Edit Shift
        </h2>

        <p class="text-muted mb-0">
            Update shift information
        </p>
    </div>

        <a
            href="<?= APP_BASE_PATH ?>/shifts"
            class="btn btn-outline-secondary mb-3"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Shifts

        </a>

    </div>

    <!-- Edit Shift Form -->

    <div class="card shadow-sm">

        <div class="card-body">

            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/shifts/<?= $shift['id'] ?>"
            >

                <div class="row g-3">

                    <!-- Shift Name -->

                    <div class="col-md-6">

                        <label
                            for="name"
                            class="form-label"
                        >
                            Shift Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $shift['name']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Shift Code -->

                    <div class="col-md-6">

                        <label
                            for="code"
                            class="form-label"
                        >
                            Shift Code
                        </label>

                        <input
                            type="text"
                            name="code"
                            id="code"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $shift['code']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Start Time -->

                    <div class="col-md-6">

                        <label
                            for="start_time"
                            class="form-label"
                        >
                            Start Time
                        </label>

                        <input
                            type="time"
                            name="start_time"
                            id="start_time"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $shift['start_time']
                            ) ?>"
                            required
                        >

                    </div>

                    <!-- End Time -->

                    <div class="col-md-6">

                        <label
                            for="end_time"
                            class="form-label"
                        >
                            End Time
                        </label>

                        <input
                            type="time"
                            name="end_time"
                            id="end_time"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $shift['end_time']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- Grace Period -->

                    <div class="col-md-6">

                        <label
                            for="grace_period_minutes"
                            class="form-label"
                        >
                            Grace Period (Minutes)
                        </label>

                        <input
                            type="number"
                            name="grace_period_minutes"
                            id="grace_period_minutes"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars(
                                $shift['grace_period_minutes']
                            ) ?>"
                        >

                    </div>

                    <!-- Break Duration -->

                    <div class="col-md-6">

                        <label
                            for="break_duration_minutes"
                            class="form-label"
                        >
                            Break Duration (Minutes)
                        </label>

                        <input
                            type="number"
                            name="break_duration_minutes"
                            id="break_duration_minutes"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars(
                                $shift['break_duration_minutes']
                            ) ?>"
                        >

                    </div>

                   <!-- Working Hours -->

                    <div class="col-md-6">

                        <label for="working_hours" class="form-label">
                            Working Hours
                        </label>

                        <input
                            type="number"
                            name="working_hours"
                            id="working_hours"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            value="<?= htmlspecialchars(
                                (string) ($shift['working_hours'] ?? '')
                            ) ?>"
                            readonly
                            required
                        >

                        <small class="text-muted">
                            Calculated automatically from shift times and break duration.
                        </small>

                    </div>


                    <!-- Status -->

                    <div class="col-md-6">

                        <label
                            for="status_id"
                            class="form-label"
                        >
                            Status
                        </label>

                        <select
                            name="status_id"
                            id="status_id"
                            class="form-select"
                            required
                        >

                            <?php foreach ($statuses as $status): ?>

                                <option
                                    value="<?= $status['id'] ?>"
                                    <?= (int) $status['id']
                                        === (int) $shift['status_id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $status['name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- Overnight -->

                    <div class="col-12">

                        <div class="form-check">

                            <input
                                type="checkbox"
                                name="is_overnight"
                                value="1"
                                class="form-check-input"
                                id="is_overnight"
                                <?= !empty($shift['is_overnight'])
                                    ? 'checked'
                                    : '' ?>
                            >

                            <label
                                class="form-check-label"
                                for="is_overnight"
                            >
                                Overnight Shift
                            </label>

                        </div>

                        <small class="text-muted">
                            Enable this if the shift ends on the following day.
                        </small>

                    </div>

                </div>


                <!-- Actions -->

                <div class="mt-4">

                    <?php if (
                        PermissionService::has('shift.update')
                    ): ?>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-check-lg"></i>

                            Update Shift

                        </button>

                    <?php endif; ?>

                    <a
                        href="<?= APP_BASE_PATH ?>/shifts"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');
        const breakDuration = document.getElementById('break_duration_minutes');
        const workingHours = document.getElementById('working_hours');
        const overnight = document.getElementById('is_overnight');

        function calculateWorkingHours() {

            if (!startTime.value || !endTime.value) {
                workingHours.value = '';
                return;
            }

            const startParts = startTime.value.split(':').map(Number);
            const endParts = endTime.value.split(':').map(Number);

            const startMinutes = startParts[0] * 60 + startParts[1];
            const endMinutes = endParts[0] * 60 + endParts[1];

            let totalMinutes = endMinutes - startMinutes;

            if (totalMinutes <= 0) {
                if (overnight.checked) {
                    totalMinutes += 1440;
                } else {
                    workingHours.value = '';
                    return;
                }
            }

            const breakMinutes = Math.max(
                0,
                Number(breakDuration.value) || 0
            );

            totalMinutes -= breakMinutes;

            if (totalMinutes <= 0) {
                workingHours.value = '';
                return;
            }

            workingHours.value = (totalMinutes / 60).toFixed(2);
        }

        startTime.addEventListener('input', calculateWorkingHours);
        endTime.addEventListener('input', calculateWorkingHours);
        breakDuration.addEventListener('input', calculateWorkingHours);
        overnight.addEventListener('change', calculateWorkingHours);

        calculateWorkingHours();

    });
</script>

<?php if (!empty($error)): ?>
    <div
        class="modal fade"
        id="shiftEditFlashModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-danger-subtle">
                    <h5 class="modal-title text-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Action failed
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const flashModal = new bootstrap.Modal(
                document.getElementById('shiftEditFlashModal')
            );
            flashModal.show();
        });
    </script>
<?php endif; ?>

<?php

$content = ob_get_clean();

$pageTitle = 'Edit Shift';

require __DIR__ . '/../layout/app.php';