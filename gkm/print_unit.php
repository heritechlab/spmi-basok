<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$id = (int)($_GET['id'] ?? 0);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$repository = new GkmRepository($conn);
$service    = new GkmService($repository);

$result = $service->getDetail($id);

if (!$result['success']) {
    die('<p style="font-family: sans-serif; padding: 40px;">Data Monitoring tidak ditemukan.</p>');
}

$data = $result['data'];
$monitoring = $data['monitoring'];
$items = $data['items'];
$stats = $data['stats'];

$institusiName = $profile['institution_name'] ?? '';

$gkmNama = $monitoring['gkm_nama'] ?: '(...........................)';
$gkmJabatan = $monitoring['gkm_jabatan'] ?: 'Ketua Gugus Kendali Mutu';
$ketuaLpmNama = $monitoring['lpm_nama'] ?: '(...........................)';

$gkmQrUrl = !empty($monitoring['gkm_ttd'])
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=gkm&id=' . $monitoring['id'] . '&role=gkm')
    : null;

$lpmQrUrl = !empty($monitoring['lpm_ttd'])
    ? 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=gkm&id=' . $monitoring['id'] . '&role=lpm')
    : null;

$tahapList = ['Perencanaan', 'Proses', 'Pelaporan'];

$overallDone = 0;
$overallTotal = 0;
foreach ($tahapList as $t) {
    $overallDone += $stats[$t]['done'] ?? 0;
    $overallTotal += $stats[$t]['total'] ?? 0;
}
$overallPercent = $overallTotal > 0 ? round(($overallDone / $overallTotal) * 100) : 0;

$bulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$today = (int) date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Monitoring GKM - <?= htmlspecialchars($monitoring['unit_name']) ?></title>

    <style>
        @page { size: A4; margin: 30mm 30mm 30mm 30mm; }

