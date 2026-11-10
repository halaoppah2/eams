<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use RuntimeException;

class NotificationService
{
    private NotificationRepository $notificationRepository;

    public function __construct(
        NotificationRepository $notificationRepository
    ) {
        $this->notificationRepository = $notificationRepository;
    }

    public function getTypes(): array
    {
        return $this->notificationRepository->getTypes();
    }

    public function getTypeByCode(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        return $this->notificationRepository->getTypeByCode($code);
    }

    /**
     * Create a notification and assign recipients atomically.
     */
    public function create(
        int $organizationId,
        string $typeCode,
        string $title,
        string $message,
        ?int $createdBy = null,
        array $recipientIds = []
    ): int {
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $typeCode = trim($typeCode);
        $title = trim($title);
        $message = trim($message);

        if ($typeCode === '' || $title === '' || $message === '') {
            throw new RuntimeException(
                'Notification type, title and message are required.'
            );
        }

        $recipientIds = array_values(array_unique(
            array_filter(
                array_map('intval', $recipientIds),
                fn ($id) => $id > 0
            )
        ));

        if (empty($recipientIds)) {
            throw new RuntimeException(
                'Select at least one recipient.'
            );
        }

        $type = $this->notificationRepository
            ->getTypeByCode($typeCode);

        if (!$type) {
            throw new RuntimeException('Notification type not found.');
        }

        // The repository and service share the same DB connection.
        $db = \App\Database::connection();

        try {
            $db->beginTransaction();

            $notificationId = $this->notificationRepository->create(
                $organizationId,
                (int) $type['id'],
                $title,
                $message,
                $createdBy
            );

            $this->notificationRepository->addRecipients(
                $notificationId,
                $organizationId,
                $recipientIds
            );

            $db->commit();

            return $notificationId;

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }


    /**
     * Create a notification for specified recipients
     * using the authenticated user's account.
     */
    public function createForCurrentUser(
        string $typeCode,
        string $title,
        string $message,
        array $recipientIds = []
    ): int {
        $user = $_SESSION['user'] ?? [];

        $organizationId = (int) ($user['organization_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);

        if ($organizationId <= 0 || $userId <= 0) {
            throw new RuntimeException('Invalid authenticated user.');
        }

        return $this->create(
            $organizationId,
            $typeCode,
            $title,
            $message,
            $userId,
            $recipientIds
        );
    }

    /**
     * Get one notification addressed to the current user.
     */
    public function getById(
        int $id,
        int $organizationId
    ): ?array {
        $user = $_SESSION['user'] ?? [];
        $userId = (int) ($user['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        if ($organizationId <= 0 || $userId <= 0) {
            throw new RuntimeException('Invalid user or organization.');
        }

        return $this->notificationRepository->getById(
            $id,
            $organizationId,
            $userId
        );
    }

    public function getForCurrentUser(): array
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        return $this->notificationRepository->getForUser(
            $userId,
            $organizationId
        );
    }

    public function getUnreadForCurrentUser(): array
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        return $this->notificationRepository->getUnreadForUser(
            $userId,
            $organizationId
        );
    }

    public function countUnreadForCurrentUser(): int
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        return $this->notificationRepository->countUnread(
            $userId,
            $organizationId
        );
    }

    public function countForCurrentUser(): int
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        return $this->notificationRepository->countForUser(
            $userId,
            $organizationId
        );
    }

    public function markAsRead(int $notificationId): void
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        if ($notificationId <= 0) {
            throw new RuntimeException('Invalid notification.');
        }

        $notification = $this->notificationRepository->getById(
            $notificationId,
            $organizationId,
            $userId
        );

        if (!$notification) {
            throw new RuntimeException('Notification not found.');
        }

        $this->notificationRepository->markAsRead(
            $notificationId,
            $userId,
            $organizationId
        );
    }

    public function markAllAsRead(): void
    {
        [$userId, $organizationId] = $this->getCurrentUserContext();

        $this->notificationRepository->markAllAsRead(
            $userId,
            $organizationId
        );
    }

    /**
     * Validate and return the current user's ID and organization ID.
     */
    private function getCurrentUserContext(): array
    {
        $user = $_SESSION['user'] ?? [];

        $userId = (int) ($user['id'] ?? 0);
        $organizationId = (int) ($user['organization_id'] ?? 0);

        if ($userId <= 0) {
            throw new RuntimeException('Invalid user.');
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        return [$userId, $organizationId];
    }
}
