<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$repository = new AkreditasiInstitusiRepository($conn);
$service    = new AkreditasiInstitusiService($repository);

$kriteriaOptions = $service->getKriteriaOptions();

$kriteria = trim($_GET['kriteria'] ?? '');
$academicYear = trim($_GET['academic_year'] ?? '');

$result = $service->getAll($kriteria, $academicYear, 500, 0);
$documents = $result['data'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dukung Akreditasi Institusi - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f2fb; font-family: Arial, sans-serif; }
        .public-header {
            background: linear-gradient(135deg, #5b21b6 0%, #6a11cb 100%);
            color: #fff;
            padding: 24px 0;
            margin-bottom: 24px;
        }
        .public-header img { height: 55px; margin-bottom: 10px; background:#fff; border-radius:10px; padding:4px; }
        .kriteria-group { margin-bottom: 24px; }
        .kriteria-title {
            background: #5b21b6;
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
            font-size: 14px;
        }
        .doc-item {
            background: #fff;
            border: 1px solid #eee;
            border-top: none;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-item:last-child { border-radius: 0 0 10px 10px; }
    </style>
</head>
<body>

    <div class="public-header text-center">
        <div class="container">
            <?php if (!empty($profile['logo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
            <?php endif; ?>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars($profile['institution_name'] ?? '') ?></h4>
            <p class="mb-0">Data Dukung Akreditasi Institusi</p>
        </div>
    </div>

    <div class="container pb-5">

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form class="row g-2" method="get">

                    <div class="col-md-6">
                        <label class="form-label small">Kriteria</label>
                        <select class="form-select" name="kriteria">
                            <option value="">Semua Kriteria</option>
                            <?php foreach ($kriteriaOptions as $k): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= $kriteria === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small">Tahun Akademik</label>
                        <input type="text" class="form-control" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>" placeholder="2025/2026">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                    </div>

                </form>
            </div>
        </div>

        <?php if (empty($documents)): ?>

            <div class="alert alert-secondary text-center">Belum ada dokumen yang sesuai dengan filter.</div>

        <?php else: ?>

            <?php
                $grouped = [];
                foreach ($documents as $doc) {
                    $grouped[$doc['kriteria']][] = $doc;
                }
            ?>

            <?php foreach ($grouped as $kriteriaLabel => $docs): ?>
                <div class="kriteria-group">
                    <div class="kriteria-title"><?= htmlspecialchars($kriteriaLabel) ?></div>
                    <?php foreach ($docs as $doc): ?>
                        <div class="doc-item">
                            <div>
                                <strong><?= htmlspecialchars($doc['document_name']) ?></strong>
                                <div class="text-muted small">TA <?= htmlspecialchars($doc['academic_year']) ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if (!empty($doc['file_path'])): ?>
                                    <a href="<?= BASE_URL . htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-download"></i> Unduh
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($doc['link_url'])): ?>
                                    <a href="<?= htmlspecialchars($doc['link_url']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-link-45deg"></i> Buka Link
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</body>
</html>