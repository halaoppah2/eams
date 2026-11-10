<?php

namespace App\Services;

use App\Database;
use App\Repositories\AuditRepository;
use App\Repositories\HolidayRepository;
use App\Repositories\NotificationRepository;
use RuntimeException;
use Throwable;

class HolidayService
{
    private HolidayRepository $holidays;
    private NotificationRepository $notifications;
    private AuditService $audit;

    public function __construct(
        HolidayRepository $holidays,
        NotificationRepository $notifications
    ) {
        $this->holidays = $holidays;
        $this->notifications = $notifications;
        $this->audit = new AuditService(new AuditRepository());
    }

    public function getAll(int $organizationId): array
    {
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        return $this->holidays->getAll($organizationId);
    }

    public function getById(int $id, int $organizationId): array
    {
        if ($id <= 0 || $organizationId <= 0) {
            throw new RuntimeException('Invalid holiday or organization.');
        }

        $holiday = $this->holidays->findById($id, $organizationId);
        if ($holiday === null) {
            throw new RuntimeException('Holiday not found.');
        }

        return $holiday;
    }

    public function createAndNotify(
        int $organizationId,
        int $createdBy,
        string $name,
        string $startDate,
        string $endDate,
        bool $isRecurring,
        array $recipientIds
    ): int {
        if ($organizationId <= 0 || $createdBy <= 0) {
            throw new RuntimeException('Invalid organization or user.');
        }

        $recipientIds = array_values(array_unique(
            array_filter(
                array_map('intval', $recipientIds),
                static fn(int $id): bool => $id > 0
            )
        ));
        if ($recipientIds === []) {
            throw new RuntimeException('Select at least one recipient.');
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();

            $holidayId = $this->holidays->create(
                $organizationId,
                $name,
                $startDate,
                $endDate,
                $isRecurring
            );

            $notificationType = $this->notifications
                ->getTypeByCode('SYSTEM');
            if ($notificationType === null) {
                throw new RuntimeException(
                    'System notifications are not configured.'
                );
            }

            $dateLabel = date('F j, Y', strtotime($startDate))
                . ' to '
                . date('F j, Y', strtotime($endDate));
            $recurrenceLabel = $isRecurring
                ? ' This holiday repeats annually.'
                : '';
            $notificationId = $this->notifications->create(
                $organizationId,
                (int) $notificationType['id'],
                'Holiday: ' . $name,
                $name . ' is scheduled from ' . $dateLabel . '.'
                    . $recurrenceLabel,
                $createdBy
            );
            $this->notifications->addRecipients(
                $notificationId,
                $organizationId,
                $recipientIds
            );
            $this->audit->recordAction(
                'HOLIDAY_CREATED',
                'holiday',
                'holiday',
                $holidayId,
                null,
                [
                    'name' => $name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_recurring' => $isRecurring,
                    'recipient_count' => count($recipientIds),
                ]
            );

            $db->commit();

            return $holidayId;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function update(
        int $id,
        int $organizationId,
        string $name,
        string $startDate,
        string $endDate,
        bool $isRecurring
    ): void {
        if ($id <= 0 || $organizationId <= 0) {
            throw new RuntimeException('Invalid holiday or organization.');
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();
            $oldHoliday = $this->holidays->findById($id, $organizationId);
            if ($oldHoliday === null) {
                throw new RuntimeException('Holiday not found.');
            }

            if (!$this->holidays->update(
                $id,
                $organizationId,
                $name,
                $startDate,
                $endDate,
                $isRecurring
            )) {
                throw new RuntimeException('The holiday could not be updated.');
            }

            $this->audit->recordAction(
                'HOLIDAY_UPDATED',
                'holiday',
                'holiday',
                $id,
                $oldHoliday,
                [
                    'name' => $name,
                    'holiday_date' => $startDate,
                    'end_date' => $endDate,
                    'is_recurring' => $isRecurring,
                ]
            );
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id, int $organizationId): void
    {
        if ($id <= 0 || $organizationId <= 0) {
            throw new RuntimeException('Invalid holiday or organization.');
        }

        $db = Database::connection();
        try {
            $db->beginTransaction();
            $holiday = $this->holidays->findById($id, $organizationId);
            if ($holiday === null) {
                throw new RuntimeException('Holiday not found.');
            }
            if (!$this->holidays->delete($id, $organizationId)) {
                throw new RuntimeException('The holiday could not be deleted.');
            }

            $this->audit->recordAction(
                'HOLIDAY_DELETED',
                'holiday',
                'holiday',
                $id,
                $holiday,
                null
            );
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
