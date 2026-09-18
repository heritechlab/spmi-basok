<?php

declare(strict_types=1);

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

$pageTitle = $pageTitle ?? APP_TITLE;

?><!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta http-equiv="X-UA-Compatible" content="IE=edge">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars($pageTitle) ?></title>

<link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.png">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/base.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sidebar.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cards.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/workspace.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/indicators.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/utilities.css">

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/responsive.css">

<?php
/*
|--------------------------------------------------------------------------
| Page Specific CSS
|--------------------------------------------------------------------------
*/
if (!empty($pageCss)) {
    echo '<link rel="stylesheet" href="' . BASE_URL . $pageCss . '?v=' . APP_VERSION . '">';
}
?>

</head>

<body>