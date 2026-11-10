<?php

namespace App\Services;

use App\Repositories\ShiftRepository;
use RuntimeException;

class ShiftService
{
    private ShiftRepository $shiftRepository;

    public function __construct()
    {
        $this->shiftRepository = new ShiftRepository();
    }

    /*
     * Get all shifts
     */
    public function getShifts(
        int $organizationId
    ): array {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->shiftRepository
            ->getAll($organizationId);
    }

    /*
     * Get one shift
     */
    public function getShift(
        int $shiftId,
        int $organizationId
    ): array {
        if ($shiftId <= 0) {
            throw new RuntimeException(
                'Invalid shift.'
            );
        }

        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        $shift = $this
            ->shiftRepository
            ->findById(
                $shiftId,
                $organizationId
            );

        if ($shift === null) {
            throw new RuntimeException(
                'Shift not found.'
            );
        }

        return $shift;
    }

    /*
     * Get shift statuses
     */
    public function getShiftStatuses(): array
    {
        return $this
            ->shiftRepository
            ->getStatuses();
    }

    /*
     * Create shift
     */
    public function createShift(
        array $data,
        int $organizationId
    ): int {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        /*
         * Required fields
         */
        $required = [
            'name',
            'code',
            'start_time',
            'end_time',
            'working_hours',
            'status_id'
        ];

        foreach ($required as $field) {
            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {
                throw new RuntimeException(
                    "The {$field} field is required."
                );
            }
        }

        /*
         * Clean input
         */
        $name = trim($data['name']);
        $code = strtoupper(
            trim($data['code'])
        );

        /*
         * Prevent duplicate shift codes
         */
        $existingShift =
            $this
                ->shiftRepository
                ->findByCode(
                    $code,
                    $organizationId
                );

        if ($existingShift !== null) {
            throw new RuntimeException(
                'Shift code already exists.'
            );
        }

        /*
         * Validate working hours
         */
        $workingHours = $this->calculateWorkingHours($data);

        /*
         * Prepare shift data
         */
        $shiftData = [
            'organization_id' =>
                $organizationId,
            'name' =>
                $name,
            'code' =>
                $code,
            'start_time' =>
                $data['start_time'],
            'end_time' =>
                $data['end_time'],
            'grace_period_minutes' =>
                isset($data['grace_period_minutes'])
                    ? (int) $data['grace_period_minutes']
                    : 0,
            'break_duration_minutes' =>
                isset($data['break_duration_minutes'])
                    ? (int) $data['break_duration_minutes']
                    : 0,
            'working_hours' =>
                $workingHours,
            'is_overnight' =>
                !empty($data['is_overnight'])
                    ? 1
                    : 0,
            'status_id' =>
                (int) $data['status_id']
        ];

        return $this
            ->shiftRepository
            ->create($shiftData);
    }

    /*
     * Update shift
     */
    public function updateShift(
        int $shiftId,
        array $data,
        int $organizationId
    ): bool {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        if ($shiftId <= 0) {
            throw new RuntimeException(
                'Invalid shift.'
            );
        }

        /*
         * Make sure shift exists
         */
        $shift = $this
            ->shiftRepository
            ->findById(
                $shiftId,
                $organizationId
            );

        if ($shift === null) {
            throw new RuntimeException(
                'Shift not found.'
            );
        }

        /*
         * Required fields
         */
        $required = [
            'name',
            'code',
            'start_time',
            'end_time',
            'status_id'
        ];

        foreach ($required as $field) {
            if (
                !isset($data[$field]) ||
                trim((string) $data[$field]) === ''
            ) {
                throw new RuntimeException(
                    "The {$field} field is required."
                );
            }
        }

        /*
         * Clean input
         */
        $name = trim($data['name']);
        $code = strtoupper(
            trim($data['code'])
        );

        /*
         * Prevent duplicate shift codes
         *
         * Allow the current shift to keep
         * its own code.
         */
        $existingShift =
            $this
                ->shiftRepository
                ->findByCode(
                    $code,
                    $organizationId
                );

        if (
            $existingShift !== null &&
            (int) $existingShift['id'] !== $shiftId
        ) {
            throw new RuntimeException(
                'Shift code already exists.'
            );
        }

        /*
         * Validate working hours
         */
        $workingHours = $this->calculateWorkingHours($data);

        /*
         * Prepare update data
         */
        $shiftData = [
            'name' =>
                $name,
            'code' =>
                $code,
            'start_time' =>
                $data['start_time'],
            'end_time' =>
                $data['end_time'],
            'grace_period_minutes' =>
                isset($data['grace_period_minutes'])
                    ? (int) $data['grace_period_minutes']
                    : 0,
            'break_duration_minutes' =>
                isset($data['break_duration_minutes'])
                    ? (int) $data['break_duration_minutes']
                    : 0,
            'working_hours' =>
                $workingHours,
            'is_overnight' =>
                !empty($data['is_overnight'])
                    ? 1
                    : 0,
            'status_id' =>
                (int) $data['status_id']
        ];

        return $this
            ->shiftRepository
            ->update(
                $shiftId,
                $organizationId,
                $shiftData
            );
    }

    private function calculateWorkingHours(array $data): float
    {
        $start = trim((string) ($data['start_time'] ?? ''));
        $end = trim((string) ($data['end_time'] ?? ''));
        $isOvernight = !empty($data['is_overnight']);
        $breakMinutes = (int) ($data['break_duration_minutes'] ?? 0);

        if (
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)
        ) {
            throw new RuntimeException('Please enter valid start and end times.');
        }

