<?php

namespace App\Controllers;

use App\HttpException;
use App\Repositories\HolidayRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Services\HolidayService;
use App\Services\PermissionService;
use App\Session;
use PDOException;
use RuntimeException;

class HolidayController
{
    private HolidayService $holidays;
    private UserRepository $users;

    public function __construct()
    {
        $this->holidays = new HolidayService(
            new HolidayRepository(),
            new NotificationRepository()
        );
        $this->users = new UserRepository();
    }

    public function index(): void
    {
        $organizationId = $this->organizationId();
        $holidays = $this->holidays->getAll($organizationId);
        $canCreate = PermissionService::has('holiday.create');
        $canUpdate = PermissionService::has('holiday.update');
        $canDelete = PermissionService::has('holiday.delete');
        $csrfToken = $this->csrfToken();

        require __DIR__ . '/../Views/holidays/index.php';
    }

    public function create(): void
    {
        $organizationId = $this->organizationId();
        $users = $this->users->findActiveByOrganization($organizationId);
        $csrfToken = $this->csrfToken();

        require __DIR__ . '/../Views/holidays/create.php';
    }

    public function edit(int $id): void
    {
        try {
            $organizationId = $this->organizationId();
            $holiday = $this->holidays->getById($id, $organizationId);
            $csrfToken = $this->csrfToken();

            require __DIR__ . '/../Views/holidays/edit.php';
        } catch (RuntimeException $e) {
            throw new HttpException($e->getMessage(), 404);
        }
    }

    public function store(): void
    {
        try {
            $this->verifyCsrfToken();
            $organizationId = $this->organizationId();
            $userId = (int) (Session::get('user', [])['id'] ?? 0);
            [$name, $startDate, $endDate, $isRecurring] =
                $this->validatedHolidayInput();

            if (
                isset($_POST['send_to_all'])
                && $_POST['send_to_all'] === '1'
            ) {
                $users = $this->users->findActiveByOrganization($organizationId);
                $recipientIds = array_map(
                    static fn(array $user): int => (int) $user['id'],
                    $users
                );
            } else {
                $recipientIds = $this->recipientIds(
                    $_POST['recipient_ids'] ?? []
                );
            }

            $this->holidays->createAndNotify(
                $organizationId,
                $userId,
                $name,
                $startDate,
                $endDate,
                $isRecurring,
                $recipientIds
            );
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->flashAndRedirect(
                    'A holiday is already recorded with that start date in this organization.',
                    'error',
                    APP_BASE_PATH . '/holidays/create'
                );
                return;
            }
            throw $e;
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/holidays/create'
            );
            return;
        }

        $this->flashAndRedirect(
            'Holiday created and notification sent.',
            'success',
            APP_BASE_PATH . '/holidays'
        );
    }

    public function update(int $id): void
    {
        $redirectUrl = APP_BASE_PATH . '/holidays/' . $id . '/edit';
        try {
            $this->verifyCsrfToken();
            [$name, $startDate, $endDate, $isRecurring] =
                $this->validatedHolidayInput();
            $this->holidays->update(
                $id,
                $this->organizationId(),
                $name,
                $startDate,
                $endDate,
                $isRecurring
            );
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->flashAndRedirect(
                    'A holiday is already recorded with that start date in this organization.',
                    'error',
                    $redirectUrl
                );
                return;
            }
            throw $e;
        } catch (RuntimeException $e) {
            $this->flashAndRedirect($e->getMessage(), 'error', $redirectUrl);
            return;
        }

        $this->flashAndRedirect(
            'Holiday updated.',
            'success',
            APP_BASE_PATH . '/holidays'
        );
    }

    public function delete(int $id): void
    {
        try {
            $this->verifyCsrfToken();
            $this->holidays->delete($id, $this->organizationId());
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/holidays'
            );
            return;
        }

        $this->flashAndRedirect(
            'Holiday deleted.',
            'success',
            APP_BASE_PATH . '/holidays'
        );
    }

    /**
     * @return array{string, string, string, bool}
     */
    private function validatedHolidayInput(): array
    {
        $name = trim($this->postString('name'));
        $startDate = trim($this->postString('start_date'));
        $endDate = trim($this->postString('end_date'));
        $isRecurring = isset($_POST['is_recurring'])
            && $_POST['is_recurring'] === '1';

        if ($name === '' || mb_strlen($name) > 150) {
            throw new RuntimeException(
                'Holiday name is required and must be at most 150 characters.'
            );
        }
        if (
            !$this->isValidDate($startDate)
            || !$this->isValidDate($endDate)
        ) {
            throw new RuntimeException(
                'Select a valid holiday start and end date.'
            );
        }
        if ($endDate < $startDate) {
            throw new RuntimeException(
                'The holiday end date cannot be before its start date.'
            );
        }

        return [$name, $startDate, $endDate, $isRecurring];
    }

    /** @return list<int> */
    private function recipientIds(mixed $postedIds): array
    {
        if (!is_array($postedIds)) {
            throw new RuntimeException('Invalid recipient selection.');
        }

        $recipientIds = [];
        foreach ($postedIds as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0) {
                throw new RuntimeException('Invalid recipient selection.');
            }
            $recipientIds[] = (int) $id;
        }

        return array_values(array_unique($recipientIds));
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            throw new RuntimeException('Invalid form input.');
        }
        return $value;
    }

    private function organizationId(): int
    {
        $organizationId = (int) (
            Session::get('user', [])['organization_id'] ?? 0
        );
        if ($organizationId <= 0) {
            throw new HttpException('Invalid organization.', 403);
        }
        return $organizationId;
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && (
                $errors === false
                || (
                    $errors['warning_count'] === 0
                    && $errors['error_count'] === 0
                )
            );
    }

    private function csrfToken(): string
    {
        $token = Session::get('holiday_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('holiday_csrf_token', $token);
        }
        return $token;
    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('holiday_csrf_token');
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

    private function flashAndRedirect(
        string $message,
        string $type,
        string $url
    ): void {
        Session::set('flash_message', [
            'type' => $type,
            'title' => $type === 'success' ? 'Holiday created' : 'Action failed',
            'message' => $message,
        ]);
        header('Location: ' . $url, true, 303);
        exit;
    }
}
