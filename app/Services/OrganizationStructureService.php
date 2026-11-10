<?php

namespace App\Services;

use App\Repositories\OrganizationStructureRepository;
use RuntimeException;

class OrganizationStructureService
{
    private OrganizationStructureRepository $repository;

    public function __construct(
        ?OrganizationStructureRepository $repository = null
    ) {
        $this->repository = $repository
            ?? new OrganizationStructureRepository();
    }

    public function getSettings(int $organizationId): array
    {
        $this->assertOrganization($organizationId);

        $settings = $this->repository->getStructure($organizationId);
        $settings['active_branches'] =
            $this->repository->getActiveBranches($organizationId);
        $settings['managers'] = $this->repository->getManagers($organizationId);

        return $settings;
    }

    public function create(
        string $type,
        array $input,
        int $organizationId
    ): void {
        $this->assertOrganization($organizationId);
        $type = $this->normalizeType($type);
        $statusId = $this->repository->getStatusId($type, 'ACTIVE');

        if ($statusId === null) {
            throw new RuntimeException(
                'The ACTIVE status is missing for ' . $type . ' records.'
            );
        }

        $data = $this->validateInput($type, $input);
        $this->repository->create(
            $type,
            $organizationId,
            $statusId,
            $data
        );
    }

    public function update(
        string $type,
        int $id,
        array $input,
        int $organizationId
    ): void {
        $this->assertOrganization($organizationId);
        if ($id <= 0) {
            throw new RuntimeException('Invalid organization structure record.');
        }

        $type = $this->normalizeType($type);
        $data = $this->validateInput($type, $input);
        if (!$this->repository->update($type, $organizationId, $id, $data)) {
            throw new RuntimeException('The organization structure record was not found.');
        }
    }

    public function toggleStatus(
        string $type,
        int $id,
        int $organizationId
    ): void {
        $this->assertOrganization($organizationId);
        if ($id <= 0) {
            throw new RuntimeException('Invalid organization structure record.');
        }

        $type = $this->normalizeType($type);
        if (!$this->repository->exists($type, $organizationId, $id)) {
            throw new RuntimeException('The organization structure record was not found.');
        }

        $settings = $this->repository->getStructure($organizationId);
        $rows = $settings[$type === 'branch'
            ? 'branches'
            : ($type === 'department' ? 'departments' : 'positions')];
        $current = null;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) {
                $current = $row;
                break;
            }
        }

        if ($current === null) {
            throw new RuntimeException('The organization structure record was not found.');
        }

        $targetCode = strtoupper((string) $current['status_code']) === 'ACTIVE'
            ? 'INACTIVE'
            : 'ACTIVE';
        $statusId = $this->repository->getStatusId($type, $targetCode);
        if ($statusId === null) {
            throw new RuntimeException(
                "The {$targetCode} status is missing for {$type} records."
            );
        }

        $this->repository->updateStatus(
            $type,
            $organizationId,
            $id,
            $statusId
        );
    }

    private function validateInput(string $type, array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if (
            $code === ''
            || strlen($code) > 30
            || !preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $code)
        ) {
            throw new RuntimeException(
                'Code is required and must be at most 30 characters using letters, numbers, hyphens, or underscores.'
            );
        }

        if ($type === 'branch') {
            return [
                'name' => $this->requiredText($input['name'] ?? '', 'Branch name', 150),
                'code' => $code,
                'email' => $this->optionalEmail($input['email'] ?? ''),
                'phone' => $this->optionalText($input['phone'] ?? '', 'Phone', 30),
                'address' => trim((string) ($input['address'] ?? '')) ?: null,
            ];
        }

        if ($type === 'department') {
            $branchId = filter_var(
                $input['branch_id'] ?? null,
                FILTER_VALIDATE_INT
            );
            $managerId = trim((string) ($input['manager_id'] ?? ''));
            if ($branchId === false || $branchId <= 0) {
                throw new RuntimeException('Select a valid branch.');
            }
            if ($managerId !== '' && (!ctype_digit($managerId) || (int) $managerId <= 0)) {
                throw new RuntimeException('Select a valid department manager.');
            }

            return [
                'name' => $this->requiredText($input['name'] ?? '', 'Department name', 150),
                'code' => $code,
                'branch_id' => (int) $branchId,
                'manager_id' => $managerId === '' ? null : (int) $managerId,
            ];
        }

        return [
            'title' => $this->requiredText($input['title'] ?? '', 'Position title', 150),
            'code' => $code,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
        ];
    }

    private function requiredText(
        mixed $value,
        string $label,
        int $maximumLength
    ): string {
        $value = trim((string) $value);
        $length = preg_match_all('/./us', $value);
        if ($value === '' || $length === false || $length > $maximumLength) {
            throw new RuntimeException(
                "{$label} is required and must be at most {$maximumLength} characters."
            );
        }

        return $value;
    }

    private function optionalText(
        mixed $value,
        string $label,
        int $maximumLength
    ): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $this->requiredText($value, $label, $maximumLength);
    }

    private function optionalEmail(mixed $value): ?string
    {
        $email = $this->optionalText($value, 'Email', 150);
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid branch email address.');
        }

        return $email;
    }

    private function normalizeType(string $type): string
    {
        if (!in_array($type, ['branch', 'department', 'position'], true)) {
            throw new RuntimeException('Invalid organization structure type.');
        }

        return $type;
    }

    private function assertOrganization(int $organizationId): void
    {
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }
    }
}
