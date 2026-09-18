<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #bmpPage { font-size: 13px; }
    #bmpPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #bmpPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #bmpPage .nav-pills .nav-link { font-size: 12.5px; font-weight: 600; color: #6b6785; padding: 8px 18px; border-radius: 24px; }
    #bmpPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #bmpPage iframe { width: 100%; min-height: 640px; border: 0; display: block; }
</style>

<div class="container-fluid py-4" id="bmpPage">

    <div class="page-title">Bentuk &amp; Metode Pembelajaran</div>
    <div class="page-subtitle">Master baku Bentuk dan Metode Pembelajaran (SN-Dikti), berlaku untuk seluruh Program Studi</div>

    <ul class="nav nav-pills mb-3" id="bmpTabs">
        <li class="nav-item">
            <button class="nav-link active" data-tab="bentuk" type="button">Bentuk Pembelajaran</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-tab="metode" type="button">Metode Pembelajaran</button>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <iframe id="bmpFrameBentuk" src="<?= BASE_URL ?>obe/bentuk_pembelajaran/index.php?embed=1"></iframe>
            <iframe id="bmpFrameMetode" src="about:blank" style="display:none;"></iframe>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    $(document).on('click', '#bmpTabs .nav-link', function () {
        $('#bmpTabs .nav-link').removeClass('active');
        $(this).addClass('active');

        const tab = $(this).data('tab');

        if (tab === 'bentuk') {
            $('#bmpFrameBentuk').show();
            $('#bmpFrameMetode').hide();
        } else {
            if ($('#bmpFrameMetode').attr('src') === 'about:blank') {
                $('#bmpFrameMetode').attr('src', '<?= BASE_URL ?>obe/metode_pembelajaran/index.php?embed=1');
            }
            $('#bmpFrameMetode').show();
            $('#bmpFrameBentuk').hide();
        }
    });
</script>