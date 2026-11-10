<?php

/** @var array{id: int|string, name: string, holiday_date: string, end_date: string, is_recurring: int|string} $holiday */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">
                Edit Holiday
            </h2>
            <p class="text-muted mb-0">
                Update this organization's holiday details.
            </p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/holidays" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Holidays
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= APP_BASE_PATH ?>/holidays/<?= (int) $holiday['id'] ?>">
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="holiday-name" class="form-label">Holiday name</label>
                        <input
                            id="holiday-name"
                            name="name"
                            class="form-control"
                            maxlength="150"
                            value="<?= $escape($holiday['name']) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-3">
                        <label for="holiday-start-date" class="form-label">From</label>
                        <input
                            id="holiday-start-date"
                            name="start_date"
                            type="date"
                            class="form-control"
                            value="<?= $escape($holiday['holiday_date']) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-3">
                        <label for="holiday-end-date" class="form-label">To</label>
                        <input
                            id="holiday-end-date"
                            name="end_date"
                            type="date"
                            class="form-control"
                            value="<?= $escape($holiday['end_date']) ?>"
                            required
                        >
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input
                                id="is-recurring"
                                name="is_recurring"
                                type="checkbox"
                                class="form-check-input"
                                value="1"
                                <?= (int) $holiday['is_recurring'] === 1 ? 'checked' : '' ?>
                            >
                            <label for="is-recurring" class="form-check-label">
                                Repeats annually
                            </label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= APP_BASE_PATH ?>/holidays" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Edit Holiday';
require __DIR__ . '/../layout/app.php';
