<?php

namespace App\Controllers;

use App\HttpException;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use App\Services\PermissionService;
use RuntimeException;

class NotificationController
{
    private NotificationService $notificationService;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->notificationService = new NotificationService(
            new NotificationRepository()
        );

        $this->userRepository = new UserRepository();
    }

    /**
     * List notifications for the authenticated user.
     */
    public function index(): void
    {
        $notifications = $this->notificationService
            ->getForCurrentUser();

        $unreadCount = $this->notificationService
            ->countUnreadForCurrentUser();

        require __DIR__ . '/../Views/notifications/index.php';
    }

    /**
     * Display the notification creation form.
     */
    public function create(): void
    {
        if (!$this->canCreateNotifications()) {
            throw new HttpException(
                'You are not authorized to create notifications.',
                403
            );
        }

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0) {
            throw new HttpException('Invalid organization.', 403);
        }

        $users = $this->userRepository
            ->findActiveByOrganization($organizationId);

        // SYSTEM is used for manually created announcements.
        $types = array_values(array_filter(
            $this->notificationService->getTypes(),
            static fn(array $type): bool =>
                ($type['code'] ?? '') === 'SYSTEM'
        ));

        require __DIR__ . '/../Views/notifications/create.php';
    }

    /**
     * Save a notification and assign its intended recipients.
     */
    public function store(): void
    {
        if (!$this->canCreateNotifications()) {
            throw new HttpException(
                'You are not authorized to create notifications.',
                403
            );
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new HttpException('Method not allowed.', 405);
        }

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $typeCode = (string) ($_POST['type_code'] ?? 'SYSTEM');

        $sendToAll = isset($_POST['send_to_all'])
            && $_POST['send_to_all'] === '1';

        if ($organizationId <= 0) {
            throw new HttpException('Invalid organization.', 403);
        }

        if ($title === '' || $message === '') {
            throw new RuntimeException('Title and message are required.');
        }

        // Only allow manually created system announcements.
        if ($typeCode !== 'SYSTEM') {
            throw new RuntimeException('Invalid notification type.');
        }

        if ($sendToAll) {
            $users = $this->userRepository
                ->findActiveByOrganization($organizationId);

            $recipientIds = array_map(
                static fn(array $user): int => (int) $user['id'],
                $users
            );
        } else {
            $postedIds = $_POST['recipient_ids'] ?? [];

            if (!is_array($postedIds)) {
                throw new RuntimeException('Invalid recipient selection.');
            }

            $recipientIds = array_values(array_unique(
                array_filter(
                    array_map(
                        static fn($id): int =>
                            filter_var($id, FILTER_VALIDATE_INT) !== false
                                ? (int) $id
                                : 0,
                        $postedIds
                    ),
                    static fn(int $id): bool => $id > 0
                )
            ));
        }

        if (empty($recipientIds)) {
            throw new RuntimeException('Select at least one active recipient.');
        }

        $this->notificationService->createForCurrentUser(
            'SYSTEM',
            $title,
            $message,
            $recipientIds
        );

        header('Location: ' . APP_BASE_PATH . '/notifications', true, 303);
        exit;
    }

    /**
     * Show one notification assigned to the current user.
     */
    public function show(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('Invalid notification.');
        }

        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );

        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        $notification = $this->notificationService->getById(
            $id,
            $organizationId
        );

        if (!$notification) {
            throw new HttpException('Notification not found.', 404);
        }

        $this->notificationService->markAsRead($id);

        require __DIR__ . '/../Views/notifications/show.php';
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('Invalid notification.');
        }

        $this->notificationService->markAsRead($id);

        header('Location: ' . APP_BASE_PATH . '/notifications', true, 303);
        exit;
    }

    /**
     * Mark all notifications assigned to the current user as read.
     */
    public function markAllAsRead(): void
    {
        $this->notificationService->markAllAsRead();

        header('Location: ' . APP_BASE_PATH . '/notifications', true, 303);
        exit;
    }

    /**
     * Restrict notification creation to approved roles.
     */
    private function canCreateNotifications(): bool
    {
        return PermissionService::has('notification.manage');
    }
}
