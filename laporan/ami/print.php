<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/institution/repository.php';

$unitId = (int)($_GET['unit_id'] ?? 0);
$periodId = (int)($_GET['period_id'] ?? 0);

$repository = new AmiReportRepository($conn);
$service    = new AmiReportService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

require_once __DIR__ . '/../signatures/repository.php';
$sigRepo = new LaporanSignatureRepository($conn);
$signature = $sigRepo->find('unit', $periodId, $unitId) ?? [];

$ketuaTimNamaFinal = !empty($signature['ketua_tim_nama']) ? $signature['ketua_tim_nama'] : null;
$ketuaLpmNamaFinal = !empty($signature['ketua_lpm_nama']) ? $signature['ketua_lpm_nama'] : null;
$ketuaInstitusiNamaFinal = !empty($signature['ketua_institusi_nama']) ? $signature['ketua_institusi_nama'] : null;

$qrKetuaTim = (!empty($signature['id']) && !empty($signature['ketua_tim_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=ami&id=' . $signature['id'] . '&role=ketua_tim')
    : null;

$qrKetuaLpm = (!empty($signature['id']) && !empty($signature['ketua_lpm_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=ami&id=' . $signature['id'] . '&role=ketua_lpm')
    : null;

$qrKetuaInstitusi = (!empty($signature['id']) && !empty($signature['ketua_institusi_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=ami&id=' . $signature['id'] . '&role=ketua_institusi')
    : null;

$result = $service->getReportData($unitId, $periodId);

if (!$result['success']) {
    die('<p style="font-family: sans-serif; padding: 40px;">' . htmlspecialchars($result['message']) . '</p>');
}

$data = $result['data'];
$standardSummary = $data['standard_summary'] ?? [];

$unit = $data['unit'];
$period = $data['period'];
$ketuaLpm = $data['ketua_lpm']['full_name'] ?? '(...........................)';
$ketuaTimUtama = $data['ketua_tim_utama'];
$assignments = $data['assignments'];
$auditedStandards = $data['audited_standards'];
$team = $data['team'];
$findings = $data['findings'];
$stats = $data['statistics'];
$actionPlans = $data['action_plans'];


$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan AMI - <?= htmlspecialchars($unit['name']) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #6d28d9;
            --primary-light: #a78bfa;
            --gold: #c9a227;
            --ink: #1f2937;
            --muted: #6b7280;
            --paper: #fdfcfb;
        }

        @page {
            size: A4;
            margin: 30mm 30mm 30mm 30mm;
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

        .kop {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px double var(--primary);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop img { height: 80px; width: auto; }

        .kop-text { text-align: center; flex: 1; }

        .kop-text h1 {
            font-family: "Playfair Display", serif;
            font-size: 22px;
            margin: 0;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--primary);
        }

        .kop-text p { margin: 3px 0; font-size: 13px; }

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
            background: linear-gradient(90deg, var(--primary), #2563eb, var(--gold));
        }

        .cover-page::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--gold), #2563eb, var(--primary));
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

        .cover-page img.cover-logo { display: block; height: 120px; margin: 0 auto 20px; filter: drop-shadow(0 4px 10px rgba(109,40,217,0.15)); }

        .cover-top-group {
            width: 100%;
        }

        .cover-bottom-group {
            width: 100%;
            margin-top: 90px;
        }

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
            background: linear-gradient(90deg, var(--primary), var(--gold));
            margin: 22px auto;
            border-radius: 3px;
        }

        .cover-ribbon {
            display: inline-block;
            margin-top: 28px;
            padding: 10px 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), #2563eb);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.4px;
        }

        .cover-page .cover-info {
            margin-top: 26px;
            font-size: 14px;
        }

        .cover-page .cover-info p { margin: 3px 0; }

        .cover-page .cover-year {
            margin-top: 50px;
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 1px;
        }

        p { text-align: justify; }

        .cover-page p, .cover-page h1, .cover-page h2 { text-align: center; }

        .no-justify p { text-align: left; }

        .bab-title {
            position: relative;
            font-family: "Playfair Display", serif;
            font-size: 30px;
            font-weight: 700;
            text-align: center;
            text-transform: none;
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
            color: var(--gold);
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
            content: "❖";
            position: absolute;
            bottom: -7px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--gold);
            font-size: 13px;
            background: var(--paper);
            padding: 0 10px;
        }

        .bab-title .bab-icon {
            display: block;
            font-size: 26px;
            color: var(--gold);
            margin-bottom: 8px;
        }

        h3.sub-title {
            font-family: "Playfair Display", serif;
            font-size: 14px;
            font-weight: 700;
            margin-top: 22px;
            margin-bottom: 10px;
            color: var(--ink);
            padding-left: 10px;
            border-left: 4px solid var(--gold);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        table th, table td { border: 1px solid #e0dcf0; padding: 5px 8px; font-size: 12px; vertical-align: top; line-height: 1.3; }

        table th {
            background: linear-gradient(135deg, #f3effc, #e9e3fb);
            text-align: center;
            color: var(--primary);
            font-weight: 700;
        }

        table tr:nth-child(even) td { background: #fafafd; }

        .finding-box {
            border: 1px solid #e5e0f5;
            border-left: 4px solid var(--primary);
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 14px;
            page-break-inside: avoid;
            background: #fdfcff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        }

        .finding-header-table { width: 100%; border: none; margin-bottom: 8px; }
        .finding-header-table td { border: none; padding: 2px 4px; font-size: 12px; }

        .finding-field {
            font-size: 12px;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #ddd;
        }

        .signature-block { display: flex; justify-content: center; align-items: flex-start; gap: 60px; margin-top: 20px; }
        .signature { width: 220px; text-align: center; margin: 0 auto; }
        .signature p { text-align: center !important; margin: 4px 0; }
        .signature img { display: block !important; margin: 8px auto !important; }
        .signature-center { width: 260px; text-align: center; margin: 90px auto 0; }
        .signature-center p { text-align: center !important; margin: 4px 0; word-wrap: break-word; }
        .signature-center img { display: block !important; margin: 8px auto !important; }
        .signature .space, .signature-center .space { height: 70px; }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 40px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), #2563eb);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(109,40,217,0.3);
            z-index: 999;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #fff;
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

            <span class="cover-kicker"><i class="bi bi-patch-check-fill"></i> Lembaga Penjaminan Mutu (LPM)</span>

            <h1>Laporan Audit Mutu Internal</h1>

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
    <!-- HALAMAN PENGESAHAN -->
    <!-- ===================================================== -->

 <div class="page-break no-justify">

        <div class="bab-title">Halaman Pengesahan</div>

        <p>
            Laporan Audit Mutu Internal untuk Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            pada Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> ini telah disusun
            berdasarkan hasil pelaksanaan Audit Mutu Internal (AMI) yang dilakukan oleh Tim Auditor dan
            disahkan oleh pihak-pihak berikut:
        </p>

        <p style="text-align: center; margin-top: 40px;">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?>
        </p>

        <div class="signature-block">

            <div class="signature">
                <p>Ketua Tim Audit,<br>&nbsp;</p>
                <?php if ($qrKetuaTim): ?>
                    <img src="<?= $qrKetuaTim ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;">
                    <?= htmlspecialchars($ketuaTimNamaFinal ?: $ketuaTimUtama) ?>
                </p>
            </div>

            <div class="signature">
                <p>Mengetahui,<br>Kepala Lembaga Penjaminan Mutu,</p>
                <?php if ($qrKetuaLpm): ?>
                    <img src="<?= $qrKetuaLpm ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;">
                    <?= htmlspecialchars($ketuaLpmNamaFinal ?: $ketuaLpm) ?>
                </p>
            </div>

        </div>

        <div class="signature-center">

            <p>Menyetujui,<br>Ketua,</p>
            <?php if ($qrKetuaInstitusi): ?>
                <img src="<?= $qrKetuaInstitusi ?>" style="width:45px; height:45px; margin: 8px auto; display:block;">
            <?php else: ?>
                <div class="space"></div>
            <?php endif; ?>
            <p style="font-weight:bold; text-decoration: underline;">
                <?= htmlspecialchars($ketuaInstitusiNamaFinal ?: ($profile['leader_name'] ?? '(...........................)')) ?>
            </p>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- KATA PENGANTAR -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Kata Pengantar</div>

        <p>
            Puji syukur kami panjatkan kehadirat Tuhan Yang Maha Esa, karena atas rahmat dan karunia-Nya,
            pelaksanaan Audit Mutu Internal (AMI) pada Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            untuk Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> dapat terlaksana dengan baik.
        </p>

        <p>
            Laporan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan Sistem Penjaminan Mutu Internal (SPMI)
            pada tahap Evaluasi, sekaligus sebagai dasar bagi pengambilan keputusan pada tahap Pengendalian dan
            Peningkatan mutu di lingkungan <?= htmlspecialchars($profile['institution_name'] ?? '') ?>.
        </p>

        <p>
            Kami menyampaikan terima kasih kepada seluruh pihak yang telah berkontribusi dalam pelaksanaan audit ini,
            baik Tim Auditor, Auditee, maupun pimpinan unit kerja terkait. Semoga laporan ini dapat memberikan
            manfaat bagi peningkatan mutu berkelanjutan.
        </p>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua Tim Audit,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaTimUtama) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Latar Belakang</h3>
        <p>
            Sesuai dengan amanat Undang Undang Nomor 12 Tahun 2012 setiap
            perguruan tinggi memiliki kewajiban untuk melaksanakan Sistem Penjaminan Mutu
            Internal atau SPMI. Demikian pula dengan Permenkdiktisaintek No. 39 Tahun 2025
            mengatur tentang Sistem Penjaminan Mutu Pendidikan Tinggi mengatur tentang
            SPMI. Di tingkat Sekolah Tinggi Ilmu Kesehatan Harapan Ibu Jambi mengatur SPMI
            (Sistem Penjaminan Mutu Internal). 
        </p>
        <p>
            Mengacu pada Permenkdiktisaintek No. 39 Tahun 2025, SPMI adalah kegiatan sistemik 
            penjaminan mutu oleh perguruan tinggi secara
            otonom untuk mengendalikan dan meningkatkan penyelenggaraan pendidikan tinggi
            secara berencana dan berkelanjutan. Perencanaan, pelaksanaan, evaluasi,
            pengendalian, dan pengembangan didasarkan pada Standar Pendidikan Tinggi.
            Standar Pendidikan Tinggi terdiri dari Standar Nasional Pendidikan Tinggi dan Standar
            Pendidikan Tinggi yang ditetapkan oleh Perguruan Tinggi. SPMI memiliki siklus
            kegiatan yang terdiri atas 1.penetapan Standar Pendidikan Tinggi; 2.pelaksanaan
            Standar Pendidikan Tinggi; 3.evaluasi pelaksanaan Standar Pendidikan Tinggi; 4.
            pengendalian pelaksanaan Standar Pendidikan Tinggi; dan 5. peningkatan Standar
            Pendidikan Tinggi.
        </p>
        <p>
            Dalam memastikan pelaksanaan penjaminan mutu maka dilaksanakan Audit
            Mutu Internal di seluruh unit di Sekolah Tinggi Ilmu Kesehatan Harapan Ibu Jambi,
            khususnya saat ini adalah di bagian kemahasiswaan pada unit penerimaan
            mahasiswa baru. Tim Audit Mutu Internal dibentuk dan ditetapkan dengan Keputusan
            Ketua No.175a/STIKES/JBI/IX/SK-2021. 
        </p>
        <p>
            Kegiatan AMI mencakup evaluasi mengenai
            kesesuaian pelaksanaan kegiatan program studi kesehatan masyarakat dalam
            melaksanakan standar perguruan tinggi dengan kreteria, target capaian renstra dan
            standar mutu yang ditetapkan. Pelaksanaan AMI juga diharapkan dapat menjadi salah
            satu indikasi kesiapan program studi kesehatan masyarakat dalam rangka
            mempersiapkan Laporan Kinerja dan Evaluasi Diri menuju pengajuan akreditasi. Oleh
            karena itu pelaksanaan AMI Sekolah Tinggi Ilmu Kesehatan Harapan Ibu Jambi
            dilaksanakan secara rutin dalam setiap tahun akademik.
        </p>

        <h3 class="sub-title">B. Dasar Hukum</h3>
        <p>
            1. Permendiktisaintek No. 39 TAHUN 2025 tentang Sistem Penjaminan Mutu Internal;<br>
            2. Keputusan Ketua No. 318/STIKES/JBI/X/SK-2017 tentang Penetapan Dokumen Manual Mutu <?= htmlspecialchars($profile['institution_name'] ?? '') ?>;<br>
            3. Keputusan Ketua No. 319/STIKES/JBI/X/SK-2017 tentang Penetapan Dokumen Standar Mutu <?= htmlspecialchars($profile['institution_name'] ?? '') ?>;<br>
            4. Surat Tugas pelaksanaan Audit Mutu Internal Periode <?= htmlspecialchars($period['period_name']) ?>.
        </p>

        <h3 class="sub-title">C. Tujuan</h3>
        <p>
            1. Mengevaluasi tingkat pemenuhan standar mutu pada Unit Kerja <?= htmlspecialchars($unit['name']) ?>;<br>
            2. Mengidentifikasi temuan ketidaksesuaian sebagai dasar penyusunan Rencana Tindak Lanjut (RTL);<br>
            3. Mendorong peningkatan mutu berkelanjutan pada unit kerja.
        </p>

        <h3 class="sub-title">D. Ruang Lingkup</h3>
        <p>Audit ini mencakup Standar Mutu sebagai berikut:</p>
        <table>
            <thead>
                <tr><th width="30">No</th><th width="120">Kode</th><th>Nama Standar</th></tr>
            </thead>
            <tbody>
                <?php if (empty($auditedStandards)): ?>
                    <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
                <?php else: ?>
                    <?php foreach ($auditedStandards as $i => $s): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($s['code']) ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- ===================================================== -->
    <!-- BAB II PELAKSANAAN AUDIT -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Pelaksanaan Audit</div>

        <h3 class="sub-title">A. Penugasan Audit</h3>
        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th>No. Penugasan</th>
                    <th>Jenis Audit</th>
                    <th width="100">Tanggal</th>
                    <th width="100">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $i => $a): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($a['assignment_number']) ?></td>
                        <td><?= htmlspecialchars($a['audit_type']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($a['audit_date'] ?? '-') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($a['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 class="sub-title">B. Tim Auditor</h3>
        <table>
            <thead>
                <tr><th width="30">No</th><th>Nama</th><th width="150">Peran</th></tr>
            </thead>
            <tbody>
                <?php if (empty($team)): ?>
                    <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
                <?php else: ?>
                    <?php foreach ($team as $i => $t): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($t['full_name']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($t['role']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 class="sub-title">C. Tahapan Pelaksanaan</h3>
        <p>
            Pelaksanaan audit ini melalui tahapan: (1) Penugasan Audit dan persetujuan Auditee,
            (2) Desk Evaluation oleh Auditee, (3) Audit Dokumen dan Audit Lapangan/Visitasi oleh Tim Auditor
            melalui Lembar Kerja Audit (LKA).
        </p>

    </div>

<!-- ===================================================== -->
    <!-- BAB III HASIL TEMUAN AUDIT -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB III<br>Hasil Temuan Audit</div>

        <h3 class="sub-title">A. Ringkasan Status Capaian</h3>
        <table>
            <thead>
                <tr>
                    <th>Total Indikator</th>
                    <th>Menyimpang</th>
                    <th>Belum Mencapai</th>
                    <th>Mencapai</th>
                    <th>Melampaui</th>
                </tr>
            </thead>
            <tbody>
                <tr style="text-align:center;">
                    <td><?= $stats['total'] ?></td>
                    <td><?= $stats['tidak_terpenuhi'] ?></td>
                    <td><?= $stats['sebagian'] ?></td>
                    <td><?= $stats['memenuhi'] ?></td>
                    <td><?= $stats['melampaui'] ?></td>
                </tr>
            </tbody>
        </table>

<?php
            $skorTertinggi = null;
            $skorTerendah = null;

            foreach ($standardSummary as $s) {
                if ($skorTertinggi === null || $s['skor_capaian'] > $skorTertinggi['skor_capaian']) {
                    $skorTertinggi = $s;
                }
                if ($skorTerendah === null || $s['skor_capaian'] < $skorTerendah['skor_capaian']) {
                    $skorTerendah = $s;
                }
            }

            $rataRataSkor = !empty($standardSummary)
                ? round(array_sum(array_column($standardSummary, 'skor_capaian')) / count($standardSummary), 1)
                : 0;
        ?>

        <h3 class="sub-title">B. Grafik Perbandingan Capaian Antar Standar</h3>
        <div style="width: 450px; height: 240px; margin: 0 auto;">
            <canvas id="chartBar" width="450" height="240" style="width: 450px; height: 240px;"></canvas>
        </div>

        <?php if (!empty($standardSummary)): ?>
        <?php
            $kategoriRataRata = $rataRataSkor < 2 ? 'Sangat Kurang Baik' : ($rataRataSkor < 3 ? 'Kurang Baik' : ($rataRataSkor < 4 ? 'Baik' : 'Sangat Baik'));
        ?>
        <p style="margin-top: 15px;">
            Berdasarkan grafik di atas, rata-rata skor capaian seluruh Standar pada Unit Kerja ini adalah
            <strong><?= $rataRataSkor ?></strong> (kategori <strong><?= $kategoriRataRata ?></strong>). Standar dengan capaian tertinggi adalah
            <strong><?= htmlspecialchars($skorTertinggi['standard_code']) ?> - <?= htmlspecialchars($skorTertinggi['standard_name']) ?></strong>
            dengan skor <strong><?= $skorTertinggi['skor_capaian'] ?></strong> (<?= htmlspecialchars($skorTertinggi['kategori_capaian'] ?? '-') ?>), sedangkan capaian terendah
            ada pada <strong><?= htmlspecialchars($skorTerendah['standard_code']) ?> - <?= htmlspecialchars($skorTerendah['standard_name']) ?></strong>
            dengan skor <strong><?= $skorTerendah['skor_capaian'] ?></strong> (<?= htmlspecialchars($skorTerendah['kategori_capaian'] ?? '-') ?>), yang perlu menjadi perhatian
            khusus dalam Rencana Tindak Lanjut.
        </p>
        <?php endif; ?>

        <h3 class="sub-title">C. Profil Capaian Standar (Spiderweb)</h3>
        <div style="width: 320px; height: 320px; margin: 0 auto;">
            <canvas id="chartRadar" width="320" height="320" style="width: 320px; height: 320px;"></canvas>
        </div>

        <?php if (!empty($standardSummary)): ?>
        <p style="margin-top: 15px;">
            Grafik spiderweb di atas menggambarkan profil capaian seluruh Standar secara menyeluruh.
            Semakin luas area yang terbentuk, semakin baik tingkat pemenuhan standar mutu pada unit ini.
            Standar yang membentuk titik mendekati pusat (skor rendah) menunjukkan area yang memerlukan
            perhatian dan tindak lanjut prioritas, sementara titik yang mendekati tepi luar menunjukkan
            standar yang telah terpenuhi dengan baik.
        </p>
        <?php endif; ?>

        <h3 class="sub-title">D. Detail Temuan per Indikator</h3>

        <?php if (empty($findings)): ?>

            <p class="text-muted">Tidak ada data.</p>

        <?php else: ?>

            <?php foreach ($findings as $i => $f): ?>

                <?php $statusBadgeMap = ['Menyimpang' => '#dc3545', 'Belum Mencapai' => '#fd7e14', 'Mencapai' => '#198754', 'Melampaui' => '#0d6efd']; ?>

                <div class="finding-box">

                    <table class="finding-header-table">
                        <tr>
                            <td width="55%">
                                <strong><?= $i + 1 ?>. <?= htmlspecialchars($f['standard_code']) ?> - <?= htmlspecialchars($f['standard_name']) ?></strong><br>
                                <span style="font-size:11px; color:#555;"><?= htmlspecialchars($f['statement'] ?? '-') ?></span>
                            </td>
                            <td width="20%">
                                <small>Indikator</small><br>
                                <?= htmlspecialchars($f['item_code'] . ' - ' . $f['indicator']) ?>
                            </td>
                            <td width="10%" style="text-align:center;">
                                <small>Target</small><br><?= htmlspecialchars($f['target'] ?? '-') ?>
                            </td>
                            <td width="10%" style="text-align:center;">
                                <small>Capaian</small><br><?= htmlspecialchars($f['achievement'] ?? '-') ?>
                            </td>
                            <td width="5%" style="text-align:center;">
                                <span style="display:inline-block; padding:2px 6px; border-radius:4px; background:<?= $statusBadgeMap[$f['audit_status']] ?? '#6c757d' ?>; color:#fff; font-size:10px;">
                                    <?= htmlspecialchars($f['audit_status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <?php if (!empty($f['document_audit_result'])): ?>
                        <div class="finding-field"><strong>Hasil Audit Dokumen:</strong> <?= nl2br(htmlspecialchars($f['document_audit_result'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['field_audit_result'])): ?>
                        <div class="finding-field"><strong>Hasil Visitasi:</strong> <?= nl2br(htmlspecialchars($f['field_audit_result'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['root_cause'])): ?>
                        <div class="finding-field"><strong>Akar Masalah:</strong> <?= nl2br(htmlspecialchars($f['root_cause'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['supporting_factor'])): ?>
                        <div class="finding-field"><strong>Faktor Pendukung:</strong> <?= nl2br(htmlspecialchars($f['supporting_factor'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['recommendation'])): ?>
                        <div class="finding-field"><strong>Rekomendasi:</strong> <?= nl2br(htmlspecialchars($f['recommendation'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['evidence'])): ?>
                        <div class="finding-field"><strong>Eviden:</strong> <?= nl2br(htmlspecialchars($f['evidence'])) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($f['notes'])): ?>
                        <div class="finding-field"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($f['notes'])) ?></div>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB IV RENCANA TINDAK LANJUT -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB IV<br>Rencana Tindak Lanjut</div>

<table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th>Indikator</th>
                    <th>Kegiatan</th>
                    <th width="110">Waktu</th>
                    <th width="110">PIC</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($actionPlans)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada Rencana Tindak Lanjut untuk unit ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($actionPlans as $i => $ap): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($ap['item_code'] . ' - ' . $ap['indicator']) ?></td>
                            <td><?= htmlspecialchars($ap['activity']) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($ap['implementation_time'] ?? '-') ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($ap['pic'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- ===================================================== -->
    <!-- BAB V PENUTUP -->
    <!-- ===================================================== -->

<div class="no-justify">

        <div class="bab-title">BAB V<br>Penutup</div>

        <p>
            Berdasarkan hasil pelaksanaan Audit Mutu Internal pada Unit Kerja <strong><?= htmlspecialchars($unit['name']) ?></strong>
            Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong>, diperoleh sebanyak
            <strong><?= $stats['total'] ?></strong> indikator yang telah dievaluasi, dengan rincian
            <strong><?= $stats['tidak_terpenuhi'] ?></strong> indikator Menyimpang,
            <strong><?= $stats['sebagian'] ?></strong> indikator Belum mencapai,
            <strong><?= $stats['memenuhi'] ?></strong> indikator Mencapai, dan
            <strong><?= $stats['melampaui'] ?></strong> indikator Melampaui target.
        </p>

        <p>
            Terhadap temuan yang bersifat ketidaksesuaian, telah disusun Rencana Tindak Lanjut (RTL) sebagaimana
            tercantum pada BAB IV, yang akan dipantau pelaksanaannya melalui mekanisme Pengendalian dan
            ditinjau kembali pada Rapat Tinjauan Manajemen (RTM) berikutnya sebagai bagian dari siklus
            Peningkatan mutu berkelanjutan.
        </p>

        <p>
            Demikian laporan ini disusun untuk dapat dipergunakan sebagaimana mestinya.
        </p>

<div class="signature-block">

            <div class="signature">
                <p>Ketua Tim Audit,<br>&nbsp;</p>
                <?php if ($qrKetuaTim): ?>
                    <img src="<?= $qrKetuaTim ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;">
                    <?= htmlspecialchars($ketuaTimNamaFinal ?: $ketuaTimUtama) ?>
                </p>
            </div>

            <div class="signature">
                <p>Mengetahui,<br>Kepala Lembaga Penjaminan Mutu,</p>
                <?php if ($qrKetuaLpm): ?>
                    <img src="<?= $qrKetuaLpm ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;">
                    <?= htmlspecialchars($ketuaLpmNamaFinal ?: $ketuaLpm) ?>
                </p>
            </div>

        </div>

        <div class="signature-center">

            <p>Menyetujui,<br>Ketua,</p>
            <?php if ($qrKetuaInstitusi): ?>
                <img src="<?= $qrKetuaInstitusi ?>" style="width:45px; height:45px; margin: 8px auto; display:block;">
            <?php else: ?>
                <div class="space"></div>
            <?php endif; ?>
            <p style="font-weight:bold; text-decoration: underline;">
                <?= htmlspecialchars($ketuaInstitusiNamaFinal ?: ($profile['leader_name'] ?? '(...........................)')) ?>
            </p>

        </div>

    </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
        const standardLabelsRaw = <?= json_encode(array_column($standardSummary, 'standard_name')) ?>;
        const standardScores = <?= json_encode(array_map('floatval', array_column($standardSummary, 'skor_capaian'))) ?>;

        // Pecah nama Standar yang panjang jadi beberapa baris pendek (maks ~18 karakter per baris)
        function wrapLabel(text, maxLineLength) {
            const words = text.split(' ');
            const lines = [];
            let currentLine = '';

            words.forEach(function (word) {
                const testLine = currentLine ? currentLine + ' ' + word : word;
                if (testLine.length > maxLineLength && currentLine) {
                    lines.push(currentLine);
                    currentLine = word;
                } else {
                    currentLine = testLine;
                }
            });

            if (currentLine) lines.push(currentLine);

            return lines;
        }

        const isManyStandards = standardLabelsRaw.length > 8;
        const barLabels = standardLabelsRaw.map((l) => wrapLabel(l, isManyStandards ? 20 : 14));
        const radarLabels = standardLabelsRaw.map((l) => wrapLabel(l, 12));

        // Sesuaikan tinggi kanvas Bar Chart otomatis kalau grafik dibuat horizontal
        const barCanvas = document.getElementById('chartBar');
        if (isManyStandards) {
            const dynamicHeight = Math.max(240, standardLabelsRaw.length * 34);
            barCanvas.height = dynamicHeight;
            barCanvas.style.height = dynamicHeight + 'px';
            barCanvas.parentElement.style.height = dynamicHeight + 'px';
        }

        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Skor Capaian',
                    data: standardScores,
                    backgroundColor: '#2575fc',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: isManyStandards ? 'y' : 'x',
                responsive: false,
                scales: isManyStandards
                    ? {
                        x: { beginAtZero: true, max: 4, ticks: { stepSize: 1 } },
                        y: { ticks: { font: { size: 10 } } }
                    }
                    : {
                        y: { beginAtZero: true, max: 4, ticks: { stepSize: 1 } },
                        x: { ticks: { font: { size: 10 } } }
                    },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        new Chart(document.getElementById('chartRadar'), {
            type: 'radar',
            data: {
                labels: radarLabels,
                datasets: [{
                    label: 'Skor Capaian',
                    data: standardScores,
                    backgroundColor: 'rgba(37, 117, 252, 0.2)',
                    borderColor: '#2575fc',
                    pointBackgroundColor: '#2575fc'
                }]
            },
            options: {
                responsive: false,
                scales: {
                    r: {
                        min: 0, max: 4, ticks: { stepSize: 1, font: { size: 9 } },
                        pointLabels: { font: { size: standardLabelsRaw.length > 8 ? 8 : 10 } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>