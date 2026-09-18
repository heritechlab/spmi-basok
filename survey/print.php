<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$slug = trim($_GET['type'] ?? '');
$year = (int) ($_GET['year'] ?? 0);
$unitId = (int) ($_GET['unit_id'] ?? 0);

$typeResult = $service->getTypeBySlug($slug);

if (!$typeResult['success'] || $year <= 0) {
    die('<p style="font-family: sans-serif; padding: 40px;">Parameter tidak valid.</p>');
}

$type = $typeResult['data'];
$typeId = (int) $type['id'];

$isLayananBased = $service->isLayananBased($typeId);
$requiresIdentity = (int) ($type['requires_identity'] ?? 0) === 1;
$scaleConfigResult = $service->getScaleConfig($typeId);
$surveyScaleMax = $scaleConfigResult['max'] ?? 5;

$stmtValTest = $conn->prepare("SELECT * FROM survey_validity_tests WHERE type_id = ? LIMIT 1");
$stmtValTest->bind_param("i", $typeId);
$stmtValTest->execute();
$validityTest = $stmtValTest->get_result()->fetch_assoc();

$validityItems = [];

if ($validityTest) {
    $stmtValItems = $conn->prepare("
        SELECT vi.r_hitung, vi.is_valid, sq.question_text, sc.name AS category_name
        FROM survey_validity_items vi
        JOIN survey_questions sq ON sq.id = vi.question_id
        JOIN survey_categories sc ON sc.id = sq.category_id
        WHERE vi.test_id = ?
        ORDER BY sc.sort_order ASC, sq.sort_order ASC
    ");
    $stmtValItems->bind_param("i", $validityTest['id']);
    $stmtValItems->execute();
    $validityItems = $stmtValItems->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stmtRTL = $conn->prepare("
    SELECT rap.*, sc.name AS survey_category_name, rm.meeting_number, rm.meeting_date
    FROM rtm_action_plans rap
    LEFT JOIN survey_categories sc ON sc.id = rap.survey_category_id
    LEFT JOIN rtm_meetings rm ON rm.id = rap.rtm_meeting_id
    WHERE rap.source_type = 'survey' AND rap.survey_type_id = ? AND rap.survey_year = ?
    ORDER BY rm.meeting_date ASC, rap.id ASC
");
$stmtRTL->bind_param("ii", $typeId, $year);
$stmtRTL->execute();
$rtlList = $stmtRTL->get_result()->fetch_all(MYSQLI_ASSOC);

$recapLabels = [
    'pengguna' => [
        'bab_title'    => 'Rekapitulasi Penilaian Kinerja Lulusan per Jenis Kemampuan',
        'col1_header'  => 'Jenis Kemampuan',
        'group_header' => 'Jumlah Lulusan yang Dinilai (%)',
        'interpretasi_subjek' => 'jenis kemampuan lulusan',
        'interpretasi_pihak'  => 'pengguna lulusan',
    ],
    'mitra' => [
        'bab_title'    => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama',
        'col1_header'  => 'Aspek Kerjasama',
        'group_header' => 'Tingkat Kepuasan Mitra (%)',
        'interpretasi_subjek' => 'aspek kerjasama',
        'interpretasi_pihak'  => 'mitra kerjasama',
    ],
'mitra_penelitian' => [
        'bab_title'    => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama Penelitian',
        'col1_header'  => 'Aspek Kerjasama Penelitian',
        'group_header' => 'Tingkat Kepuasan Mitra Penelitian (%)',
        'interpretasi_subjek' => 'aspek kerjasama penelitian',
        'interpretasi_pihak'  => 'mitra penelitian',
    ],
    'mitra_pkm' => [
        'bab_title'    => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama Pengabdian Masyarakat',
        'col1_header'  => 'Aspek Kerjasama Pengabdian Masyarakat',
        'group_header' => 'Tingkat Kepuasan Mitra Pengabdian Masyarakat (%)',
        'interpretasi_subjek' => 'aspek kerjasama pengabdian masyarakat',
        'interpretasi_pihak'  => 'mitra pengabdian masyarakat',
    ],
];

$currentRecapLabel = $recapLabels[$type['slug']] ?? [
    'bab_title'    => 'Rekapitulasi Tingkat Kepuasan per Kategori',
    'col1_header'  => 'Kategori',
    'group_header' => 'Tingkat Kepuasan (%)',
    'interpretasi_subjek' => 'kategori',
    'interpretasi_pihak'  => 'responden',
];

$recapKemampuan = (!$isLayananBased && $surveyScaleMax == 4)
    ? $service->getCategoryRecapTable($typeId, $year, $unitId)['data']
    : [];

$selectedProdiName = null;

if ($unitId > 0) {
    $stmtUnit = $conn->prepare("SELECT name FROM units WHERE id = ? LIMIT 1");
    $stmtUnit->bind_param("i", $unitId);
    $stmtUnit->execute();
    $unitRow = $stmtUnit->get_result()->fetch_assoc();
    $selectedProdiName = $unitRow['name'] ?? null;
}

$overall = $service->getOverallStats($typeId, $year, $unitId)['data'];
$saranList = $service->getSaranList($typeId, $year, 200, 0, $unitId)['data'];
$totalSaran = $service->countSaran($typeId, $year, $unitId)['data'];

if ($isLayananBased) {
    $layananScores = $service->getLayananScores($typeId, $year, $unitId)['data'];
    $recapData = $service->getLayananRecapTable($typeId, $year, $unitId)['data'];
    $categoryScores = [];
    $questionScores = [];
} else {
    $categoryScores = $service->getCategoryScores($typeId, $year, $unitId)['data'];
    $questionScores = $service->getQuestionScores($typeId, $year, $unitId)['data'];
    $layananScores = [];
    $recapData = [];
}

$stmtKetuaLpm = $conn->prepare("SELECT full_name FROM users WHERE role_id = 2 LIMIT 1");
$stmtKetuaLpm->execute();
$ketuaLpmRow = $stmtKetuaLpm->get_result()->fetch_assoc();
$ketuaLpmNama = $ketuaLpmRow['full_name'] ?? '(...........................)';

$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Survey - <?= htmlspecialchars($type['name']) ?> <?= $year ?></title>

 <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #be185d;
            --primary-light: #f9a8d4;
            --accent: #d97706;
            --rose-deep: #831843;
            --ink: #1f2937;
            --muted: #6b7280;
            --paper: #fdfcfd;
        }

        @page { size: A4; margin: 30mm 30mm 30mm 30mm; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0 20px !important; }
            .page-break { page-break-after: always; }
            .cover-page { padding-top: 0 !important; min-height: auto !important; }
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
        .cover-page p, .cover-page h1, .cover-page h2 { text-align: center; }
        .no-justify p { text-align: center; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        table thead { display: table-header-group; }
        table tr { page-break-inside: avoid; break-inside: avoid; }
        table th, table td { border: 1px solid #fbe0ee; padding: 5px 8px; font-size: 12px; vertical-align: top; }
        table th { background: linear-gradient(135deg, #fdf0f7, #fbe0ee); text-align: center; color: var(--primary); font-weight: 700; }
        table tr:nth-child(even) td { background: #fffbfd; }

        .recap-table th, .recap-table td { font-size: 12px; padding: 1px 6px; line-height: 1.15; }
        .recap-table td, .recap-table th { vertical-align: middle; }

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
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--rose-deep));
        }

        .cover-page::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--rose-deep), var(--accent), var(--primary));
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

        .cover-page img.cover-logo { display: block; height: 120px; margin: 0 auto 20px; filter: drop-shadow(0 4px 10px rgba(190,24,93,0.18)); }

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
            font-size: 28px;
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
            background: linear-gradient(90deg, var(--primary), var(--accent));
            margin: 22px auto;
            border-radius: 3px;
        }

        .cover-ribbon {
            display: inline-block;
            margin-top: 28px;
            padding: 10px 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), var(--rose-deep));
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

        /* ===== JUDUL BAB (gaya pembatas buku, motif bunga) ===== */

        .bab-title {
            position: relative;
            font-family: "Playfair Display", serif;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            text-transform: none;
            color: var(--ink);
            letter-spacing: 0.3px;
            max-width: 520px;
            margin: 0 auto 40px;
            padding-bottom: 26px;
        }

        .bab-title::first-line {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--accent);
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
            content: "❀";
            position: absolute;
            bottom: -9px;
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
            margin-bottom: 8px;
            color: var(--ink);
            padding-left: 10px;
            border-left: 4px solid var(--accent);
        }

        .summary-table th, .summary-table td { text-align: center; }

        .signature-block { display: flex; justify-content: center; align-items: flex-start; gap: 60px; margin-top: 20px; }
        .signature { width: 240px; text-align: center; margin: 0 auto; }
        .signature p { text-align: center !important; margin: 4px 0; }
        .signature img { display: block !important; margin: 8px auto !important; }
        .signature .space { height: 70px; }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 40px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--rose-deep));
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(190,24,93,0.3);
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

            <span class="cover-kicker"><i class="bi bi-emoji-smile"></i> Sistem Penjaminan Mutu Internal</span>

            <h1>Laporan Hasil<br><?= htmlspecialchars($type['name']) ?></h1>

            <div class="cover-divider"></div>

            <?php if ($selectedProdiName): ?>
                <h2>Program Studi: <?= htmlspecialchars($selectedProdiName) ?></h2>
            <?php endif; ?>

            <div class="cover-ribbon">
                <i class="bi bi-calendar-event"></i> Tahun <?= $year ?>
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
                <?= htmlspecialchars($profile['city'] ?? '') ?> &middot; <?= $year ?>
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
            pelaksanaan <?= htmlspecialchars($type['name']) ?> Tahun <?= $year ?> dapat terlaksana dengan baik.
        </p>

        <p>
            Laporan ini disusun sebagai bentuk evaluasi kepuasan layanan institusi dari sudut pandang responden,
            guna menjadi bahan masukan bagi perbaikan dan peningkatan mutu layanan secara berkelanjutan sebagai
            bagian dari siklus Sistem Penjaminan Mutu Internal (SPMI).
        </p>

        <p>Kami menyampaikan terima kasih kepada seluruh responden yang telah berpartisipasi dalam survey ini.</p>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua LPM,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpmNama) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Latar Belakang</h3>
        <p>
            <?= htmlspecialchars($type['name']) ?> dilaksanakan sebagai bagian dari tahap Evaluasi dalam siklus
            Sistem Penjaminan Mutu Internal (SPMI), guna mengukur tingkat kepuasan responden terhadap layanan
            yang diberikan oleh <?= htmlspecialchars($profile['institution_name'] ?? '') ?>.
        </p>

        <h3 class="sub-title">B. Tujuan</h3>
        <p>
            1. Mengukur tingkat kepuasan responden terhadap layanan Akademik, Administrasi, Sarana Prasarana,
            dan Sistem Informasi;<br>
            2. Mengidentifikasi aspek layanan yang memerlukan perbaikan berdasarkan skor terendah;<br>
            3. Menghimpun saran dan masukan sebagai bahan tindak lanjut peningkatan mutu layanan.
        </p>

        <h3 class="sub-title">C. Metode</h3>
        <p>
            Survey dilaksanakan secara daring menggunakan skala Likert 4 (empat) untuk setiap pertanyaan, dilengkapi kolom saran dan masukan terbuka.
            Survey diisi oleh responden dengan teknik klaster random sampling. penarikan sampel survey dilakukan atas persetujuan responden pada Tahun <?= $year ?>.
        </p>

    </div>

