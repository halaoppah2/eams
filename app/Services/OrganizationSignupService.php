<?php

namespace App\Services;

use App\Database;
use App\Repositories\SignupRepository;
use RuntimeException;
use Throwable;

class OrganizationSignupService
{
    private SignupRepository $signupRepository;
    private MailService $mailService;

    public function __construct()
    {
        $this->signupRepository = new SignupRepository();
        $this->mailService = new MailService();
    }

    public function signup(array $input): void
    {
        $organizationName = trim($this->inputString($input, 'organization_name'));
        $firstName = trim($this->inputString($input, 'first_name'));
        $lastName = trim($this->inputString($input, 'last_name'));
        $email = trim($this->inputString($input, 'email'));
        $phone = trim($this->inputString($input, 'phone'));
        $username = trim($this->inputString($input, 'username'));
        $password = $this->inputString($input, 'password');
        $passwordConfirmation = $this->inputString(
            $input,
            'password_confirmation'
        );

        if ($organizationName === '' || strlen($organizationName) > 150) {
            throw new RuntimeException(
                'Enter an organization name no longer than 150 characters.'
            );
        }

        if (
            $firstName === ''
            || strlen($firstName) > 100
            || $lastName === ''
            || strlen($lastName) > 100
        ) {
            throw new RuntimeException(
                'Enter the owner first and last names.'
            );
        }

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 150
        ) {
            throw new RuntimeException('Enter a valid email address.');
        }

        // check number length and should have only digits
        if (strlen($phone) > 30) {
            throw new RuntimeException(
                'The phone number cannot exceed 30 characters.'
            );
        }

        if ($phone !== '' && !ctype_digit($phone)) {
            throw new RuntimeException(
                'The phone number can only contain digits.'
            );
        }

        if (!preg_match('/\A[A-Za-z0-9._-]{3,100}\z/', $username)) {
            throw new RuntimeException(
                'Username must be 3-100 characters and use only letters, numbers, dots, underscores, or hyphens.'
            );
        }

        if (strlen($password) < 8) {
            throw new RuntimeException(
                'Password must be at least 8 characters long.'
            );
        }

        if (strlen($password) > 72) {
            throw new RuntimeException(
                'Password cannot exceed 72 bytes.'
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new RuntimeException('The passwords do not match.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Unable to secure the password.');
        }

        $db = Database::connection();
        $rawToken = bin2hex(random_bytes(32));

        try {
            $db->beginTransaction();

            if ($this->signupRepository->usernameExists($username)) {
                throw new RuntimeException(
                    'That username is already in use. Choose another; usernames must be unique across EAMS.'
                );
            }

            $organizationStatusId = $this->signupRepository->statusId(
                'organization_statuses',
                'INACTIVE'
            );
            $branchStatusId = $this->signupRepository->statusId(
                'branch_statuses',
                'ACTIVE'
            );
            $departmentStatusId = $this->signupRepository->statusId(
                'department_statuses',
                'ACTIVE'
            );
            $positionStatusId = $this->signupRepository->statusId(
                'position_statuses',
                'ACTIVE'
            );
            $employeeStatusId = $this->signupRepository->statusId(
                'employee_statuses',
                'ACTIVE'
            );
            $employmentTypeId = $this->signupRepository->statusId(
                'employment_types',
                'FULL_TIME'
            );
            $userStatusId = $this->signupRepository->statusId(
                'user_statuses',
                'INACTIVE'
            );
            $leaveTypeStatusId = $this->signupRepository->statusId(
                'leave_type_statuses',
                'ACTIVE'
            );

            $organizationId = $this->signupRepository->createOrganization(
                $organizationName,
                $email,
                $phone === '' ? null : $phone,
                'Africa/Accra',
                $organizationStatusId
            );
            $this->signupRepository->createDefaultLeaveTypes(
                $organizationId,
                $leaveTypeStatusId
            );
            $branchId = $this->signupRepository->createBranch(
                $organizationId,
                $branchStatusId
            );
            $departmentId = $this->signupRepository->createDepartment(
                $organizationId,
                $branchId,
                $departmentStatusId
            );
            $positionId = $this->signupRepository->createPosition(
                $organizationId,
                $positionStatusId
            );
            $employeeId = $this->signupRepository->createEmployee([
                'organization_id' => $organizationId,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'employee_number' => 'OWNER-001',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'hire_date' => date('Y-m-d'),
                'employment_type_id' => $employmentTypeId,
                'employee_status_id' => $employeeStatusId,
            ]);
            $roleId = $this->signupRepository->createOwnerRole(
                $organizationId
            );
            $this->signupRepository->createEmployeeRole(
                $organizationId
            );
            $userId = $this->signupRepository->createUser(
                $organizationId,
                $employeeId,
                $username,
                $email,
                $passwordHash,
                $userStatusId
            );
            $this->signupRepository->assignRole($userId, $roleId);
            $this->signupRepository->createTrialSubscription(
                $organizationId,
                14
            );
            $this->signupRepository->createVerificationToken(
                $userId,
                hash('sha256', $rawToken)
            );

            $mailConfig = require __DIR__ . '/../../config/mail.php';
            $verificationUrl = $mailConfig['app_url']
                . '/verify-email?token='
                . rawurlencode($rawToken);

            if (!$this->mailService->sendEmailVerificationLink(
                $email,
                $verificationUrl
            )) {
                throw new RuntimeException(
                    'We could not send the verification email. Check the SMTP settings and try again.'
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public function verifyEmail(string $token): void
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            throw new RuntimeException(
                'This verification link is invalid or expired.'
            );
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();
            $verification = $this->signupRepository
                ->findValidVerificationToken(hash('sha256', $token));

            if (!$verification) {
                throw new RuntimeException(
                    'This verification link is invalid or expired.'
                );
            }

            $userId = (int) $verification['user_id'];
            $organizationId = (int) $verification['organization_id'];

            if (
                !$this->signupRepository->activatePendingUser(
                    $userId,
                    $organizationId
                )
                || !$this->signupRepository->activateOrganization(
                    $organizationId
                )
                || !$this->signupRepository->markVerificationTokenUsed(
                    (int) $verification['token_id']
                )
            ) {
                throw new RuntimeException(
                    'This verification link is invalid or the account has already been activated.'
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private function inputString(array $input, string $key): string
    {
        return is_string($input[$key] ?? null)
            ? $input[$key]
            : '';
    }
}
