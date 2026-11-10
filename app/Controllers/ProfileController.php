<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Session;
use RuntimeException;

class ProfileController extends Controller
{
    private const MAX_PROFILE_PICTURE_BYTES = 5242880;
    private const PROFILE_PICTURE_URL_PREFIX =
        APP_BASE_PATH . '/uploads/profile-pictures/';

    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function show(): string
    {
        $user = $_SESSION['user'] ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $employeeId = (int) ($user['employee_id'] ?? 0);
        $organizationId = (int) ($user['organization_id'] ?? 0);

        if ($userId <= 0 || $employeeId <= 0 || $organizationId <= 0) {
            throw new RuntimeException(
                'Your user account is not linked to an employee.'
            );
        }

        $profile = $this->userRepository->findProfile(
            $userId,
            $employeeId,
            $organizationId
        );

        if ($profile === null) {
            throw new RuntimeException(
                'Your employee profile could not be found.'
            );
        }

        $returnTo = $this->validatedReturnTo(
            $_GET['return_to'] ?? null
        );

        return $this->view('profile/show', [
            'profile' => $profile,
            'returnTo' => $returnTo,
            'profilePictureUrl' => $this->profilePictureUrl(
                $profile['profile_picture'] ?? null
            ),
        ]);
    }

    public function updatePicture(): never
    {
        $user = $_SESSION['user'] ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $employeeId = (int) ($user['employee_id'] ?? 0);
        $organizationId = (int) ($user['organization_id'] ?? 0);
        $returnTo = $this->validatedReturnTo(
            $_POST['return_to'] ?? null
        );
        $newFilePath = null;

        try {
            if ($userId <= 0 || $employeeId <= 0 || $organizationId <= 0) {
                throw new RuntimeException(
                    'Your user account is not linked to an employee.'
                );
            }

            $profile = $this->userRepository->findProfile(
                $userId,
                $employeeId,
                $organizationId
            );

            if ($profile === null) {
                throw new RuntimeException(
                    'Your employee profile could not be found.'
                );
            }

            $uploadedFile = $_FILES['profile_picture'] ?? null;

            if (!is_array($uploadedFile)) {
                throw new RuntimeException(
                    'Choose an image to upload.'
                );
            }

            $mimeType = $this->validateUploadedPicture($uploadedFile);

            $extensionByMimeType = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $filename = bin2hex(random_bytes(16))
                . '.'
                . $extensionByMimeType[$mimeType];
            $uploadDirectory = dirname(__DIR__, 2)
                . '/public/uploads/profile-pictures';

            if (
                !is_dir($uploadDirectory) &&
                !mkdir($uploadDirectory, 0755, true) &&
                !is_dir($uploadDirectory)
            ) {
                throw new RuntimeException(
                    'The profile picture folder could not be created.'
                );
            }

            $newFilePath = $uploadDirectory . '/' . $filename;

            if (!move_uploaded_file(
                $uploadedFile['tmp_name'],
                $newFilePath
            )) {
                $newFilePath = null;
                throw new RuntimeException(
                    'The profile picture could not be saved.'
                );
            }

            $pictureUrl = self::PROFILE_PICTURE_URL_PREFIX . $filename;

            if (!$this->userRepository->updateProfilePicture(
                $userId,
                $employeeId,
                $organizationId,
                $pictureUrl
            )) {
                throw new RuntimeException(
                    'The profile picture could not be updated.'
                );
            }

            $sessionUser = Session::get('user', []);
            $sessionUser['profile_picture'] = $pictureUrl;
            Session::set('user', $sessionUser);

            $this->removePreviousPicture(
                $profile['profile_picture'] ?? null,
                $filename
            );

            Session::set('flash_message', [
                'type' => 'success',
                'title' => 'Profile updated',
                'message' => 'Your profile picture has been updated.'
            ]);
        } catch (RuntimeException $e) {
            if ($newFilePath !== null && is_file($newFilePath)) {
                unlink($newFilePath);
            }

            Session::set('flash_message', [
                'type' => 'error',
                'title' => 'Action failed',
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            if ($newFilePath !== null && is_file($newFilePath)) {
                unlink($newFilePath);
            }

            throw $e;
        }

        header(
            'Location: ' . APP_BASE_PATH . '/profile?return_to='
                . rawurlencode($returnTo),
            true,
            303
        );
        exit;
    }

    private function validateUploadedPicture(array $file): string
    {
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'The image exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'The image is too large.',
                UPLOAD_ERR_PARTIAL => 'The image upload was incomplete.',
                UPLOAD_ERR_NO_FILE => 'Choose an image to upload.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server upload folder is unavailable.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the image.',
                UPLOAD_ERR_EXTENSION => 'The server rejected the image upload.',
            ];

            throw new RuntimeException(
                $messages[$uploadError] ?? 'The image upload failed.'
            );
        }

        $temporaryPath = $file['tmp_name'] ?? '';

        if (
            !is_string($temporaryPath) ||
            !is_uploaded_file($temporaryPath)
        ) {
            throw new RuntimeException(
                'The uploaded image is invalid.'
            );
        }

        $fileSize = filesize($temporaryPath);

        if (
            $fileSize === false ||
            $fileSize <= 0 ||
            $fileSize > self::MAX_PROFILE_PICTURE_BYTES
        ) {
            throw new RuntimeException(
                'Choose an image no larger than 5 MB.'
            );
        }

        $imageInfo = getimagesize($temporaryPath);
        $mimeType = $imageInfo['mime'] ?? '';
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (
            $imageInfo === false ||
            !in_array($mimeType, $allowedMimeTypes, true)
        ) {
            throw new RuntimeException(
                'Use a valid JPEG, PNG, or WebP image.'
            );
        }

        if (class_exists(\finfo::class)) {
            $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMimeType = $fileInfo->file($temporaryPath);

            if ($detectedMimeType !== $mimeType) {
                throw new RuntimeException(
                    'The uploaded file is not a valid image.'
                );
            }
        }

        return $mimeType;
    }

