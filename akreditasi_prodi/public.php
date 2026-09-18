<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../master/unit/repository.php';

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$repository = new AkreditasiProdiRepository($conn);
$service    = new AkreditasiProdiService($repository);

$kriteriaOptions = $service->getKriteriaOptions();

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();

$unitId = (int) ($_GET['unit_id'] ?? 0);
$kriteria = trim($_GET['kriteria'] ?? '');
$academicYear = trim($_GET['academic_year'] ?? '');

$result = $service->getAll($kriteria, $unitId, $academicYear, 500, 0);
$documents = $result['data'];

$totalDocuments = count($documents);
$totalFiles = count(array_filter($documents, fn($d) => !empty($d['file_path'])));
$totalLinks = count(array_filter($documents, fn($d) => !empty($d['link_url'])));

$kriteriaIcons = [
    0 => 'bi-flag-fill', 1 => 'bi-journal-bookmark-fill', 2 => 'bi-clipboard-check-fill', 3 => 'bi-people-fill',
    4 => 'bi-person-workspace', 5 => 'bi-building-gear', 6 => 'bi-patch-check-fill', 7 => 'bi-diagram-3-fill',
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dukung Akreditasi Program Studi - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: #f7f6fb;
            margin: 0;
        }

        /* ===================== HERO ===================== */

        .hero {
            position: relative;
            background: linear-gradient(135deg, #2e0854 0%, #5b21b6 45%, #7c3aed 100%);
            color: #fff;
            padding: 70px 20px 130px;
            overflow: hidden;
            text-align: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: -120px;
            right: -100px;
            width: 380px;
            height: 380px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: -140px;
            left: -80px;
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
            margin: 0 auto;
        }

        .hero-logo {
            width: 92px;
            height: 92px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .hero-logo img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }

        .hero-eyebrow {
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #d6bbfb;
            margin-bottom: 10px;
        }

        .hero-title {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 15.5px;
            color: rgba(255,255,255,0.82);
            font-weight: 400;
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ===================== STAT CARDS (mengambang) ===================== */

        .stat-strip {
            max-width: 760px;
            margin: -70px auto 40px;
            position: relative;
            z-index: 3;
            padding: 0 20px;
        }

        .stat-card-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            background: #fff;
            border-radius: 22px;
            padding: 26px 20px;
            box-shadow: 0 25px 60px rgba(46, 8, 84, 0.18);
        }

        .stat-item {
            text-align: center;
            padding: 0 10px;
            border-right: 1px solid #f0edf9;
        }

        .stat-item:last-child { border-right: none; }

        .stat-value {
            font-size: 30px;
            font-weight: 800;
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 12px;
            color: #8b8698;
            font-weight: 600;
            margin-top: 4px;
        }

        /* ===================== FILTER ===================== */

        .filter-card {
            max-width: 1080px;
            margin: 0 auto 30px;
            padding: 0 20px;
        }

        .filter-inner {
            background: #fff;
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: 0 8px 24px rgba(46,8,84,0.06);
            border: 1px solid #f0edf9;
        }

        .filter-inner label {
            font-size: 11.5px;
            font-weight: 700;
            color: #6b6478;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .filter-inner .form-select,
        .filter-inner .form-control {
            border-radius: 10px;
            border: 1.5px solid #ece8f7;
            font-size: 13.5px;
            padding: 9px 12px;
        }

        .filter-inner .form-select:focus,
        .filter-inner .form-control:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }

        .btn-search-premium {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            border: none;
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13.5px;
            padding: 9px 0;
            width: 100%;
        }

        /* ===================== KONTEN ===================== */

        .content-wrap {
            max-width: 1080px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .kriteria-block {
            margin-bottom: 26px;
        }

        .kriteria-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .kriteria-head-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex: 0 0 auto;
            box-shadow: 0 8px 18px rgba(91,33,182,0.25);
        }

        .kriteria-head-title {
            font-size: 16px;
            font-weight: 700;
            color: #2b2438;
        }

        .kriteria-head-count {
            font-size: 12px;
            color: #9b96a8;
            font-weight: 500;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }

        .doc-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #f0edf9;
            transition: all 0.25s ease;
        }

        .doc-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(46,8,84,0.1);
            border-color: #e4dbfa;
        }

        .doc-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #2b2438;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .doc-card-meta {
            font-size: 11.5px;
            color: #9b96a8;
            margin-bottom: 14px;
        }

        .doc-card-actions {
            display: flex;
            gap: 8px;
        }

        .doc-card-actions a {
            flex: 1;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 0;
            border-radius: 9px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-file {
            background: #f3effc;
            color: #5b21b6;
        }

        .btn-file:hover { background: #e4dbfa; color: #4c1d95; }

        .btn-link {
            background: #eef4ff;
            color: #1d4ed8;
        }

        .btn-link:hover { background: #dbe8ff; color: #1e3a8a; }

        .empty-state {
            text-align: center;
            padding: 70px 20px;
            color: #a8a2b8;
        }

        .empty-state i { font-size: 44px; margin-bottom: 14px; display: block; }

        .public-footer {
            text-align: center;
            padding: 30px 20px 40px;
            color: #a8a2b8;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <!-- ===================== HERO ===================== -->

    <div class="hero">
        <div class="hero-content">

            <div class="hero-logo">
                <?php if (!empty($profile['logo'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
                <?php else: ?>
                    <i class="bi bi-mortarboard-fill" style="font-size:36px;"></i>
                <?php endif; ?>
            </div>

            <div class="hero-eyebrow">Portal Data Dukung Akreditasi</div>
            <div class="hero-title">Program Studi</div>
            <div class="hero-subtitle">
                <?= htmlspecialchars($profile['institution_name'] ?? '') ?> &mdash;
                Kumpulan dokumen bukti dukung akreditasi Program Studi, disusun berdasarkan 8 Kriteria penilaian
                untuk memudahkan proses asesmen.
            </div>

        </div>
    </div>

    <!-- ===================== STAT MENGAMBANG ===================== -->

    <div class="stat-strip">
        <div class="stat-card-grid">

            <div class="stat-item">
                <div class="stat-value"><?= $totalDocuments ?></div>
                <div class="stat-label">Total Dokumen</div>
            </div>

            <div class="stat-item">
                <div class="stat-value"><?= $totalFiles ?></div>
                <div class="stat-label">Berkas Terunggah</div>
            </div>

            <div class="stat-item">
                <div class="stat-value"><?= $totalLinks ?></div>
                <div class="stat-label">Tautan Referensi</div>
            </div>

        </div>
    </div>

    <!-- ===================== FILTER ===================== -->

    <div class="filter-card">
        <div class="filter-inner">
            <form class="row g-3 align-items-end" method="get">

                <div class="col-md-4">
                    <label class="form-label d-block">Program Studi</label>
                    <select class="form-select" name="unit_id">
                        <option value="">Semua Program Studi</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $unitId === (int) $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label d-block">Kriteria</label>
                    <select class="form-select" name="kriteria">
                        <option value="">Semua Kriteria</option>
                        <?php foreach ($kriteriaOptions as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= $kriteria === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label d-block">Tahun Akademik</label>
                    <input type="text" class="form-control" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>" placeholder="2025/2026">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn-search-premium"><i class="bi bi-search"></i></button>
                </div>

            </form>
        </div>
    </div>

    <!-- ===================== KONTEN ===================== -->

    <div class="content-wrap">

        <?php if (empty($documents)): ?>

            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                Belum ada dokumen yang sesuai dengan filter.
            </div>

        <?php else: ?>

            <?php
                $grouped = [];
                foreach ($documents as $doc) {
                    $grouped[$doc['kriteria']][] = $doc;
                }
                $ki = 0;
            ?>

            <?php foreach ($grouped as $kriteriaLabel => $docs): ?>
                <div class="kriteria-block">

                    <div class="kriteria-head">
                        <div class="kriteria-head-icon"><i class="bi <?= $kriteriaIcons[$ki % 8] ?>"></i></div>
                        <div>
                            <div class="kriteria-head-title"><?= htmlspecialchars($kriteriaLabel) ?></div>
                            <div class="kriteria-head-count"><?= count($docs) ?> dokumen</div>
                        </div>
                    </div>

                    <div class="doc-grid">
                        <?php foreach ($docs as $doc): ?>
                            <div class="doc-card">
                                <div class="doc-card-title"><?= htmlspecialchars($doc['document_name']) ?></div>
                                <div class="doc-card-meta">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars(($doc['unit_code'] ?? '-') . ' - ' . ($doc['unit_name'] ?? '-')) ?><br>
                                    <i class="bi bi-calendar3"></i> TA <?= htmlspecialchars($doc['academic_year']) ?>
                                </div>
                                <div class="doc-card-actions">
                                    <?php if (!empty($doc['file_path'])): ?>
                                        <a href="<?= BASE_URL . htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn-file"><i class="bi bi-download"></i> Unduh</a>
                                    <?php endif; ?>
                                    <?php if (!empty($doc['link_url'])): ?>
                                        <a href="<?= htmlspecialchars($doc['link_url']) ?>" target="_blank" class="btn-link"><i class="bi bi-link-45deg"></i> Buka Link</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
                <?php $ki++; ?>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <div class="public-footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($profile['institution_name'] ?? '') ?> &mdash; Sistem Informasi Audit Mutu Internal (SIQUA)
    </div>

</body>
</html>