        if ($breakMinutes < 0) {
            throw new RuntimeException('Break duration cannot be negative.');
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));

        $startTotal = ($startHour * 60) + $startMinute;
        $endTotal = ($endHour * 60) + $endMinute;

        $totalMinutes = $endTotal - $startTotal;

        if ($totalMinutes <= 0) {
            if (!$isOvernight) {
                throw new RuntimeException(
                    'End time must be later than start time unless overnight is enabled.'
                );
            }

            $totalMinutes += 1440;
        }

        $workingMinutes = $totalMinutes - $breakMinutes;

        if ($workingMinutes <= 0) {
            throw new RuntimeException(
                'Break duration must be shorter than the total shift duration.'
            );
        }

        return round($workingMinutes / 60, 2);
    }

    /*
     * Delete shift
     */

    public function deleteShift(int $shiftId, int $organizationId): void
    {
        if ($shiftId <= 0) {
            throw new RuntimeException('Invalid shift.');
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $shift = $this->shiftRepository->findById(
            $shiftId,
            $organizationId
        );

        if ($shift === null) {
            throw new RuntimeException('Shift not found.');
        }

        if ($this->shiftRepository->hasAssignments(
            $shiftId,
            $organizationId
        )) {
            throw new RuntimeException(
                'Cannot delete this shift because it is assigned to one or more employees.'
            );
        }

        $deleted = $this->shiftRepository->delete(
            $shiftId,
            $organizationId
        );

        if (!$deleted) {
            throw new RuntimeException(
                'Shift was not deleted. It may no longer exist or may not belong to your organization.'
            );
        }
    }

    /*
     * Get employees for shift assignment
     */

    public function getEmployeesForAssignment(
        int $organizationId
    ): array {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->shiftRepository
            ->getEmployeesForAssignment($organizationId);
    }

    /*
     * Get active shifts for assignment
     */
    public function getActiveShiftsForAssignment(
        int $organizationId
    ): array {
        if ($organizationId <= 0) {
            throw new RuntimeException(
                'Invalid organization.'
            );
        }

        return $this
            ->shiftRepository
            ->getActiveShiftsForAssignment($organizationId);
    }

    /*
     * Assign a shift to an employee.
     */

    public function assignShift(
        int $employeeId,
        int $shiftId,
        string $effectiveFrom,
        int $organizationId,
        int $assignedBy
    ): int {
        if (
            $employeeId <= 0 ||
            $shiftId <= 0 ||
            $organizationId <= 0 ||
            $assignedBy <= 0
        ) {
            throw new RuntimeException(
                'Invalid shift assignment details.'
            );
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $effectiveFrom
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $effectiveFrom
        ) {
            throw new RuntimeException(
                'Invalid effective date.'
            );
        }

        if (
            !$this
                ->shiftRepository
                ->employeeBelongsToOrganization(
                    $employeeId,
                    $organizationId
                )
        ) {
            throw new RuntimeException(
                'Employee not found in this organization.'
            );
        }

        if (
            !$this
                ->shiftRepository
                ->activeShiftBelongsToOrganization(
                    $shiftId,
                    $organizationId
                )
        ) {
            throw new RuntimeException(
                'The selected shift is invalid or inactive.'
            );
        }

        if (
            $this
                ->shiftRepository
                ->hasConflictingAssignment(
                    $employeeId,
                    $organizationId,
                    $effectiveFrom
                )
        ) {
            throw new RuntimeException(
                'A conflicting future shift assignment already exists.'
            );
        }

        $db = \App\Database::connection();

        try {
            $db->beginTransaction();

            $this
                ->shiftRepository
                ->endPreviousAssignments(
                    $employeeId,
                    $organizationId,
                    $effectiveFrom
                );

            $assignmentId = $this
                ->shiftRepository
                ->createAssignment([
                    'organization_id' => $organizationId,
                    'employee_id' => $employeeId,
                    'shift_id' => $shiftId,
                    'effective_from' => $effectiveFrom,
                    'assigned_by' => $assignedBy
                ]);

            $db->commit();

            return $assignmentId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw new RuntimeException(
                'Unable to assign shift: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function unassignShift(
        int $employeeId,
        string $effectiveFrom,
        int $organizationId
    ): void {
        if (
            $employeeId <= 0 ||
            $organizationId <= 0
        ) {
            throw new RuntimeException(
                'Invalid shift unassignment details.'
            );
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $effectiveFrom
        );

        if (
            !$date ||
            $date->format('Y-m-d') !== $effectiveFrom
        ) {
            throw new RuntimeException(
                'Invalid effective date.'
            );
        }

        if (
            !$this
                ->shiftRepository
                ->employeeBelongsToOrganization(
                    $employeeId,
                    $organizationId
                )
        ) {
            throw new RuntimeException(
                'Employee not found in this organization.'
            );
        }

        $db = \App\Database::connection();

        try {
            $db->beginTransaction();

            $unassigned = $this
                ->shiftRepository
                ->unassignShift(
                    $employeeId,
                    $organizationId,
                    $effectiveFrom
                );

            if (!$unassigned) {
                throw new RuntimeException(
                    'No shift assignment can be ended on that date.'
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw new RuntimeException(
                'Unable to unassign shift: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
