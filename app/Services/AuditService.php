<?php

namespace App\Services;

use App\Repositories\AuditRepository;

class AuditService
{
    private AuditRepository $auditRepository;

    public function __construct(
        AuditRepository $auditRepository
    ) {
        $this->auditRepository =
            $auditRepository;
    }

    /**
     * Record an audit event.
     */
    public function record(
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): int {

        $user =
            $_SESSION['user']
                ?? [];

        $organizationId =
            isset($user['organization_id'])
                ? (int) $user['organization_id']
                : null;

        $userId =
            isset($user['id'])
                ? (int) $user['id']
                : null;

        $ipAddress =
            $_SERVER['REMOTE_ADDR']
                ?? null;

        $userAgent =
            $_SERVER['HTTP_USER_AGENT']
                ?? null;

        return $this
            ->auditRepository
            ->create(
                $organizationId,
                $userId,
                $action,
                $module,
                $entityType,
                $entityId,
                $oldValues,
                $newValues,
                $ipAddress,
                $userAgent
            );
    }

    /**
     * Record a create action.
     */
    public function recordCreate(
        string $module,
        string $entityType,
        int $entityId,
        ?array $newValues = null
    ): int {

        return $this->record(
            strtoupper($entityType) . '_CREATED',
            $module,
            $entityType,
            $entityId,
            null,
            $newValues
        );
    }

    /**
     * Record an update action.
     */
    public function recordUpdate(
        string $module,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): int {

        return $this->record(
            strtoupper($entityType) . '_UPDATED',
            $module,
            $entityType,
            $entityId,
            $oldValues,
            $newValues
        );
    }

    /**
     * Record a delete action.
     */
    public function recordDelete(
        string $module,
        string $entityType,
        int $entityId,
        ?array $oldValues = null
    ): int {

        return $this->record(
            strtoupper($entityType) . '_DELETED',
            $module,
            $entityType,
            $entityId,
            $oldValues,
            null
        );
    }

    /**
     * Record a custom action.
     */
    public function recordAction(
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): int {

        return $this->record(
            strtoupper($action),
            $module,
            $entityType,
            $entityId,
            $oldValues,
            $newValues
        );
    }

    /**
     * Get all audit logs for an organization.
     */
    public function getAll(
        int $organizationId
    ): array {

        return $this
            ->auditRepository
            ->getAll(
                $organizationId
            );
    }

    /**
     * Get one audit log.
     */
    public function getById(
        int $id,
        int $organizationId
    ): ?array {

        return $this
            ->auditRepository
            ->getById(
                $id,
                $organizationId
            );
    }

    /**
     * Get logs by user.
     */
    public function getByUser(
        int $userId,
        int $organizationId
    ): array {

        return $this
            ->auditRepository
            ->getByUser(
                $userId,
                $organizationId
            );
    }

    /**
     * Get logs by module.
     */
    public function getByModule(
        string $module,
        int $organizationId
    ): array {

        return $this
            ->auditRepository
            ->getByModule(
                $module,
                $organizationId
            );
    }

    /**
     * Get logs by entity.
     */
    public function getByEntity(
        string $entityType,
        int $entityId,
        int $organizationId
    ): array {

        return $this
            ->auditRepository
            ->getByEntity(
                $entityType,
                $entityId,
                $organizationId
            );
    }

    
    /**
     * Delete one audit log.
     */
    public function deleteById(
        int $id,
        int $organizationId
    ): bool {
        return $this->auditRepository->deleteById(
            $id,
            $organizationId
        );
    }

    /**
     * Delete all audit logs for an organization.
     */
    public function deleteAll(
        int $organizationId
    ): int {
        return $this->auditRepository->deleteAll(
            $organizationId
        );
    }

}