    private function profilePictureUrl($storedPath): ?string
    {
        if (!is_string($storedPath)) {
            return null;
        }

        $filename = basename($storedPath);

        if (
            !preg_match(
                '/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/',
                $filename
            )
        ) {
            return null;
        }

        return self::PROFILE_PICTURE_URL_PREFIX . $filename;
    }

    private function removePreviousPicture(
        $storedPath,
        string $newFilename
    ): void {
        $pictureUrl = $this->profilePictureUrl($storedPath);

        if (
            $pictureUrl === null ||
            basename($pictureUrl) === $newFilename
        ) {
            return;
        }

        $oldFilePath = dirname(__DIR__, 2)
            . '/public/uploads/profile-pictures/'
            . basename($pictureUrl);

        if (is_file($oldFilePath) && !unlink($oldFilePath)) {
            error_log(
                'Unable to remove the previous profile picture: '
                . basename($oldFilePath)
            );
        }
    }

    private function validatedReturnTo($returnTo): string
    {
        $fallback = APP_BASE_PATH . '/dashboard';

        if (
            !is_string($returnTo) ||
            $returnTo === '' ||
            $returnTo[0] !== '/' ||
            str_starts_with($returnTo, '//') ||
            str_contains($returnTo, '\\') ||
            preg_match('/[\r\n]/', $returnTo)
        ) {
            return $fallback;
        }

        $parts = parse_url($returnTo);
        $path = $parts['path'] ?? '';

        if (
            isset($parts['scheme']) ||
            isset($parts['host']) ||
            !is_string($path) ||
            !str_starts_with($path, APP_BASE_PATH . '/') ||
            $path === APP_BASE_PATH . '/profile'
        ) {
            return $fallback;
        }

        return $returnTo;
    }
}
