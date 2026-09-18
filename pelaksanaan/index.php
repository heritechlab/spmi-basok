<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$portals = [
    ['label' => 'Portal LMS (E-Learning)', 'desc' => 'Sistem pembelajaran daring untuk dosen dan mahasiswa.', 'url' => 'https://elearning.stikes-hi.ac.id/', 'icon' => 'bi-laptop', 'color' => 'purple', 'external' => true],
    ['label' => 'Portal Dosen', 'desc' => 'Akses akademik dan administrasi khusus dosen.', 'url' => 'https://dosen.stikes-hi.ac.id/', 'icon' => 'bi-person-workspace', 'color' => 'blue', 'external' => true],
    ['label' => 'Portal Mahasiswa', 'desc' => 'Akses akademik dan administrasi khusus mahasiswa.', 'url' => 'https://mhs.stikes-hi.ac.id/', 'icon' => 'bi-mortarboard', 'color' => 'green', 'external' => true],
    ['label' => 'Jurnal Ilmiah', 'desc' => 'Publikasi dan pengelolaan jurnal ilmiah institusi.', 'url' => 'https://jurnal.stikes-hi.ac.id/', 'icon' => 'bi-journal-richtext', 'color' => 'orange', 'external' => true],
    ['label' => 'E-Catalog', 'desc' => 'Katalog elektronik institusi.', 'url' => 'https://ecatalog.stikes-hi.ac.id/', 'icon' => 'bi-collection', 'color' => 'red', 'external' => true],
    ['label' => 'Upload Bukti Pelaksanaan Standar Mutu', 'desc' => 'Kurikulum, Panduan, RPS, Absensi, Nilai, SK Dosen Pengampu, dan dokumen lainnya.', 'url' => BASE_URL . 'bukti/', 'icon' => 'bi-cloud-upload', 'color' => 'teal', 'external' => false],
    ['label' => 'Data Dukung Akreditasi Program Studi', 'desc' => 'Upload dokumen bukti dukung akreditasi Program Studi per Kriteria.', 'url' => BASE_URL . 'akreditasi_prodi/', 'icon' => 'bi-mortarboard-fill', 'color' => 'indigo', 'external' => false],
    ['label' => 'Data Dukung Akreditasi Institusi', 'desc' => 'Upload dokumen bukti dukung akreditasi Institusi per Kriteria.', 'url' => BASE_URL . 'akreditasi_institusi/', 'icon' => 'bi-bank2', 'color' => 'pink', 'external' => false],
];

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Pelaksanaan</h4>

    <div class="dual-card-row mb-4">

        <div class="greeting-card">

            <?php
                $hour = (int) date('H');
                if ($hour < 11) { $greeting = 'Selamat Pagi'; }
                elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
                elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
                else { $greeting = 'Selamat Malam'; }
            ?>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?php
                    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
                ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Pengguna') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Tautan cepat ke sistem/portal operasional yang menjadi bukti pelaksanaan kegiatan akademik dan non-akademik di lingkungan institusi. Setiap portal memiliki sistem login tersendiri.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Pengguna" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                Ringkasan Portal
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Portal Tersedia</div>
                    <div class="info-card-total-value"><?= count($portals) ?></div>
                </div>

                <div class="info-card-items-grid info-card-items-grid-1">

                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Status</div>
                            <div class="info-card-value" style="font-size:14px;">Semua Aktif</div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="portal-grid">

        <?php foreach ($portals as $p): ?>
            <a href="<?= htmlspecialchars($p['url']) ?>" <?= $p['external'] ? 'target="_blank" rel="noopener"' : '' ?> class="portal-card accent-<?= $p['color'] ?>">

                <div class="portal-card-icon">
                    <i class="bi <?= $p['icon'] ?>"></i>
                </div>

                <div class="portal-card-body">
                    <div class="portal-card-title"><?= htmlspecialchars($p['label']) ?></div>
                    <div class="portal-card-desc"><?= htmlspecialchars($p['desc']) ?></div>
                </div>

                <div class="portal-card-btn">
                    Kunjungi Sistem <i class="bi bi-arrow-right"></i>
                </div>

            </a>
        <?php endforeach; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>