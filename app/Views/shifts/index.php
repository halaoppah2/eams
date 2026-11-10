<?php

use App\Services\PermissionService;
use App\Session;

$flashMessage = Session::get('flash_message');
Session::remove('flash_message');

if (empty($flashMessage) && isset($_SESSION['shift_error'])) {
    $flashMessage = [
        'title' => 'Shift action failed',
        'message' => $_SESSION['shift_error']
    ];
    unset($_SESSION['shift_error']);
}

$shiftError = $flashMessage['message'] ?? null;

ob_start();

?>

<div class="container py-5">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Shifts
            </h2>

            <p class="text-muted mb-0">
                Shift Management
            </p>

        </div>

        <div>

            <?php if (PermissionService::has('shift.create')): ?>

                <a
                    href="<?= APP_BASE_PATH ?>/shifts/create"
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-lg"></i>
                    Add Shift
                </a>

            <?php endif; ?>
            
            <?php if (PermissionService::has('shift.update')): ?>
                <a
                    href="<?= APP_BASE_PATH ?>/shifts/assignments"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-person-check"></i>
                    Assign Shift
                </a>
            <?php endif; ?>

        </div>

    </div>

    <!-- Shift Table -->

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Name</th>

                            <th>Code</th>

                            <th>Start Time</th>

                            <th>End Time</th>

                            <th>Grace Period</th>

                            <th>Working Hours</th>

                            <th>Status</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($shifts)): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >
                                No shifts found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($shifts as $shift): ?>

                            <tr>

                                <td>

                                    <?= htmlspecialchars(
                                        $shift['name']
                                    ) ?>

                                </td>

                                <td>

                                    <span class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $shift['code']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $shift['start_time']
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $shift['end_time']
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $shift['grace_period_minutes']
                                    ) ?>

                                    min

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $shift['working_hours']
                                    ) ?>

                                    hrs

                                </td>

                                <td>

                                    <?php if (
                                        ($shift['status_code'] ?? '')
                                        === 'ACTIVE'
                                    ): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars(
                                                $shift['status_name']
                                                    ?? 'Unknown'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <?php if (
                                            PermissionService::has(
                                                'shift.update'
                                            )
                                        ): ?>

                                            <a
                                                href="<?= APP_BASE_PATH ?>/shifts/<?= $shift['id'] ?>/edit"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Edit
                                            </a>

                                        <?php endif; ?>

                                        <?php if (
                                            PermissionService::has(
                                                'shift.delete'
                                            )
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="<?= APP_BASE_PATH ?>/shifts/<?= $shift['id'] ?>/delete"
                                                onsubmit="return confirm('Are you sure you want to delete this shift?');"
                                            >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

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

<!-- Shift Error Modal -->

<?php if ($shiftError): ?>

    <div
        class="modal fade"
        id="shiftErrorModal"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title text-danger">

                        <i class="bi bi-exclamation-triangle-fill"></i>

                        Unable to Delete Shift

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>

                <div class="modal-body">

                    <?= htmlspecialchars($shiftError) ?>

                </div>

                <div class="modal-footer">

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


    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const errorModal =
                new bootstrap.Modal(
                    document.getElementById('shiftErrorModal')
                );

            errorModal.show();

        });

    </script>

<?php endif; ?>

<?php

$content = ob_get_clean();
$pageTitle = 'Shifts';

require __DIR__ . '/../layout/app.php';

?>