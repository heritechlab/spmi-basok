<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$slug = trim($_GET['type'] ?? '');

$typeResult = $service->getTypeBySlug($slug);

if (!$typeResult['success']) {
    die('Jenis survey tidak ditemukan.');
}

$type = $typeResult['data'];
$typeId = (int) $type['id'];

$selectedYear = (int) ($_GET['year'] ?? 0);
$selectedUnit = (int) ($_GET['unit_id'] ?? 0);

$availableYears = $service->getAvailableYears($typeId, $selectedUnit)['data'];
$prodiUnits = $service->getProdiUnits()['data'];

$isLayananBased = $service->isLayananBased($typeId);
$requiresIdentity = (int) ($type['requires_identity'] ?? 0) === 1;

$recapLabels = [
    'pengguna' => [
        'table_title'  => 'Rekapitulasi Penilaian Kinerja Lulusan per Jenis Kemampuan',
        'col1_header'  => 'Jenis Kemampuan',
        'group_header' => 'Jumlah Lulusan yang Dinilai oleh Pengguna (%)',
        'identity_title' => 'Daftar Responden (Pengguna Lulusan)',
    ],
    'mitra' => [
        'table_title'  => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama',
        'col1_header'  => 'Aspek Kerjasama',
        'group_header' => 'Tingkat Kepuasan Mitra (%)',
        'identity_title' => 'Daftar Responden (Mitra Kerjasama)',
    ],
'mitra_penelitian' => [
        'table_title'  => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama Penelitian',
        'col1_header'  => 'Aspek Kerjasama Penelitian',
        'group_header' => 'Tingkat Kepuasan Mitra Penelitian (%)',
        'identity_title' => 'Daftar Responden (Mitra Penelitian)',
    ],
    'mitra_pkm' => [
        'table_title'  => 'Rekapitulasi Tingkat Kepuasan Mitra per Aspek Kerjasama Pengabdian Masyarakat',
        'col1_header'  => 'Aspek Kerjasama Pengabdian Masyarakat',
        'group_header' => 'Tingkat Kepuasan Mitra Pengabdian Masyarakat (%)',
        'identity_title' => 'Daftar Responden (Mitra Pengabdian Masyarakat)',
    ],
];

$currentRecapLabel = $recapLabels[$type['slug']] ?? [
    'table_title'  => 'Rekapitulasi Tingkat Kepuasan per Kategori',
    'col1_header'  => 'Kategori',
    'group_header' => 'Tingkat Kepuasan (%)',
    'identity_title' => 'Daftar Responden',
];
$scaleConfigResult = $service->getScaleConfig($typeId);
$surveyScaleMax = $scaleConfigResult['max'] ?? 5;

$recapKemampuan = (!$isLayananBased && $surveyScaleMax == 4)
    ? $service->getCategoryRecapTable($typeId, $selectedYear, $selectedUnit)['data']
    : [];

$overall = $service->getOverallStats($typeId, $selectedYear, $selectedUnit)['data'];
$saranList = $service->getSaranList($typeId, $selectedYear, 20, 0, $selectedUnit)['data'];
$totalSaran = $service->countSaran($typeId, $selectedYear, $selectedUnit)['data'];

if ($isLayananBased) {
    $layananScores = $service->getLayananScores($typeId, $selectedYear, $selectedUnit)['data'];
    $recapData = $service->getLayananRecapTable($typeId, $selectedYear, $selectedUnit)['data'];
    $categoryScores = [];
    $questionScores = [];
} else {
    $categoryScores = $service->getCategoryScores($typeId, $selectedYear, $selectedUnit)['data'];
    $questionScores = $service->getQuestionScores($typeId, $selectedYear, $selectedUnit)['data'];
    $layananScores = [];
    $recapData = [];
}

$publicFormUrl = BASE_URL . 'survey/?type=' . urlencode($type['slug']);

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

