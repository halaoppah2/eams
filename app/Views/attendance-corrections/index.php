<?php

/** @var list<array<string, mixed>> $requests */
/** @var bool $isAdmin */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$canRequest = \App\Services\PermissionService::has(
    'attendance.correction.request'
);
$canApprove = \App\Services\PermissionService::has(
    'attendance.correction.approve'
);
$canReject = \App\Services\PermissionService::has(
    'attendance.correction.reject'
);

ob_start();
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">
                <?= $isAdmin ? 'Attendance Correction Requests' : 'My Correction Requests' ?>
            </h2>
            <p class="text-muted mb-0">
                <?= $isAdmin
                    ? 'Review employee attendance correction requests for your organization.'
                    : 'Track the status of your attendance correction requests.' ?>
            </p>
        </div>
        <a href="<?= APP_BASE_PATH ?>/attendance" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Attendance
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <?php if ($isAdmin): ?><th>Employee</th><?php endif; ?>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Requested clock-in</th>
                        <th>Requested clock-out</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <?php if ($isAdmin): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <?php
                        $statusClass = match ($request['status_code'] ?? '') {
                            'APPROVED' => 'text-bg-success',
                            'REJECTED' => 'text-bg-danger',
                            'PENDING' => 'text-bg-warning',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <tr>
                            <?php if ($isAdmin): ?>
                                <td>
                                    <div class="fw-medium">
                                        <?= $escape(trim(
                                            ($request['first_name'] ?? '') . ' '
                                            . ($request['last_name'] ?? '')
                                        )) ?>
                                    </div>
                                    <small class="text-muted"><?= $escape($request['employee_number'] ?? '') ?></small>
                                </td>
                            <?php endif; ?>
                            <td><?= $escape($request['attendance_date'] ?? '') ?></td>
                            <td class="text-break" style="min-width: 180px">
                                <?= nl2br($escape($request['reason'] ?? '')) ?>
                                <?php if (!empty($request['review_comment'])): ?>
                                    <div class="small text-muted mt-1">
                                        Review: <?= nl2br($escape($request['review_comment'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $escape($request['requested_clock_in'] ?? '—') ?></td>
                            <td><?= $escape($request['requested_clock_out'] ?? '—') ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $escape($request['status_name'] ?? '') ?></span></td>
                            <td><?= $escape($request['created_at'] ?? '') ?></td>
                            <?php if ($isAdmin): ?>
                                <td>
                                    <?php if (
                                        ($request['status_code'] ?? '') === 'PENDING'
                                        && ($canApprove || $canReject)
                                    ): ?>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= APP_BASE_PATH ?>/attendance/corrections/<?= (int) $request['id'] ?>">
                                            Review
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($requests === []): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 8 : 6 ?>" class="text-center text-muted py-4">
                                No attendance correction requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!$isAdmin && $canRequest): ?>
        <p class="form-text mt-3">
            To request a correction, open your attendance history and choose the record to correct.
        </p>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$pageTitle = $isAdmin ? 'Attendance Correction Requests' : 'My Correction Requests';
require __DIR__ . '/../layout/app.php';
