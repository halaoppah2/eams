<?php

/** @var list<array{id: int|string, first_name: string, last_name: string, employee_number: ?string, username: ?string, email: ?string}> $users */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">
                Add Holiday
            </h2>
            <p class="text-muted mb-0">
                Create an organization holiday and send an in-app notice.
            </p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/holidays" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Holidays
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= APP_BASE_PATH ?>/holidays">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="holiday-name" class="form-label">
                            Holiday name
                        </label>
                        <input id="holiday-name" name="name" class="form-control"
                               maxlength="150" required>
                    </div>
                    <div class="col-md-3">
                        <label for="holiday-start-date" class="form-label">
                            From
                        </label>
                        <input id="holiday-start-date" name="start_date" type="date"
                               class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="holiday-end-date" class="form-label">
                            To
                        </label>
                        <input id="holiday-end-date" name="end_date" type="date"
                               class="form-control" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input id="is-recurring" name="is_recurring" type="checkbox"
                                   class="form-check-input" value="1">
                            <label for="is-recurring" class="form-check-label">
                                Repeats annually
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notify recipients</label>
                        <div class="form-check mb-2">
                            <input id="send-to-all" name="send_to_all" type="checkbox"
                                   class="form-check-input" value="1" checked>
                            <label for="send-to-all" class="form-check-label">
                                Send an in-app notification to all active employees
                            </label>
                        </div>
                        <label for="recipient-ids" class="form-label">
                            Or choose specific recipients
                        </label>
                        <select id="recipient-ids" name="recipient_ids[]" class="form-select"
                                multiple size="7" disabled>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $fullName = trim(
                                    (string) ($user['first_name'] ?? '') . ' '
                                    . (string) ($user['last_name'] ?? '')
                                );
                                $label = $fullName;
                                if (!empty($user['employee_number'])) {
                                    $label .= ' (' . $user['employee_number'] . ')';
                                }
                                ?>
                                <option value="<?= (int) $user['id'] ?>">
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Clear the all-employees checkbox to select one or more employees.
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= APP_BASE_PATH ?>/holidays" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-1"></i>
                        Create and Notify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allEmployees = document.getElementById('send-to-all');
    const recipients = document.getElementById('recipient-ids');
    allEmployees.addEventListener('change', function () {
        recipients.disabled = allEmployees.checked;
        recipients.required = !allEmployees.checked;
    });
});
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Add Holiday';
require __DIR__ . '/../layout/app.php';
