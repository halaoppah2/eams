<?php

namespace App\Repositories;

use App\Database;
use PDO;
use RuntimeException;

class HolidayRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAll(int $organizationId): array
    {
        $stmt = $this->db->prepare('
            SELECT id, organization_id, name, holiday_date, end_date, is_recurring
            FROM holidays
            WHERE organization_id = :organization_id
            ORDER BY MONTH(holiday_date), DAY(holiday_date),
                holiday_date, end_date, name
        ');
        $stmt->execute(['organization_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, int $organizationId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, organization_id, name, holiday_date, end_date, is_recurring
            FROM holidays
            WHERE id = :id
              AND organization_id = :organization_id
            LIMIT 1
        ');
        $stmt->execute([
            'id' => $id,
            'organization_id' => $organizationId,
        ]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC);

        return $holiday ?: null;
    }

    public function create(
        int $organizationId,
        string $name,
        string $startDate,
        string $endDate,
        bool $isRecurring
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO holidays (
                organization_id,
                name,
                holiday_date,
                end_date,
                is_recurring
            )
            SELECT o.id, :name, :holiday_date, :end_date, :is_recurring
            FROM organizations o
            WHERE o.id = :organization_id
        ');
        $stmt->execute([
            'organization_id' => $organizationId,
            'name' => $name,
            'holiday_date' => $startDate,
            'end_date' => $endDate,
            'is_recurring' => $isRecurring ? 1 : 0,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'The holiday could not be created for this organization.'
            );
        }

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        int $organizationId,
        string $name,
        string $startDate,
        string $endDate,
        bool $isRecurring
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE holidays
            SET name = :name,
                holiday_date = :holiday_date,
                end_date = :end_date,
                is_recurring = :is_recurring
            WHERE id = :id
              AND organization_id = :organization_id
        ');

        return $stmt->execute([
            'id' => $id,
            'organization_id' => $organizationId,
            'name' => $name,
            'holiday_date' => $startDate,
            'end_date' => $endDate,
            'is_recurring' => $isRecurring ? 1 : 0,
        ]);
    }

    public function delete(int $id, int $organizationId): bool
    {
        $stmt = $this->db->prepare('
            DELETE FROM holidays
            WHERE id = :id
              AND organization_id = :organization_id
        ');
        $stmt->execute([
            'id' => $id,
            'organization_id' => $organizationId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
