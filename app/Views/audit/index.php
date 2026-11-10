<?php

$auditLogs = $auditLogs ?? [];
$actionLabels = [
    'ORGANIZATION_DETAILS_UPDATED' => 'Organization details updated',
    'ORGANIZATION_LOGO_UPDATED' => 'Organization logo updated',
    'ORGANIZATION_LOGO_REMOVED' => 'Organization logo removed',
    'ORGANIZATION_DELETED' => 'Organization permanently deleted',
    'AUDIT_LOG_DELETED' => 'Audit log deleted',
    'AUDIT_LOGS_CLEARED' => 'Audit logs cleared',
];

$canDelete = (bool) ($canDelete ?? false);
$csrfToken = (string) ($csrfToken ?? '');

ob_start();

?>

<div class="container py-5">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Audit Logs</h2>
            <p class="text-muted mb-0">
                Monitor important activities performed within the system.
            </p>
        </div>

        <?php if ($canDelete): ?>
            <form
                method="POST"
                action="<?= APP_BASE_PATH ?>/audit/empty"
                onsubmit="return confirm('WARNING: This will permanently delete ALL audit logs for your organization. Continue?');"
            >
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i>
                    Empty All Logs
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Audit Log Table -->

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>User</th>

                            <th>Action</th>

                            <th>Module</th>

                            <th>Entity</th>

                            <th>IP Address</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($auditLogs)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center text-muted py-4"
                            >
                                No audit records found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($auditLogs as $log): ?>

                            <tr>

                                <!-- Date -->

                                <td>

                                    <?= htmlspecialchars(
                                        $log['created_at']
                                            ?? 'N/A'
                                    ) ?>

                                </td>

                                <!-- User -->

                                <td>

                                    <?= htmlspecialchars(
                                        $log['username']
                                            ?? 'System'
                                    ) ?>

                                </td>

                                <!-- Action -->

                                <td>

                                    <span class="badge bg-primary">

                                        <?= htmlspecialchars(
                                            $actionLabels[$log['action'] ?? '']
                                                ?? ($log['action'] ?? 'N/A')
                                        ) ?>

                                    </span>

                                </td>

                                <!-- Module -->

                                <td>

                                    <?= htmlspecialchars(
                                        $log['module']
                                            ?? 'N/A'
                                    ) ?>

                                </td>

                                <!-- Entity -->

                                <td>

                                    <?php

                                    $entityType =
                                        $log['entity_type']
                                            ?? null;

                                    $entityId =
                                        $log['entity_id']
                                            ?? null;

                                    ?>

                                    <?php if ($entityType): ?>

                                        <?= htmlspecialchars(
                                            $entityType
                                        ) ?>

                                        <?php if ($entityId): ?>

                                            #<?= htmlspecialchars(
                                                $entityId
                                            ) ?>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        N/A

                                    <?php endif; ?>

                                </td>

                                <!-- IP Address -->

                                <td>

                                    <?= htmlspecialchars(
                                        $log['ip_address']
                                            ?? 'N/A'
                                    ) ?>

                                </td>

                                <!-- View -->

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a
                                            href="<?= APP_BASE_PATH ?>/audit/<?= (int) $log['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="View Audit Log"
                                        >
                                            <i class="bi bi-eye"></i>
                                             View
                                        </a>

                                        <?php if ($canDelete): ?>
                                            <form
                                                method="POST"
                                                action="<?= APP_BASE_PATH ?>/audit/delete/<?= (int) $log['id'] ?>"
                                                onsubmit="return confirm('Permanently delete this audit log?');"
                                            >
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> 
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();
$pageTitle = 'Audit Logs';

require __DIR__ . '/../layout/app.php';

?>