<?php

$leaveRequest = $leaveRequest ?? [];
$approvals = $approvals ?? [];

$statusCode = strtoupper($leaveRequest['status_code'] ?? '');

$statusBadgeClass = static function ($code) {
    return match (strtoupper($code ?? '')) {
        'APPROVED' => 'bg-success',
        'REJECTED' => 'bg-danger',
        'CANCELLED' => 'bg-secondary',
        default => 'bg-warning text-dark',
    };
};

$employeeName = trim(
    ($leaveRequest['first_name'] ?? '') . ' ' .
    ($leaveRequest['middle_name'] ?? '') . ' ' .
    ($leaveRequest['last_name'] ?? '')
);

$badgeClass = $statusBadgeClass($statusCode);

ob_start();
?>

<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Leave Request Details</h2>
        <p class="text-muted mb-0">
            View request information and approval history.
        </p>
    </div>

    <a href="<?= APP_BASE_PATH ?>/leave" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Back to Leave
    </a>
</div>

<!-- Request Information -->
<div class="container-fluid card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Request Information</h5>
    </div>

    <div class="card-body">
        <div class="row g-4">

            <div class="col-md-6">
                <label class="form-label text-muted">Employee</label>
                <div class="fw-semibold">
                    <?= htmlspecialchars($employeeName ?: 'N/A') ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Employee Number</label>
                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $leaveRequest['employee_number'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Employee Email</label>
                <div>
                    <?= htmlspecialchars(
                        $leaveRequest['employee_email'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Leave Type</label>
                <div class="fw-semibold">
                    <?= htmlspecialchars(
                        $leaveRequest['leave_type'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Start Date</label>
                <div>
                    <?= htmlspecialchars(
                        $leaveRequest['start_date'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">End Date</label>
                <div>
                    <?= htmlspecialchars(
                        $leaveRequest['end_date'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Status</label>
                <div>
                    <span class="badge <?= $badgeClass ?>">
                        <?= htmlspecialchars(
                            $leaveRequest['status_name'] ?? 'Unknown'
                        ) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label text-muted">Requested At</label>
                <div>
                    <?= htmlspecialchars(
                        $leaveRequest['created_at'] ?? 'N/A'
                    ) ?>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label text-muted">Reason</label>
                <div class="border rounded p-3 bg-light">
                    <?php if (!empty($leaveRequest['reason'])): ?>
                        <?= nl2br(
                            htmlspecialchars($leaveRequest['reason'])
                        ) ?>
                    <?php else: ?>
                        <span class="text-muted">No reason provided.</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Leave Decision -->
<?php if ($statusCode === 'PENDING' && !empty($canMakeLeaveDecision)): ?>
    <div class="container-fluid card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Leave Decision</h5>
        </div>

        <div class="card-body">
            <p class="text-muted">
                Review this leave request and choose an action.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <button
                    type="button"
                    class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#approveModal"
                >
                    <i class="bi bi-check-circle me-1"></i>
                    Approve
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#rejectModal"
                >
                    <i class="bi bi-x-circle me-1"></i>
                    Reject
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Approval History -->
<div class="container-fluid card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Approval/Rejection History</h5>
    </div>

    <div class="card-body">
        <?php if (empty($approvals)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history fs-3"></i>
                <p class="mb-0 mt-2">
                    No approval actions have been recorded yet.
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Approver</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($approvals as $approval): ?>
                            <?php
                            $actionCode = strtoupper(
                                $approval['action_code'] ?? ''
                            );

                            $actionBadge = $statusBadgeClass($actionCode);
                            ?>

                            <tr>
                                <td>
                                    <span class="badge <?= $actionBadge ?>">
                                        <?= htmlspecialchars(
                                            $approval['action_name'] ?? 'Unknown'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $approval['approver_username'] ?? 'N/A'
                                        ) ?>
                                    </div>

                                    <?php if (!empty($approval['approver_email'])): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(
                                                $approval['approver_email']
                                            ) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($approval['comment'])): ?>
                                        <?= nl2br(
                                            htmlspecialchars($approval['comment'])
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No comment</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $approval['created_at'] ?? 'N/A'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($statusCode === 'PENDING'): ?>

    <!-- Approve Modal -->
    <div
        class="modal fade"
        id="approveModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Leave Request</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <form
                    method="POST"
                    action="<?= APP_BASE_PATH ?>/leave/<?= (int) $leaveRequest['id'] ?>/approve"
                >
                    <div class="modal-body">
                        <p>
                            Are you sure you want to approve this leave request?
                        </p>

                        <label for="approve_comment" class="form-label">
                            Comment <span class="text-muted">(Optional)</span>
                        </label>

                        <textarea
                            name="comment"
                            id="approve_comment"
                            class="form-control"
                            rows="3"
                            placeholder="Enter an optional comment..."
                        ></textarea>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Approve Leave
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div
        class="modal fade"
        id="rejectModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <form
                    method="POST"
                    action="<?= APP_BASE_PATH ?>/leave/<?= (int) $leaveRequest['id'] ?>/reject"
                >
                    <div class="modal-body">
                        <p>
                            Are you sure you want to reject this leave request?
                        </p>

                        <label for="reject_comment" class="form-label">
                            Reason / Comment
                            <span class="text-muted">(Optional)</span>
                        </label>

                        <textarea
                            name="comment"
                            id="reject_comment"
                            class="form-control"
                            rows="3"
                            placeholder="Enter the reason for rejection..."
                        ></textarea>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>
                            Reject Leave
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php

$content = ob_get_clean();
$pageTitle = 'Leave Request Details';

require __DIR__ . '/../layout/app.php';