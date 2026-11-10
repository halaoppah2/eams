<?php

$employee = $data['employee'] ?? null;
$error = $error ?? null;

ob_start();

?>
 
<div class="container py-5">

    <div class="mb-4">
        <h2 class="mb-1">Create User Account</h2>
        <p class="text-muted mb-0">
            Create a login account for this employee.
        </p>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <!-- Employee Information -->
            <h5 class="mb-3">
                <i class="bi bi-person"></i>
                Employee Information
            </h5>

            <div class="row mb-4">

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Employee Number
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $employee['employee_number']
                        ) ?>"
                        readonly
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Employee Name
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            trim(
                                $employee['first_name']
                                . ' '
                                . ($employee['middle_name'] ?? '')
                                . ' '
                                . $employee['last_name']
                            )
                        ) ?>"
                        readonly
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Department
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $employee['department_name']
                                ?? 'N/A'
                        ) ?>"
                        readonly
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Employee Email
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $employee['email']
                                ?? 'N/A'
                        ) ?>"
                        readonly
                    >
                </div>

            </div>

            <hr>

            <!-- Account Information -->
            <h5 class="mb-3">
                <i class="bi bi-person-lock"></i>
                Account Information
            </h5>

            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/users"
            >

                <input
                    type="hidden"
                    name="employee_id"
                    value="<?= (int) $employee['id'] ?>"
                >

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label
                            for="email"
                            class="form-label"
                        >
                            Account Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $employee['email'] ?? ''
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            minlength="8"
                            required
                        >

                        <div class="form-text">
                            Minimum 8 characters.
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label
                            for="password_confirmation"
                            class="form-label"
                        >
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            minlength="8"
                            required
                        >
                    </div>

                </div>

                <div class="d-flex gap-2 mt-3">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-person-plus"></i>
                        Create Account
                    </button>

                    <a
                        href="<?= APP_BASE_PATH ?>/employees"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<?php if (!empty($error)): ?>
    <div
        class="modal fade"
        id="userCreateFlashModal"
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
                document.getElementById('userCreateFlashModal')
            );
            flashModal.show();
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Create User Account';

require __DIR__ . '/../layout/app.php';
?>
