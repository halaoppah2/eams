<?php

namespace App\Controllers;

use App\Services\OrganizationStructureService;
use App\Session;
use PDOException;
use RuntimeException;

class OrganizationStructureController
{
    private OrganizationStructureService $service;

    public function __construct()
    {
        $this->service = new OrganizationStructureService();
    }

    public function index(): void
    {
        try {
            $organizationId = $this->organizationId();
            $csrfToken = Session::get('organization_structure_csrf_token');
            if (!is_string($csrfToken) || $csrfToken === '') {
                $csrfToken = bin2hex(random_bytes(32));
                Session::set('organization_structure_csrf_token', $csrfToken);
            }

            $settings = $this->service->getSettings($organizationId);

            require __DIR__ . '/../Views/organization/structure.php';

        } catch (RuntimeException $e) {
            $this->redirectWithMessage(
                APP_BASE_PATH . '/employees',
                $e->getMessage(),
                'error'
            );
        }
    }

    public function create(string $type): void
    {
        $this->runChange(
            fn () => $this->service->create(
                $type,
                $_POST,
                $this->organizationId()
            ),

            'Organization structure item added.'
        );
    }

    public function update(string $type, int $id): void
    {
        $this->runChange(
            fn () => $this->service->update(
                $type,
                $id,
                $_POST,
                $this->organizationId()
            ),

            'Organization structure item updated.'
        );
    }

    public function toggleStatus(string $type, int $id): void
    {
        $this->runChange(
            fn () => $this->service->toggleStatus(
                $type,
                $id,
                $this->organizationId()
            ),
            
            'Organization structure status updated.'
        );
    }

    private function runChange(callable $change, string $successMessage): void
    {
        try {
            $this->verifyCsrfToken();
            $change();
            $this->redirectWithMessage(
                APP_BASE_PATH . '/settings/organization-structure',
                $successMessage,
                'success'
            );
        } catch (RuntimeException $e) {
            $message = $e instanceof PDOException
                && (string) $e->getCode() === '23000'
                    ? 'That code is already in use for this organization.'
                    : $e->getMessage();
            $this->redirectWithMessage(
                APP_BASE_PATH . '/settings/organization-structure',
                $message,
                'error'
            );
        }

    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('organization_structure_csrf_token');
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

    private function organizationId(): int
    {
        $organizationId = (int) (
            $_SESSION['user']['organization_id'] ?? 0
        );
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        return $organizationId;
    }

    private function redirectWithMessage(
        string $url,
        string $message,
        string $type
    ): void {
        Session::set('flash_message', [
            'type' => $type,
            'title' => $type === 'error' ? 'Action failed' : 'Success',
            'message' => $message,
        ]);
        header('Location: ' . $url, true, 303);
        exit;
    }
}
