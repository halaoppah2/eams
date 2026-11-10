<?php

namespace App\Controllers;

use App\Database;
use App\HttpException;
use App\Repositories\AuditRepository;
use App\Repositories\OrganizationRepository;
use App\Services\AuditService;
use App\Session;
use PDOException;
use RuntimeException;

class OrganizationController
{
    private const MAX_LOGO_BYTES = 5242880;
    private const LOGO_DIRECTORY = '/public/uploads/organization-logos';

    private OrganizationRepository $organizations;
    private AuditService $audit;

    public function __construct()
    {
        $this->organizations = new OrganizationRepository();
        $this->audit = new AuditService(new AuditRepository());
    }

    public function systemIndex(): void
    {
        $this->requireSystemAdmin();
        $csrfToken = $this->csrfToken();
        $organizations = $this->organizations->getAll();

        require __DIR__ . '/../Views/organizations/system-index.php';
    }

    public function setSystemStatus(int $organizationId): void
    {
        $this->requireSystemAdmin();

        try {
            $this->verifyCsrfToken();

            if ($organizationId <= 0) {
                throw new RuntimeException('Invalid organization.');
            }

            $statusCode = strtoupper(trim((string) ($_POST['status'] ?? '')));
            if (!in_array($statusCode, ['ACTIVE', 'INACTIVE'], true)) {
                throw new RuntimeException('Invalid organization status.');
            }
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/system/organizations'
            );
            return;
        }

        $organization = $this->organizations->findById($organizationId);
        if ($organization === null) {
            throw new HttpException('Organization not found.', 404);
        }

        $oldStatus = $organization['status_code'];
        $this->organizations->updateStatus($organizationId, $statusCode);
        if ($oldStatus !== $statusCode) {
            $this->audit->recordAction(
                'ORGANIZATION_' . $statusCode,
                'organization',
                'organization',
                $organizationId,
                ['status' => $oldStatus],
                ['status' => $statusCode]
            );
        }

