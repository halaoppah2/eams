<?php

namespace App\Controllers;

use App\HttpException;
use App\Repositories\AuditRepository;
use App\Services\AuditService;
use App\Services\AuditAccessService;
use App\Session;
use RuntimeException;

class AuditController
{
    private AuditRepository $auditRepository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->auditRepository = new AuditRepository();
        $this->auditService = new AuditService($this->auditRepository);
    }

    /**
     * Display all audit logs for the current organization.
     */
    public function index(): void
    {
        $this->requireAdmin();
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $auditLogs =
            $this
                ->auditRepository
                ->getAll($organizationId);

        [$csrfToken, $canDelete] = $this->prepareIndexView();
        require __DIR__ . '/../Views/audit/index.php';
    }

    /**
     * Display one audit log.
     */
    public function show(int $id): void
    {
        $this->requireAdmin();
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $auditLog =
            $this
                ->auditRepository
                ->getById(
                    $id,
                    $organizationId
                );

        if (!$auditLog) {
            throw new RuntimeException(
                'Audit log not found.'
            );
        }

        require __DIR__ . '/../Views/audit/show.php';
    }

    /**
     * Display audit logs created by a specific user.
     */
    public function byUser(int $userId): void
    {
        $this->requireAdmin();
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid user.'
            );
        }

        $auditLogs =
            $this
                ->auditRepository
                ->getByUser(
                    $userId,
                    $organizationId
                );

        [$csrfToken, $canDelete] = $this->prepareIndexView();
        require __DIR__ . '/../Views/audit/index.php';
    }

    /**
     * Display audit logs for a specific module.
     */
    public function byModule(string $module): void
    {
        $this->requireAdmin();
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $module = trim($module);

        if ($module === '') {
            throw new RuntimeException(
                'Invalid module.'
            );
        }

        $auditLogs =
            $this
                ->auditRepository
                ->getByModule(
                    $module,
                    $organizationId
                );

        [$csrfToken, $canDelete] = $this->prepareIndexView();
        require __DIR__ . '/../Views/audit/index.php';
    }

    /**
     * Display audit logs for a specific entity.
     */
    public function byEntity(
        string $entityType,
        int $entityId
    ): void {
        $this->requireAdmin();
        $organizationId =
            (int) (
                $_SESSION['user']['organization_id']
                    ?? 0
            );

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $entityType = trim($entityType);

        if ($entityType === '') {
            throw new RuntimeException(
                'Invalid entity type.'
            );
        }

        if ($entityId <= 0) {
            throw new RuntimeException(
                'Invalid entity.'
            );
        }

        $auditLogs =
            $this
                ->auditRepository
                ->getByEntity(
                    $entityType,
                    $entityId,
                    $organizationId
                );

        [$csrfToken, $canDelete] = $this->prepareIndexView();
        require __DIR__ . '/../Views/audit/index.php';
    }

    
    /**
     * Delete one audit log.
     * Admins only.
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();
        $this->verifyCsrfToken();

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0 || $id <= 0) {
            throw new RuntimeException('Invalid request.');
        }

        $deleted = $this->auditRepository->deleteById(
            $id,
            $organizationId
        );

        if ($deleted) {
            $this->auditService->recordAction(
                'AUDIT_LOG_DELETED',
                'audit',
                'audit_log',
                $id,
                ['deleted_log_id' => $id]
            );
        }

        $_SESSION['flash'] = [
            'type' => $deleted ? 'success' : 'danger',
            'message' => $deleted
                ? 'Audit log deleted successfully.'
                : 'Audit log not found.'
        ];

        header('Location: ' . APP_BASE_PATH . '/audit');
        exit;
    }

    /**
     * Delete all audit logs for the current organization.
     * Admins only.
     */
    public function emptyAll(): void
    {
        $this->requireAdmin();
        $this->verifyCsrfToken();

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $deletedCount = $this->auditRepository->deleteAll(
            $organizationId
        );

        $this->auditService->recordAction(
            'AUDIT_LOGS_CLEARED',
            'audit',
            'audit_logs',
            null,
            null,
            ['deleted_count' => $deletedCount]
        );

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => "Audit logs cleared. Records deleted: {$deletedCount}. This cleanup action was retained."
        ];

        header('Location: ' . APP_BASE_PATH . '/audit');
        exit;
    }
   
    private function requireAdmin(): void
    {
        if (!AuditAccessService::canView()) {
            throw new HttpException(
                'Only organization administrators can access audit logs.',
                403
            );
        }
    }

    /** @return array{string, bool} */
    private function prepareIndexView(): array
    {
        $csrfToken = Session::get('audit_csrf_token');
        if (!is_string($csrfToken) || $csrfToken === '') {
            $csrfToken = bin2hex(random_bytes(32));
            Session::set('audit_csrf_token', $csrfToken);
        }

        $canDelete = AuditAccessService::canDelete();

        return [$csrfToken, $canDelete];
    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('audit_csrf_token');
        $submittedToken = $_POST['_csrf_token'] ?? null;
        if (
            !is_string($sessionToken)
            || !is_string($submittedToken)
            || !hash_equals($sessionToken, $submittedToken)
        ) {
            throw new RuntimeException(
                'This form has expired. Reload the page and try again.'
            );
        }
    }

}
