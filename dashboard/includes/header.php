<?php
/*
|--------------------------------------------------------------------------
| SIQUA v1.0 Production
|--------------------------------------------------------------------------
| File    : dashboard/includes/header.php
| Version : 0.1.2
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>SIQUA | Sistem Informasi Audit Mutu Internal</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icon -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <!-- Google Font -->
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <!-- ======================
         CSS SIQUA
    ======================= -->

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/dashboard.css">

      <link rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/workspace.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/sidebar.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/navbar.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/cards.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/chart.css">

      <link rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/progress.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/responsive.css">

      <link rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/warning.css">

</head>

<body>

<div class="wrapper">