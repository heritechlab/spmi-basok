<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/institution/repository.php';

$unitId = (int)($_GET['unit_id'] ?? 0);
$periodId = (int)($_GET['period_id'] ?? 0);

$repository = new PtpReportRepository($conn);
$service    = new PtpReportService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

require_once __DIR__ . '/../signatures/repository.php';
$sigRepo = new LaporanSignatureRepository($conn);
$signature = $sigRepo->find('ptp', $periodId, $unitId) ?? [];

$ketuaLpmNamaFinal = !empty($signature['ketua_lpm_nama']) ? $signature['ketua_lpm_nama'] : null;
$ketuaInstitusiNamaFinal = !empty($signature['ketua_institusi_nama']) ? $signature['ketua_institusi_nama'] : null;

$qrKetuaLpm = (!empty($signature['id']) && !empty($signature['ketua_lpm_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=ptp&id=' . $signature['id'] . '&role=ketua_lpm')
    : null;

$qrKetuaInstitusi = (!empty($signature['id']) && !empty($signature['ketua_institusi_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=ptp&id=' . $signature['id'] . '&role=ketua_institusi')
    : null;

$result = $service->getReportData($unitId, $periodId);

if (!$result['success']) {
    die('<p style="font-family: sans-serif; padding: 40px;">' . htmlspecialchars($result['message']) . '</p>');
}

$data = $result['data'];

$unit = $data['unit'];
$period = $data['period'];
$ketuaLpm = $data['ketua_lpm'];
$notulis = $data['notulis'];
$meetings = $data['meetings'];

$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

$statusBadgeText = ['Diusulkan' => 'Diusulkan', 'Ditingkatkan' => 'Sudah Ditingkatkan'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan PTP - <?= htmlspecialchars($unit['name']) ?></title>

 <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #059669;
            --primary-light: #6ee7b7;
            --accent: #0891b2;
            --amber: #d97706;
            --ink: #1f2937;
            --muted: #6b7280;
            --paper: #fdfefc;
        }

        @page { size: A4; margin: 30mm 30mm 30mm 30mm; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0 20px !important; }
            .page-break { page-break-after: always; }
            .cover-page { padding-top: 0 !important; }
        }

        body {
            font-family: "Lora", "Times New Roman", Times, serif;
            font-size: 13px;
            color: var(--ink);
            padding: 30px 50px;
            line-height: 1.7;
            background: var(--paper);
        }

        p { text-align: justify; }

        .no-justify p { text-align: center; }

        /* ===== SAMPUL PREMIUM ===== */

        .cover-page {
            position: relative;
            text-align: center;
            min-height: 237mm;
            padding: 0 !important;
            margin: -30px -50px;
            overflow: hidden;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-page::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--amber));
        }

        .cover-page::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--amber), var(--accent), var(--primary));
        }

        .cover-frame {
            width: calc(100% - 60px);
            height: calc(100% - 60px);
            margin: 30px;
            padding: 60px 30px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        .cover-top-group { width: 100%; }
        .cover-bottom-group { width: 100%; margin-top: 90px; }

        .cover-page img.cover-logo { display: block; height: 120px; margin: 0 auto 20px; filter: drop-shadow(0 4px 10px rgba(5,150,105,0.18)); }

        .cover-page .cover-kicker {
            display: inline-block;
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 14px;
        }

        .cover-page h1 {
            font-family: "Playfair Display", serif;
            font-size: 30px;
            font-weight: 800;
            margin: 6px 0 4px;
            color: var(--ink);
            letter-spacing: 0.5px;
        }

        .cover-page h2 {
            font-family: "Lora", serif;
            font-size: 15px;
            font-weight: 500;
            color: var(--muted);
            margin: 4px 0;
        }

        .cover-page .cover-divider {
            width: 90px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--amber));
            margin: 22px auto;
            border-radius: 3px;
        }

        .cover-ribbon {
            display: inline-block;
            margin-top: 28px;
            padding: 10px 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.4px;
        }

        .cover-page .cover-info { margin-top: 26px; font-size: 14px; }
        .cover-page .cover-info p { margin: 3px 0; }

        .cover-page .cover-year {
            margin-top: 50px;
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
        }

        /* ===== JUDUL BAB (gaya pembatas buku, motif bintang) ===== */

        .bab-title {
            position: relative;
            font-family: "Playfair Display", serif;
            font-size: 30px;
            font-weight: 700;
            text-align: center;
            color: var(--ink);
            letter-spacing: 0.3px;
            max-width: 520px;
            margin: 0 auto 40px;
            padding-bottom: 26px;
        }

        .bab-title::first-line {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--amber);
        }

        .bab-title::before {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary-light), transparent);
        }

        .bab-title::after {
            content: "✦";
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--primary);
            font-size: 14px;
            background: var(--paper);
            padding: 0 10px;
        }

        h3.sub-title {
            font-family: "Playfair Display", serif;
            font-size: 13px;
            font-weight: 700;
            margin-top: 20px;
            margin-bottom: 10px;
            color: var(--ink);
            padding-left: 10px;
            border-left: 4px solid var(--amber);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        table th, table td { border: 1px solid #dcf2e8; padding: 5px 8px; font-size: 12px; vertical-align: top; line-height: 1.3; }

        table th {
            background: linear-gradient(135deg, #ecfaf3, #dcf2e8);
            text-align: center;
            color: var(--primary);
            font-weight: 700;
        }

        table tr:nth-child(even) td { background: #fafdfb; }

        .signature-block { display: flex; justify-content: center; align-items: flex-start; gap: 60px; margin-top: 20px; }
        .signature { width: 220px; text-align: center; margin: 0 auto; }
        .signature p { text-align: center !important; margin: 4px 0; }
        .signature img { display: block !important; margin: 8px auto !important; }

        .signature-center { width: 260px; text-align: center; margin: 90px auto 0; }
        .signature-center p { text-align: center !important; margin: 4px 0; word-wrap: break-word; }
        .signature-center img { display: block !important; margin: 8px auto !important; }

        .signature .space, .signature-center .space { height: 70px; }

        .ba-box {
            border: 1px solid #dcf2e8;
            border-left: 4px solid var(--primary);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            background: #fdfefb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }

        .ba-box table { margin-bottom: 0; box-shadow: none; }
        .ba-box table td, .ba-box table th { border: none; padding: 3px 6px; }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 40px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(5,150,105,0.3);
            z-index: 999;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

    <!-- ===================================================== -->
    <!-- COVER -->
    <!-- ===================================================== -->

    <div class="cover-page page-break">
      <div class="cover-frame">

        <div class="cover-top-group">

            <?php if (!empty($profile['logo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" class="cover-logo" alt="Logo">
            <?php endif; ?>

            <span class="cover-kicker"><i class="bi bi-graph-up-arrow"></i> Sistem Penjaminan Mutu Internal</span>

            <h1>Laporan RTM<br>Permintaan Tindakan Peningkatan</h1>

            <div class="cover-divider"></div>

            <h2><?= htmlspecialchars($unit['name']) ?></h2>

            <div class="cover-ribbon">
                <i class="bi bi-calendar-event"></i> <?= htmlspecialchars($period['period_name']) ?>
            </div>

        </div>

        <div class="cover-bottom-group">

            <div class="cover-info">
                <?php if (!empty($profile['foundation_name'])): ?>
                    <h2><?= htmlspecialchars($profile['foundation_name']) ?></h2>
                <?php endif; ?>
                <h2><?= htmlspecialchars($profile['institution_name'] ?? '') ?></h2>
            </div>

            <div class="cover-year">
                <?= htmlspecialchars($profile['city'] ?? '') ?> &middot; <?= (int) $period['year'] ?>
            </div>

        </div>

      </div>
    </div>

    <!-- ===================================================== -->
    <!-- KATA PENGANTAR -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Kata Pengantar</div>

        <p>
            Puji syukur kami panjatkan kehadirat Tuhan Yang Maha Esa, karena atas rahmat dan karunia-Nya,
            pelaksanaan Permintaan Tindakan Peningkatan (PTP) pada Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            untuk Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> dapat terlaksana dengan baik.
        </p>

        <p>
            Laporan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tahap Peningkatan dalam siklus
            Sistem Penjaminan Mutu Internal (SPMI), yang membahas peluang peningkatan standar dan indikator
            mutu yang telah Mencapai/Melampaui target, guna mendorong budaya mutu berkelanjutan.
        </p>

        <p>Kami menyampaikan terima kasih kepada seluruh pihak yang telah berkontribusi dalam pelaksanaan rapat ini.</p>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua LPM,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpm) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- HALAMAN PENGESAHAN -->
    <!-- ===================================================== -->

    <div class="page-break no-justify">

        <div class="bab-title">Halaman Pengesahan</div>

        <p>
            Laporan Permintaan Tindakan Peningkatan (PTP) untuk Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            pada Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> ini disahkan oleh:
        </p>

        <p style="text-align: center; margin-top: 40px;">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?>
        </p>

<div class="signature-block">
            <div class="signature">
                <p>Ketua LPM,<br>&nbsp;</p>
                <?php if ($qrKetuaLpm): ?>
                    <img src="<?= $qrKetuaLpm ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpmNamaFinal ?: $ketuaLpm) ?></p>
            </div>
        </div>

        <div class="signature-center">
            <p>Menyetujui,<br>Ketua <?= htmlspecialchars($profile['institution_name'] ?? 'Institusi') ?>,</p>
            <?php if ($qrKetuaInstitusi): ?>
                <img src="<?= $qrKetuaInstitusi ?>" style="width:45px; height:45px; margin: 8px 0;">
            <?php else: ?>
                <div class="space"></div>
            <?php endif; ?>
            <p style="font-weight:bold; text-decoration: underline;">
                <?= htmlspecialchars($ketuaInstitusiNamaFinal ?: ($profile['leader_name'] ?? '(...........................)')) ?>
            </p>
        </div>

    </div>

<!-- ===================================================== -->
    <!-- BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Latar Belakang</h3>
        <p>
            Permintaan Tindakan Peningkatan (PTP) merupakan bagian dari tahap Peningkatan dalam siklus
            Sistem Penjaminan Mutu Internal (SPMI), yang bertujuan mendorong peningkatan standar dan
            indikator mutu yang telah mencapai atau melampaui target pada pelaksanaan Audit Mutu Internal
            sebelumnya. Rapat PTP diselenggarakan sebagai forum untuk merumuskan usulan peningkatan
            pernyataan standar, indikator, maupun target yang berlaku pada Unit Kerja
            <strong><?= htmlspecialchars($unit['name']) ?></strong>.
        </p>

        <h3 class="sub-title">B. Tujuan</h3>
        <p>
            1. Merumuskan usulan peningkatan standar dan/atau indikator mutu berdasarkan capaian yang telah
            Mencapai atau Melampaui target;<br>
            2. Menetapkan perubahan pernyataan standar, indikator, dan/atau target yang lebih tinggi guna
            mendorong budaya mutu berkelanjutan;<br>
            3. Mendokumentasikan proses peningkatan standar sebagai bagian dari siklus PPEPP yang utuh dan
            tertelusur.
        </p>

        <h3 class="sub-title">C. Ruang Lingkup</h3>
        <p>
            Laporan ini mencakup pelaksanaan Rapat Peningkatan (PTP) pada Periode Audit
            <strong><?= htmlspecialchars($period['period_name']) ?></strong> untuk Unit Kerja
            <strong><?= htmlspecialchars($unit['name']) ?></strong>, sebagaimana diuraikan pada Berita
            Acara Rapat dan Daftar Peningkatan Standar &amp; Indikator pada BAB II.
        </p>

    </div>

<!-- ===================================================== -->
    <!-- BAB II DAFTAR PENINGKATAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Daftar Peningkatan Standar</div>

        <?php
            $allItems = [];
            foreach ($meetings as $m) {
                foreach ($m['items'] as $it) {
                    $allItems[] = $it;
                }
            }
        ?>

        <p style="font-weight:bold; margin-bottom: 6px;">Tabel 2.1 Daftar Peningkatan Standar</p>

        <table>
            <thead>
                <tr>
                    <th width="25">No</th>
                    <th width="130">Standar</th>
                    <th>Sebelum</th>
                    <th>Sesudah (Peningkatan)</th>
                    <th width="90">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allItems)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada usulan peningkatan.</td></tr>
                <?php else: ?>
                    <?php foreach ($allItems as $i => $it): ?>
                        <?php
                            $before = '';
                            $after = '';
                            if (!empty($it['new_statement'])) {
                                $before .= '<div><small>Pernyataan:</small> ' . htmlspecialchars($it['old_statement'] ?: '-') . '</div>';
                                $after .= '<div><small>Pernyataan:</small> ' . htmlspecialchars($it['new_statement']) . '</div>';
                            }
                            if (!empty($it['new_indicator'])) {
                                $before .= '<div><small>Indikator:</small> ' . htmlspecialchars($it['old_indicator'] ?: '-') . '</div>';
                                $after .= '<div><small>Indikator:</small> ' . htmlspecialchars($it['new_indicator']) . '</div>';
                            }
                            if (!empty($it['new_target'])) {
                                $before .= '<div><small>Target:</small> ' . htmlspecialchars($it['old_target'] ?: '-') . '</div>';
                                $after .= '<div><small>Target:</small> ' . htmlspecialchars($it['new_target']) . '</div>';
                            }
                            $standardLabel = $it['standard_name'] ?? $it['standard_code'] ?? '-';
                        ?>
<tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($standardLabel) ?></td>
                            <td><?= $before ?></td>
                            <td><?= $after ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($statusBadgeText[$it['status']] ?? $it['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($allItems)): ?>
        <?php
            $standardCounts = [];
            $ditingkatkanCount = 0;

            foreach ($allItems as $it) {
                $sn = $it['standard_name'] ?? $it['standard_code'] ?? '-';
                $standardCounts[$sn] = ($standardCounts[$sn] ?? 0) + 1;

                if (($it['status'] ?? '') === 'Ditingkatkan') {
                    $ditingkatkanCount++;
                }
            }

            arsort($standardCounts);
            $topStandardName = array_key_first($standardCounts);
            $topStandardCount = $standardCounts[$topStandardName] ?? 0;
            $totalUsulan = count($allItems);
            $diusulkanCount = $totalUsulan - $ditingkatkanCount;
        ?>
        <p>
            Berdasarkan Tabel 2.1 di atas terlihat bahwa terdapat <strong><?= $totalUsulan ?></strong> usulan
            peningkatan standar dan/atau indikator mutu pada Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>.
            Dari jumlah tersebut, sebanyak <strong><?= $ditingkatkanCount ?></strong> usulan telah berstatus
            "Sudah Ditingkatkan" dan diterapkan pada Master Indikator, sedangkan
            <strong><?= $diusulkanCount ?></strong> usulan lainnya masih berstatus "Diusulkan" dan menunggu
            proses penetapan lebih lanjut. Standar dengan usulan peningkatan terbanyak adalah
            <strong><?= htmlspecialchars($topStandardName) ?></strong> dengan <strong><?= $topStandardCount ?></strong>
            usulan, yang menunjukkan Unit Kerja ini memiliki peluang peningkatan mutu yang cukup besar pada
            standar tersebut, sekaligus menjadi perhatian khusus dalam proses Penetapan pada siklus berikutnya.
        </p>
        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB III PENUTUP -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB III<br>Penutup</div>

        <p>
            Berdasarkan pelaksanaan Rapat Peningkatan pada Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong>, telah dirumuskan
            sebanyak <strong><?= count($allItems) ?></strong> usulan peningkatan standar dan/atau indikator
            mutu, sebagaimana diuraikan pada BAB II.
        </p>

        <p>
            Usulan peningkatan yang telah disahkan akan diterapkan ke Master Indikator sebagai pembaruan
            standar acuan pada siklus Penetapan berikutnya, sehingga siklus PPEPP dapat berjalan secara
            berkelanjutan dan terus meningkat.
        </p>

        <p>Demikian laporan ini disusun untuk dapat dipergunakan sebagaimana mestinya.</p>

    </div>

</body>
</html>