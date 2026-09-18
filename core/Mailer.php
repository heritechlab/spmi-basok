<?php

declare(strict_types=1);

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $config;

    public function __construct()
    {
        $fileConfig = require __DIR__ . '/../config/mail.php';

        $this->config = $fileConfig;

        try {

            global $conn;

            if (isset($conn) && $conn instanceof mysqli) {

                $result = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%'");

                if ($result) {

                    $dbSettings = [];

                    while ($row = $result->fetch_assoc()) {
                        $dbSettings[$row['setting_key']] = $row['setting_value'];
                    }

                    if (!empty($dbSettings['smtp_host'])) {
                        $this->config['host'] = $dbSettings['smtp_host'];
                    }

                    if (!empty($dbSettings['smtp_port'])) {
                        $this->config['port'] = (int) $dbSettings['smtp_port'];
                    }

                    if (!empty($dbSettings['smtp_username'])) {
                        $this->config['username'] = $dbSettings['smtp_username'];
                    }

                    if (!empty($dbSettings['smtp_password'])) {
                        $this->config['password'] = $dbSettings['smtp_password'];
                    }

                    if (!empty($dbSettings['smtp_from_email'])) {
                        $this->config['from_email'] = $dbSettings['smtp_from_email'];
                    } elseif (!empty($dbSettings['smtp_username'])) {
                        $this->config['from_email'] = $dbSettings['smtp_username'];
                    }

                    if (!empty($dbSettings['smtp_from_name'])) {
                        $this->config['from_name'] = $dbSettings['smtp_from_name'];
                    }
                }
            }

        } catch (Throwable $e) {
            // Kalau tabel belum ada / query gagal, tetap pakai config file lama.
        }
    }

    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml): bool
    {
        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;

            $mail->send();

            return true;

        } catch (Exception $e) {

            error_log('Mailer error: ' . $mail->ErrorInfo);

            return false;
        }
    }
}