        $this->flashAndRedirect(
            'Organization status updated. Tenant data has been retained.',
            'success',
            APP_BASE_PATH . '/system/organizations'
        );
    }

    public function deleteSystemOrganization(int $organizationId): void
    {
        $this->requireSystemAdmin();

        try {
            $this->verifyCsrfToken();
            if ($organizationId <= 0) {
                throw new RuntimeException('Invalid organization.');
            }

            $organization = $this->organizations->findById($organizationId);
            if ($organization === null) {
                throw new HttpException('Organization not found.', 404);
            }

            $this->verifyOrganizationNameConfirmation(
                (string) $organization['name']
            );
            $assets = $this->organizations->deletePermanently(
                $organizationId,
                $this->currentUserId()
            );
            $this->removeDeletedOrganizationAssets($assets);
        } catch (PDOException $e) {
            error_log(
                'System administrator organization deletion failed: '
                . $e->getMessage()
            );
            $this->flashAndRedirect(
                'The organization could not be deleted. No database changes were committed.',
                'error',
                APP_BASE_PATH . '/system/organizations'
            );
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/system/organizations'
            );
            return;
        }

        if (
            (int) (Session::get('user', [])['organization_id'] ?? 0)
            === $organizationId
        ) {
            Session::destroy();
            header('Location: ' . APP_BASE_PATH . '/?organization_deleted=1', true, 303);
            exit;
        }

        $this->flashAndRedirect(
            'Organization and its data were permanently deleted.',
            'success',
            APP_BASE_PATH . '/system/organizations'
        );
    }

    public function ownerSettings(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $organization = $this->organizations->findById($organizationId);
        if ($organization === null) {
            throw new HttpException('Organization not found.', 404);
        }

        $csrfToken = $this->csrfToken();
        require __DIR__ . '/../Views/organizations/owner-settings.php';
    }

    public function uploadLogo(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $newFilePath = null;
        $db = Database::connection();

        try {
            $this->verifyCsrfToken();
            $organization = $this->organizations->findById($organizationId);
            if ($organization === null) {
                throw new HttpException('Organization not found.', 404);
            }

            $uploadedFile = $_FILES['logo'] ?? null;
            if (!is_array($uploadedFile)) {
                throw new RuntimeException('Choose an organization logo to upload.');
            }
            $mimeType = $this->validateUploadedLogo($uploadedFile);
            $extensionByMimeType = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $filename = bin2hex(random_bytes(16))
                . '.'
                . $extensionByMimeType[$mimeType];
            $uploadDirectory = dirname(__DIR__, 2)
                . self::LOGO_DIRECTORY;

            if (
                !is_dir($uploadDirectory)
                && !mkdir($uploadDirectory, 0755, true)
                && !is_dir($uploadDirectory)
            ) {
                throw new RuntimeException(
                    'The organization logo folder could not be created.'
                );
            }

            $newFilePath = $uploadDirectory . '/' . $filename;
            if (!move_uploaded_file(
                $uploadedFile['tmp_name'],
                $newFilePath
            )) {
                $newFilePath = null;
                throw new RuntimeException(
                    'The organization logo could not be saved.'
                );
            }

            $db->beginTransaction();
            if (!$this->organizations->updateLogo($organizationId, $filename)) {
                throw new RuntimeException(
                    'The organization logo could not be updated.'
                );
            }

            $this->audit->recordAction(
                'ORGANIZATION_LOGO_UPDATED',
                'organization',
                'organization',
                $organizationId,
                ['logo' => $organization['logo']],
                ['logo' => $filename]
            );
            $db->commit();

            $sessionUser = Session::get('user', []);
            $sessionUser['organization_logo'] = $filename;
            Session::set('user', $sessionUser);

            $this->removePreviousLogo($organization['logo'], $filename);
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->removeUploadedLogo($newFilePath);
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->removeUploadedLogo($newFilePath);
            throw $e;
        }

        $this->flashAndRedirect(
            'Organization logo updated and set as the favicon.',
            'success',
            APP_BASE_PATH . '/settings/organization'
        );
    }

    public function removeOwnLogo(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $db = Database::connection();

        try {
            $this->verifyCsrfToken();
            $organization = $this->organizations->findById($organizationId);
            if ($organization === null) {
                throw new HttpException('Organization not found.', 404);
            }
            if (!is_string($organization['logo']) || $organization['logo'] === '') {
                throw new RuntimeException(
                    'This organization does not have a logo to remove.'
                );
            }

            $db->beginTransaction();
            if (!$this->organizations->updateLogo($organizationId, null)) {
                throw new RuntimeException(
                    'The organization logo could not be removed.'
                );
            }

            $this->audit->recordAction(
                'ORGANIZATION_LOGO_REMOVED',
                'organization',
                'organization',
                $organizationId,
                ['logo' => $organization['logo']],
                ['logo' => null]
            );
            $db->commit();

            $sessionUser = Session::get('user', []);
            $sessionUser['organization_logo'] = null;
            Session::set('user', $sessionUser);

            $this->removePreviousLogo($organization['logo'], '');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Organization logo removal failed: ' . $e->getMessage());
            $this->flashAndRedirect(
                'The organization logo could not be removed.',
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
            return;
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
            return;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $this->flashAndRedirect(
            'Organization logo removed.',
            'success',
            APP_BASE_PATH . '/settings/organization'
        );
    }

    public function updateOwnDetails(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $db = Database::connection();

        try {
            $this->verifyCsrfToken();
            $organization = $this->organizations->findById($organizationId);
            if ($organization === null) {
                throw new HttpException('Organization not found.', 404);
            }

            $details = $this->validatedOrganizationDetails();
            $db->beginTransaction();
            if (!$this->organizations->updateDetails($organizationId, $details)) {
                throw new RuntimeException(
                    'Organization details could not be updated.'
                );
            }

            $auditDetails = $details;
            $auditDetails['registration_no'] =
                $organization['registration_no'];

            $this->audit->recordAction(
                'ORGANIZATION_DETAILS_UPDATED',
                'organization',
                'organization',
                $organizationId,
                [
                    'name' => $organization['name'],
                    'registration_no' => $organization['registration_no'],
                    'email' => $organization['email'],
                    'phone' => $organization['phone'],
                    'address' => $organization['address'],
                    'timezone' => $organization['timezone'],
                ],
                $auditDetails
            );
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Organization profile update failed: ' . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
            return;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        $this->flashAndRedirect(
            'Organization details updated.',
            'success',
            APP_BASE_PATH . '/settings/organization'
        );
    }

    public function deactivateOwnOrganization(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $organization = $this->organizations->findById($organizationId);

        if ($organization === null) {
            throw new HttpException('Organization not found.', 404);
        }

        try {
            $this->verifyCsrfToken();
            $confirmation = trim((string) ($_POST['confirmation_name'] ?? ''));
            if (!hash_equals((string) $organization['name'], $confirmation)) {
                throw new RuntimeException(
                    'Enter the organization name exactly to confirm deactivation.'
                );
            }
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
            return;
        }

        if ($organization['status_code'] !== 'INACTIVE') {
            $this->organizations->updateStatus($organizationId, 'INACTIVE');
            $this->audit->recordAction(
                'ORGANIZATION_DEACTIVATED',
                'organization',
                'organization',
                $organizationId,
                ['status' => $organization['status_code']],
                ['status' => 'INACTIVE']
            );
        }

        Session::destroy();
        header('Location: ' . APP_BASE_PATH . '/?organization_deactivated=1', true, 303);
        exit;
    }

    public function deleteOwnOrganization(): void
    {
        $this->requireOrganizationOwner();
        $organizationId = $this->currentOrganizationId();
        $organization = $this->organizations->findById($organizationId);
        if ($organization === null) {
            throw new HttpException('Organization not found.', 404);
        }

        try {
            $this->verifyCsrfToken();
            $this->verifyOrganizationNameConfirmation(
                (string) $organization['name']
            );
            $assets = $this->organizations->deletePermanently(
                $organizationId,
                $this->currentUserId()
            );
            $this->removeDeletedOrganizationAssets($assets);
        } catch (PDOException $e) {
            error_log(
                'Organization owner deletion failed: ' . $e->getMessage()
            );
            $this->flashAndRedirect(
                'The organization could not be deleted. No database changes were committed.',
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
        } catch (RuntimeException $e) {
            $this->flashAndRedirect(
                $e->getMessage(),
                'error',
                APP_BASE_PATH . '/settings/organization'
            );
            return;
        }

        Session::destroy();
        header('Location: ' . APP_BASE_PATH . '/?organization_deleted=1', true, 303);
        exit;
    }

    private function requireSystemAdmin(): void
    {
        $user = Session::get('user', []);
        if (!is_array($user) || empty($user['is_system_admin'])) {
            throw new HttpException(
                'You do not have permission to manage organizations.',
                403
            );
        }
    }

    private function requireOrganizationOwner(): void
    {
        foreach (Session::get('roles', []) as $role) {
            $name = strtolower(trim((string) ($role['name'] ?? '')));
            if ($name === 'organization owner') {
                return;
            }
        }

        throw new HttpException(
            'Only the organization owner can manage organization settings.',
            403
        );
    }

    private function currentOrganizationId(): int
    {
        $organizationId = (int) (
            Session::get('user', [])['organization_id'] ?? 0
        );
        if ($organizationId <= 0) {
            throw new RuntimeException('Invalid organization.');
        }

        return $organizationId;
    }

    private function currentUserId(): int
    {
        $userId = (int) (Session::get('user', [])['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Invalid user session.');
        }

        return $userId;
    }

    private function verifyOrganizationNameConfirmation(
        string $organizationName
    ): void {
        $confirmation = $_POST['confirmation_name'] ?? null;
        if (
            !is_string($confirmation)
            || !hash_equals($organizationName, trim($confirmation))
        ) {
            throw new RuntimeException(
                'Enter the organization name exactly to confirm permanent deletion.'
            );
        }
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     phone: ?string,
     *     address: ?string,
     *     timezone: string
     * }
     */
    private function validatedOrganizationDetails(): array
    {
        $name = $this->postedString('name');
        $email = $this->postedString('email');
        $phone = $this->postedString('phone');
        $address = $this->postedString('address');
        $timezone = $this->postedString('timezone');

        $name = trim($name);
        $email = trim($email);
        $phone = trim($phone);
        $address = trim($address);
        $timezone = trim($timezone);

        if ($name === '' || mb_strlen($name) > 150) {
            throw new RuntimeException(
                'Organization name is required and must be at most 150 characters.'
            );
        }
        if ($email === '' || mb_strlen($email) > 150
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new RuntimeException(
                'Enter a valid organization email address (maximum 150 characters).'
            );
        }
        if (mb_strlen($phone) > 30) {
            throw new RuntimeException(
                'Phone number must be at most 30 characters.'
            );
        }
        if (strlen($address) > 65535) {
            throw new RuntimeException(
                'Organization address is too long.'
            );
        }
        try {
            new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            throw new RuntimeException('Select a valid timezone.', 0, $e);
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'timezone' => $timezone,
        ];
    }

    private function postedString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            throw new RuntimeException('Invalid organization profile input.');
        }

        return $value;
    }

    private function csrfToken(): string
    {
        $token = Session::get('organization_management_csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('organization_management_csrf_token', $token);
        }

        return $token;
    }

    private function verifyCsrfToken(): void
    {
        $sessionToken = Session::get('organization_management_csrf_token');
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

    private function validateUploadedLogo(array $file): string
    {
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'The logo exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'The logo is too large.',
                UPLOAD_ERR_PARTIAL => 'The logo upload was incomplete.',
                UPLOAD_ERR_NO_FILE => 'Choose an organization logo to upload.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the logo.',
                UPLOAD_ERR_EXTENSION => 'The server rejected the logo upload.',
            ];
            throw new RuntimeException(
                $messages[$uploadError] ?? 'The logo upload failed.'
            );
        }

        $temporaryPath = $file['tmp_name'] ?? '';
        if (
            !is_string($temporaryPath)
            || !is_uploaded_file($temporaryPath)
        ) {
            throw new RuntimeException('The uploaded logo is invalid.');
        }

        $fileSize = filesize($temporaryPath);
        if (
            $fileSize === false
            || $fileSize <= 0
            || $fileSize > self::MAX_LOGO_BYTES
        ) {
            throw new RuntimeException('Choose a logo no larger than 5 MB.');
        }

        $imageInfo = getimagesize($temporaryPath);
        $mimeType = $imageInfo['mime'] ?? '';
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (
            $imageInfo === false
            || !in_array($mimeType, $allowedMimeTypes, true)
        ) {
            throw new RuntimeException(
                'Use a valid JPEG, PNG, or WebP logo image.'
            );
        }

        if (class_exists(\finfo::class)) {
            $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
            if ($fileInfo->file($temporaryPath) !== $mimeType) {
                throw new RuntimeException(
                    'The uploaded file is not a valid image.'
                );
            }
        }

        return $mimeType;
    }

    private function removePreviousLogo(
        ?string $storedLogo,
        string $newFilename
    ): void
    {
        $filename = $this->storedLogoFilename($storedLogo);
        if ($filename === null || $filename === $newFilename) {
            return;
        }

        $oldFilePath = dirname(__DIR__, 2)
            . self::LOGO_DIRECTORY
            . '/'
            . $filename;
        if (is_file($oldFilePath) && !unlink($oldFilePath)) {
            error_log(
                'Unable to remove the previous organization logo: ' . $filename
            );
        }
    }

    private function removeUploadedLogo(?string $filePath): void
    {
        if ($filePath !== null && is_file($filePath) && !unlink($filePath)) {
            error_log('Unable to remove a failed organization logo upload.');
        }
    }

    private function storedLogoFilename(?string $storedLogo): ?string
    {
        if (!is_string($storedLogo)) {
            return null;
        }

        $filename = basename($storedLogo);
        return preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename)
            ? $filename
            : null;
    }

    /**
     * @param array{logo: ?string, profile_pictures: list<string>} $assets
     */
    private function removeDeletedOrganizationAssets(array $assets): void
    {
        $this->removePreviousLogo($assets['logo'], '');

        foreach ($assets['profile_pictures'] as $storedPicture) {
            $filename = basename($storedPicture);
            if (!preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename)) {
                continue;
            }

            $filePath = dirname(__DIR__, 2)
                . '/public/uploads/profile-pictures/'
                . $filename;
            if (is_file($filePath) && !unlink($filePath)) {
                error_log(
                    'Unable to remove an organization profile picture: '
                    . $filename
                );
            }
        }
    }

    private function flashAndRedirect(
        string $message,
        string $type,
        string $url
    ): void {
        Session::set('flash_message', [
            'type' => $type,
            'title' => $type === 'success' ? 'Organization updated' : 'Action failed',
            'message' => $message,
        ]);
        header('Location: ' . $url, true, 303);
        exit;
    }
}
