<?php

$employees = $employees ?? [];

$permissions = $_SESSION['permissions'] ?? [];

$hasPermission = function (string $permission) use ($permissions): bool {
    return in_array(
        $permission,
        $permissions,
        true
    );
};

ob_start();

?>


<div class="container-fluid py-4">

    <!-- Page Header -->
    <div
        class="d-flex
               flex-column
               flex-md-row
               justify-content-between
               align-items-md-center
               gap-3
               mb-4"
        >

        <div>

            <h2 class="fw-semibold mb-1">
                Employees
            </h2>

            <p class="text-muted mb-0">
                Manage employees within your organization.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="<?= APP_BASE_PATH ?>/employees/recycle-bin"
                class="btn btn-outline-secondary"
                >
                <i class="bi bi-trash me-1"></i>
                Recycle Bin
            </a>


            <?php if (
                $hasPermission('employee.create')
            ): ?>

                <a
                    href="<?= APP_BASE_PATH ?>/employees/create"
                    class="btn btn-primary"
                >
                    <i class="bi bi-person-plus me-1"></i>
                    Add Employee
                </a>

            <?php endif; ?>

        </div>

    </div>

    <!-- Employee Card -->
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <!-- Search -->
            <div class="row mb-4">

                <div class="col-md-6 col-lg-4">

                    <div class="input-group">

                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="text"
                            id="employeeSearch"
                            class="form-control"
                            placeholder="Search employees..."
                        >

                    </div>

                </div>

            </div>

            <!-- Employee Table -->
            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                    id="employeeTable"
                    >

                    <thead class="table-light">

                        <tr>

                            <th>
                                Employee
                            </th>

                            <th>
                                Employee No.
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Position
                            </th>

                            <th>
                                Employment Type
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($employees)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-people
                                           text-muted
                                           fs-1"
                                ></i>

                                <p class="text-muted mt-3 mb-0">
                                    No employees found.
                                </p>

                            </td>

                        </tr>

                    <?php else: ?>


                    <?php foreach (
                        $employees as $employee
                    ): ?>

                        <tr>

                            <?php
                            $profilePicture = $employee['profile_picture'] ?? null;
                            $profilePictureFilename = is_string($profilePicture)
                                ? basename($profilePicture)
                                : '';
                            $profilePicturePrefix =
                                APP_BASE_PATH . '/uploads/profile-pictures/';
                            $profilePictureUrl = preg_match(
                                '/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/',
                                $profilePictureFilename
                            )
                                ? $profilePicturePrefix . $profilePictureFilename
                                : null;
                            ?>

                                <!-- Employee -->
                                <td>

                                    <div
                                        class="d-flex
                                               align-items-center"
                                    >

                                    <?php if ($profilePictureUrl !== null): ?>
                                        <img
                                            src="<?= htmlspecialchars(
                                            $profilePictureUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                                alt=""
                                                class="rounded-circle me-3"
                                                style="
                                                    width: 40px;
                                                    height: 40px;
                                                    object-fit: cover;
                                                "
                                            >
                                        <?php else: ?>
                                            <div
                                                class="rounded-circle
                                                       bg-primary-subtle
                                                       text-primary
                                                       d-flex
                                                       align-items-center
                                                       justify-content-center
                                                       me-3 flex-shrink-0"
                                                style="
                                                    width: 40px;
                                                    height: 40px;
                                                "
                                                >
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div>

                                            <div class="fw-semibold">

                                                <?= htmlspecialchars(
                                                    trim(
                                                        ($employee['first_name'] ?? '')
                                                        . ' '
                                                        . ($employee['middle_name'] ?? '')
                                                        . ' '
                                                        . ($employee['last_name'] ?? '')
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </div>

                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    $employee['email'] ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>

                                <!-- Employee Number -->
                                <td>

                                    <?= htmlspecialchars(
                                        $employee['employee_number'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                                <!-- Department -->
                                <td>

                                    <?= htmlspecialchars(
                                        $employee['department_name']
                                            ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                                <!-- Position -->
                                <td>

                                    <?= htmlspecialchars(
                                        $employee['position_name']
                                            ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                                <!-- Employment Type -->
                                <td>

                                    <?= htmlspecialchars(
                                        $employee['employment_type']
                                            ?? 'N/A',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </td>

                                <!-- Status -->
                                <td>

                                    <?php
                                    $status =
                                        $employee['employee_status']
                                            ?? 'N/A';
                                    ?>

                                    <span class="badge text-bg-success">

                                        <?= htmlspecialchars(
                                            $status,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#employeeActionsModal"
                                        data-view-url="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>"
                                        <?php if ($hasPermission('employee.update')): ?>
                                            data-edit-url="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>/edit"
                                        <?php endif; ?>
                                        <?php if (
                                            $hasPermission('employee.delete')
                                            && $hasPermission('user.create')
                                            && empty($employee['user_id'])
                                        ): ?>
                                            data-create-account-url="<?= APP_BASE_PATH ?>/users/create?employee_id=<?= (int) $employee['id'] ?>"
                                        <?php endif; ?>
                                        <?php if ($hasPermission('employee.delete')): ?>
                                            data-delete-url="<?= APP_BASE_PATH ?>/employees/<?= (int) $employee['id'] ?>/delete"
                                        <?php endif; ?>
                                        aria-label="Actions for <?= htmlspecialchars(
                                            trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <i class="bi bi-three-dots"></i>
                                        <span class="visually-hidden">Actions</span>
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- Employee Count -->
            <?php if (!empty($employees)): ?>

                <div
                    class="border-top
                           mt-3
                           pt-3
                           text-muted
                           small"
                >

                    Showing
                    <strong>
                        <?= count($employees) ?>
                    </strong>
                    employee(s)

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<div
    class="modal fade"
    id="employeeActionsModal"
    tabindex="-1"
    aria-labelledby="employeeActionsModalTitle"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="employeeActionsModalTitle">
                    Employee Actions
                </h2>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush">
                    <a
                        class="list-group-item list-group-item-action"
                        id="employeeActionView"
                        href="#"
                    >
                        <i class="bi bi-eye me-2"></i>
                        View
                    </a>

                    <?php if ($hasPermission('employee.update')): ?>
                        <a
                            class="list-group-item list-group-item-action"
                            id="employeeActionEdit"
                            href="#"
                        >
                            <i class="bi bi-pencil me-2"></i>
                            Edit
                        </a>
                    <?php endif; ?>

                    <?php if (
                        $hasPermission('employee.delete')
                        && $hasPermission('user.create')
                    ): ?>
                        <a
                            class="list-group-item list-group-item-action"
                            id="employeeActionCreateAccount"
                            href="#"
                            hidden
                        >
                            <i class="bi bi-person-plus me-2"></i>
                            Create Account
                        </a>
                    <?php endif; ?>

                    <?php if ($hasPermission('employee.delete')): ?>
                        <form
                            method="POST"
                            id="employeeActionDeleteForm"
                            action=""
                            onsubmit="return confirm('Are you sure you want to move this employee to the recycle bin?');"
                        >
                            <button
                                type="submit"
                                class="list-group-item list-group-item-action text-danger w-100 text-start"
                            >
                                <i class="bi bi-trash me-2"></i>
                                Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const searchInput =
                document.getElementById(
                    'employeeSearch'
                );

            const table =
                document.getElementById(
                    'employeeTable'
                );

            if (!searchInput || !table) {
                return;
            }

            searchInput.addEventListener(
                'input',
                function () {

                    const search =
                        this.value
                            .toLowerCase()
                            .trim();

                    const rows =
                        table.querySelectorAll(
                            'tbody tr'
                        );

                    rows.forEach(
                        function (row) {

                            const text =
                                row.textContent
                                    .toLowerCase();

                            row.style.display =
                                text.includes(search)
                                    ? ''
                                    : 'none';

                        }
                    );

                }
            );

            const actionsModal =
                document.getElementById('employeeActionsModal');

            if (actionsModal) {
                actionsModal.addEventListener(
                    'show.bs.modal',
                    function (event) {
                        const trigger = event.relatedTarget;
                        if (!(trigger instanceof HTMLElement)) {
                            return;
                        }

                        const viewLink =
                            document.getElementById('employeeActionView');
                        if (viewLink) {
                            viewLink.href = trigger.dataset.viewUrl || '#';
                        }

                        const editLink =
                            document.getElementById('employeeActionEdit');
                        if (editLink) {
                            editLink.href = trigger.dataset.editUrl || '#';
                        }

                        const createAccountLink =
                            document.getElementById('employeeActionCreateAccount');
                        if (createAccountLink) {
                            const createAccountUrl =
                                trigger.dataset.createAccountUrl;
                            createAccountLink.hidden = !createAccountUrl;
                            createAccountLink.href = createAccountUrl || '#';
                        }

                        const deleteForm =
                            document.getElementById('employeeActionDeleteForm');
                        if (deleteForm) {
                            deleteForm.action = trigger.dataset.deleteUrl || '';
                        }
                    }
                );
            }

        }
    );

</script>

<?php

$content = ob_get_clean();

$pageTitle = 'Employees';

require __DIR__ . '/../layout/app.php';
