<?php

/** @var list<array{id: int|string, name: string, holiday_date: string, end_date: string, is_recurring: int|string}> $holidays */
/** @var bool $canCreate */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $csrfToken */
$escape = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-semibold mb-1">Holidays</h2>
            <p class="text-muted mb-0">
                Organization holidays and annual observances.
            </p>
        </div>
        <?php if ($canCreate): ?>
            <a href="<?= APP_BASE_PATH ?>/holidays/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                Add Holiday
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Holiday</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Repeats</th>
                        <?php if ($canUpdate || $canDelete): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holidays as $holiday): ?>
                        <?php $recurring = (int) $holiday['is_recurring'] === 1; ?>
                        <tr>
                            <td class="fw-medium"><?= $escape($holiday['name']) ?></td>
                            <?php
                            $startTimestamp = strtotime(
                                (string) $holiday['holiday_date']
                            );
                            $endTimestamp = strtotime(
                                (string) $holiday['end_date']
                            );
                            $dateFormat = $recurring ? 'F j' : 'F j, Y';
                            ?>
                            <td><?= $escape(date($dateFormat, $startTimestamp)) ?></td>
                            <td><?= $escape(date($dateFormat, $endTimestamp)) ?></td>
                            <td><?= $recurring ? 'Annually' : 'No' ?></td>
                            <?php if ($canUpdate || $canDelete): ?>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if ($canUpdate): ?>
                                            <a
                                                href="<?= APP_BASE_PATH ?>/holidays/<?= (int) $holiday['id'] ?>/edit"
                                                class="btn btn-sm btn-outline-secondary"
                                            >Edit</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <form
                                                method="POST"
                                                action="<?= APP_BASE_PATH ?>/holidays/<?= (int) $holiday['id'] ?>/delete"
                                                onsubmit="return confirm('Delete this holiday? This cannot be undone.');"
                                            >
                                                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($holidays === []): ?>
                        <tr>
                            <td colspan="<?= 4 + ($canUpdate || $canDelete ? 1 : 0) ?>" class="text-center text-muted py-4">
                                No holidays have been added for this organization.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Holidays';
require __DIR__ . '/../layout/app.php';