<!-- ===================================================== -->
    <!-- BAB II UJI VALIDITAS DAN RELIABILITAS INSTRUMEN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Uji Validitas dan Reliabilitas Instrumen</div>

        <?php if (!$validityTest): ?>

        <p>
            Data hasil Uji Validitas dan Reliabilitas Instrumen untuk <?= htmlspecialchars($type['name']) ?>
            belum diisi. Silakan lengkapi melalui menu Kelola Hasil Uji Validitas &amp; Reliabilitas Instrumen.
        </p>

        <?php else: ?>

        <h3 class="sub-title">A. Jumlah Responden Uji Coba</h3>

        <p>
            Uji coba instrumen <?= htmlspecialchars($type['name']) ?> dilaksanakan terhadap
            <strong><?= $validityTest['n_responden'] ?? '-' ?></strong> responden uji coba, terpisah dari
            responden yang mengisi survey pada pelaksanaan sesungguhnya.
        </p>

        <h3 class="sub-title">B. Uji Validitas Instrumen</h3>

        <p>
            Uji validitas dilakukan dengan metode <strong>Korelasi Item-Total (Pearson Product Moment)</strong>.
            Suatu butir pertanyaan dinyatakan <strong>valid</strong> apabila nilai r-hitung lebih besar dari
            r-tabel sebesar <strong><?= $validityTest['r_tabel'] !== null ? number_format((float) $validityTest['r_tabel'], 3) : '-' ?></strong>.
        </p>

        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="130">Kategori</th>
                    <th>Pernyataan</th>
                    <th width="70">r Hitung</th>
                    <th width="70">r Tabel</th>
                    <th width="70">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($validityItems)): ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada data.</td></tr>
                <?php endif; ?>
                <?php foreach ($validityItems as $i => $item): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                        <td><?= htmlspecialchars($item['question_text']) ?></td>
                        <td style="text-align:center;"><?= $item['r_hitung'] !== null ? number_format((float) $item['r_hitung'], 3) : '-' ?></td>
                        <td style="text-align:center;"><?= $validityTest['r_tabel'] !== null ? number_format((float) $validityTest['r_tabel'], 3) : '-' ?></td>
                        <td style="text-align:center;">
                            <?php if ((int) ($item['is_valid'] ?? -1) === 1): ?>
                                Valid
                            <?php elseif ((int) ($item['is_valid'] ?? -1) === 0): ?>
                                Tidak Valid
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
            $validCount = count(array_filter($validityItems, fn($it) => (int) ($it['is_valid'] ?? -1) === 1));
            $totalValItems = count($validityItems);
        ?>

        <?php if ($totalValItems > 0): ?>
        <p>
            Berdasarkan hasil pengujian, sebanyak <strong><?= $validCount ?></strong> dari <strong><?= $totalValItems ?></strong>
            butir pertanyaan dinyatakan valid (r-hitung &gt; r-tabel).
        </p>
        <?php endif; ?>

        <h3 class="sub-title">C. Uji Reliabilitas Instrumen</h3>

        <?php if ($validityTest['cronbach_alpha'] !== null): ?>

        <p>
            Uji reliabilitas dilakukan dengan metode <strong>Cronbach's Alpha</strong>. Hasil perhitungan
            menunjukkan nilai Cronbach's Alpha sebesar
            <strong><?= number_format((float) $validityTest['cronbach_alpha'], 3) ?></strong>, yang termasuk
            dalam kategori <strong><?= htmlspecialchars($validityTest['reliability_label'] ?? '-') ?></strong>.
        </p>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Nilai Cronbach's Alpha</th>
                    <th>Kategori Reliabilitas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= number_format((float) $validityTest['cronbach_alpha'], 3) ?></td>
                    <td><?= htmlspecialchars($validityTest['reliability_label'] ?? '-') ?></td>
                </tr>
            </tbody>
        </table>

        <?php else: ?>

        <p>Nilai Cronbach's Alpha belum diisi.</p>

        <?php endif; ?>

        <?php if (!empty($validityTest['catatan'])): ?>
        <h3 class="sub-title">D. Catatan Tambahan</h3>
        <p><?= nl2br(htmlspecialchars($validityTest['catatan'])) ?></p>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB III HASIL SURVEY -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB III<br>Hasil Survey</div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Total Responden</th>
                    <th>Rata-rata Skor Kepuasan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
    <td><?= $overall['total_responden'] ?></td>
                    <td><?= $overall['rata_rata_skor'] ?> / <?= number_format($surveyScaleMax, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <h3 class="sub-title">A. Profil Skor per <?= $isLayananBased ? 'Layanan' : 'Kategori Layanan' ?></h3>

        <?php
            $radarLabels = $isLayananBased ? array_column($layananScores, 'name') : array_column($categoryScores, 'name');
            $radarValues = $isLayananBased ? array_column($layananScores, 'skor_akhir') : array_map(fn($c) => round((float) $c['avg_score'], 2), $categoryScores);
            $radarMax = $surveyScaleMax;

            $interpretasiLabel = function (float $skor, int $max): string {
                $persen = $max > 0 ? ($skor / $max) * 100 : 0;
                if ($persen >= 84) return 'Sangat Puas';
                if ($persen >= 68) return 'Puas';
                if ($persen >= 52) return 'Cukup Puas';
                if ($persen >= 36) return 'Kurang Puas';
                return 'Sangat Tidak Puas';
            };

            $skorKeseluruhan = (float) $overall['rata_rata_skor'];
            $labelKeseluruhan = $interpretasiLabel($skorKeseluruhan, $radarMax);

            $profilTertinggi = null;
            $profilTerendah = null;
            $profilSumber = $isLayananBased ? $layananScores : $categoryScores;
            $profilSkorKey = $isLayananBased ? 'skor_akhir' : 'avg_score';

            foreach ($profilSumber as $p) {
                $skor = (float) $p[$profilSkorKey];
                if ($profilTertinggi === null || $skor > (float) $profilTertinggi[$profilSkorKey]) {
                    $profilTertinggi = $p;
                }
                if ($profilTerendah === null || $skor < (float) $profilTerendah[$profilSkorKey]) {
                    $profilTerendah = $p;
                }
            }
        ?>

        <div style="text-align:center; margin: 16px 0;">
            <div style="width: 340px; height: 340px; margin: 0 auto;">
                <canvas
                    id="categoryRadarChart"
                    data-max="<?= $radarMax ?>"
                    data-labels='<?= json_encode($radarLabels) ?>'
                    data-values='<?= json_encode($radarValues) ?>'
                    width="340" height="340"></canvas>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th><?= $isLayananBased ? 'Layanan' : 'Kategori Layanan' ?></th>
                    <th width="100">Rata-rata Skor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($profilSumber)): ?>
                    <tr><td colspan="3" style="text-align:center;">Belum ada data.</td></tr>
                <?php endif; ?>
                <?php foreach ($profilSumber as $i => $p): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td style="text-align:center;"><?= round((float) $p[$profilSkorKey], 2) ?> / <?= $radarMax ?>.00</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($profilSumber)): ?>
        <p>
            Berdasarkan hasil survey, diperoleh rata-rata skor kepuasan keseluruhan sebesar
            <strong><?= $skorKeseluruhan ?> dari <?= $radarMax ?>.00</strong>, yang berada pada kategori
            <strong><?= $labelKeseluruhan ?></strong>. <?= $isLayananBased ? 'Layanan' : 'Kategori layanan' ?> dengan skor tertinggi adalah
            <strong><?= htmlspecialchars($profilTertinggi['name']) ?></strong>
            (<?= round((float) $profilTertinggi[$profilSkorKey], 2) ?>/<?= $radarMax ?>.00), sedangkan yang skornya
            terendah adalah <strong><?= htmlspecialchars($profilTerendah['name']) ?></strong>
            (<?= round((float) $profilTerendah[$profilSkorKey], 2) ?>/<?= $radarMax ?>.00).
        </p>
        <?php endif; ?>

        <?php if ($isLayananBased && !empty($layananScores)): ?>

        <?php
            $genericAxisLabels = ['Keandalan', 'Daya Tanggap', 'Kepastian', 'Kepedulian'];
            $datasetColors = ['#7c3aed', '#2563eb', '#16a34a', '#ea580c'];

            $layananGabungan = [];
            $layananTerpisah = [];

            foreach ($layananScores as $layanan) {
                if (count($layanan['aspek_scores']) === 4) {
                    $layananGabungan[] = $layanan;
                } else {
                    $layananTerpisah[] = $layanan;
                }
            }
        ?>

        <h3 class="sub-title">B. Profil Skor per Aspek pada Setiap Layanan</h3>

        <?php if (!empty($layananGabungan)): ?>

        <div style="text-align:center; margin: 16px 0;">

            <div style="width: 340px; height: 340px; margin: 0 auto;">
                <canvas
                    id="aspekCombinedChart"
                    data-axis-labels='<?= json_encode($genericAxisLabels) ?>'
                    data-datasets='<?= json_encode(array_map(function ($layanan, $i) use ($datasetColors) {
                        return [
                            'label' => 'Layanan ' . $layanan['name'],
                            'values' => array_map(fn($a) => $a['avg_score'] ?? 0, $layanan['aspek_scores']),
                            'color' => $datasetColors[$i % count($datasetColors)],
                        ];
                    }, $layananGabungan, array_keys($layananGabungan))) ?>'
                    width="340" height="340"></canvas>
            </div>

            <div style="display:flex; justify-content:center; gap: 16px; font-size: 11px; margin-top: 6px;">
                <?php foreach ($layananGabungan as $i => $layanan): ?>
                    <span>
                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:<?= $datasetColors[$i % count($datasetColors)] ?>;"></span>
                        Layanan <?= htmlspecialchars($layanan['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>

        </div>

<p style="font-size: 12px; color: #555;">
            Keempat aspek pada grafik di atas (Keandalan, Daya Tanggap, Kepastian, Kepedulian) merepresentasikan
            dimensi penilaian yang sama pada tiap Layanan, sehingga dapat dibandingkan langsung antar Layanan
            dalam satu grafik.
        </p>

        <?php
            $layananAvgList = array_map(function ($layanan) {
                $values = array_map(fn($a) => $a['avg_score'] ?? 0, $layanan['aspek_scores']);
                return ['name' => $layanan['name'], 'avg' => count($values) > 0 ? array_sum($values) / count($values) : 0];
            }, $layananGabungan);

            usort($layananAvgList, fn($a, $b) => $b['avg'] <=> $a['avg']);

            $layananTertinggiGabungan = $layananAvgList[0] ?? null;
            $layananTerendahGabungan = end($layananAvgList) ?: null;

            $aspekAvgPerSumbu = [];
            foreach ($genericAxisLabels as $idx => $axisName) {
                $nilaiSumbu = array_map(fn($layanan) => $layanan['aspek_scores'][$idx]['avg_score'] ?? 0, $layananGabungan);
                $aspekAvgPerSumbu[$axisName] = count($nilaiSumbu) > 0 ? array_sum($nilaiSumbu) / count($nilaiSumbu) : 0;
            }

            arsort($aspekAvgPerSumbu);
            $aspekTertinggiNama = array_key_first($aspekAvgPerSumbu);
            $aspekTerendahNama = array_key_last($aspekAvgPerSumbu);
        ?>

        <?php if ($layananTertinggiGabungan && $layananTerendahGabungan): ?>
        <p>
            Berdasarkan grafik perbandingan di atas, Layanan <strong><?= htmlspecialchars($layananTertinggiGabungan['name']) ?></strong>
            memperoleh skor rata-rata tertinggi (<?= round($layananTertinggiGabungan['avg'], 2) ?>/4.00) di antara ketiga Layanan,
            sedangkan Layanan <strong><?= htmlspecialchars($layananTerendahGabungan['name']) ?></strong> memperoleh skor
            rata-rata terendah (<?= round($layananTerendahGabungan['avg'], 2) ?>/4.00). Ditinjau dari sisi aspek,
            dimensi <strong><?= htmlspecialchars($aspekTertinggiNama) ?></strong> secara konsisten dinilai paling baik oleh
            mahasiswa di ketiga Layanan tersebut, sementara dimensi <strong><?= htmlspecialchars($aspekTerendahNama) ?></strong>
            relatif menjadi titik lemah yang perlu mendapat perhatian dan tindak lanjut perbaikan.
        </p>
        <?php endif; ?>

        <?php endif; ?>

<?php foreach ($layananTerpisah as $layanan): ?>

            <?php
                $aspekLabels = array_column($layanan['aspek_scores'], 'name');
                $aspekValues = array_map(fn($a) => $a['avg_score'] ?? 0, $layanan['aspek_scores']);

                $aspekMap = [];
                foreach ($layanan['aspek_scores'] as $a) {
                    $aspekMap[$a['name']] = $a['avg_score'] ?? 0;
                }
                arsort($aspekMap);
                $aspekTerbaikNama = array_key_first($aspekMap);
                $aspekTerburukNama = array_key_last($aspekMap);
            ?>

            <div style="text-align:center; margin: 16px 0;">
                <strong style="font-size: 13px;">Layanan <?= htmlspecialchars($layanan['name']) ?></strong>
                <div style="width: 300px; height: 300px; margin: 6px auto;">
                    <canvas
                        class="aspekRadarChart"
                        data-max="4"
                        data-labels='<?= json_encode($aspekLabels) ?>'
                        data-values='<?= json_encode($aspekValues) ?>'
                        width="300" height="300"></canvas>
                </div>
            </div>

            <?php if (!empty($aspekMap)): ?>
            <p>
                Pada Layanan <?= htmlspecialchars($layanan['name']) ?>, aspek
                <strong><?= htmlspecialchars($aspekTerbaikNama) ?></strong> memperoleh penilaian tertinggi
                (<?= round($aspekMap[$aspekTerbaikNama], 2) ?>/4.00), sementara aspek
                <strong><?= htmlspecialchars($aspekTerburukNama) ?></strong> memperoleh penilaian terendah
                (<?= round($aspekMap[$aspekTerburukNama], 2) ?>/4.00) dan perlu menjadi perhatian dalam
                peningkatan mutu layanan ini.
            </p>
            <?php endif; ?>

        <?php endforeach; ?>

        <?php endif; ?>

        <?php if (!$isLayananBased): ?>

        <h3 class="sub-title">B. Detail Skor per Pertanyaan</h3>

        <table>
            <thead>
                <tr>
                    <th width="130">Kategori</th>
                    <th>Pertanyaan</th>
                    <th width="90">Rata-rata</th>
                    <th width="80">Responden</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questionScores)): ?>
                    <tr><td colspan="4" style="text-align:center;">Belum ada data.</td></tr>
                <?php endif; ?>
                <?php foreach ($questionScores as $q): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['category_name']) ?></td>
                        <td><?= htmlspecialchars($q['question_text']) ?></td>
                        <td style="text-align:center;"><?= round((float) $q['avg_score'], 2) ?></td>
                        <td style="text-align:center;"><?= (int) $q['total_jawaban'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 class="sub-title">C. Saran dan Masukan Responden (<?= $totalSaran ?> masukan)</h3>

        <?php else: ?>

        <h3 class="sub-title">B. Saran dan Masukan Responden (<?= $totalSaran ?> masukan)</h3>

        <?php endif; ?>

        <?php if (empty($saranList)): ?>
            <p>Tidak ada saran/masukan yang diberikan responden pada tahun ini.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Saran / Masukan</th>
                        <th width="90">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saranList as $i => $s): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= nl2br(htmlspecialchars($s['saran'])) ?></td>
                            <td style="text-align:center;"><?= date('d/m/Y', strtotime($s['submitted_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

<?php if ($isLayananBased): ?>

    <!-- ===================================================== -->
    <!-- BAB IV REKAPITULASI TINGKAT KEPUASAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB IV<br>Rekapitulasi Tingkat Kepuasan Mahasiswa</div>

        <?php
            $romanNumerals = ['i', 'ii', 'iii', 'iv', 'v'];

            $totalSangatBaik = 0;
            $totalBaik = 0;
            $totalCukup = 0;
            $totalKurang = 0;
            $totalBaris = 0;

            $layananPuasTertinggi = null;
            $layananPuasTerendah = null;
            $aspekKritis = null;
            $aspekKritisNilai = null;
        ?>

        <table class="recap-table">
            <thead>
                <tr>
                    <th rowspan="2" width="25">No</th>
                    <th rowspan="2" width="260">Aspek yang Diukur</th>
                    <th colspan="4">Tingkat Kepuasan Mahasiswa (%)</th>
                    <th rowspan="2" width="70">Persentase</th>
                </tr>
                <?php $scaleLabelsArrPrint = $scaleConfigResult['labels'] ?? ['Kurang Baik', 'Cukup Baik', 'Baik', 'Sangat Baik']; ?>
                <tr>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[3] ?? 'Sangat Baik') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[2] ?? 'Baik') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[1] ?? 'Cukup') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[0] ?? 'Kurang') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recapData as $li => $layanan): ?>

                    <?php
                        $layananSangatBaik = 0;
                        $layananBaik = 0;
                        $layananCukup = 0;
                        $layananKurang = 0;
                        $jumlahAspek = count($layanan['aspek_scores']);

                        foreach ($layanan['aspek_scores'] as $aspekHitung) {
                            $dh = $aspekHitung['distribusi'];
                            $layananSangatBaik += $dh['sangat_baik'];
                            $layananBaik += $dh['baik'];
                            $layananCukup += $dh['cukup'];
                            $layananKurang += $dh['kurang'];

                            $aspekPuas = $dh['sangat_baik'] + $dh['baik'];
                            if ($aspekKritisNilai === null || $aspekPuas < $aspekKritisNilai) {
                                $aspekKritisNilai = $aspekPuas;
                                $aspekKritis = $aspekHitung['name'] . ' (Layanan ' . $layanan['name'] . ')';
                            }
                        }

                        $layananSangatBaikAvg = $jumlahAspek > 0 ? round($layananSangatBaik / $jumlahAspek, 1) : 0;
                        $layananBaikAvg = $jumlahAspek > 0 ? round($layananBaik / $jumlahAspek, 1) : 0;
                        $layananCukupAvg = $jumlahAspek > 0 ? round($layananCukup / $jumlahAspek, 1) : 0;
                        $layananKurangAvg = $jumlahAspek > 0 ? round($layananKurang / $jumlahAspek, 1) : 0;
                        $layananTotalAvg = round($layananSangatBaikAvg + $layananBaikAvg + $layananCukupAvg + $layananKurangAvg, 1);
                        $layananPuas = round($layananSangatBaikAvg + $layananBaikAvg, 1);

                        if ($layananPuasTertinggi === null || $layananPuas > $layananPuasTertinggi['nilai']) {
                            $layananPuasTertinggi = ['nama' => $layanan['name'], 'nilai' => $layananPuas];
                        }
                        if ($layananPuasTerendah === null || $layananPuas < $layananPuasTerendah['nilai']) {
                            $layananPuasTerendah = ['nama' => $layanan['name'], 'nilai' => $layananPuas];
                        }
                    ?>

                    <tr>
                        <td style="text-align:center;"><?= $li + 1 ?></td>
                        <td><strong>Layanan <?= htmlspecialchars($layanan['name']) ?> :</strong></td>
                        <td style="text-align:center;"><strong><?= $layananSangatBaikAvg ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $layananBaikAvg ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $layananCukupAvg ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $layananKurangAvg ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $layananTotalAvg ?>%</strong></td>
                    </tr>

                    <?php foreach ($layanan['aspek_scores'] as $ai => $aspek): ?>
                        <?php
                            $d = $aspek['distribusi'];
                            $totalSangatBaik += $d['sangat_baik'];
                            $totalBaik += $d['baik'];
                            $totalCukup += $d['cukup'];
                            $totalKurang += $d['kurang'];
                            $totalBaris++;
                        ?>
                        <tr>
                            <td></td>
                            <td><?= $romanNumerals[$ai] ?? ($ai + 1) ?>. &nbsp;<?= htmlspecialchars($aspek['name']) ?></td>
                            <td style="text-align:center;"><?= $d['sangat_baik'] ?>%</td>
                            <td style="text-align:center;"><?= $d['baik'] ?>%</td>
                            <td style="text-align:center;"><?= $d['cukup'] ?>%</td>
                            <td style="text-align:center;"><?= $d['kurang'] ?>%</td>
                            <td style="text-align:center;">-</td>
                        </tr>
                    <?php endforeach; ?>

                <?php endforeach; ?>

                <tr>
                    <td colspan="2" style="text-align:center;"><strong>Jumlah</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBaris > 0 ? round($totalSangatBaik / $totalBaris, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBaris > 0 ? round($totalBaik / $totalBaris, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBaris > 0 ? round($totalCukup / $totalBaris, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBaris > 0 ? round($totalKurang / $totalBaris, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBaris * 100 ?>%</strong></td>
                </tr>
            </tbody>
        </table>

        <h3 class="sub-title">Interpretasi dan Analisis</h3>

        <p>
            Berdasarkan tabel rekapitulasi di atas, tingkat kepuasan mahasiswa dengan kategori jawaban
            "Sangat Baik" dan "Baik" (dikategorikan puas) tertinggi diperoleh pada Layanan
            <strong><?= htmlspecialchars($layananPuasTertinggi['nama']) ?></strong> dengan persentase
            gabungan sebesar <strong><?= $layananPuasTertinggi['nilai'] ?>%</strong>, sedangkan tingkat
            kepuasan terendah diperoleh pada Layanan
            <strong><?= htmlspecialchars($layananPuasTerendah['nama']) ?></strong> dengan persentase
            gabungan sebesar <strong><?= $layananPuasTerendah['nilai'] ?>%</strong>.
        </p>

        <?php if ($aspekKritis !== null): ?>
        <p>
            Secara lebih rinci, aspek dengan tingkat kepuasan (Sangat Baik + Baik) paling rendah di antara
            seluruh aspek yang dinilai adalah <strong><?= htmlspecialchars($aspekKritis) ?></strong> dengan
            persentase sebesar <strong><?= $aspekKritisNilai ?>%</strong>. Aspek ini perlu menjadi prioritas
            utama dalam penyusunan Rencana Tindak Lanjut oleh Unit Pengelola Program Studi (UPPS)/Program
            Studi guna meningkatkan mutu layanan pada periode berikutnya.
        </p>
        <?php endif; ?>

        <?php if ($layananPuasTerendah['nama'] !== $layananPuasTertinggi['nama']): ?>
        <p>
            Secara umum, capaian pada Layanan <strong><?= htmlspecialchars($layananPuasTertinggi['nama']) ?></strong>
            perlu dipertahankan dan dijadikan praktik baik, sementara Layanan
            <strong><?= htmlspecialchars($layananPuasTerendah['nama']) ?></strong> memerlukan perhatian dan
            langkah perbaikan yang lebih terarah.
        </p>
        <?php endif; ?>

</div>

<?php endif; ?>

    <?php if (!empty($recapKemampuan)): ?>

    <!-- ===================================================== -->
    <!-- BAB V REKAPITULASI -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB IV<br><?= htmlspecialchars($currentRecapLabel['bab_title']) ?></div>

        <?php
            $totalSBP = 0; $totalBP = 0; $totalCP = 0; $totalKP = 0; $totalBarisP = 0;

            $kemampuanTertinggi = null;
            $kemampuanTerendah = null;

            foreach ($recapKemampuan as $kat) {
                $d = $kat['distribusi'];
                $puas = round($d['sangat_baik'] + $d['baik'], 1);

                if ($kemampuanTertinggi === null || $puas > $kemampuanTertinggi['puas']) {
                    $kemampuanTertinggi = ['nama' => $kat['name'], 'puas' => $puas];
                }
                if ($kemampuanTerendah === null || $puas < $kemampuanTerendah['puas']) {
                    $kemampuanTerendah = ['nama' => $kat['name'], 'puas' => $puas];
                }
            }
        ?>

        <table class="recap-table">
            <thead>
                <tr>
                    <th rowspan="2" width="25">No</th>
                    <th rowspan="2" width="260"><?= htmlspecialchars($currentRecapLabel['col1_header']) ?></th>
                    <th colspan="4"><?= htmlspecialchars($currentRecapLabel['group_header']) ?></th>
                    <th rowspan="2" width="70">Persentase</th>
                </tr>
                <?php $scaleLabelsArrPrint = $scaleConfigResult['labels'] ?? ['Kurang Baik', 'Cukup Baik', 'Baik', 'Sangat Baik']; ?>
                <tr>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[3] ?? 'Sangat Baik') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[2] ?? 'Baik') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[1] ?? 'Cukup') ?></th>
                    <th width="65"><?= htmlspecialchars($scaleLabelsArrPrint[0] ?? 'Kurang') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $romanNumeralsRecapPrint = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x']; ?>
                <?php foreach ($recapKemampuan as $i => $kat): ?>

                    <?php
                        $katSBp = 0; $katBp = 0; $katCp = 0; $katKp = 0;
                        $jumlahPertanyaanP = count($kat['questions']);

                        foreach ($kat['questions'] as $qHitungP) {
                            $dqP = $qHitungP['distribusi'];
                            $katSBp += $dqP['sangat_baik'];
                            $katBp += $dqP['baik'];
                            $katCp += $dqP['cukup'];
                            $katKp += $dqP['kurang'];
                        }

                        $katSBAvgP = $jumlahPertanyaanP > 0 ? round($katSBp / $jumlahPertanyaanP, 1) : 0;
                        $katBAvgP = $jumlahPertanyaanP > 0 ? round($katBp / $jumlahPertanyaanP, 1) : 0;
                        $katCAvgP = $jumlahPertanyaanP > 0 ? round($katCp / $jumlahPertanyaanP, 1) : 0;
                        $katKAvgP = $jumlahPertanyaanP > 0 ? round($katKp / $jumlahPertanyaanP, 1) : 0;
                        $katTotalAvgP = round($katSBAvgP + $katBAvgP + $katCAvgP + $katKAvgP, 1);

                        $totalSBP += $katSBAvgP;
                        $totalBP += $katBAvgP;
                        $totalCP += $katCAvgP;
                        $totalKP += $katKAvgP;
                        $totalBarisP++;
                    ?>

                    <tr>
                        <td style="text-align:center;"><strong><?= $i + 1 ?></strong></td>
                        <td><strong><?= htmlspecialchars($kat['name']) ?> :</strong></td>
                        <td style="text-align:center;"><strong><?= $katSBAvgP ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $katBAvgP ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $katCAvgP ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $katKAvgP ?>%</strong></td>
                        <td style="text-align:center;"><strong><?= $katTotalAvgP ?>%</strong></td>
                    </tr>

                    <?php foreach ($kat['questions'] as $qi => $q): ?>
                        <?php $dqRow = $q['distribusi']; ?>
                        <tr>
                            <td></td>
                            <td>
                                <?= $romanNumeralsRecapPrint[$qi] ?? ($qi + 1) ?>. &nbsp;<?= htmlspecialchars($q['question_text']) ?>
                            </td>
                            <td style="text-align:center;"><?= $dqRow['sangat_baik'] ?>%</td>
                            <td style="text-align:center;"><?= $dqRow['baik'] ?>%</td>
                            <td style="text-align:center;"><?= $dqRow['cukup'] ?>%</td>
                            <td style="text-align:center;"><?= $dqRow['kurang'] ?>%</td>
                            <td style="text-align:center;">-</td>
                        </tr>
                    <?php endforeach; ?>

                <?php endforeach; ?>

                <tr>
                    <td colspan="2" style="text-align:center;"><strong>Total</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBarisP > 0 ? round($totalSBP / $totalBarisP, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBarisP > 0 ? round($totalBP / $totalBarisP, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBarisP > 0 ? round($totalCP / $totalBarisP, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBarisP > 0 ? round($totalKP / $totalBarisP, 1) : 0 ?>%</strong></td>
                    <td style="text-align:center;"><strong><?= $totalBarisP * 100 ?>%</strong></td>
                </tr>
            </tbody>
        </table>

        <h3 class="sub-title">Interpretasi dan Analisis</h3>

        <?php if ($kemampuanTertinggi && $kemampuanTerendah): ?>
        <p>
            Berdasarkan tabel rekapitulasi di atas, <?= $currentRecapLabel['interpretasi_subjek'] ?> yang dinilai "Sangat Baik" dan "Baik"
            (dikategorikan puas) tertinggi oleh <?= $currentRecapLabel['interpretasi_pihak'] ?> adalah
            <strong><?= htmlspecialchars($kemampuanTertinggi['nama']) ?></strong> dengan persentase gabungan
            sebesar <strong><?= $kemampuanTertinggi['puas'] ?>%</strong>, sedangkan penilaian terendah diperoleh
            pada <?= htmlspecialchars($kemampuanTerendah['nama']) ?> dengan persentase
            gabungan sebesar <strong><?= $kemampuanTerendah['puas'] ?>%</strong>.
        </p>

        <p>
            Hasil ini mengindikasikan capaian yang baik pada
            <strong><?= htmlspecialchars($kemampuanTertinggi['nama']) ?></strong>, dan perlu
            dipertahankan sebagai kekuatan yang sudah dicapai. Sebaliknya,
            <strong><?= htmlspecialchars($kemampuanTerendah['nama']) ?></strong> perlu menjadi perhatian dan
            prioritas dalam perbaikan pada periode berikutnya.
        </p>
        <?php endif; ?>

    </div>

    <?php endif; ?>

<!-- ===================================================== -->
    <!-- BAB V RENCANA TINDAK LANJUT -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB V<br>Rencana Tindak Lanjut</div>

        <p>
            Berdasarkan hasil <?= htmlspecialchars($type['name']) ?> Tahun <?= $year ?>, aspek/kategori dengan
            tingkat kepuasan di bawah ambang batas telah ditindaklanjuti secara otomatis melalui Rapat
            Tinjauan Manajemen (RTM) Pengendalian, sebagaimana diuraikan pada tabel berikut.
        </p>

        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="130">Kategori/Aspek</th>
                    <th>Rencana Tindak Lanjut</th>
                    <th width="90">Prioritas</th>
                    <th width="110">Nomor Rapat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rtlList)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada Rencana Tindak Lanjut yang terbentuk dari hasil survey ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($rtlList as $i => $rtl): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($rtl['survey_category_name'] ?? '-') ?></td>
                            <td><?= nl2br(htmlspecialchars($rtl['activity'] ?? '-')) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars(($rtl['importance'] ?? '-') . ' / ' . ($rtl['urgency'] ?? '-')) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($rtl['meeting_number'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($rtlList)): ?>
        <p>
            Sebanyak <strong><?= count($rtlList) ?></strong> Rencana Tindak Lanjut telah dirumuskan berdasarkan
            hasil survey ini, yang akan dipantau pelaksanaannya melalui mekanisme Pengendalian pada siklus
            Sistem Penjaminan Mutu Internal (SPMI).
        </p>
        <?php endif; ?>

    </div>

    <div>

        <div class="bab-title">BAB VI<br>Penutup</div>

        <p>
            Berdasarkan hasil <?= htmlspecialchars($type['name']) ?> Tahun <?= $year ?>, diperoleh rata-rata
            skor kepuasan sebesar <strong><?= $overall['rata_rata_skor'] ?> dari <?= number_format($surveyScaleMax, 2) ?></strong> dengan total
            <strong><?= $overall['total_responden'] ?></strong> responden.
        </p>

        <p>
            Hasil survey ini menjadi bahan evaluasi bagi pimpinan institusi untuk merumuskan langkah perbaikan
            dan peningkatan mutu layanan pada periode berikutnya, sejalan dengan siklus Sistem Penjaminan
            Mutu Internal (SPMI) yang berkelanjutan.
        </p>

        <p>Demikian laporan ini disusun untuk dapat dipergunakan sebagaimana mestinya.</p>

    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function wrapLabel(text, maxCharsPerLine) {
            const words = text.split(" ");
            const lines = [];
            let currentLine = "";

            words.forEach(function (word) {
                const testLine = currentLine ? currentLine + " " + word : word;
                if (testLine.length > maxCharsPerLine && currentLine) {
                    lines.push(currentLine);
                    currentLine = word;
                } else {
                    currentLine = testLine;
                }
            });

            if (currentLine) {
                lines.push(currentLine);
            }

            return lines;
        }

        const combinedCanvas = document.getElementById("aspekCombinedChart");

        if (combinedCanvas) {

            const axisLabels = JSON.parse(combinedCanvas.dataset.axisLabels || "[]");
            const datasetsRaw = JSON.parse(combinedCanvas.dataset.datasets || "[]");

            new Chart(combinedCanvas, {
                type: "radar",
                data: {
                    labels: axisLabels,
                    datasets: datasetsRaw.map(function (ds) {
                        return {
                            label: ds.label,
                            data: ds.values,
                            backgroundColor: ds.color + "22",
                            borderColor: ds.color,
                            borderWidth: 2,
                            pointBackgroundColor: ds.color,
                            pointRadius: 3,
                        };
                    }),
                },
                options: {
                    responsive: false,
                    scales: {
                        r: {
                            min: 0,
                            max: 4,
                            ticks: { stepSize: 1 },
                            pointLabels: { font: { size: 11, weight: "600" } },
                        },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        }

        document.querySelectorAll(".aspekRadarChart").forEach(function (canvas) {

            const labels = JSON.parse(canvas.dataset.labels || "[]").map(function (l) { return wrapLabel(l, 12); });
            const values = JSON.parse(canvas.dataset.values || "[]");
            const max = parseInt(canvas.dataset.max || "4", 10);

            new Chart(canvas, {
                type: "radar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Rata-rata Skor",
                        data: values,
                        backgroundColor: "rgba(124, 58, 237, 0.15)",
                        borderColor: "#7c3aed",
                        borderWidth: 2,
                        pointBackgroundColor: "#7c3aed",
                        pointRadius: 3,
                    }],
                },
                options: {
                    responsive: false,
                    scales: {
                        r: {
                            min: 0,
                            max: max,
                            ticks: { stepSize: 1, display: false },
                            pointLabels: { font: { size: 8 } },
                        },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        });

const radarCanvas = document.getElementById("categoryRadarChart");

        if (radarCanvas) {
            const rawLabels = JSON.parse(radarCanvas.dataset.labels || "[]");
            const labels = rawLabels.map(function (l) { return wrapLabel(l, 14); });
            const values = JSON.parse(radarCanvas.dataset.values || "[]");
            const chartMax = parseInt(radarCanvas.dataset.max || "5", 10);

            new Chart(radarCanvas, {
                type: "radar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Rata-rata Skor",
                        data: values,
                        backgroundColor: "rgba(124, 58, 237, 0.15)",
                        borderColor: "#7c3aed",
                        borderWidth: 2,
                        pointBackgroundColor: "#7c3aed",
                        pointRadius: 4,
                    }],
                },
                options: {
                    responsive: false,
                    scales: {
                        r: {
                            min: 0,
                            max: chartMax,
                            ticks: { stepSize: 1 },
                            pointLabels: { font: { size: 10, weight: "600" } },
                        },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        }
    </script>

</body>
</html>