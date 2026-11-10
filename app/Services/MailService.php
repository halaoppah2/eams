<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

class MailService
{
    public function sendPasswordResetLink(
        string $recipient,
        string $resetUrl
    ): bool {
        return $this->sendLink(
            $recipient,
            'Reset your EAMS password',
            'We received a request to reset your EAMS password.',
            'Reset your password',
            $resetUrl,
            'This link expires in 60 minutes. If you did not request a password reset, you can ignore this email.',
            'Password reset'
        );
    }

    public function sendEmailVerificationLink(
        string $recipient,
        string $verificationUrl
    ): bool {
        return $this->sendLink(
            $recipient,
            'Verify your EAMS organization email',
            'Verify this email address to activate your EAMS organization.',
            'Verify email address',
            $verificationUrl,
            'This link expires in 24 hours. If you did not create this account, you can ignore this email.',
            'Email verification'
        );
    }

    private function sendLink(
        string $recipient,
        string $subject,
        string $intro,
        string $linkLabel,
        string $url,
        string $expirationMessage,
        string $messageType
    ): bool {
        $config = require __DIR__ . '/../../config/mail.php';

        if (
            $config['host'] === ''
            || $config['username'] === ''
            || $config['password'] === ''
            || $config['from_address'] === ''
        ) {
            error_log(
                $messageType
                . ' email not sent: SMTP configuration is incomplete.'
            );
            return false;
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->Port = $config['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            if ($config['encryption'] === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($config['encryption'] === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($config['encryption'] !== 'none') {
                throw new RuntimeException(
                    'Unsupported SMTP encryption setting.'
                );
            }

            $mailer->setFrom(
                $config['from_address'],
                $config['from_name']
            );
            $mailer->addAddress($recipient);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = sprintf(
                '<p>%s</p><p><a href="%s">%s</a></p><p>%s</p>',
                htmlspecialchars($intro, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($expirationMessage, ENT_QUOTES, 'UTF-8')
            );
            $mailer->AltBody = "{$linkLabel}: {$url}\n\n{$expirationMessage}";
            $mailer->send();
            return true;
        } catch (Throwable $e) {
            error_log(
                $messageType . ' email failed: ' . $e->getMessage()
            );
            return false;
        }
    }
}
