<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

if (!Auth::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Halaman ini hanya untuk Administrator.']);
    exit;
}

$repository = new SettingsRepository($conn);
$service    = new SettingsService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'get':

            echo json_encode($service->getAll());

            break;

        case 'update_general':

            echo json_encode($service->updateGeneral($_POST));

            break;

        case 'update_email':

            echo json_encode($service->updateEmail($_POST));

            break;

        case 'test_email':

            $testTo = trim($_POST['test_email_to'] ?? '');

            if ($testTo === '') {
                echo json_encode(['success' => false, 'message' => 'Isi alamat email tujuan tes terlebih dahulu.']);
                break;
            }

            require_once __DIR__ . '/../core/Mailer.php';

            $mailer = new Mailer();
            $sent = $mailer->send($testTo, $testTo, 'Uji Coba Email SIQUA', '<p>Ini email percobaan dari Pengaturan Sistem SIQUA.</p>');

            echo json_encode(['success' => $sent, 'message' => $sent ? 'Email percobaan berhasil dikirim.' : 'Gagal mengirim email. Periksa kembali pengaturan SMTP.']);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}