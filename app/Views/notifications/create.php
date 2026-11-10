<?php

$users = isset($users) && is_array($users) ? $users : [];

ob_start();
?>


<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Create Notification</h2>
            <p class="text-muted mb-0">
                Send an announcement to employees in your organization.
            </p>
        </div>

        <a href="<?= APP_BASE_PATH ?>/notifications"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Notifications
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="<?= APP_BASE_PATH ?>/notifications">

                <input type="hidden" name="type_code" value="SYSTEM">

                <div class="mb-3">
                    <label for="title" class="form-label">
                        Notification Title
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        maxlength="200"
                        required
                        placeholder="Enter notification title"
                    >
                </div>

                <div class="mb-4">
                    <label for="message" class="form-label">
                        Message
                    </label>

                    <textarea
                        class="form-control"
                        id="message"
                        name="message"
                        rows="5"
                        required
                        placeholder="Write your announcement..."
                    ></textarea>
                </div>

               
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Recipients
                    </label>

                    <div class="form-check mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="send_to_all"
                            name="send_to_all"
                            value="1"
                        >
                        <label class="form-check-label" for="send_to_all">
                            Send to all active employees
                        </label>
                    </div>

                    <div id="recipientList">
                        <label for="employeeSearch" class="form-label">
                            Select employees
                        </label>

                        <input
                            type="search"
                            id="employeeSearch"
                            class="form-control mb-2"
                            placeholder="Search by employee name, number, or username..."
                        >

                        <div class="border rounded p-2" id="employeeOptions">
                            <?php foreach ($users as $user): ?>
                                <?php
                                $fullName = trim(
                                    ($user['first_name'] ?? '') . ' '
                                    . ($user['last_name'] ?? '')
                                );

                                $searchText = $fullName . ' '
                                    . ($user['employee_number'] ?? '') . ' '
                                    . ($user['username'] ?? '') . ' '
                                    . ($user['email'] ?? '');
                                ?>

                                <div
                                    class="form-check employee-option py-2 border-bottom"
                                    data-search="<?= htmlspecialchars(
                                    $searchText,
                                    ENT_QUOTES,
                                    'UTF-8'
                                    ) ?>"
                                >
                                    <input
                                        class="form-check-input employee-checkbox"
                                        type="checkbox"
                                        name="recipient_ids[]"
                                        value="<?= (int) $user['id'] ?>"
                                        id="employee_<?= (int) $user['id'] ?>"
                                    >

                                    <label
                                        class="form-check-label w-100"
                                        for="employee_<?= (int) $user['id'] ?>"
                                    >
                                        <span class="fw-medium">
                                            <?= htmlspecialchars(
                                                $fullName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <br>

                                        <small class="text-muted">
                                            <?= htmlspecialchars(
                                                $user['employee_number'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                            <?php if (!empty($user['username'])): ?>
                                                · <?= htmlspecialchars(
                                            $user['username'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                            <?php endif; ?>
                                        </small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <small class="text-muted" id="selectionCount">
                            0 employees selected
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>
                        Send Notification
                    </button>

                    <a href="<?= APP_BASE_PATH ?>/notifications"
                       class="btn btn-light">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>  
    document.addEventListener('DOMContentLoaded', function () {
        const sendToAll = document.getElementById('send_to_all');
        const recipientList = document.getElementById('recipientList');
        const employeeSearch = document.getElementById('employeeSearch');
        const employeeOptions = document.querySelectorAll('.employee-option');
        const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
        const selectionCount = document.getElementById('selectionCount');

        function updateRecipients() {
            recipientList.style.display = sendToAll.checked ? 'none' : 'block';

            employeeCheckboxes.forEach(function (checkbox) {
                checkbox.required = false;
            });

            updateCount();
        }

        function updateCount() {
            const selected = document.querySelectorAll(
                '.employee-checkbox:checked'
            ).length;

            selectionCount.textContent =
                selected + (selected === 1
                    ? ' employee selected'
                    : ' employees selected');
        }

        employeeSearch.addEventListener('input', function () {
            const query = employeeSearch.value.toLowerCase().trim();

            employeeOptions.forEach(function (option) {
                const searchText = (
                    option.dataset.search || ''
                ).toLowerCase();

                option.style.display = searchText.includes(query)
                    ? ''
                    : 'none';
            });
        });

        employeeCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateCount);
        });

        sendToAll.addEventListener('change', updateRecipients);

        updateRecipients();
    });
</script>

<?php

$content = ob_get_clean();
$pageTitle = 'Create Notification';

require __DIR__ . '/../layout/app.php';
