<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/institution/repository.php';
require_once __DIR__ . '/../signatures/repository.php';

$unitId = (int) ($_GET['unit_id'] ?? 0);
$periodId = (int) ($_GET['period_id'] ?? 0);

$repository = new LedReportRepository($conn);
$service    = new LedReportService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$sigRepo = new LaporanSignatureRepository($conn);
$signature = $sigRepo->find('led', $periodId, $unitId) ?? [];

$data = $service->getReportData($unitId, $periodId);

if (empty($data['unit']) || empty($data['period'])) {
    die('<p style="font-family: sans-serif; padding: 40px;">Data Program Studi atau Periode tidak ditemukan.</p>');
}

$unit = $data['unit'];
$period = $data['period'];
$babII = $data['bab_ii'];
$narratives = $data['narratives'];
$kriteriaAnalysis = $data['kriteria_analysis'];

$ketuaProdiNama = !empty($signature['ketua_tim_nama']) ? $signature['ketua_tim_nama'] : ($unit['head_name'] ?? '(...........................)');
$ketuaLpmNama = !empty($signature['ketua_lpm_nama']) ? $signature['ketua_lpm_nama'] : '(...........................)';

$qrKetuaProdi = (!empty($signature['id']) && !empty($signature['ketua_tim_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=led&id=' . $signature['id'] . '&role=ketua_tim')
    : null;

$qrKetuaLpm = (!empty($signature['id']) && !empty($signature['ketua_lpm_ttd']))
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=led&id=' . $signature['id'] . '&role=ketua_lpm')
    : null;

$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Evaluasi Diri - <?= htmlspecialchars($unit['name']) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary: #b45309;
            --primary-light: #fcd34d;
            --accent: #d97706;
            --amber: #92400e;
            --ink: #1f2937;
            --muted: #6b7280;
            --paper: #fffdfa;
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
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary-light));
        }

        .cover-page::after {
            content: "";
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 14px;
            background: linear-gradient(90deg, var(--primary-light), var(--accent), var(--primary));
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

        .cover-page img.cover-logo { display: block; height: 120px; margin: 0 auto 20px; filter: drop-shadow(0 4px 10px rgba(180,83,9,0.18)); }

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
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
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
            content: "✎";
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
            border-left: 4px solid var(--accent);
        }

        h4.kriteria-title {
            font-family: "Playfair Display", serif;
            font-size: 14.5px;
            font-weight: 700;
            margin-top: 26px;
            margin-bottom: 12px;
            color: var(--primary);
        }

        h5.standard-title {
            font-size: 12.5px;
            font-weight: 700;
            margin-top: 14px;
            margin-bottom: 6px;
            color: var(--amber);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        table th, table td { border: 1px solid #f3e0c5; padding: 5px 8px; font-size: 11.5px; vertical-align: top; line-height: 1.3; }

        table th {
            background: linear-gradient(135deg, #fef3e0, #fbe6c5);
            text-align: center;
            color: var(--primary);
            font-weight: 700;
        }

        table tr:nth-child(even) td { background: #fffaf2; }

        .toc-row { display: flex; justify-content: space-between; border-bottom: 1px dotted #d9ba8a; padding: 5px 0; font-size: 13px; }

        .signature-block { display: flex; justify-content: center; align-items: flex-start; gap: 60px; margin-top: 20px; }
        .signature { width: 220px; text-align: center; margin: 0 auto; }
        .signature p { text-align: center !important; margin: 4px 0; }
        .signature img { display: block !important; margin: 8px auto !important; }
        .signature .space { height: 70px; }

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
            box-shadow: 0 6px 16px rgba(180,83,9,0.3);
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

            <span class="cover-kicker"><i class="bi bi-journal-check"></i> Sistem Penjaminan Mutu Internal</span>

            <h1>Laporan Evaluasi Diri<br>(LED)</h1>

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
    <!-- HALAMAN PENGESAHAN -->
    <!-- ===================================================== -->

    <div class="page-break no-justify">

        <div class="bab-title">Halaman Pengesahan</div>

        <p>
            Laporan Evaluasi Diri (LED) Program Studi <strong><?= htmlspecialchars($unit['name']) ?></strong>
            pada Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> ini disahkan oleh:
        </p>

        <p style="text-align: center; margin-top: 30px;">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?>
        </p>

        <div class="signature-block">
            <div class="signature">
                <p>Ketua Program Studi,<br>&nbsp;</p>
                <?php if ($qrKetuaProdi): ?>
                    <img src="<?= $qrKetuaProdi ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaProdiNama) ?></p>
            </div>

            <div class="signature">
                <p>Mengetahui,<br>Ketua LPM,</p>
                <?php if ($qrKetuaLpm): ?>
                    <img src="<?= $qrKetuaLpm ?>" style="width:45px; height:45px; margin: 8px 0;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpmNama) ?></p>
            </div>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- KATA PENGANTAR -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Kata Pengantar</div>

        <?php if (!empty($narratives['kata_pengantar'])): ?>
            <p><?= nl2br(htmlspecialchars($narratives['kata_pengantar'])) ?></p>
        <?php else: ?>
            <p>
                Puji syukur kami panjatkan kehadirat Tuhan Yang Maha Esa, atas rahmat dan karunia-Nya
                Laporan Evaluasi Diri (LED) Program Studi <strong><?= htmlspecialchars($unit['name']) ?></strong>
                Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong> dapat tersusun.
            </p>
        <?php endif; ?>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua Program Studi,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaProdiNama) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- DAFTAR ISI -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Daftar Isi</div>

        <div class="toc-row"><span>Halaman Pengesahan</span><span>i</span></div>
        <div class="toc-row"><span>Kata Pengantar</span><span>ii</span></div>
        <div class="toc-row"><span>Daftar Isi</span><span>iii</span></div>
        <div class="toc-row"><span><strong>BAB I &nbsp; Pendahuluan</strong></span><span>1</span></div>
        <div class="toc-row"><span><strong>BAB II &nbsp; Laporan Evaluasi Diri</strong></span><span>2</span></div>
        <?php foreach ($babII as $idx => $kriteria): ?>
            <div class="toc-row" style="padding-left: 20px;">
                <span><?= htmlspecialchars($kriteria['criteria_name']) ?></span>
                <span>2.<?= $idx + 1 ?></span>
            </div>
        <?php endforeach; ?>
        <div class="toc-row"><span><strong>BAB III &nbsp; Analisis Kekuatan dan Kelemahan</strong></span><span>3</span></div>
        <div class="toc-row"><span><strong>BAB IV &nbsp; Penutup</strong></span><span>4</span></div>

    </div>

    <!-- ===================================================== -->
    <!-- BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Dasar Penyusunan</h3>
        <p>
            <?php if (!empty($narratives['dasar_penyusunan'])): ?>
                <?= nl2br(htmlspecialchars($narratives['dasar_penyusunan'])) ?>
            <?php else: ?>
                Laporan Evaluasi Diri (LED) ini disusun sebagai bagian dari tahap Evaluasi dalam siklus
                Sistem Penjaminan Mutu Internal (SPMI), berdasarkan hasil isian evaluasi diri oleh
                Program Studi <strong><?= htmlspecialchars($unit['name']) ?></strong> pada Periode
                <strong><?= htmlspecialchars($period['period_name']) ?></strong>.
            <?php endif; ?>
        </p>

        <h3 class="sub-title">B. Tim Penyusun</h3>
        <p>
            <?php if (!empty($narratives['tim_penyusun'])): ?>
                <?= nl2br(htmlspecialchars($narratives['tim_penyusun'])) ?>
            <?php else: ?>
                (Belum diisi)
            <?php endif; ?>
        </p>

        <h3 class="sub-title">C. Mekanisme Kerja Penyusunan</h3>
        <p>
            <?php if (!empty($narratives['mekanisme_kerja'])): ?>
                <?= nl2br(htmlspecialchars($narratives['mekanisme_kerja'])) ?>
            <?php else: ?>
                (Belum diisi)
            <?php endif; ?>
        </p>

    </div>

    <!-- ===================================================== -->
    <!-- BAB II LAPORAN EVALUASI DIRI -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Laporan Evaluasi Diri</div>

        <?php if (empty($babII)): ?>
            <p style="text-align:center; color:#9ca3af;">Belum ada Standar yang ditugaskan pada Periode ini.</p>
        <?php endif; ?>

        <?php foreach ($babII as $kIdx => $kriteria): ?>

            <?php $kriteriaLabel = preg_replace('/^Kriteria\s+[\d.]+\.?\s*/i', '', $kriteria['criteria_name']); ?>

            <h4 class="kriteria-title">2.<?= $kIdx + 1 ?> <?= htmlspecialchars($kriteriaLabel) ?></h4>

            <?php foreach ($kriteria['standards'] as $sIdx => $std): ?>

                <h5 class="standard-title"><?= $kIdx + 1 ?>.<?= $sIdx + 1 ?> <?= htmlspecialchars($std['standard_code']) ?> - <?= htmlspecialchars($std['standard_name']) ?></h5>

                <table>
                    <thead>
                        <tr>
                            <th width="22%">Sub-Elemen<br>(Indikator &amp; Target)</th>
                            <th width="10%">Capaian Target</th>
                            <th width="40%">Pernyataan Evaluasi</th>
                            <th width="10%">Dokumen Pendukung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($std['indicators'])): ?>
                            <tr><td colspan="4" style="text-align:center; color:#9ca3af;">Belum ada Indikator untuk Standar ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($std['indicators'] as $ind): ?>
                                <?php
                                    $entry = $ind['entry'] ?? null;
                                    $docs = $entry['documents'] ?? [];
                                    $docCount = count($docs);
                                ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($ind['item_code']) ?> - <?= htmlspecialchars($ind['indicator']) ?>
                                        <div style="color:#9ca3af; font-size:10.5px;">Target: <?= htmlspecialchars($ind['target'] ?: '-') ?></div>
                                    </td>
                                    <td><?= $entry && !empty($entry['capaian_realisasi']) ? htmlspecialchars($entry['capaian_realisasi']) : '-' ?></td>
                                    <td><?= $entry && !empty($entry['notes']) ? nl2br(htmlspecialchars($entry['notes'])) : '<span style="color:#9ca3af;">Belum diisi</span>' ?></td>
                                    <td style="text-align:center;">
                                        <?= $docCount > 0 ? $docCount . ' Dokumen' : '<span style="color:#9ca3af;">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB III ANALISIS KEKUATAN DAN KELEMAHAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB III<br>Analisis Kekuatan dan Kelemahan</div>

        <?php if (empty($kriteriaAnalysis)): ?>
            <p style="color:#9ca3af;">Belum ada analisis yang diisi.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="28%">Kriteria</th>
                        <th width="36%">Kekuatan</th>
                        <th width="36%">Kelemahan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($babII as $kriteria): ?>
                        <?php $an = $kriteriaAnalysis[$kriteria['criteria_id']] ?? null; ?>
                        <?php if ($an && (!empty($an['kekuatan']) || !empty($an['kelemahan']))): ?>
                            <tr>
                                <td><?= htmlspecialchars($kriteria['criteria_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($an['kekuatan'] ?: '-')) ?></td>
                                <td><?= nl2br(htmlspecialchars($an['kelemahan'] ?: '-')) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB IV PENUTUP -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB IV<br>Penutup</div>

        <h3 class="sub-title">A. Kesimpulan</h3>
        <p>
            <?php if (!empty($narratives['kesimpulan'])): ?>
                <?= nl2br(htmlspecialchars($narratives['kesimpulan'])) ?>
            <?php else: ?>
                Berdasarkan hasil evaluasi diri pada Periode <strong><?= htmlspecialchars($period['period_name']) ?></strong>,
                sebanyak <strong><?= $data['total_terisi'] ?></strong> dari <strong><?= $data['total_indikator'] ?></strong>
                Indikator (<?= $data['persen_terisi'] ?>%) telah diisi dan dievaluasi oleh Program Studi
                <strong><?= htmlspecialchars($unit['name']) ?></strong>.
            <?php endif; ?>
        </p>

        <p style="margin-top: 20px;">Demikian Laporan Evaluasi Diri ini disusun untuk dapat dipergunakan sebagaimana mestinya.</p>

    </div>

</body>
</html>