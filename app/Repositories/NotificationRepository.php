<?php

namespace App\Repositories;

use App\Database;
use PDO;

class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Create a notification.
     */
    public function create(
        int $organizationId,
        int $typeId,
        string $title,
        string $message,
        ?int $createdBy = null
    ): int {
        $sql = '
            INSERT INTO notifications (
                organization_id,
                type_id,
                title,
                message,
                created_by
            )
            SELECT
                o.id,
                :type_id,
                :title,
                :message,
                :created_by
            FROM organizations o
            WHERE o.id = :organization_id
              AND (
                    :creator_check IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM users u
                        WHERE u.id = :creator_id
                          AND u.organization_id = o.id
                          AND u.deleted_at IS NULL
                    )
              )
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'organization_id' => $organizationId,
            'type_id' => $typeId,
            'title' => $title,
            'message' => $message,
            'created_by' => $createdBy,
            'creator_check' => $createdBy,
            'creator_id' => $createdBy,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \RuntimeException(
                'The notification creator must belong to its organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Assign a notification to one or more users.
     */
   
    /**
     * Assign recipients after validating organization membership.
     */
    public function addRecipients(
        int $notificationId,
        int $organizationId,
        array $userIds
    ): void {
        $sql = '
            SELECT COUNT(*)
            FROM users u
            INNER JOIN user_statuses us
                ON us.id = u.status_id
            WHERE u.id = :user_id
              AND u.organization_id = :organization_id
              AND u.deleted_at IS NULL
              AND us.code = :active_status
        ';

        $checkStmt = $this->db->prepare($sql);

        $insertStmt = $this->db->prepare('
            INSERT INTO notification_recipients (
                notification_id,
                user_id
            )
            SELECT n.id, u.id
            FROM notifications n
            INNER JOIN users u
                ON u.id = :recipient_id
               AND u.organization_id = n.organization_id
               AND u.deleted_at IS NULL
            WHERE n.id = :notification_id
              AND n.organization_id = :notification_organization_id
        ');

        $userIds = array_values(array_unique(
            array_map('intval', $userIds)
        ));

        if (empty($userIds)) {
            throw new \RuntimeException(
                'At least one recipient is required.'
            );
        }

        foreach ($userIds as $userId) {
            if ($userId <= 0) {
                throw new \RuntimeException(
                    'Invalid recipient.'
                );
            }

            $checkStmt->execute([
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'active_status' => 'ACTIVE'
            ]);

            if ((int) $checkStmt->fetchColumn() !== 1) {
                throw new \RuntimeException(
                    'A recipient is inactive or does not belong to this organization.'
                );
            }

            $insertStmt->execute([
                'notification_id' => $notificationId,
                'notification_organization_id' => $organizationId,
                'recipient_id' => $userId,
            ]);

            if ($insertStmt->rowCount() !== 1) {
                throw new \RuntimeException(
                    'The notification does not belong to this organization.'
                );
            }
        }
    }

    /**
     * Get all notifications addressed to a user.
     */
    public function getForUser(
        int $userId,
        int $organizationId
    ): array {
        $sql = '
            SELECT
                n.id,
                n.organization_id,
                n.type_id,
                nt.code AS type_code,
                nt.name AS type_name,
                n.title,
                n.message,
                n.created_by,
                n.created_at,
                nr.read_at

            FROM notification_recipients recipients

            INNER JOIN notifications n
                ON n.id = recipients.notification_id
               AND n.organization_id = :notification_organization_id
            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL

            INNER JOIN notification_types nt
                ON nt.id = n.type_id

            LEFT JOIN notification_reads nr
                ON nr.notification_id = n.id
                AND nr.user_id = recipients.user_id

            WHERE recipients.user_id = :user_id
              AND n.organization_id = :organization_id

            ORDER BY n.created_at DESC, n.id DESC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'notification_organization_id' => $organizationId,
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notifications addressed to a user.
     */
    public function getUnreadForUser(
        int $userId,
        int $organizationId
    ): array {
        $sql = '
            SELECT
                n.id,
                n.organization_id,
                n.type_id,
                nt.code AS type_code,
                nt.name AS type_name,
                n.title,
                n.message,
                n.created_by,
                n.created_at

            FROM notification_recipients recipients

            INNER JOIN notifications n
                ON n.id = recipients.notification_id
            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL

            INNER JOIN notification_types nt
                ON nt.id = n.type_id

            LEFT JOIN notification_reads nr
                ON nr.notification_id = n.id
                AND nr.user_id = recipients.user_id

            WHERE recipients.user_id = :user_id
              AND n.organization_id = :organization_id
              AND nr.id IS NULL

            ORDER BY n.created_at DESC, n.id DESC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all notifications addressed to a user.
     */
    public function countForUser(
        int $userId,
        int $organizationId
    ): int {
        $sql = '
            SELECT COUNT(*)
            FROM notification_recipients recipients
            INNER JOIN notifications n
                ON n.id = recipients.notification_id
            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL
            WHERE recipients.user_id = :user_id
              AND n.organization_id = :organization_id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count unread notifications addressed to a user.
     */
    public function countUnread(
        int $userId,
        int $organizationId
    ): int {
        $sql = '
            SELECT COUNT(*)

            FROM notification_recipients recipients

            INNER JOIN notifications n
                ON n.id = recipients.notification_id
            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL

            LEFT JOIN notification_reads nr
                ON nr.notification_id = n.id
                AND nr.user_id = recipients.user_id

            WHERE recipients.user_id = :user_id
              AND n.organization_id = :organization_id
              AND nr.id IS NULL
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get a notification only if it is addressed to the user.
     */
    public function getById(
        int $notificationId,
        int $organizationId,
        int $userId
    ): ?array {
        $sql = '
            SELECT
                n.id,
                n.organization_id,
                n.type_id,
                nt.code AS type_code,
                nt.name AS type_name,
                n.title,
                n.message,
                n.created_by,
                n.created_at

            FROM notifications n

            INNER JOIN notification_types nt
                ON nt.id = n.type_id

            INNER JOIN notification_recipients recipients
                ON recipients.notification_id = n.id
                AND recipients.user_id = :user_id
            INNER JOIN users u
                ON u.id = recipients.user_id
               AND u.organization_id = n.organization_id
               AND u.deleted_at IS NULL

            WHERE n.id = :id
              AND n.organization_id = :organization_id

            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'id' => $notificationId,
            'organization_id' => $organizationId
        ]);

        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        return $notification ?: null;
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(
        int $notificationId,
        int $userId,
        int $organizationId
    ): void {
        $sql = '
            INSERT INTO notification_reads (
                notification_id,
                user_id
            )
            SELECT
                recipients.notification_id,
                recipients.user_id

            FROM notification_recipients recipients

            INNER JOIN notifications n
                ON n.id = recipients.notification_id
            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL
               AND n.organization_id = :organization_id

            WHERE recipients.notification_id = :notification_id
              AND recipients.user_id = :user_id
              AND EXISTS (
                  SELECT 1
                  FROM users u
                  WHERE u.id = recipients.user_id
                    AND u.organization_id = n.organization_id
                    AND u.organization_id = :recipient_organization_id
                    AND u.deleted_at IS NULL
              )

              AND NOT EXISTS (
                  SELECT 1
                  FROM notification_reads existing
                  WHERE existing.notification_id =
                        recipients.notification_id
                    AND existing.user_id = recipients.user_id
              )
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'recipient_organization_id' => $organizationId,
        ]);
    }

    /**
     * Mark all notifications addressed to a user as read.
     */
    public function markAllAsRead(
        int $userId,
        int $organizationId
    ): void {
        $sql = '
            INSERT INTO notification_reads (
                notification_id,
                user_id
            )
            SELECT
                recipients.notification_id,
                recipients.user_id

            FROM notification_recipients recipients

            INNER JOIN notifications n
                ON n.id = recipients.notification_id

            INNER JOIN users recipient
                ON recipient.id = recipients.user_id
               AND recipient.organization_id = n.organization_id
               AND recipient.deleted_at IS NULL

            LEFT JOIN notification_reads nr
                ON nr.notification_id = recipients.notification_id
                AND nr.user_id = recipients.user_id

            WHERE recipients.user_id = :user_id
              AND n.organization_id = :organization_id
              AND nr.id IS NULL
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'user_id' => $userId,
            'organization_id' => $organizationId
        ]);
    }

    /**
     * Get notification types.
     */
    public function getTypes(): array
    {
        $sql = '
            SELECT id, name, code
            FROM notification_types
            ORDER BY name ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get one notification type by code.
     */
    public function getTypeByCode(string $code): ?array
    {
        $sql = '
            SELECT id, name, code
            FROM notification_types
            WHERE code = :code
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['code' => $code]);

        $type = $stmt->fetch(PDO::FETCH_ASSOC);

        return $type ?: null;
    }
}