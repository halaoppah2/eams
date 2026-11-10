<?php

use App\Services\PermissionService;

$leaveRequests = $leaveRequests ?? [];

$statusBadgeClass = static function ($statusCode) {
    return match (strtoupper($statusCode ?? '')) {
        'APPROVED' => 'bg-success',
        'REJECTED' => 'bg-danger',
        'CANCELLED' => 'bg-secondary',
        default => 'bg-warning text-dark',
    };
};

ob_start();
?>

<div class=" container d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Leave Management</h2>
        <p class="text-muted mb-0">Manage employee leave requests.</p>
    </div>

    <a href="<?= APP_BASE_PATH ?>/leave/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>
        Request Leave
    </a>
</div>

<div class="container card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Leave Requests</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($leaveRequests)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                No leave requests found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveRequests as $request): ?>
                            <?php
                            $employeeName = trim(
                                ($request['first_name'] ?? '') . ' ' .
                                ($request['middle_name'] ?? '') . ' ' .
                                ($request['last_name'] ?? '')
                            );

                            $badgeClass = $statusBadgeClass(
                                $request['status_code'] ?? ''
                            );
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $request['employee_number'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($employeeName ?: 'N/A') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $request['leave_type'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $request['start_date'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $request['end_date'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars(
                                            $request['status_name'] ?? 'Unknown'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $request['created_at'] ?? 'N/A'
                                    ) ?>
                                </td>

                                <td class="text-center">
                                    <a
                                        href="<?= APP_BASE_PATH ?>/leave/<?= (int) $request['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="View Leave Request"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();
$pageTitle = 'Leave Management';

require __DIR__ . '/../layout/app.php';