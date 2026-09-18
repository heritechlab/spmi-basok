<?php

require_once __DIR__ . '/core/Mailer.php';

$mailer = new Mailer();

$result = $mailer->send(
    'basokbukhari01@gmail.com', // ganti dengan email Bapak sendiri untuk tes
    'Test SIQUA',
    'Uji Coba Email SIQUA',
    '<h3>Halo!</h3><p>Ini email percobaan dari sistem SIQUA.</p>'
);

echo $result ? 'BERHASIL terkirim!' : 'GAGAL, cek error_log.';