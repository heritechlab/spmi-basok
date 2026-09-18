<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['login']) || (int)($_SESSION['role_id'] ?? 0) !== 4) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}