@media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .page-break { page-break-after: always; }
            .cover-page { padding-top: 15px !important; min-height: auto !important; }
        }

        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            color: #000;
            padding: 20px 30px;
            line-height: 1.6;
        }

        p { text-align: justify; }
        .cover-page p, .cover-page h1, .cover-page h2 { text-align: center; }
        .no-justify p { text-align: center; }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            page-break-inside: auto;
        }
        table thead { display: table-header-group; }
        table tbody tr { page-break-inside: avoid; }

        .kop {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop img { height: 70px; }

        .cover-page { text-align: center; padding-top: 60px; min-height: 850px; }
        .cover-page img.cover-logo { height: 130px; margin-bottom: 24px; }
        .cover-page h1 { font-size: 22px; text-transform: uppercase; font-weight: bold; margin: 30px 0 8px; }
        .cover-page h2 { font-size: 16px; font-weight: normal; margin: 4px 0; }
        .cover-page .cover-info { margin-top: 50px; font-size: 14px; }
        .cover-page .cover-year { margin-top: 70px; font-size: 15px; font-weight: bold; }

        .bab-title {
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        h3.sub-title { font-size: 13px; font-weight: bold; margin-top: 18px; margin-bottom: 6px; }

        .signature-block { display: flex; justify-content: center; gap: 60px; margin-top: 20px; }
        .signature { width: 220px; text-align: center; }
        .signature p { margin: 4px 0; }
        .signature .space { height: 70px; }

        .info-table { margin-bottom: 16px; }
        .info-table td { border: 1px solid #000; padding: 5px 8px; font-size: 12px; }
        .info-table td.label { font-weight: bold; width: 160px; background: #f5f5f5; }

        .summary-table th, .summary-table td { border: 1px solid #000; padding: 6px 8px; font-size: 12px; text-align: center; }
        .summary-table th { background: #eee; }

        .tahap-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 6px;
            background: #f0f0f0;
            padding: 6px 8px;
            border-left: 4px solid #5b21b6;
            page-break-after: avoid;
            break-after: avoid;
        }

        .content-table th, .content-table td { border: 1px solid #000; padding: 5px 8px; font-size: 11px; vertical-align: top; margin: 0; }
        .content-table th { background: #eee; text-align: center; }

        .status-sudah { color: #198754; font-weight: bold; }
        .status-belum { color: #dc3545; font-weight: bold; }

        .btn-print {
            position: fixed; top: 20px; right: 40px;
            padding: 10px 18px; background: #5b21b6; color: #fff;
            border: none; border-radius: 6px; cursor: pointer; font-size: 14px; z-index: 999;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

    <!-- ===================================================== -->
    <!-- COVER -->
    <!-- ===================================================== -->

    <div class="cover-page page-break">

        <?php if (!empty($profile['logo'])): ?>
            <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" class="cover-logo" alt="Logo">
        <?php endif; ?>

        <h1>Laporan Monitoring Pembelajaran</h1>
        <h2>oleh Gugus Kendali Mutu (GKM)</h2>

        <div class="cover-info">
            <?php if (!empty($profile['foundation_name'])): ?>
                <h2><?= htmlspecialchars($profile['foundation_name']) ?></h2>
            <?php endif; ?>
            <h2><?= htmlspecialchars($institusiName) ?></h2>
            <p><strong>Unit Kerja/Program Studi:</strong> <?= htmlspecialchars($monitoring['unit_name']) ?></p>
            <p><strong>Semester:</strong> <?= htmlspecialchars($monitoring['semester']) ?> &mdash; <strong>Tahun Akademik:</strong> <?= htmlspecialchars($monitoring['academic_year']) ?></p>
        </div>

        <div class="cover-year">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= date('Y') ?>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- KATA PENGANTAR -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">Kata Pengantar</div>

        <p>
            Puji syukur kami panjatkan kehadirat Tuhan Yang Maha Esa, karena atas rahmat dan karunia-Nya,
            pelaksanaan Monitoring Pembelajaran oleh Gugus Kendali Mutu (GKM) pada Unit Kerja/Program Studi
            <strong><?= htmlspecialchars($monitoring['unit_name']) ?></strong> untuk Semester
            <strong><?= htmlspecialchars($monitoring['semester']) ?> Tahun Akademik <?= htmlspecialchars($monitoring['academic_year']) ?></strong>
            dapat terlaksana dengan baik.
        </p>

        <p>
            Laporan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tahap Evaluasi dalam siklus
            Sistem Penjaminan Mutu Internal (SPMI), yang mencakup penilaian kesiapan Perencanaan Pembelajaran,
            pemantauan Proses Pembelajaran, dan evaluasi Pelaporan Akhir Semester.
        </p>

        <p>
            Kami menyampaikan terima kasih kepada seluruh pihak yang telah berkontribusi dalam pelaksanaan
            monitoring ini. Semoga laporan ini dapat memberikan manfaat bagi peningkatan mutu pembelajaran
            secara berkelanjutan.
        </p>

        <p style="margin-top: 40px;"><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?></p>
        <p>Ketua Gugus Kendali Mutu,</p>
        <div style="height: 60px;"></div>
        <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($gkmNama) ?></p>

    </div>

    <!-- ===================================================== -->
    <!-- HALAMAN PENGESAHAN -->
    <!-- ===================================================== -->

    <div class="page-break no-justify">

        <div class="bab-title">Halaman Pengesahan</div>

        <p>
            Laporan Monitoring Pembelajaran oleh Gugus Kendali Mutu (GKM) untuk Unit Kerja/Program Studi
            <strong><?= htmlspecialchars($monitoring['unit_name']) ?></strong> pada Semester
            <strong><?= htmlspecialchars($monitoring['semester']) ?> Tahun Akademik <?= htmlspecialchars($monitoring['academic_year']) ?></strong>
            ini disahkan oleh:
        </p>

        <p style="text-align: center; margin-top: 40px;">
            <?= htmlspecialchars($profile['city'] ?? '') ?>, <?= $today ?>
        </p>

        <div class="signature-block">

            <div class="signature">
                <p><?= htmlspecialchars($gkmJabatan) ?>,<br>&nbsp;</p>
                <?php if ($gkmQrUrl): ?>
                    <img src="<?= $gkmQrUrl ?>" style="width:45px; height:45px; margin: 8px auto; display:block;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($gkmNama) ?></p>
            </div>

            <div class="signature">
                <p>Mengetahui,<br>Ketua Lembaga Penjaminan Mutu,</p>
                <?php if ($lpmQrUrl): ?>
                    <img src="<?= $lpmQrUrl ?>" style="width:45px; height:45px; margin: 8px auto; display:block;">
                <?php else: ?>
                    <div class="space"></div>
                <?php endif; ?>
                <p style="font-weight:bold; text-decoration: underline;"><?= htmlspecialchars($ketuaLpmNama) ?></p>
            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- BAB I PENDAHULUAN -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB I<br>Pendahuluan</div>

        <h3 class="sub-title">A. Latar Belakang</h3>
        <p>
            Gugus Kendali Mutu (GKM) berperan aktif dalam memantau kinerja Program Studi, mulai dari
            perencanaan pembelajaran, pelaksanaan proses pembelajaran, hingga pelaporan hasil belajar pada
            akhir semester. Monitoring ini dilaksanakan secara berkala setiap semester guna memastikan
            kesesuaian pelaksanaan pembelajaran dengan standar mutu yang telah ditetapkan.
        </p>

        <h3 class="sub-title">B. Tujuan</h3>
        <p>
            1. Menilai kesiapan Perencanaan Pembelajaran (RPS, jadwal, rubrik penilaian, dan bahan ajar);<br>
            2. Memantau pelaksanaan Proses Pembelajaran (kehadiran dosen dan mahasiswa, kesesuaian materi
            dengan RPS, pemanfaatan LMS);<br>
            3. Mengevaluasi Pelaporan Akhir Semester (pelaksanaan UTS/UAS, rekap nilai, dan penyusunan laporan).
        </p>

        <h3 class="sub-title">C. Ruang Lingkup</h3>
        <p>
            Monitoring ini mencakup <?= count($items['Perencanaan']) + count($items['Proses']) + count($items['Pelaporan']) ?>
            item penilaian yang terbagi dalam tiga tahap: Perencanaan Pembelajaran, Proses Pembelajaran,
            dan Pelaporan Akhir Semester, sebagaimana diuraikan pada BAB II.
        </p>

    </div>

    <!-- ===================================================== -->
    <!-- BAB II HASIL MONITORING -->
    <!-- ===================================================== -->

    <div class="page-break">

        <div class="bab-title">BAB II<br>Hasil Monitoring</div>

        <table class="info-table">
            <tr>
                <td class="label">Unit Kerja / Program Studi</td>
                <td><?= htmlspecialchars($monitoring['unit_code'] . ' - ' . $monitoring['unit_name']) ?></td>
            </tr>
            <tr>
                <td class="label">Semester</td>
                <td><?= htmlspecialchars($monitoring['semester']) ?></td>
            </tr>
            <tr>
                <td class="label">Tahun Akademik</td>
                <td><?= htmlspecialchars($monitoring['academic_year']) ?></td>
            </tr>
        </table>

        <table class="summary-table">
            <thead>
                <tr>
                    <th>Tahap</th>
                    <th width="120">Item Terpenuhi</th>
                    <th width="120">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tahapList as $tahap): ?>
                    <?php $st = $stats[$tahap] ?? ['total' => 0, 'done' => 0, 'percent' => 0]; ?>
                    <tr>
                        <td style="text-align:left;"><strong><?= $tahap ?></strong></td>
                        <td><?= $st['done'] ?> / <?= $st['total'] ?></td>
                        <td><?= $st['percent'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php $chartCounter = 0; ?>

        <?php foreach ($tahapList as $tahap): ?>

            <?php
                $chartCounter++;
                $canvasId = 'radarTahap' . $chartCounter;

                $itemLabels = array_map(fn($it) => $it['item_text'], $items[$tahap]);
                $itemValues = array_map(fn($it) => $it['status'] === 'Sudah' ? 100 : 0, $items[$tahap]);

                $st = $stats[$tahap] ?? ['total' => 0, 'done' => 0, 'percent' => 0];

                $belumItems = array_values(array_filter($items[$tahap], fn($it) => $it['status'] !== 'Sudah'));
            ?>

            <h3 class="tahap-title">Tahap <?= $tahap ?></h3>

            <div style="text-align:center; margin-bottom: 10px;">
                <div style="width: 300px; height: 300px; margin: 0 auto;">
                    <canvas
                        id="<?= $canvasId ?>"
                        data-labels='<?= json_encode($itemLabels) ?>'
                        data-values='<?= json_encode($itemValues) ?>'
                        width="300" height="300"></canvas>
                </div>
            </div>

            <table class="content-table">
                <thead>
                    <tr>
                        <th width="30">No</th>
                        <th>Item Penilaian</th>
                        <th width="80">Status</th>
                        <th width="200">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items[$tahap] as $i => $item): ?>
                        <tr>
                            <td style="text-align:center;"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['item_text']) ?></td>
                            <td style="text-align:center;" class="<?= $item['status'] === 'Sudah' ? 'status-sudah' : 'status-belum' ?>">
                                <?= htmlspecialchars($item['status']) ?>
                            </td>
                            <td><?= htmlspecialchars($item['catatan'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top: 8px; margin-bottom: 24px;">
                <?php if ($st['percent'] >= 100): ?>
                    Seluruh <?= $st['total'] ?> item pada tahap <?= strtolower($tahap) ?> telah terpenuhi (100%), menunjukkan kesiapan yang baik pada aspek ini.
                <?php elseif (empty($belumItems)): ?>
                    Belum ada data penilaian untuk tahap ini.
                <?php else: ?>
                    Dari <?= $st['total'] ?> item pada tahap <?= strtolower($tahap) ?>, sebanyak <?= $st['done'] ?> item (<?= $st['percent'] ?>%) telah terpenuhi.
                    Item yang masih memerlukan tindak lanjut antara lain:
                    <?= htmlspecialchars(implode('; ', array_map(fn($it) => $it['item_text'], array_slice($belumItems, 0, 3)))) ?><?= count($belumItems) > 3 ? ', dan ' . (count($belumItems) - 3) . ' item lainnya' : '' ?>.
                <?php endif; ?>
            </p>

        <?php endforeach; ?>

    </div>

    <!-- ===================================================== -->
    <!-- BAB III PENUTUP -->
    <!-- ===================================================== -->

    <div>

        <div class="bab-title">BAB III<br>Penutup</div>

        <p>
            Berdasarkan hasil Monitoring Pembelajaran pada Unit Kerja/Program Studi
            <strong><?= htmlspecialchars($monitoring['unit_name']) ?></strong> Semester
            <strong><?= htmlspecialchars($monitoring['semester']) ?> Tahun Akademik <?= htmlspecialchars($monitoring['academic_year']) ?></strong>,
            diperoleh tingkat kesiapan keseluruhan sebesar <strong><?= $overallPercent ?>%</strong>
            (<?= $overallDone ?> dari <?= $overallTotal ?> item terpenuhi).
        </p>

        <p>
            Terhadap item yang belum terpenuhi, Gugus Kendali Mutu merekomendasikan agar Program Studi
            segera melakukan perbaikan sebelum monitoring periode berikutnya, guna mendukung peningkatan
            mutu pembelajaran yang berkelanjutan.
        </p>

        <p>Demikian laporan ini disusun untuk dapat dipergunakan sebagaimana mestinya.</p>

        <div class="signature-block">

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

        document.querySelectorAll('canvas[id^="radarTahap"]').forEach(function (canvas) {

            const labels = JSON.parse(canvas.dataset.labels || "[]").map(function (l) {
                return wrapLabel(l, 16);
            });

            const values = JSON.parse(canvas.dataset.values || "[]");

            new Chart(canvas, {
                type: "radar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Status (%)",
                        data: values,
                        backgroundColor: "rgba(91, 33, 182, 0.15)",
                        borderColor: "#5b21b6",
                        borderWidth: 2,
                        pointBackgroundColor: "#5b21b6",
                        pointRadius: 3,
                    }],
                },
                options: {
                    responsive: false,
                    scales: {
                        r: { min: 0, max: 100, ticks: { stepSize: 50, display: false }, pointLabels: { font: { size: 8 } } },
                    },
                    plugins: { legend: { display: false } },
                },
            });
        });
    </script>

</body>
</html>