<?php
        $mitraTabs = [
            'mitra' => 'Kepuasan Mitra Kerjasama',
            'mitra_penelitian' => 'Kepuasan Mitra Penelitian',
            'mitra_pkm' => 'Kepuasan Mitra Pengabdian Masyarakat',
        ];
        $isMitraGroup = array_key_exists($type['slug'], $mitraTabs);
    ?>

    <?php if ($isMitraGroup): ?>
    <ul class="nav nav-tabs mb-3">
        <?php foreach ($mitraTabs as $tabSlug => $tabLabel): ?>
            <li class="nav-item">
                <a class="nav-link <?= $type['slug'] === $tabSlug ? 'active fw-semibold' : '' ?>" href="<?= BASE_URL ?>survey/results.php?type=<?= $tabSlug ?>">
                    <?= htmlspecialchars($tabLabel) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

        <h4 class="mb-0"><?= htmlspecialchars($type['name']) ?></h4>

        <div class="d-flex gap-2 align-items-center">

<form method="get" class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type['slug']) ?>">

<?php if ($isLayananBased || $requiresIdentity): ?>
                <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">Semua Program Studi</option>
                    <?php foreach ($prodiUnits as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $selectedUnit === (int) $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">Semua Tahun</option>
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($selectedYear > 0): ?>
                <a href="<?= BASE_URL ?>survey/print.php?type=<?= htmlspecialchars($type['slug']) ?>&year=<?= $selectedYear ?>&unit_id=<?= $selectedUnit ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan Tahunan
                </a>

                <button type="button" class="btn btn-outline-success btn-sm" id="btnGenerateFollowUp">
                    <i class="bi bi-magic"></i> Buat RTL/PTP dari Hasil Survey
                </button>
            <?php endif; ?>

            <button type="button" class="btn btn-primary btn-sm" id="btnCopyLink">
                <i class="bi bi-link-45deg"></i> Salin Link Form
            </button>

        </div>

    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <small class="text-muted d-block">Link Form Publik (bagikan ke responden)</small>
            <code id="publicFormLinkText"><?= htmlspecialchars($publicFormUrl) ?></code>
        </div>
    </div>

    <div class="dual-card-row mb-3">

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
                Rekap hasil <?= htmlspecialchars($type['name']) ?> sebagai bahan evaluasi mutu layanan.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Pengguna" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-emoji-smile"></i>
                Ringkasan
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Responden</div>
                    <div class="info-card-total-value"><?= $overall['total_responden'] ?></div>
                </div>

<div class="info-card-items-grid info-card-items-grid-1">
                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-star-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Rata-rata Skor Kepuasan</div>
                            <div class="info-card-value"><?= $overall['rata_rata_skor'] ?> / <?= number_format($surveyScaleMax, 2) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($recapKemampuan)): ?>

                <hr class="my-2">

                <small class="text-muted d-block mb-1" style="font-size:11px;">Capaian per <?= htmlspecialchars($currentRecapLabel['col1_header'] ?? 'Kategori') ?></small>

                <div style="position: relative; height: 100px;">
                    <canvas
                        id="miniKemampuanChart"
                        data-labels='<?= json_encode(array_map(fn($k, $i) => $i + 1, $recapKemampuan, array_keys($recapKemampuan))) ?>'
                        data-sb='<?= json_encode(array_map(fn($k) => $k['distribusi']['sangat_baik'], $recapKemampuan)) ?>'
                        data-b='<?= json_encode(array_map(fn($k) => $k['distribusi']['baik'], $recapKemampuan)) ?>'
                        data-c='<?= json_encode(array_map(fn($k) => $k['distribusi']['cukup'], $recapKemampuan)) ?>'
                        data-k='<?= json_encode(array_map(fn($k) => $k['distribusi']['kurang'], $recapKemampuan)) ?>'>
                    </canvas>
                </div>

                <?php
                    $scaleLabelsArr = $scaleConfigResult['labels'] ?? ['Kurang Baik', 'Cukup Baik', 'Baik', 'Sangat Baik'];

                    $abbr = function (string $label): string {
                        $words = explode(' ', trim($label));
                        $initials = array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words);
                        return implode('', $initials);
                    };

                    $labelSangatBaik = $abbr($scaleLabelsArr[3] ?? 'Sangat Baik');
                    $labelBaik = $abbr($scaleLabelsArr[2] ?? 'Baik');
                    $labelCukup = $abbr($scaleLabelsArr[1] ?? 'Cukup');
                    $labelKurang = $abbr($scaleLabelsArr[0] ?? 'Kurang');
                ?>

                <div class="d-flex justify-content-center flex-wrap gap-2 mt-1" style="font-size:9.5px;">
                    <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#7c3aed;"></span> <?= $labelSangatBaik ?></span>
                    <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;"></span> <?= $labelBaik ?></span>
                    <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#f59e0b;"></span> <?= $labelCukup ?></span>
                    <span><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#ef4444;"></span> <?= $labelKurang ?></span>
                </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

