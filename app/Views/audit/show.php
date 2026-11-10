<?php

$auditLog = $auditLog ?? [];
$actionLabels = [
    'ORGANIZATION_DETAILS_UPDATED' => 'Organization details updated',
    'ORGANIZATION_LOGO_UPDATED' => 'Organization logo updated',
    'ORGANIZATION_LOGO_REMOVED' => 'Organization logo removed',
    'ORGANIZATION_DELETED' => 'Organization permanently deleted',
    'AUDIT_LOG_DELETED' => 'Audit log deleted',
    'AUDIT_LOGS_CLEARED' => 'Audit logs cleared',
];

ob_start();

?>

<div class="container py-5">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Audit Log Details
            </h2>

            <p class="text-muted mb-0">
                View details of this system activity.
            </p>

        </div>

        <a
            href="<?= APP_BASE_PATH ?>/audit"
            class="btn btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Audit Logs
        </a>

    </div>


    <!-- Main Information -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-dark text-white">

            <i class="bi bi-shield-check"></i>
            Activity Information

        </div>

        <div class="card-body">

            <div class="row g-4">

                <!-- Action -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Action
                    </label>

                    <div>
                        <span class="badge bg-primary fs-6">

                            <?= htmlspecialchars(
                                $actionLabels[$auditLog['action'] ?? '']
                                    ?? ($auditLog['action'] ?? 'N/A')
                            ) ?>

                        </span>
                    </div>

                </div>


                <!-- Module -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Module
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $auditLog['module'] ?? 'N/A'
                        ) ?>

                    </p>

                </div>


                <!-- User -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        User
                    </label>

                    <p class="mb-0">

                        <?php if (!empty($auditLog['username'])): ?>

                            <i class="bi bi-person"></i>

                            <?= htmlspecialchars(
                                $auditLog['username']
                            ) ?>

                        <?php else: ?>

                            <span class="text-muted">
                                System / Unknown
                            </span>

                        <?php endif; ?>

                    </p>

                </div>


                <!-- Organization -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Organization ID
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            (string) (
                                $auditLog['organization_id']
                                    ?? 'N/A'
                            )
                        ) ?>

                    </p>

                </div>


                <!-- Entity Type -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Entity Type
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $auditLog['entity_type']
                                ?? 'N/A'
                        ) ?>

                    </p>

                </div>


                <!-- Entity ID -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Entity ID
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            (string) (
                                $auditLog['entity_id']
                                    ?? 'N/A'
                            )
                        ) ?>

                    </p>

                </div>


                <!-- IP Address -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        IP Address
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $auditLog['ip_address']
                                ?? 'N/A'
                        ) ?>

                    </p>

                </div>


                <!-- Created At -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Date & Time
                    </label>

                    <p class="mb-0">

                        <?= htmlspecialchars(
                            $auditLog['created_at']
                                ?? 'N/A'
                        ) ?>

                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- Old Values -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <i class="bi bi-arrow-counterclockwise"></i>
            Previous Values

        </div>

        <div class="card-body">

            <?php if (!empty($auditLog['old_values'])): ?>

                <?php
                $oldValues = json_decode(
                    $auditLog['old_values'],
                    true
                );
                ?>

                <?php if (is_array($oldValues)): ?>

                    <pre class="bg-light border rounded p-3 mb-0"><?= htmlspecialchars(
                        json_encode(
                            $oldValues,
                            JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        )
                    ) ?></pre>

                <?php else: ?>

                    <pre class="bg-light border rounded p-3 mb-0"><?= htmlspecialchars(
                        $auditLog['old_values']
                    ) ?></pre>

                <?php endif; ?>

            <?php else: ?>

                <p class="text-muted mb-0">
                    No previous values recorded.
                </p>

            <?php endif; ?>

        </div>

    </div>


    <!-- New Values -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <i class="bi bi-arrow-right-circle"></i>
            New Values

        </div>

        <div class="card-body">

            <?php if (!empty($auditLog['new_values'])): ?>

                <?php
                $newValues = json_decode(
                    $auditLog['new_values'],
                    true
                );
                ?>

                <?php if (is_array($newValues)): ?>

                    <pre class="bg-light border rounded p-3 mb-0"><?= htmlspecialchars(
                        json_encode(
                            $newValues,
                            JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        )
                    ) ?></pre>

                <?php else: ?>

                    <pre class="bg-light border rounded p-3 mb-0"><?= htmlspecialchars(
                        $auditLog['new_values']
                    ) ?></pre>

                <?php endif; ?>

            <?php else: ?>

                <p class="text-muted mb-0">
                    No new values recorded.
                </p>

            <?php endif; ?>

        </div>

    </div>


    <!-- Technical Information -->

    <div class="card shadow-sm">

        <div class="card-header">

            <i class="bi bi-pc-display"></i>
            Technical Information

        </div>

        <div class="card-body">

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    User Agent
                </label>

                <div class="bg-light border rounded p-3">

                    <small>

                        <?= htmlspecialchars(
                            $auditLog['user_agent']
                                ?? 'N/A'
                        ) ?>

                    </small>

                </div>

            </div>

            <div>

                <label class="form-label fw-semibold">
                    Audit Log ID
                </label>

                <p class="mb-0">

                    <?= htmlspecialchars(
                        (string) (
                            $auditLog['id']
                                ?? 'N/A'
                        )
                    ) ?>

                </p>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();
$pageTitle = 'Audit Logs';

require __DIR__ . '/../layout/app.php';

?>
