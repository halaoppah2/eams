<?php

/** @var array<string, mixed> $attendance */
/** @var bool $hasPendingRequest */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatDateTime = static function (?string $value): string {
    return $value === null || $value === ''
        ? ''
        : (new DateTimeImmutable($value))->format('Y-m-d\TH:i');
};

ob_start();
?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-semibold mb-1">Request Attendance Correction</h2>
        <p class="text-muted mb-0">
            Attendance date: <?= $escape($attendance['attendance_date']) ?>
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="alert alert-light border">
                Current clock-in: <strong><?= $escape($attendance['clock_in_at'] ?? '—') ?></strong>
                <span class="mx-2">|</span>
                Current clock-out: <strong><?= $escape($attendance['clock_out_at'] ?? '—') ?></strong>
            </div>

            <?php if ($hasPendingRequest): ?>
                <div class="alert alert-warning mb-0">
                    A correction request for this record is already pending review.
                </div>
            <?php else: ?>
                <form method="POST"
                      action="<?= APP_BASE_PATH ?>/attendance/<?= (int) $attendance['id'] ?>/correction-request">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="requested-clock-in" class="form-label">Correct clock-in</label>
                            <input id="requested-clock-in" name="requested_clock_in"
                                   type="datetime-local" class="form-control"
                                   value="<?= $escape($formatDateTime($attendance['clock_in_at'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="requested-clock-out" class="form-label">Correct clock-out</label>
                            <input id="requested-clock-out" name="requested_clock_out"
                                   type="datetime-local" class="form-control"
                                   value="<?= $escape($formatDateTime($attendance['clock_out_at'])) ?>">
                        </div>
                        <div class="col-12">
                            <label for="reason" class="form-label">Reason for correction</label>
                            <textarea id="reason" name="reason" class="form-control"
                                      rows="4" maxlength="2000" required></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= APP_BASE_PATH ?>/attendance" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Request
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Request Attendance Correction';
require __DIR__ . '/../layout/app.php';