<?php
        $genericAxisLabels = ['Keandalan', 'Daya Tanggap', 'Kepastian', 'Kepedulian'];
        $datasetColors = ['#7c3aed', '#2563eb', '#16a34a', '#ea580c'];

        $layananGabungan = [];
        $layananTerpisah = [];

        if ($isLayananBased) {
            foreach ($layananScores as $layanan) {
                if (count($layanan['aspek_scores']) === 4) {
                    $layananGabungan[] = $layanan;
                } else {
                    $layananTerpisah[] = $layanan;
                }
            }
        }
    ?>

    <div class="row mt-3">

        <div class="col-lg-6 mb-4">

            <div class="widget h-100">

                <div class="widget-header mb-3">
                <h4><i class="bi bi-bullseye"></i> Profil Skor per <?= $isLayananBased ? 'Layanan' : 'Kategori' ?></h4>
                    <small>Skala 1 &mdash; <?= $surveyScaleMax ?></small>
                </div>

                <?php
                    $radarLabels = $isLayananBased
                        ? array_column($layananScores, 'name')
                        : array_column($categoryScores, 'name');

                    $radarValues = $isLayananBased
                        ? array_column($layananScores, 'skor_akhir')
                        : array_map(fn($c) => round((float) $c['avg_score'], 2), $categoryScores);

                    $radarMax = $surveyScaleMax;
                ?>

                <div style="position: relative; height: 280px;">
                    <canvas
                        id="categoryScoreChart"
                        data-max="<?= $radarMax ?>"
                        data-labels='<?= json_encode($radarLabels) ?>'
                        data-values='<?= json_encode($radarValues) ?>'>
                    </canvas>
                </div>

            </div>

        </div>

        <?php if ($isLayananBased && !empty($layananGabungan)): ?>
        <div class="col-lg-6 mb-4">

            <div class="widget h-100">

                <div class="widget-header mb-3">
                    <h4><i class="bi bi-bullseye"></i> Profil Aspek: Dosen, Tendik &amp; Pengelola</h4>
                    <small>Perbandingan 4 Aspek yang sama pada 3 Layanan</small>
                </div>

                <div style="position: relative; height: 280px;">
                    <canvas
                        id="aspekCombinedChart"
                        data-axis-labels='<?= json_encode($genericAxisLabels) ?>'
                        data-datasets='<?= json_encode(array_map(function ($layanan, $i) use ($datasetColors) {
                            return [
                                'label' => 'Layanan ' . $layanan['name'],
                                'values' => array_map(fn($a) => $a['avg_score'] ?? 0, $layanan['aspek_scores']),
                                'color' => $datasetColors[$i % count($datasetColors)],
                            ];
                        }, $layananGabungan, array_keys($layananGabungan))) ?>'>
                    </canvas>
                </div>

                <div class="d-flex justify-content-center gap-3 mt-2" style="font-size: 11px;">
                    <?php foreach ($layananGabungan as $i => $layanan): ?>
                        <span>
                            <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:<?= $datasetColors[$i % count($datasetColors)] ?>;"></span>
                            Layanan <?= htmlspecialchars($layanan['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>
        <?php endif; ?>

        <?php if (!$isLayananBased): ?>
        <div class="col-lg-6 mb-4">

            <div class="widget h-100">

                <div class="widget-header mb-3">
                    <h4><i class="bi bi-chat-square-text"></i> Saran &amp; Masukan</h4>
                    <small><?= $totalSaran ?> total masukan</small>
                </div>

                <div style="max-height: 280px; overflow-y: auto;">

                    <?php if (empty($saranList)): ?>
                        <p class="text-muted mb-0">Belum ada saran/masukan yang diberikan responden.</p>
                    <?php else: ?>
                        <?php foreach ($saranList as $s): ?>
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <?php if ($s['unit_name']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($s['unit_name']) ?></span>
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($s['submitted_at'])) ?></small>
                                </div>
                                <div style="font-size:13px;"><?= nl2br(htmlspecialchars($s['saran'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

            </div>

        </div>
        <?php endif; ?>

    </div>

    <?php if ($isLayananBased): ?>

    <div class="row mt-3">

        <?php foreach ($layananTerpisah as $layanan): ?>

            <?php
                $aspekLabels = array_column($layanan['aspek_scores'], 'name');
                $aspekValues = array_map(fn($a) => $a['avg_score'] ?? 0, $layanan['aspek_scores']);
            ?>

            <div class="col-lg-6 mb-4">

                <div class="widget h-100">

                    <div class="widget-header mb-3">
                        <h4><i class="bi bi-bullseye"></i> Profil Aspek: Layanan <?= htmlspecialchars($layanan['name']) ?></h4>
                        <small>Skala 1 &mdash; 4</small>
                    </div>

                    <div style="position: relative; height: 260px;">
                        <canvas
                            class="aspekRadarChart"
                            data-max="4"
                            data-labels='<?= json_encode($aspekLabels) ?>'
                            data-values='<?= json_encode($aspekValues) ?>'>
                        </canvas>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

        <div class="col-lg-6 mb-4">

            <div class="widget h-100">

                <div class="widget-header mb-3">
                    <h4><i class="bi bi-chat-square-text"></i> Saran &amp; Masukan</h4>
                    <small><?= $totalSaran ?> total masukan</small>
                </div>

                <div style="max-height: 260px; overflow-y: auto;">

                    <?php if (empty($saranList)): ?>
                        <p class="text-muted mb-0">Belum ada saran/masukan yang diberikan responden.</p>
                    <?php else: ?>
                        <?php foreach ($saranList as $s): ?>
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <?php if ($s['unit_name']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($s['unit_name']) ?></span>
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($s['submitted_at'])) ?></small>
                                </div>
                                <div style="font-size:13px;"><?= nl2br(htmlspecialchars($s['saran'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <?php endif; ?>

<?php if ($isLayananBased): ?>

        <?php foreach ($layananScores as $layanan): ?>

            <div class="card shadow-sm mb-3">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Layanan <?= htmlspecialchars($layanan['name']) ?></strong>
                    <span class="badge bg-primary">Skor Akhir: <?= $layanan['skor_akhir'] ?> / 4.00</span>
                </div>

                <div class="card-body">

                    <?php foreach ($layanan['aspek_scores'] as $aspek): ?>

                        <h6 class="fw-bold mb-2">
                            <?= htmlspecialchars($aspek['name']) ?>
                            <span class="text-muted fw-normal">(<?= $aspek['avg_score'] ?? '-' ?> / 4.00)</span>
                        </h6>

                        <table class="table table-bordered table-sm align-middle mb-4">
                            <thead>
                                <tr>
                                    <th>Pertanyaan</th>
                                    <th width="100">Rata-rata</th>
                                    <th width="90">Responden</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($aspek['questions'])): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Belum ada pertanyaan.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($aspek['questions'] as $q): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($q['question_text']) ?></td>
                                        <td>
                                            <?= $q['avg_score'] ?? '-' ?>
                                            <?php if ($q['avg_score'] !== null): ?>
                                                <div class="progress" style="height: 5px; margin-top: 3px;">
                                                    <div class="progress-bar bg-primary" style="width: <?= ($q['avg_score'] / 4) * 100 ?>%;"></div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int) $q['total_jawaban'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

    <div class="card shadow-sm">

        <div class="card-header"><strong>Detail Skor per Pertanyaan</strong></div>

        <div class="card-body">

            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="180">Kategori</th>
                        <th>Pertanyaan</th>
                        <th width="120">Rata-rata</th>
                        <th width="100">Responden</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questionScores)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Belum ada pertanyaan/jawaban untuk jenis survey ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($questionScores as $q): ?>
                        <?php $avg = round((float) $q['avg_score'], 2); ?>
                        <tr>
                            <td><?= htmlspecialchars($q['category_name']) ?></td>
                            <td><?= htmlspecialchars($q['question_text']) ?></td>
                            <td>
                                <strong><?= $avg ?></strong> / <?= number_format($surveyScaleMax, 2) ?>
                                <div class="progress" style="height: 6px; margin-top: 4px;">
                                    <div class="progress-bar bg-primary" style="width: <?= ($avg / $surveyScaleMax) * 100 ?>%;"></div>
                                </div>
                            </td>
                            <td><?= (int) $q['total_jawaban'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    </div>

    <?php endif; ?>
    <?php if ($isLayananBased): ?>

        <?php
            $recapResult = $service->getLayananRecapTable($typeId, $selectedYear, $selectedUnit);
            $recapData = $recapResult['data'];

            $romanNumerals = ['i', 'ii', 'iii', 'iv', 'v'];

            $totalSangatBaik = 0;
            $totalBaik = 0;
            $totalCukup = 0;
            $totalKurang = 0;
            $totalBaris = 0;
        ?>

        <div class="card shadow-sm mb-3">

            <div class="card-header"><strong>Rekapitulasi Tingkat Kepuasan Mahasiswa (%)</strong></div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" width="30">No</th>
                                <th rowspan="2">Aspek yang Diukur</th>
                                <th colspan="4">Tingkat Kepuasan Mahasiswa (%)</th>
                                <th rowspan="2" width="90">Persentase (%)</th>
                                <th rowspan="2" width="140">Rencana Tindak Lanjut</th>
                            </tr>
                            <?php
                                $scaleLabelsArr2 = $scaleConfigResult['labels'] ?? ['Kurang Baik', 'Cukup Baik', 'Baik', 'Sangat Baik'];
                            ?>
                            <tr>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[3] ?? 'Sangat Baik') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[2] ?? 'Baik') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[1] ?? 'Cukup') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[0] ?? 'Kurang') ?></th>
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
                                    }

                                    $layananSangatBaikAvg = $jumlahAspek > 0 ? round($layananSangatBaik / $jumlahAspek, 1) : 0;
                                    $layananBaikAvg = $jumlahAspek > 0 ? round($layananBaik / $jumlahAspek, 1) : 0;
                                    $layananCukupAvg = $jumlahAspek > 0 ? round($layananCukup / $jumlahAspek, 1) : 0;
                                    $layananKurangAvg = $jumlahAspek > 0 ? round($layananKurang / $jumlahAspek, 1) : 0;
                                    $layananTotalAvg = round($layananSangatBaikAvg + $layananBaikAvg + $layananCukupAvg + $layananKurangAvg, 1);
                                ?>

                                <tr class="table-light">
                                    <td><strong><?= $li + 1 ?></strong></td>
                                    <td class="text-start"><strong>Layanan <?= htmlspecialchars($layanan['name']) ?> :</strong></td>
                                    <td><strong><?= $layananSangatBaikAvg ?>%</strong></td>
                                    <td><strong><?= $layananBaikAvg ?>%</strong></td>
                                    <td><strong><?= $layananCukupAvg ?>%</strong></td>
                                    <td><strong><?= $layananKurangAvg ?>%</strong></td>
                                    <td><strong><?= $layananTotalAvg ?>%</strong></td>
                                    <td></td>
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
                                        <td class="text-start">
                                            <?= $romanNumerals[$ai] ?? ($ai + 1) ?>. &nbsp;<?= htmlspecialchars($aspek['name']) ?>
                                        </td>
                                        <td><?= $d['sangat_baik'] ?>%</td>
                                        <td><?= $d['baik'] ?>%</td>
                                        <td><?= $d['cukup'] ?>%</td>
                                        <td><?= $d['kurang'] ?>%</td>
                                        <td>-</td>
                                        <td><span class="badge bg-secondary">Belum Ada</span></td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endforeach; ?>

                            <tr class="table-light">
                                <td colspan="2"><strong>Jumlah</strong></td>
                                <td><strong><?= $totalBaris > 0 ? round($totalSangatBaik / $totalBaris, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBaris > 0 ? round($totalBaik / $totalBaris, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBaris > 0 ? round($totalCukup / $totalBaris, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBaris > 0 ? round($totalKurang / $totalBaris, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBaris * 100 ?>%</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Kolom "Rencana Tindak Lanjut" akan otomatis terhubung dengan data RTM Pengendalian pada pengembangan berikutnya.
                </small>

            </div>

        </div>

    <?php endif; ?>

<?php if (!empty($recapKemampuan)): ?>

        <?php
            $totalSB = 0; $totalB = 0; $totalC = 0; $totalK = 0; $totalBarisKemampuan = 0;
        ?>

        <div class="card shadow-sm mb-3">

            <div class="card-header"><strong><?= htmlspecialchars($currentRecapLabel['table_title']) ?></strong></div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" width="30">No</th>
                            <th rowspan="2"><?= htmlspecialchars($currentRecapLabel['col1_header']) ?></th>
                                <th colspan="4"><?= htmlspecialchars($currentRecapLabel['group_header']) ?></th>
                                <th rowspan="2" width="90">Persentase</th>
                                <th rowspan="2" width="140">Rencana Tindak Lanjut</th>
                            </tr>
                            <?php $scaleLabelsArr2 = $scaleConfigResult['labels'] ?? ['Kurang Baik', 'Cukup Baik', 'Baik', 'Sangat Baik']; ?>
                            <tr>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[3] ?? 'Sangat Baik') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[2] ?? 'Baik') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[1] ?? 'Cukup') ?></th>
                                <th width="80"><?= htmlspecialchars($scaleLabelsArr2[0] ?? 'Kurang') ?></th>
                            </tr>
                        </thead>
<tbody>
                            <?php $romanNumeralsRecap = ['i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x']; ?>
                            <?php foreach ($recapKemampuan as $i => $kat): ?>

                                <?php
                                    $katSB = 0; $katB = 0; $katC = 0; $katK = 0;
                                    $jumlahPertanyaan = count($kat['questions']);

                                    foreach ($kat['questions'] as $qHitung) {
                                        $dq = $qHitung['distribusi'];
                                        $katSB += $dq['sangat_baik'];
                                        $katB += $dq['baik'];
                                        $katC += $dq['cukup'];
                                        $katK += $dq['kurang'];
                                    }

                                    $katSBAvg = $jumlahPertanyaan > 0 ? round($katSB / $jumlahPertanyaan, 1) : 0;
                                    $katBAvg = $jumlahPertanyaan > 0 ? round($katB / $jumlahPertanyaan, 1) : 0;
                                    $katCAvg = $jumlahPertanyaan > 0 ? round($katC / $jumlahPertanyaan, 1) : 0;
                                    $katKAvg = $jumlahPertanyaan > 0 ? round($katK / $jumlahPertanyaan, 1) : 0;
                                    $katTotalAvg = round($katSBAvg + $katBAvg + $katCAvg + $katKAvg, 1);

                                    $totalSB += $katSBAvg;
                                    $totalB += $katBAvg;
                                    $totalC += $katCAvg;
                                    $totalK += $katKAvg;
                                    $totalBarisKemampuan++;
                                ?>

                                <tr class="table-light">
                                    <td><strong><?= $i + 1 ?></strong></td>
                                    <td class="text-start"><strong><?= htmlspecialchars($kat['name']) ?> :</strong></td>
                                    <td><strong><?= $katSBAvg ?>%</strong></td>
                                    <td><strong><?= $katBAvg ?>%</strong></td>
                                    <td><strong><?= $katCAvg ?>%</strong></td>
                                    <td><strong><?= $katKAvg ?>%</strong></td>
                                    <td><strong><?= $katTotalAvg ?>%</strong></td>
                                    <td></td>
                                </tr>

                                <?php foreach ($kat['questions'] as $qi => $q): ?>
                                    <?php $dq = $q['distribusi']; ?>
                                    <tr>
                                        <td></td>
                                        <td class="text-start">
                                            <?= $romanNumeralsRecap[$qi] ?? ($qi + 1) ?>. &nbsp;<?= htmlspecialchars($q['question_text']) ?>
                                        </td>
                                        <td><?= $dq['sangat_baik'] ?>%</td>
                                        <td><?= $dq['baik'] ?>%</td>
                                        <td><?= $dq['cukup'] ?>%</td>
                                        <td><?= $dq['kurang'] ?>%</td>
                                        <td>-</td>
                                        <td><span class="badge bg-secondary">Belum Ada</span></td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endforeach; ?>

                            <tr class="table-light">
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong><?= $totalBarisKemampuan > 0 ? round($totalSB / $totalBarisKemampuan, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBarisKemampuan > 0 ? round($totalB / $totalBarisKemampuan, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBarisKemampuan > 0 ? round($totalC / $totalBarisKemampuan, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBarisKemampuan > 0 ? round($totalK / $totalBarisKemampuan, 1) : 0 ?>%</strong></td>
                                <td><strong><?= $totalBarisKemampuan * 100 ?>%</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($requiresIdentity): ?>

        <?php $identityList = $service->getIdentityResponses($typeId, $selectedYear, $selectedUnit, 100, 0)['data']; ?>

        <div class="card shadow-sm mb-3">

<div class="card-header"><strong><?= htmlspecialchars($currentRecapLabel['identity_title']) ?></strong></div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered align-middle" style="font-size: 12.5px;">
                        <thead class="table-light">
                            <?php if (in_array($type['slug'], ['mitra', 'mitra_penelitian', 'mitra_pkm'], true)): ?>
                            <?php
                                $bidangHeader = 'Bidang/Bentuk Kerjasama';
                                if ($type['slug'] === 'mitra_penelitian') $bidangHeader = 'Bidang/Jenis Penelitian';
                                if ($type['slug'] === 'mitra_pkm') $bidangHeader = 'Bidang/Jenis Pengabdian Masyarakat';
                            ?>
                            <tr>
                                <th width="30">No</th>
                                <th>Instansi/Lembaga Mitra</th>
                                <th><?= $bidangHeader ?></th>
                                <th>Nama Pengisi</th>
                                <th>Jabatan</th>
                                <th>Lama Kerjasama</th>
                                <th width="90">Tanggal</th>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <th width="30">No</th>
                                <th>Instansi/Perusahaan</th>
                                <th>Pimpinan</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Prodi Lulusan</th>
                                <th>Jumlah Lulusan Bekerja</th>
                                <th>Rata-rata Masa Kerja</th>
                                <th width="90">Tanggal</th>
                            </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php if (empty($identityList)): ?>
                                <tr><td colspan="9" class="text-center text-muted">Belum ada responden.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($identityList as $i => $r): ?>
                                <?php if (in_array($type['slug'], ['mitra', 'mitra_penelitian', 'mitra_pkm'], true)): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['nama_instansi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['bidang_kerjasama'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['nama_pengisi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['lama_kerjasama'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['submitted_at'])) ?></td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['nama_instansi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['nama_pengisi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['no_hp'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['unit_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['jumlah_lulusan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['masa_kerja'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['submitted_at'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

const miniCanvas = document.getElementById('miniKemampuanChart');

    if (miniCanvas) {

        new Chart(miniCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(miniCanvas.dataset.labels || '[]'),
                datasets: [
                    { label: 'Sangat Baik', data: JSON.parse(miniCanvas.dataset.sb || '[]'), backgroundColor: '#7c3aed' },
                    { label: 'Baik', data: JSON.parse(miniCanvas.dataset.b || '[]'), backgroundColor: '#22c55e' },
                    { label: 'Cukup', data: JSON.parse(miniCanvas.dataset.c || '[]'), backgroundColor: '#f59e0b' },
                    { label: 'Kurang', data: JSON.parse(miniCanvas.dataset.k || '[]'), backgroundColor: '#ef4444' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: { stacked: true, min: 0, max: 100, display: false },
                },
            },
        });
    }

const combinedCanvas = document.getElementById('aspekCombinedChart');

    if (combinedCanvas) {

        const axisLabels = JSON.parse(combinedCanvas.dataset.axisLabels || '[]');
        const datasetsRaw = JSON.parse(combinedCanvas.dataset.datasets || '[]');

        new Chart(combinedCanvas, {
            type: 'radar',
            data: {
                labels: axisLabels,
                datasets: datasetsRaw.map(function (ds) {
                    return {
                        label: ds.label,
                        data: ds.values,
                        backgroundColor: ds.color + '22',
                        borderColor: ds.color,
                        borderWidth: 2,
                        pointBackgroundColor: ds.color,
                        pointRadius: 3,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    r: {
                        min: 0,
                        max: 4,
                        ticks: { stepSize: 1 },
                        pointLabels: { font: { size: 11, weight: '600' } },
                    },
                },
            },
        });
    }

    document.querySelectorAll('.aspekRadarChart').forEach(function (canvas) {

        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const max = parseInt(canvas.dataset.max || '4', 10);

        new Chart(canvas, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Rata-rata Skor',
                    data: values,
                    backgroundColor: 'rgba(124, 58, 237, 0.15)',
                    borderColor: '#7c3aed',
                    borderWidth: 2,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    r: {
                        min: 0,
                        max: max,
                        ticks: { stepSize: 1 },
                        pointLabels: { font: { size: 10 } },
                    },
                },
            },
        });
    });

    const catCanvas = document.getElementById('categoryScoreChart');
    const catMax = catCanvas ? parseInt(catCanvas.dataset.max || '5', 10) : 5;

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

    if (catCanvas) {

        const rawLabels = JSON.parse(catCanvas.dataset.labels || '[]');
        const labels = rawLabels.map(function (l) { return wrapLabel(l, 14); });
        const values = JSON.parse(catCanvas.dataset.values || '[]');

        new Chart(catCanvas, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Rata-rata Skor',
                    data: values,
                    backgroundColor: 'rgba(124, 58, 237, 0.15)',
                    borderColor: '#7c3aed',
                    borderWidth: 2,
                    pointBackgroundColor: '#7c3aed',
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) { return 'Skor: ' + ctx.raw; },
                        },
                    },
                },
                scales: {
                    r: {
                        min: 0,
                        max: catMax,
                        ticks: { stepSize: 1, backdropColor: 'transparent' },
                        grid: { color: '#eef0f5' },
                        pointLabels: { font: { size: 11, weight: '600' }, color: '#3a3348' },
                    },
                },
            },
        });
    }

document.getElementById('btnCopyLink').addEventListener('click', function () {
        const text = document.getElementById('publicFormLinkText').textContent;
        navigator.clipboard.writeText(text).then(function () {
            const btn = document.getElementById('btnCopyLink');
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin!';
            setTimeout(function () { btn.innerHTML = original; }, 1800);
        });
    });

    const btnGenerateFollowUp = document.getElementById('btnGenerateFollowUp');

    if (btnGenerateFollowUp) {
        btnGenerateFollowUp.addEventListener('click', function () {

            Swal.fire({
                icon: 'question',
                title: 'Buat Tindak Lanjut Otomatis?',
                html: 'Sistem akan menelusuri seluruh Kategori/Aspek pada hasil survey ini (Tahun <?= $selectedYear ?><?php if ($selectedUnit > 0): ?>, Prodi terpilih<?php endif; ?>), lalu otomatis membuat RTL di RTM Pengendalian (skor rendah) atau usulan di PTP (skor tinggi).<br><br><small class="text-muted">Aman diklik berulang kali — tidak akan membuat duplikat untuk kombinasi yang sama.</small>',
                showCancelButton: true,
                confirmButtonText: 'Ya, Buat Sekarang',
                cancelButtonText: 'Batal',
            }).then(function (result) {

                if (!result.isConfirmed) return;

                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: '<?= BASE_URL ?>survey/api.php?action=generate_follow_up',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        type_id: <?= (int) $typeId ?>,
                        year: <?= $selectedYear ?>,
                        unit_id: <?= $selectedUnit ?>,
                    },
                    success: function (response) {
                        Swal.close();

                        if (!response.success) {
                            Swal.fire('Gagal', response.message, 'error');
                            return;
                        }

                        Swal.fire('Berhasil', response.message, 'success');
                    },
                    error: function () {
                        Swal.close();
                        Swal.fire('Gagal', 'Terjadi kesalahan pada server.', 'error');
                    },
                });
            });
        });
    }

});
</script>