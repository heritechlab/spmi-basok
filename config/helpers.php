<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function setAlert($type, $message)
{
    $_SESSION['flash_alert'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function showAlert()
{
    if (!isset($_SESSION['flash_alert'])) {
        return;
    }

    $alert = $_SESSION['flash_alert'];

    echo '
    <div class="alert alert-'.$alert['type'].' alert-dismissible fade show">
        '.$alert['message'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';

    unset($_SESSION['flash_alert']);
}

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

function currentUser()
{
    return $_SESSION['user_id'] ?? null;
}

function isLogin()
{
    return isset($_SESSION['user_id']);
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

function redirect($url)
{
    header("Location: ".$url);
    exit;
}