<?php

/** @var array<string, mixed> $request */
/** @var string $csrfToken */
/** @var bool $canApprove */
/** @var bool $canReject */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatDateTime = static function (?string $value): string {
    return $value === null || $value === ''
        ? ''
        : (new DateTimeImmutable($value))->format('Y-m-d\TH:i');
};
$proposedClockIn = $request['requested_clock_in']
    ?? $request['clock_in_at'];
$proposedClockOut = $request['requested_clock_out']
    ?? $request['clock_out_at'];

ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">Review Attendance Correction</h2>
            <p class="text-muted mb-0">
                <?= $escape(trim($request['first_name'] . ' ' . $request['last_name'])) ?>
                (<?= $escape($request['employee_number']) ?>) ·
                <?= $escape($request['attendance_date']) ?>
            </p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/attendance/corrections" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Requests
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Employee's reason</h5>
            <p class="mb-0"><?= nl2br($escape($request['reason'])) ?></p>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    Current clock-in: <strong><?= $escape($request['clock_in_at'] ?? '—') ?></strong><br>
                    Requested clock-in: <strong><?= $escape($request['requested_clock_in'] ?? '—') ?></strong>
                </div>
                <div class="col-md-6">
                    Current clock-out: <strong><?= $escape($request['clock_out_at'] ?? '—') ?></strong><br>
                    Requested clock-out: <strong><?= $escape($request['requested_clock_out'] ?? '—') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canApprove): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Correct and approve</div>
            <div class="card-body">
                <form method="POST"
                      action="<?= APP_BASE_PATH ?>/attendance/corrections/<?= (int) $request['id'] ?>/approve">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="clock-in-at" class="form-label">Final clock-in</label>
                            <input id="clock-in-at" name="clock_in_at" type="datetime-local"
                                   class="form-control"
                                   value="<?= $escape($formatDateTime($proposedClockIn)) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="clock-out-at" class="form-label">Final clock-out</label>
                            <input id="clock-out-at" name="clock_out_at" type="datetime-local"
                                   class="form-control"
                                   value="<?= $escape($formatDateTime($proposedClockOut)) ?>">
                            <div class="form-text">Leave blank if the employee has not clocked out.</div>
                        </div>
                        <div class="col-12">
                            <label for="approval-comment" class="form-label">Comment (optional)</label>
                            <textarea id="approval-comment" name="review_comment"
                                      class="form-control" rows="2" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Save Correction and Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canReject): ?>
        <div class="card border-danger">
            <div class="card-header bg-white text-danger fw-semibold">Reject request</div>
            <div class="card-body">
                <form method="POST"
                      action="<?= APP_BASE_PATH ?>/attendance/corrections/<?= (int) $request['id'] ?>/reject">
                    <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                    <label for="rejection-comment" class="form-label">Reason for rejection</label>
                    <textarea id="rejection-comment" name="review_comment"
                              class="form-control mb-3" rows="2" maxlength="2000" required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-lg me-1"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Review Attendance Correction';
require __DIR__ . '/../layout/app.php';
