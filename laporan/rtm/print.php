<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/institution/repository.php';

$unitId = (int)($_GET['unit_id'] ?? 0);
$periodId = (int)($_GET['period_id'] ?? 0);

$repository = new RtmReportRepository($conn);
$service    = new RtmReportService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

require_once __DIR__ . '/../signatures/repository.php';
$sigRepo = new LaporanSignatureRepository($conn);
$pengesahanSignature = $sigRepo->find('rtm', $periodId, $unitId) ?? [];

$ketuaLpmNamaFinal = !empty($pengesahanSignature['ketua_lpm_nama']) ? $pengesahanSignature['ketua_lpm_nama'] : null;
$ketuaInstitusiNamaFinal = !empty($pengesahanSignature['ketua_institusi_nama']) ? $pengesahanSignature['ketua_institusi_nama'] : null;

$qrKetuaLpmPengesahan = (!empty($pengesahanSignature['id']) && !empty($pengesahanSignature['ketua_lpm_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=rtm_pengesahan&id=' . $pengesahanSignature['id'] . '&role=ketua_lpm')
    : null;

$qrKetuaInstitusiPengesahan = (!empty($pengesahanSignature['id']) && !empty($pengesahanSignature['ketua_institusi_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=rtm_pengesahan&id=' . $pengesahanSignature['id'] . '&role=ketua_institusi')
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
$detailedPlans = $data['detailed_plans'];
$prioritizedPlans = $data['prioritized_plans'];
$allDocuments = $data['all_documents'];

$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

$kuadranLabel = [
    'Important-Urgent'         => 'I - Penting & Mendesak',
    'Important-Not Urgent'     => 'II - Penting, Tidak Mendesak',
    'Not Important-Urgent'     => 'III - Tidak Penting, Mendesak',
    'Not Important-Not Urgent' => 'IV - Tidak Penting, Tidak Mendesak',
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <title>Laporan RTM - <?= htmlspecialchars($unit['name']) ?></title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #0e7490;
            --primary-light: #67e8f9;
            --accent: #2563eb;
            --copper: #b45309;
            --ink: #1f2937;
            --muted: #6b7280;
            --paper: #fcfdfd;
        }

        @page {
            size: A4;
            margin: 30mm 30mm 34mm 30mm;

            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-family: "Lora", serif;
                font-size: 9px;
                color: #6b7280;
                letter-spacing: 0.5px;
            }
        }

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
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--copper));
        }

        .cover-page::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--copper), var(--accent), var(--primary));
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

        .cover-page img.cover-logo { display: block; height: 120px; margin: 0 auto 20px; filter: drop-shadow(0 4px 10px rgba(14,116,144,0.18)); }

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
            background: linear-gradient(90deg, var(--primary), var(--copper));
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

        /* ===== JUDUL BAB (gaya pembatas buku, motif segi enam) ===== */

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
            color: var(--copper);
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
            content: "⬡";
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
            font-size: 14px;
            font-weight: 700;
            margin-top: 22px;
            margin-bottom: 10px;
            color: var(--ink);
            padding-left: 10px;
            border-left: 4px solid var(--copper);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        table th, table td { border: 1px solid #dceef2; padding: 5px 8px; font-size: 12px; vertical-align: top; line-height: 1.3; }

        table th {
            background: linear-gradient(135deg, #ecf8fa, #dcf0f4);
            text-align: center;
            color: var(--primary);
            font-weight: 700;
        }

        table tr:nth-child(even) td { background: #fafdfe; }

        .signature-block { display: flex; justify-content: center; align-items: flex-start; gap: 60px; margin-top: 20px; }
        .signature { width: 220px; text-align: center; margin: 0 auto; }
        .signature p { text-align: center !important; margin: 4px 0; }
        .signature img { display: block !important; margin: 8px auto !important; }

        .signature-center { width: 260px; text-align: center; margin: 90px auto 0; }
        .signature-center p { text-align: center !important; margin: 4px 0; word-wrap: break-word; }
        .signature-center img { display: block !important; margin: 8px auto !important; }

        .signature .space, .signature-center .space { height: 70px; }

        .ba-box {
            border: 1px solid #dceef2;
            border-left: 4px solid var(--primary);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            background: #fdfeff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }

        .ba-box table { margin-bottom: 0; box-shadow: none; }
        .ba-box table td, .ba-box table th { border: none; padding: 3px 6px; }

        .meeting-box {
            border: 1px solid #dceef2;
            border-left: 4px solid var(--copper);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
            background: #fdfcfa;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }

        .meeting-box h4 {
            font-family: "Playfair Display", serif;
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 8px;
            color: var(--primary);
        }

        .kuadran-header {
            background: linear-gradient(135deg, #ecf8fa, #dcf0f4) !important;
            font-weight: 700;
            text-align: left !important;
            color: var(--primary);
        }

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
            box-shadow: 0 6px 16px rgba(14,116,144,0.3);
            z-index: 999;
        }

</style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

    <!-- ===================================================== -->
    <!-- 1. COVER -->
    <!-- ===================================================== -->

    <div class="cover-page page-break">
      <div class="cover-frame">

        <div class="cover-top-group">

            <?php if (!empty($profile['logo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" class="cover-logo" alt="Logo">
            <?php endif; ?>

            <span class="cover-kicker"><i class="bi bi-diagram-3-fill"></i> Sistem Penjaminan Mutu Internal</span>

            <h1>Laporan<br>Rapat Tinjauan Manajemen</h1>

            <div class="cover-divider"></div>

            <h2><?= htmlspecialchars($unit['name']) ?></h2>

            <div class="cover-ribbon">
                <i class="bi bi-calendar-event"></i> <?= htmlspecialchars($period['period_name']) ?>
            </div>

        </div>

        <div class="cover-bottom-group">

            <div class="cover-info">
                <h2><?= htmlspecialchars($profile['foundation_name']) ?></h2>
                <h2><?= htmlspecialchars($profile['institution_name'] ?? '') ?></h2>
            </div>

            <div class="cover-year">
                <?= htmlspecialchars($profile['city'] ?? '') ?> &middot; <?= (int) $period['year'] ?>
            </div>

        </div>

      </div>
    </div>

    <!-- ===================================================== -->
    <!-- 2. KATA PENGANTAR -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Kata Pengantar</div>

        <p>
            Saat ini amanah pemerintah dalam Permenristekdikti No. 39 Tahun 2025 Tentang 
            Sistem Penjaminan Mutu Internal menjadi bagian penting dalam peningkatan mutu institusi,
            SPMI di suatu perguruan tinggi direncanakan, dilaksanakan, dievaluasi, dikendalikan,
            dan dikembangkan oleh perguruan tinggi. SPMI yaitu kegiatan sistemik penjaminan
            mutu pendidikan tinggi oleh setiap perguruan tinggi secara otonom atau mandiri untuk
            mengendalikan dan meningkatkan mutu penyelenggaraan pendidikan tinggi secara
            berencana dan berkelanjutan. <strong><?= htmlspecialchars($period['period_name']) ?></strong> dapat terlaksana dengan baik.
        </p>



        <p>
            Laporan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tahap Pengendalian dan Peningkatan
            dalam siklus Sistem Penjaminan Mutu Internal (SPMI), yang membahas hasil temuan Audit Mutu Internal
            (AMI) bersama pimpinan untuk merumuskan Rencana Tindak Lanjut (RTL).
        </p>

        <p>
            Kami menyampaikan terima kasih kepada seluruh pihak yang telah berkontribusi dalam pelaksanaan
            rapat ini. Semoga laporan ini dapat memberikan manfaat bagi peningkatan mutu berkelanjutan.
        </p>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua LPM,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpm) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- 3. HALAMAN PENGESAHAN -->
    <!-- ===================================================== -->

    <div class="page-break no-justify">

        <div class="bab-title">Halaman Pengesahan</div>

        <p>
            Laporan Rapat Tinjauan Manajemen (RTM) untuk Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            pada Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> ini disusun berdasarkan
            hasil pelaksanaan RTM yang telah dilakukan dan disahkan oleh:
        </p>

        <p style="text-align: center; margin-top: 40px;">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?>
        </p>

<div class="signature-block">
            <div class="signature">
                <p>Kepala Lembaga Penjaminan Mutu,<br>&nbsp;</p>
                <?php if ($qrKetuaLpmPengesahan): ?>
                    <img src="<?= $qrKetuaLpmPengesahan ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpmNamaFinal ?: $ketuaLpm) ?></p>
            </div>
        </div>

        <div class="signature-center">
            <p>Menyetujui,<br>Ketua</p>
            <?php if ($qrKetuaInstitusiPengesahan): ?>
                <img src="<?= $qrKetuaInstitusiPengesahan ?>" style="width:45px; height:45px; margin: 8px auto; display:block;">
            <?php else: ?>
                <div class="space"></div>
            <?php endif; ?>
            <p style="font-weight:bold; text-decoration: underline;">
                <?= htmlspecialchars($ketuaInstitusiNamaFinal ?: ($profile['leader_name'] ?? '(...........................)')) ?>
            </p>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- 4. BERITA ACARA RAPAT -->
    <!-- ===================================================== -->

    <div class="page-break no-justify">

        <div class="bab-title">Berita Acara Rapat Tinjauan Manajemen</div>

        <?php foreach ($meetings as $m): ?>

            <div class="ba-box">

                <table>
                    <tr>
                        <td width="160"><strong>Nomor Rapat</strong></td>
                        <td width="10">:</td>
                        <td><?= htmlspecialchars($m['meeting_number']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Pelaksanaan</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($m['meeting_date'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Unit Kerja</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($unit['name']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Agenda</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($m['agenda'] ?? '-') ?></td>
                    </tr>
                </table>

                <p style="margin-top: 12px;"><strong>Hasil Rapat / Notulen:</strong></p>
                <p><?= nl2br(htmlspecialchars($m['minutes'] ?? '-')) ?></p>

                <p style="margin-top: 12px;">
                    Pada hari ini, telah dilaksanakan Rapat Tinjauan Manajemen sebagaimana tercantum di atas,
                    dan disepakati Rencana Tindak Lanjut yang tercantum pada BAB II laporan ini.
                </p>

        <?php
                    $qrNotulis = !empty($m['notulis_ttd'])
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=rtm_meeting&id=' . $m['id'] . '&role=notulis')
                        : null;
                    $qrPimpinan = !empty($m['pimpinan_ttd'])
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=rtm_meeting&id=' . $m['id'] . '&role=pimpinan')
                        : null;
                ?>

                <div class="signature-block" style="margin-top: 30px;">

                    <div class="signature">
                        <p>Notulis Rapat,<br>&nbsp;</p>
                        <?php if ($qrNotulis): ?>
                            <img src="<?= $qrNotulis ?>" style="width:45px; height:45px; margin: 8px 0;">
                        <?php else: ?>
                            <div class="space"></div>
                        <?php endif; ?>
                        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($m['notulis_nama'] ?: $notulis) ?></p>
                    </div>

                    <div class="signature">
                        <p>Pimpinan Rapat,<br>Mengusulkan,</p>
                        <?php if ($qrPimpinan): ?>
                            <img src="<?= $qrPimpinan ?>" style="width:45px; height:45px; margin: 8px 0;">
                        <?php else: ?>
                            <div class="space"></div>
                        <?php endif; ?>
                        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($m['pimpinan_nama'] ?: $ketuaLpm) ?></p>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <!-- ===================================================== -->
    <!-- 5. BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Latar Belakang</h3>
        <p>
            Rapat Tinjauan Manajemen (RTM) merupakan salah satu tahapan Pengendalian dalam siklus Sistem
            Penjaminan Mutu Internal (SPMI) yang bertujuan membahas hasil temuan Audit Mutu Internal (AMI)
            di tingkat manajemen bersama Auditee, guna merumuskan Rencana Tindak Lanjut (RTL) yang tepat
            dan terukur.
        </p>

        <p>
            Menindaklanjuti maksud di atas, STIKES Harapan Ibu Jambi telah melaksanakan
            kegiatan penjaminan mutu melalui Audit Mutu Internal (AMI) dan hasil AMI akan
            dikirim ke unit-unit terkait. Sebagai wujud dari pengendalian PPEPP maka dilakukan
            Rapat Tinjauan Manajemen (RTM) sebagai komitmen pimpinan dalam meningkatkan
            mutu perguruan tinggi. Pengendalian mutu dilakukan secara terus-menerus sehingga
            terjadi peningkatan
        </p>

         <p>
            Rapat Tinjauan Manajemen (RTM) di tingkat institusi dapat memberi gambaran
            kualitas Tridharma Perguruan Tinggi sehingga pelaksaaannya harus dipantau dan
            dipastikan berjalan. Lembaga Penjaminan Mutu (LPM) sebagai sebuah lembaga yang
            bertanggungjawab atas pelaksanaan penjaminan mutu harus memastikan sejauh
            mana kriteria-kriteria yang telah ditentukan telah terpenuhi.
        </p>

        <p>
            Selanjutnya hasil dari Rapat Tinjauan Manajemen (RTM) juga dapat memberi
            gambaran kepada pimpinan terkait sehingga dapat ditindaklanjuti hasil temuan audit di
            lapangan. Dengan harapan hasil RTM ini dapat meningkatkan mutu perguruan tinggi
            sehingga dapat tercapai siklus PPEPP.
        </p>
        <p>
            Saat ini amanah pemerintah dalam Permenristekdikti No. 39 Tahun 2025 Tentang 
            Sistem Penjaminan Mutu Internal menjadi bagian penting dalam peningkatan mutu institusi,
            SPMI di suatu perguruan tinggi direncanakan, dilaksanakan, dievaluasi, dikendalikan,
            dan dikembangkan oleh perguruan tinggi. SPMI yaitu kegiatan sistemik penjaminan
            mutu pendidikan tinggi oleh setiap perguruan tinggi secara otonom atau mandiri untuk
            mengendalikan dan meningkatkan mutu penyelenggaraan pendidikan tinggi secara
            berencana dan berkelanjutan dapat terlaksana dengan baik.
        </p>

        <h3 class="sub-title">B. Dasar Pelaksanaan</h3>
        <p>
            1. Hasil Audit Mutu Internal (AMI) pada Unit Kerja <?= htmlspecialchars($unit['name']) ?>
            Periode <?= htmlspecialchars($period['period_name']) ?>;<br>
            2. Kebijakan dan Manual Sistem Penjaminan Mutu Internal <?= htmlspecialchars($profile['institution_name'] ?? '') ?>.
        </p>

        <h3 class="sub-title">C. Tujuan</h3>
        <p>
            1. Membahas hasil temuan Audit Mutu Internal bersama pimpinan dan Auditee;<br>
            2. Merumuskan Rencana Tindak Lanjut (RTL) atas temuan yang ada;<br>
            3. Menetapkan prioritas pelaksanaan RTL berdasarkan tingkat kepentingan dan kemendesakan.
        </p>

    </div>

    <!-- ===================================================== -->
    <!-- 6. BAB II RTL YANG DIHASILKAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Rumusan Rencana Tindak Lanjut (RTL) </div>
        <p>
            Berdasarkan hasil rapat tinjauan manajemen yang telah dilakukan bersama 
            seluruh pihak manajemen, maka dihasila rumusan rencana tindak lanjut untuk dijalankan
            oleh unit kerja. adapun secara rencana tindak lanjut sebagai berikut:
        </p>
        <h3 class="sub-title">2.1 Rencana Tindak Lanjut (RTL) yang Dihasilkan</h3>
        <table>
            <thead>
                <tr>
                    <th width="25">No</th>
                    <th width="70">Standar</th>
                    <th>Indikator</th>
                    <th>Temuan</th>
                    <th>Catatan Auditor</th>
                    <th>Rekomendasi</th>
                    <th>Perumusan RTL</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detailedPlans)): ?>
                    <tr><td colspan="7" style="text-align:center;">Belum ada RTL yang dihasilkan.</td></tr>
                <?php else: ?>
                    <?php foreach ($detailedPlans as $i => $p): ?>
                        <?php $isSurveyPlan = ($p['source_type'] ?? 'audit') === 'survey'; ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <?php if ($isSurveyPlan): ?>
                                <td>
                                    <span style="background:#e0e7ff; padding:2px 6px; border-radius:4px; font-size:10px;">Survey</span><br>
                                    <?= htmlspecialchars($p['survey_type_name'] ?? '-') ?>
                                </td>
                                <td><?= htmlspecialchars($p['survey_category_name'] ?? '-') ?></td>
                            <?php else: ?>
                                <td><?= htmlspecialchars($p['standard_code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(($p['item_code'] ?? '-') . ' - ' . ($p['indicator'] ?? '-')) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($p['finding'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['root_cause'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['recommendation'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['activity'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- ===================================================== -->
    <!-- 7. BAB III PRIORITAS RTL -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB III<br>Prioritas Rencana Tindak Lanjut</div>

        <p>
            Prioritas pelaksanaan RTL diurutkan berdasarkan matriks Eisenhower (tingkat kepentingan dan
            kemendesakan), sebagai berikut:
        </p>
        <h3 class="sub-title">3.1 Rencana Tindak Lanjut (RTL) yang Dihasilkan</h3>
        <table>
            <thead>
                <tr>
                    <th width="25">No</th>
                    <th width="130">Kuadran Prioritas</th>
                    <th>Kegiatan</th>
                    <th width="90">Waktu</th>
                    <th width="80">PIC</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prioritizedPlans)): ?>
                    <tr><td colspan="7" style="text-align:center;">Belum ada RTL.</td></tr>
                <?php else: ?>
                    <?php foreach ($prioritizedPlans as $i => $p): ?>
                        <?php $kuadranKey = $p['importance'] . '-' . $p['urgency']; ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($kuadranLabel[$kuadranKey] ?? $kuadranKey) ?></td>
                            <td><?= htmlspecialchars($p['activity']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($p['implementation_time'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($p['pic'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- ===================================================== -->
    <!-- 8. PENUTUP -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Penutup</div>

        <p>
            Dalam upaya mengembangkan budaya mutu di perguruan tinggi, semua pihak yang
            berkepentingan selalu berpikir, bersikap, dan bertindak berdasarkan Standar
            Pendidikan Tinggi, maka setiap perguruan tinggi wajib mengimplementasikan SPMI.
            Rapat Tinjauan Manajemen (RTM) merupakan tindak lanjut dari bentuk komitmen
            pimpinan dalam upaya meningkatkan mutu. Dokumen laporan RTM ini merupakan
            dokumen rekaman dalam pengendalian mutu pada siklus PPEPP. Demikian Laporan RTM
            <strong><?= htmlspecialchars($unit['name']) ?></strong> Periode
            <strong><?= htmlspecialchars($period['period_name']) ?></strong> ini disusun. Sebanyak
            <strong><?= count($detailedPlans) ?></strong> Rencana Tindak Lanjut telah dirumuskan dan akan
            dipantau pelaksanaannya sebagai bagian dari siklus Peningkatan mutu berkelanjutan.
        </p>

        <p>Demikian laporan ini disusun untuk dapat dipergunakan sebagaimana mestinya.</p>

    </div>

    <!-- ===================================================== -->
    <!-- 9. LAMPIRAN -->
    <!-- ===================================================== -->

    <div>

        <div class="bab-title">Lampiran</div>

        <p>Berikut daftar dokumen dan bukti kegiatan yang diunggah oleh Auditee selama pelaksanaan RTM:</p>

        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="90">Rapat</th>
                    <th width="120">Jenis Dokumen</th>
                    <th>Nama File</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allDocuments)): ?>
                    <tr><td colspan="4" style="text-align:center;">Tidak ada dokumen yang diunggah.</td></tr>
                <?php else: ?>
                    <?php foreach ($allDocuments as $i => $d): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($d['meeting_number']) ?></td>
                            <td><?= htmlspecialchars($d['document_type']) ?></td>
                            <td><?= htmlspecialchars($d['document_original_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

<?php
            $photos = array_filter($allDocuments, fn($d) => $d['document_type'] === 'Foto Kegiatan');
            $nonPhotos = array_filter($allDocuments, fn($d) => $d['document_type'] !== 'Foto Kegiatan');
        ?>

        <?php if (!empty($nonPhotos)): ?>

            <h3 class="sub-title">Dokumen BAP &amp; Notulen</h3>

            <table>
                <thead>
                    <tr>
                        <th width="30">No</th>
                        <th width="90">Rapat</th>
                        <th width="120">Jenis</th>
                        <th>Nama File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nonPhotos as $i => $doc): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($doc['meeting_number']) ?></td>
                            <td><?= htmlspecialchars($doc['document_type']) ?></td>
                            <td><?= htmlspecialchars($doc['document_original_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="text-muted" style="font-size: 11px;">
                *Dokumen BAP dan Notulen tersimpan pada sistem SIQUA dan dapat diakses/diunduh langsung
                melalui halaman Kelola Rapat RTM. Karena keterbatasan format cetak, isi dokumen tidak
                ditampilkan langsung pada lampiran ini.
            </p>

        <?php endif; ?>

        <?php if (!empty($photos)): ?>
            <h3 class="sub-title">Dokumentasi Foto Kegiatan</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach ($photos as $photo): ?>
                    <div style="width: 200px;">
                        <img src="<?= BASE_URL . htmlspecialchars($photo['document_file']) ?>" style="width: 100%; border: 1px solid #000;">
                        <p style="text-align:center; font-size: 11px; margin-top: 4px;"><?= htmlspecialchars($photo['meeting_number']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>