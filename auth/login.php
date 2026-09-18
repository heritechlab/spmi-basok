<?php
require_once "../config/config.php";

if (isset($_SESSION['login'])) {
    header("Location: ../dashboard/");
    exit;
}

$institutionLogo = null;
$institutionName = 'STIKES Harapan Ibu Jambi';

if (isset($conn) && $conn instanceof mysqli) {
    $qLogo = mysqli_query($conn, "SELECT logo, institution_name FROM institution_profile LIMIT 1");
    if ($qLogo && $rowLogo = mysqli_fetch_assoc($qLogo)) {
        $institutionLogo = $rowLogo['logo'] ?? null;
        $institutionName = $rowLogo['institution_name'] ?? $institutionName;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME; ?> | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
    * { box-sizing: border-box; }
    html, body { margin: 0; height: 100%; }
    body {
        font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        padding: 20px;
    }

    .blob-1 {
        position: absolute; top: -15%; left: -12%; width: 55%; height: 130%;
        background: rgba(255,255,255,0.08); border-radius: 45% 55% 60% 40% / 50% 45% 55% 50%;
        z-index: 0;
    }
    .blob-2 {
        position: absolute; bottom: -25%; right: -15%; width: 60%; height: 110%;
        background: rgba(76,29,149,0.55); border-radius: 55% 45% 40% 60% / 45% 55% 45% 55%;
        z-index: 0;
    }
    .blob-3 {
        position: absolute; top: 10%; right: 8%; width: 30%; height: 60%;
        background: rgba(139,92,246,0.35); border-radius: 50%;
        z-index: 0;
    }

    .login-wrapper {
        display: flex; align-items: center; justify-content: space-between;
        width: 100%; max-width: 1200px; padding: 40px; position: relative; z-index: 1;
        gap: 40px; flex-wrap: wrap;
    }

    .left-content { flex: 1 1 420px; color: #fff; }
    .left-content .logo-img { max-width: 460px; width: 100%; margin-bottom: 28px; filter: drop-shadow(0 6px 18px rgba(0,0,0,0.3)); }
    .left-content h1 { font-size: 46px; font-weight: 800; line-height: 1.1; margin: 0 0 6px; }
    .left-content .subtitle { font-size: 20px; font-weight: 800; margin-bottom: 22px; opacity: .97; }
    .left-content .subtitle .letter { text-decoration: underline; text-underline-offset: 4px; }
    .left-content p { font-size: 16px; line-height: 1.8; opacity: .9; max-width: 520px; margin-bottom: 30px; }

    .feature-pills { display: flex; flex-wrap: wrap; gap: 10px; }
    .feature-pill {
        border: 1.5px solid rgba(255,255,255,0.5); border-radius: 30px; padding: 11px 22px;
        font-size: 14px; font-weight: 700; letter-spacing: .3px; color: #fff;
        display: flex; align-items: center; gap: 8px;
    }

    .login-card {
        background: #fff; border-radius: 28px; padding: 48px 42px;
        width: 100%;
        box-shadow: 0 40px 90px rgba(0,0,0,0.4), 0 1px 0 rgba(255,255,255,0.5) inset;
        color: #1a1523;
        position: relative;
        overflow: hidden;
    }
    .login-card::before {
        content: "";
        position: absolute; top: 0; left: 0; right: 0; height: 6px;
        background: linear-gradient(90deg, #7c3aed, #a78bfa, #6d28d9);
    }
    .login-card-wrap { display: flex; flex-direction: column; align-items: center; flex: 0 0 400px; max-width: 400px; min-width: 0; }

    .institution-logo-top { margin-bottom: 4px; width: 400px; max-width: 100%; text-align: center; }
    .institution-logo-top img { height: 64px; max-width: 100%; width: auto; display: inline-block; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.25)); }

    .login-card .card-logo {
        display: block; margin: 0 auto 30px; max-width: 190px; height: auto; margin-top: -8px;
    }

    .field-group { display: flex; align-items: center; gap: 16px; margin-bottom: 30px; }
    .field-group i { font-size: 24px; color: #1a1523; flex-shrink: 0; }
    .field-inner { flex: 1; border-bottom: 2px solid #e5e2ee; padding-bottom: 12px; transition: border-color .15s; }
    .field-inner:focus-within { border-color: #7c3aed; }
    .field-inner input {
        border: none; outline: none; width: 100%; font-size: 15px; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase; color: #1a1523; background: transparent;
        padding: 4px 0;
    }
    .field-inner input::placeholder { color: #b8b4c8; }

    .btn-login-submit {
        width: 100%; background: linear-gradient(135deg, #7c3aed, #6d28d9); border: none; color: #fff;
        border-radius: 14px; padding: 15px; font-weight: 800; font-size: 14px; letter-spacing: .5px;
        box-shadow: 0 10px 24px rgba(124,58,237,0.35); transition: transform .15s, box-shadow .15s;
        margin-top: 8px;
    }
    .btn-login-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(124,58,237,0.45); }

    .remember-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
    .btn-toggle-password { background: none; border: none; color: #b8b4c8; padding: 0; font-size: 13px; }

    .footer-note { text-align: center; margin-top: 26px; font-size: 10.5px; color: #fff; opacity: .65; position: relative; z-index: 1; }

    @media (max-width: 860px) {
        .left-content { text-align: center; }
        .left-content p { margin-left: auto; margin-right: auto; }
        .feature-pills { justify-content: center; }
        .login-wrapper { justify-content: center; }
    }
</style>
</head>
<body>

<div class="blob-1"></div>
<div class="blob-2"></div>
<div class="blob-3"></div>

<div class="login-wrapper">

    <div class="left-content">
        <img src="<?= BASE_URL ?>assets/img/brand/app-name.png" alt="eSPMI BASOK" class="logo-img">
        <div class="subtitle">
            <span class="letter">B</span>erintegritas &middot;
            <span class="letter">A</span>kuntabel &middot;
            <span class="letter">S</span>istematis &middot;
            <span class="letter">O</span>bjektif &middot;
            <span class="letter">K</span>redibel
        </div>
        <p>
            Sistem Penjaminan Mutu Internal Elektronik untuk <?= htmlspecialchars($institutionName) ?> &mdash;
            mengintegrasikan Siklus PPEPP, Siklus OBE, dan Survey Kepuasan dalam satu platform terpadu.
        </p>
        <div class="feature-pills">
            <div class="feature-pill"><i class="bi bi-diagram-3-fill"></i> Siklus PPEPP</div>
            <div class="feature-pill"><i class="bi bi-mortarboard-fill"></i> Siklus OBE</div>
            <div class="feature-pill"><i class="bi bi-clipboard2-check-fill"></i> Survey Kepuasan</div>
        </div>
    </div>

    <div class="login-card-wrap">
    <div class="login-card">
        <?php if (!empty($institutionLogo)): ?>
        <div class="institution-logo-top">
            <img src="<?= BASE_URL . htmlspecialchars($institutionLogo) ?>" alt="Logo Institusi">
        </div>
        <?php endif; ?>
        <img src="<?= BASE_URL ?>assets/img/brand/app-name.png" alt="eSPMI BASOK" class="card-logo">

        <form action="login_process.php" method="POST">

            <div class="field-group">
                <i class="bi bi-person-fill"></i>
                <div class="field-inner">
                    <input type="text" name="username" placeholder="ENTER USERNAME" required>
                </div>
            </div>

            <div class="field-group">
                <i class="bi bi-lock-fill"></i>
                <div class="field-inner d-flex align-items-center justify-content-between">
                    <input type="password" id="password" name="password" placeholder="ENTER PASSWORD" required style="flex:1;">
                    <button class="btn-toggle-password" type="button" onclick="showPassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label small" for="remember" style="font-size:11px;">Ingat saya</label>
                </div>
            </div>

            <div class="cf-turnstile" data-sitekey="<?= TURNSTILE_SITE_KEY ?>" style="margin-bottom:20px;"></div>

            <button type="submit" class="btn-login-submit">LOGIN</button>

        </form>
    </div>
    </div>

</div>

<div class="footer-note">
    &copy; <?= date('Y') ?> eSPMI BASOK &mdash; <?= htmlspecialchars($institutionName) ?> &middot; Versi <?= APP_VERSION; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if(isset($_SESSION['success'])): ?>
<script>
Swal.fire({ icon:'success', title:'Berhasil', text:'<?= $_SESSION['success']; ?>', confirmButtonColor:'#7c3aed' });
</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<script>
Swal.fire({ icon:'error', title:'Login Gagal', text:'<?= $_SESSION['error']; ?>', confirmButtonColor:'#dc3545' });
</script>
<?php unset($_SESSION['error']); endif; ?>

<script>
function showPassword(){
    const password = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");
    password.type = password.type === "password" ? "text" : "password";
    icon.className = password.type === "password" ? "bi bi-eye" : "bi bi-eye-slash";
}
</script>

</body>
</html>